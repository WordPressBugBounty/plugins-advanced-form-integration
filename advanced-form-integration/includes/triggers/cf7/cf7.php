<?php

add_action( 'wpcf7_submit', 'adfoin_cf7_submission', 10, 2 );

/**
 * Handle Contact Form 7 submissions.
 *
 * @param WPCF7_Submission $contact_form The Contact Form 7 submission object.
 * @param array $result The Contact Form 7 submission result.
 * @return void
 */
function adfoin_cf7_submission( $contact_form, $result ) {

    if( 'validation_failed' == $result['status'] || 'spam' == $result['status'] ) {
        return;
    }

    // Get the form ID.
    $form_id = $contact_form->id();

    // Get the saved integration records.
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'cf7', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    // Set the maximum execution time to 5 minutes.
    if ( function_exists( 'set_time_limit' ) ) {
        set_time_limit( 300 );
    }

    // Get the WPCF7 submission object.
    $submission  = WPCF7_Submission::get_instance();

    // Get the posted data.
    $posted_data = $submission->get_posted_data();

    // Get the form fields.
    $form_fields = $contact_form->scan_form_tags();

    // Capture pre-pipe ("raw") values for select/radio/checkbox fields.
    foreach( $form_fields as $field ) {

        if( isset( $field['type'] ) && in_array( $field['type'], array( 'select', 'select*', 'radio', 'radio*', 'checkbox', 'checkbox*' ) ) ) {

            // Check if the field exists in the POST data.
            if( array_key_exists( $field['name'], $_POST ) ) {

                // Sanitize the field value (also strips slashes).
                $pipe_value = adfoin_sanitize_text_or_array_field( $_POST[$field['name']] );

                // Add the field value to the posted data array.
                $posted_data['raw_' . $field['name']] = $pipe_value;
            }
        }
    }

    // Copy any uploaded files into a dedicated, prunable, hard-to-guess location
    // (uploads/adfoin-uploads/<token>/<file>) ONCE. uploaded_files() already
    // returns only the fields that received an upload, so there's no need to
    // re-scan it inside the field loop above. Handles both the modern
    // array-of-paths shape and the legacy single-path string shape.
    $uploaded_files = $submission->uploaded_files();

    if( ! empty( $uploaded_files ) && is_array( $uploaded_files ) ) {
        foreach( $uploaded_files as $key => $file ) {
            $uploaded_urls = array();

            foreach( (array) $file as $file_path ) {
                $stored_url = adfoin_cf7_store_uploaded_file( $file_path );

                if( $stored_url ) {
                    $uploaded_urls[] = $stored_url;
                }
            }

            // A field can hold more than one file; comma-join them. A single file
            // stays a plain URL string. Only override the field when at least one
            // copy succeeded, so a failed copy keeps CF7's original value.
            if( ! empty( $uploaded_urls ) ) {
                $posted_data[$key] = implode( ', ', $uploaded_urls );
            }
        }
    }

    // Resolve the container post for special-tag values WITHOUT clobbering the
    // global $post; read the global only as a fallback source.
    $post_id = (int) $submission->get_meta( 'container_post_id' );
    $post    = $post_id ? get_post( $post_id, 'OBJECT' ) : ( isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null );

    // Get the special tag values.
    $special_tag_values = adfoin_get_special_tags_values( $post );

    // Merge the posted data and special tag values arrays.
    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    // Set the submission date (site-local time, not UTC).
    $posted_data['submission_date'] = current_time( 'mysql' );

    // Set the form ID.
    $posted_data['form_id']         = $form_id;

    // Set the form name.
    $posted_data['form_name']       = $contact_form->title();

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

/*
 * Get Forms list
 */
function adfoin_cf7_get_forms( $form_provider ) {
    if( $form_provider != 'cf7' ) {
        return;
    }

    $args     = array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1 );
    $cf7Forms = get_posts( $args );
    $forms    = wp_list_pluck( $cf7Forms, 'post_title', 'ID' );

    return $forms;
}

/*
 * Get form fields
 */
function adfoin_cf7_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'cf7' ) {
        return;
    }

    $ContactForm  = WPCF7_ContactForm::get_instance( $form_id );
    $form_fields  = $ContactForm->scan_form_tags();
    $final_fields = array();

    foreach( $form_fields as $field ) {
        if( $field['name'] ) {
            if( isset( $field['type'] ) && in_array( $field['type'], array( 'select', 'select*', 'radio', 'radio*', 'checkbox', 'checkbox*' ) ) ) {
                if( isset( $field['pipes'] ) && $field['pipes'] ) {
                    $final_fields['raw_' . $field['name']] = $field['name'] . ' (before pipe)';
                }
            }
            
            $final_fields[$field['name']] = $field['name'];
        }
    }

    $special_tags = adfoin_get_special_tags();

    if( is_array( $final_fields ) && is_array( $special_tags ) ) {
        $final_fields = $final_fields + $special_tags;
    }

    $final_fields['form_id']   = __( 'Form ID', 'advanced-form-integration' );
    $final_fields['form_name'] = __( 'Form Name', 'advanced-form-integration' );
    return $final_fields;
}

/**
 * Store a CF7-uploaded file in a dedicated, prunable, hard-to-guess location and
 * return its public URL.
 *
 * CF7 deletes its temp upload after the mail step, but AFI may dispatch
 * asynchronously (job queue / retries), so the receiver needs a URL that
 * persists. Files go to uploads/adfoin-uploads/<random-token>/<file>:
 *   - isolated from the media library so they can be pruned safely,
 *   - under a random token folder so the URL can't be enumerated,
 *   - and reaped after a retention window by the daily cleanup below.
 *
 * @param string $source_path Absolute path to the source file (CF7 temp file).
 * @return string|false Public URL on success, false on failure.
 */
function adfoin_cf7_store_uploaded_file( $source_path ) {
    if( ! $source_path || ! file_exists( $source_path ) ) {
        return false;
    }

    $upload_dir = wp_upload_dir();

    if( ! empty( $upload_dir['error'] ) ) {
        return false;
    }

    $base  = $upload_dir['basedir'] . '/adfoin-uploads';
    $token = wp_generate_password( 20, false );
    $dir   = $base . '/' . $token;

    if( ! wp_mkdir_p( $dir ) ) {
        return false;
    }

    // Block directory listing on the parent. Files themselves must stay fetchable
    // by external receivers, so we only disable listing, not file access.
    $index = $base . '/index.html';
    if( ! file_exists( $index ) ) {
        @file_put_contents( $index, '' );
    }

    $file_name = wp_unique_filename( $dir, basename( $source_path ) );
    $dest_path = $dir . '/' . $file_name;

    if( ! @copy( $source_path, $dest_path ) ) {
        return false;
    }

    return $upload_dir['baseurl'] . '/adfoin-uploads/' . $token . '/' . $file_name;
}

/*
 * Daily cleanup of expired CF7 upload files.
 */
add_action( 'init', 'adfoin_cf7_schedule_upload_cleanup' );

function adfoin_cf7_schedule_upload_cleanup() {
    if( ! wp_next_scheduled( 'adfoin_cf7_cleanup_uploads' ) ) {
        wp_schedule_event( time(), 'daily', 'adfoin_cf7_cleanup_uploads' );
    }
}

add_action( 'adfoin_cf7_cleanup_uploads', 'adfoin_cf7_cleanup_uploaded_files' );

/**
 * Delete adfoin-uploads token folders older than the retention window.
 *
 * Default 30 days. Filter `adfoin_cf7_upload_retention_days` to change it; return
 * 0 or a negative value to disable cleanup and keep files indefinitely.
 */
function adfoin_cf7_cleanup_uploaded_files() {
    $retention_days = (int) apply_filters( 'adfoin_cf7_upload_retention_days', 30 );

    if( $retention_days <= 0 ) {
        return;
    }

    $upload_dir = wp_upload_dir();

    if( ! empty( $upload_dir['error'] ) ) {
        return;
    }

    $base = $upload_dir['basedir'] . '/adfoin-uploads';

    if( ! is_dir( $base ) ) {
        return;
    }

    $cutoff  = time() - ( $retention_days * DAY_IN_SECONDS );
    $folders = glob( $base . '/*', GLOB_ONLYDIR );

    if( ! is_array( $folders ) ) {
        return;
    }

    foreach( $folders as $folder ) {
        if( filemtime( $folder ) > $cutoff ) {
            continue;
        }

        adfoin_cf7_delete_dir( $folder );
    }
}

/**
 * Recursively delete a directory and its contents.
 */
function adfoin_cf7_delete_dir( $dir ) {
    if( ! is_dir( $dir ) ) {
        return;
    }

    $items = array_diff( (array) scandir( $dir ), array( '.', '..' ) );

    foreach( $items as $item ) {
        $path = $dir . '/' . $item;

        if( is_dir( $path ) ) {
            adfoin_cf7_delete_dir( $path );
        } else {
            @unlink( $path );
        }
    }

    @rmdir( $dir );
}