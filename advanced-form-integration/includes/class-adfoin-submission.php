<?php

class Advanced_Form_Integration_Submission {

    public function __construct() {
        add_action( 'wp_ajax_adfoin_get_forms', array( $this,'get_forms' ) );
        add_action( 'wp_ajax_adfoin_get_form_fields', array( $this,'get_form_fields' ) );
        add_action( 'wp_ajax_adfoin_get_tasks', array( $this,'get_tasks' ) );
        add_action( 'admin_post_adfoin_save_integration', array( $this,'save_integration' ) );
        add_action( 'admin_post_adfoin_resend_log_data', array( $this,'resend_log_data' ) );
        add_action( 'wp_ajax_adfoin_save_integration_ajax', array( $this, 'save_integration_ajax' ) );
        add_action('wp_ajax_adfoin_enable_integration', array( $this, 'adfoin_enable_integration' ) );
    }

    /*
    * Get all forms for a specific form provider
    */
    public function get_forms() {
        if( !wp_verify_nonce( $_POST['nonce'], 'advanced-form-integration' ) ) {
            return;
        }

        $form_provider = sanitize_text_field( $_POST['formProviderId'] );

        if( $form_provider ) {
            $forms = call_user_func( "adfoin_{$form_provider}_get_forms", $form_provider );

            if( !is_wp_error( $forms ) ) {
                wp_send_json_success( $forms );
            }
        }

        wp_die();
    }

    /*
     * Get all fields for a specific form
     */
    public function get_form_fields() {
        if( !wp_verify_nonce( $_POST['nonce'], 'advanced-form-integration' ) ) {
            return;
        }

        $form_provider = sanitize_text_field( $_POST['formProviderId'] );
        $form_id       = sanitize_text_field( $_POST['formId'] );

        if( $form_provider && $form_id ) {
            $fields = call_user_func( "adfoin_{$form_provider}_get_form_fields", $form_provider, $form_id );

            if( !is_wp_error( $fields ) ) {
                wp_send_json_success( $fields );
            }
        }

        wp_die();
    }

    /*
     * Get Tasks for a action provider
     */
    public function get_tasks() {
        if( !wp_verify_nonce( $_POST['nonce'], 'advanced-form-integration' ) ) {
            return;
        }

        $action_provider = sanitize_text_field( $_POST['actionProviderId'] );

        if( $action_provider ) {
            $tasks = adfoin_get_action_tasks( $action_provider );

            if( !is_wp_error( $tasks ) ) {
                wp_send_json_success( $tasks );
            }
        }

        wp_die();
    }

    /*
     * Save Integration
     */
    public function save_integration() {
        if( !wp_verify_nonce( $_POST['_wpnonce'], 'adfoin-integration' ) ) {
            return;
        }

        $action_provider_id = isset( $_POST['action_provider'] ) ? sanitize_text_field( $_POST['action_provider'] ) : '';

        $trigger_data = isset( $_POST['triggerData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['triggerData'] ) : array();
        if( $trigger_data && is_string( $trigger_data ) ) {
            $trigger_data = json_decode( $trigger_data, true );
        }

        $action_data = isset( $_POST['actionData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['actionData'] ) : array();

        if( $action_data && is_string( $action_data ) ) {
            $action_data = json_decode( $action_data, true );
        }

        $field_data = isset( $_POST['fieldData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['fieldData'] ) : array();

        $integration_title = isset( $trigger_data['integrationTitle'] ) ? sanitize_text_field( $trigger_data['integrationTitle'] ) : '';
        $form_provider_id  = isset( $trigger_data['formProviderId'] ) ? $trigger_data['formProviderId'] : '';
        $form_id           = isset( $trigger_data['formId'] ) ? $trigger_data['formId'] : '';
        $form_name         = isset( $trigger_data['formName'] ) ? sanitize_text_field( $trigger_data['formName'] ) : '';
        $action_provider   = isset( $action_data['actionProviderId'] ) ? $action_data['actionProviderId'] : '';
        $task              = isset( $action_data['task'] ) ? $action_data['task'] : '';
        $type              = isset( $_POST['type'] ) ? adfoin_sanitize_text_or_array_field( $_POST['type'] ) : '';

        $all_data = array(
            'trigger_data' => $trigger_data,
            'action_data'  => $action_data,
            'field_data'   => $field_data
        );

        global $wpdb;

        $integration_table = $wpdb->prefix . 'adfoin_integration';

        if ( $type == 'new_integration' ) {

            $result = $wpdb->insert(
                $integration_table,
                array(
                    'title'           => $integration_title,
                    'form_provider'   => $form_provider_id,
                    'form_id'         => $form_id,
                    'form_name'       => $form_name,
                    'action_provider' => $action_provider,
                    'task'            => $task,
                    'data'            => json_encode( $all_data, true ),
                    'status'          => 1
                )
            );

            $id = $wpdb->insert_id;
        }

        if ( $type == 'update_integration' ) {

            $id = esc_sql( trim( $_POST['edit_id'] ) );

            if ( $type != 'update_integration' &&  !empty( $id ) ) {
                exit;
            }

            $result = $wpdb->update( $integration_table,
                array(
                    'title'           => $integration_title,
                    'form_provider'   => $form_provider_id,
                    'form_id'         => $form_id,
                    'form_name'       => $form_name,
                    'action_provider' => $action_provider,
                    'task'            => $task,
                    'data'            => json_encode( $all_data, true ),
                ),
                array(
                    'id' => $id
                )
            );
        }

        advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration&action=edit&id='. $id );
    }

    /**
     * AJAX variant of save_integration.
     *
     * Mirrors the data shape and DB writes of save_integration() but
     * returns JSON instead of redirecting, so the integration form can
     * submit in-place via fetch/jQuery without losing scroll position
     * or Vue state. The non-AJAX handler remains as a graceful fallback
     * for browsers with JS disabled.
     *
     * Expected POST payload: same field names as the form (action,
     * type, _wpnonce, edit_id, integration_title, form_provider_id,
     * form_id, form_name, action_provider, task, triggerData,
     * actionData, fieldData).
     *
     * Response: JSON via wp_send_json_success / wp_send_json_error.
     *
     * @since 1.128.1
     * @return void
     */
    public function save_integration_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ) ),
                403
            );
        }

        if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'adfoin-integration' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Security check failed. Please reload and try again.', 'advanced-form-integration' ) ),
                403
            );
        }

        $type = isset( $_POST['type'] ) ? adfoin_sanitize_text_or_array_field( $_POST['type'] ) : '';

        if ( ! in_array( $type, array( 'new_integration', 'update_integration' ), true ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Invalid integration type.', 'advanced-form-integration' ) ),
                400
            );
        }

        $trigger_data = isset( $_POST['triggerData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['triggerData'] ) : array();
        if ( $trigger_data && is_string( $trigger_data ) ) {
            $trigger_data = json_decode( $trigger_data, true );
        }

        $action_data = isset( $_POST['actionData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['actionData'] ) : array();
        if ( $action_data && is_string( $action_data ) ) {
            $action_data = json_decode( $action_data, true );
        }

        $field_data = isset( $_POST['fieldData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['fieldData'] ) : array();
        // Defensive: if a caller posts fieldData as a JSON string (rather
        // than the bracketed array form admin-post.php uses), decode it
        // so what we persist always matches the array shape edit-reload
        // expects.
        if ( is_string( $field_data ) ) {
            $decoded = json_decode( wp_unslash( $field_data ), true );
            $field_data = is_array( $decoded ) ? $decoded : array();
        } elseif ( ! is_array( $field_data ) ) {
            $field_data = array();
        }

        $integration_title = isset( $trigger_data['integrationTitle'] ) ? sanitize_text_field( $trigger_data['integrationTitle'] ) : '';
        $form_provider_id  = isset( $trigger_data['formProviderId'] ) ? $trigger_data['formProviderId'] : '';
        $form_id           = isset( $trigger_data['formId'] ) ? $trigger_data['formId'] : '';
        $form_name         = isset( $trigger_data['formName'] ) ? sanitize_text_field( $trigger_data['formName'] ) : '';
        $action_provider   = isset( $action_data['actionProviderId'] ) ? $action_data['actionProviderId'] : '';
        $task              = isset( $action_data['task'] ) ? $action_data['task'] : '';

        // Server-side guard mirroring the client-side canSave check.
        $missing = array();
        if ( '' === $integration_title ) $missing[] = 'integration_title';
        if ( '' === $form_provider_id )  $missing[] = 'form_provider_id';
        if ( '' === $form_id )           $missing[] = 'form_id';
        if ( '' === $action_provider )   $missing[] = 'action_provider';
        if ( '' === $task )              $missing[] = 'task';
        if ( ! empty( $missing ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Please complete all required fields.', 'advanced-form-integration' ),
                    'missing' => $missing,
                ),
                422
            );
        }

        $all_data = array(
            'trigger_data' => $trigger_data,
            'action_data'  => $action_data,
            'field_data'   => $field_data,
        );

        global $wpdb;
        $integration_table = $wpdb->prefix . 'adfoin_integration';
        $integration_id    = 0;
        $is_new            = false;

        if ( 'new_integration' === $type ) {
            $is_new = true;
            $result = $wpdb->insert(
                $integration_table,
                array(
                    'title'           => $integration_title,
                    'form_provider'   => $form_provider_id,
                    'form_id'         => $form_id,
                    'form_name'       => $form_name,
                    'action_provider' => $action_provider,
                    'task'            => $task,
                    'data'            => wp_json_encode( $all_data ),
                    'status'          => 1,
                )
            );

            if ( false === $result ) {
                wp_send_json_error(
                    array( 'message' => __( 'Could not save the integration. Please try again.', 'advanced-form-integration' ) ),
                    500
                );
            }

            $integration_id = (int) $wpdb->insert_id;
        } else {
            $integration_id = isset( $_POST['edit_id'] ) ? (int) $_POST['edit_id'] : 0;

            if ( $integration_id <= 0 ) {
                wp_send_json_error(
                    array( 'message' => __( 'Invalid integration id.', 'advanced-form-integration' ) ),
                    400
                );
            }

            $result = $wpdb->update(
                $integration_table,
                array(
                    'title'           => $integration_title,
                    'form_provider'   => $form_provider_id,
                    'form_id'         => $form_id,
                    'form_name'       => $form_name,
                    'action_provider' => $action_provider,
                    'task'            => $task,
                    'data'            => wp_json_encode( $all_data ),
                ),
                array( 'id' => $integration_id )
            );

            if ( false === $result ) {
                wp_send_json_error(
                    array( 'message' => __( 'Could not update the integration. Please try again.', 'advanced-form-integration' ) ),
                    500
                );
            }
        }

        wp_send_json_success(
            array(
                'integration_id' => $integration_id,
                'is_new'         => $is_new,
                'edit_url'       => admin_url( 'admin.php?page=advanced-form-integration&action=edit&id=' . $integration_id ),
                'message'        => $is_new
                    ? __( 'Integration created.', 'advanced-form-integration' )
                    : __( 'Integration updated.', 'advanced-form-integration' ),
            )
        );
    }

    /*
     * Resend Log Data
     * 
     * This method handles resending failed API requests from the log.
     * For OAuth2-based platforms, it automatically updates the Authorization
     * header with the current bearer token to ensure the request succeeds.
     */
    public function resend_log_data() {
        // Verify nonce for security
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'adfoin-resend-log' ) ) {
            wp_die( __( 'Security check failed.', 'advanced-form-integration' ) );
        }

        // Sanitize and validate input data
        $log_id         = isset( $_POST['log_id'] ) ? sanitize_text_field( $_POST['log_id'] ) : '';
        $integration_id = isset( $_POST['integration_id'] ) ? sanitize_text_field( $_POST['integration_id'] ) : '';
        $raw_data       = isset( $_POST['request-data'] ) ? wp_unslash( $_POST['request-data'] ) : '';

        if ( empty( $log_id ) || empty( $integration_id ) || empty( $raw_data ) ) {
            wp_die( __( 'Required data is missing.', 'advanced-form-integration' ) );
        }

        $data = json_decode( $raw_data, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_die( __( 'Invalid request data format.', 'advanced-form-integration' ) );
        }

        $url  = isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '';
        $args = isset( $data['args'] ) ? $data['args'] : array();

        if ( empty( $url ) || ! is_array( $args ) ) {
            wp_die( __( 'Invalid URL or request arguments.', 'advanced-form-integration' ) );
        }

        // Get integration record to identify the platform
        global $wpdb;
        $integration_table = $wpdb->prefix . 'adfoin_integration';
        $integration = $wpdb->get_row( $wpdb->prepare( 
            "SELECT action_provider, data FROM {$integration_table} WHERE id = %d", 
            $integration_id 
        ), ARRAY_A );

        // Update bearer token for OAuth2-based platforms
        if ( $integration && $this->is_oauth2_platform( $integration['action_provider'] ) ) {
            $platform_info = $this->get_oauth2_platform_info( $integration['action_provider'], $url );
            
            if ( $platform_info ) {
                // Get the credential ID from the integration data
                $integration_data = json_decode( $integration['data'], true );
                $cred_id = '';
                
                if ( isset( $integration_data['field_data']['credId'] ) ) {
                    $cred_id = $integration_data['field_data']['credId'];
                } else {
                    // Fallback: get the first available credential
                    $credentials = adfoin_read_credentials( $integration['action_provider'] );
                    if ( !empty( $credentials ) && is_array( $credentials ) ) {
                        $first_credential = reset( $credentials );
                        $cred_id = isset( $first_credential['id'] ) ? $first_credential['id'] : '';
                    }
                }

                if ( $cred_id && class_exists( $platform_info['class'] ) ) {
                    // Get fresh OAuth2 instance and set credentials
                    $oauth_instance = null;
                    
                    // Try to get instance using get_instance method
                    if ( method_exists( $platform_info['class'], 'get_instance' ) ) {
                        $oauth_instance = call_user_func( array( $platform_info['class'], 'get_instance' ) );
                    } else {
                        // Fallback: create new instance
                        $oauth_instance = new $platform_info['class']();
                    }
                    
                    if ( $oauth_instance && method_exists( $oauth_instance, 'set_credentials' ) ) {
                        $oauth_instance->set_credentials( $cred_id );
                        
                        // Update the Authorization header with current bearer token
                        if ( ! isset( $args['headers'] ) ) {
                            $args['headers'] = array();
                        }
                        
                        if ( method_exists( $oauth_instance, 'get_bearer_token' ) ) {
                            $args['headers']['Authorization'] = $oauth_instance->get_bearer_token();
                        }
                    }
                }
            }
        }

        // Ensure the body is properly formatted for resending
        // Step 1: If body is a JSON string, decode it to array for processing
        if ( isset( $args['body'] ) && is_string( $args['body'] ) ) {
            $decoded_body = json_decode( $args['body'], true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
            $args['body'] = $decoded_body;
            }
        }

        // Step 2: If Content-Type indicates JSON, encode array body back to JSON string
        // This handles cases like "application/json; charset=utf-8"
        if ( isset( $args['headers']['Content-Type'] ) && strpos( $args['headers']['Content-Type'], 'application/json' ) !== false ) {
            if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
            $args['body'] = wp_json_encode( $args['body'] );
            }
        }

        // Handle merge-patch JSON format as well
        if ( isset( $args['headers']['Content-Type'] ) && strpos( $args['headers']['Content-Type'], 'application/merge-patch+json' ) !== false ) {
            if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
            $args['body'] = wp_json_encode( $args['body'] );
            }
        }

        // Perform the remote request
        $response = adfoin_remote_request( $url, $args );

        // Log the response
        adfoin_add_to_log( $response, $url, $args, array( 'id' => $integration_id ), $log_id );

        // Redirect to the log view page
        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-log&action=view&id=' . $log_id ) );
        exit;
    }

    /**
     * Check if a platform uses OAuth2 authentication
     */
    private function is_oauth2_platform( $platform ) {
        $oauth2_platforms = array(
            'zohocrm', 'aweber', 'zohobooks', 'zoomwebinar', 'googledrive', 'zohoma',
            'googlecalendar', 'zohopeople', 'bigin', 'salesforce', 'gotowebinar',
            'zohocampaigns', 'verticalresponse', 'moneybird', 'googlesheets',
            'zohorecruit', 'constantcontact', 'liondesk', 'salesforcefieldservice',
            'zohosheet', 'zohodesk', 'mailup', 'googletasks', 'cleverreach', 'bombbomb'
        );
        
        return in_array( $platform, $oauth2_platforms );
    }

    /**
     * Get OAuth2 platform information including class name and API domains
     */
    private function get_oauth2_platform_info( $platform, $url ) {
        $platform_map = array(
            'zohocrm' => array(
                'class' => 'ADFOIN_ZohoCRM',
                'domains' => array( 'zohoapis.com', 'zohoapis.eu', 'zohoapis.in', 'zohoapis.com.cn', 'zohoapis.com.au', 'zohoapis.jp', 'zohoapis.sa', 'zohoapis.ca' )
            ),
            'aweber' => array(
                'class' => 'ADFOIN_Aweber',
                'domains' => array( 'api.aweber.com' )
            ),
            'zohobooks' => array(
                'class' => 'ADFOIN_ZohoBooks',
                'domains' => array( 'books.zoho.com', 'books.zoho.eu', 'books.zoho.in', 'books.zoho.com.cn', 'books.zoho.com.au', 'books.zoho.jp' )
            ),
            'googledrive' => array(
                'class' => 'ADFOIN_GoogleDrive',
                'domains' => array( 'www.googleapis.com' )
            ),
            'googlecalendar' => array(
                'class' => 'ADFOIN_GoogleCalendar',
                'domains' => array( 'www.googleapis.com' )
            ),
            'googlesheets' => array(
                'class' => 'ADFOIN_GoogleSheets',
                'domains' => array( 'sheets.googleapis.com' )
            ),
            'googletasks' => array(
                'class' => 'ADFOIN_GoogleTasks',
                'domains' => array( 'tasks.googleapis.com' )
            ),
            'salesforce' => array(
                'class' => 'ADFOIN_Salesforce',
                'domains' => array( '.salesforce.com', '.force.com' )
            ),
            'constantcontact' => array(
                'class' => 'ADFOIN_ConstantContact',
                'domains' => array( 'api.cc.email' )
            ),
            'bigin' => array(
                'class' => 'ADFOIN_Bigin',
                'domains' => array( 'bigin.zoho.com', 'bigin.zoho.eu', 'bigin.zoho.in' )
            ),
            'zohoma' => array(
                'class' => 'ADFOIN_ZohoMA',
                'domains' => array( 'campaigns.zoho.com', 'campaigns.zoho.eu', 'campaigns.zoho.in' )
            ),
            'zohocampaigns' => array(
                'class' => 'ADFOIN_ZohoCampaigns',
                'domains' => array( 'campaigns.zoho.com', 'campaigns.zoho.eu', 'campaigns.zoho.in' )
            ),
            'zohopeople' => array(
                'class' => 'ADFOIN_ZohoPeople',
                'domains' => array( 'people.zoho.com', 'people.zoho.eu', 'people.zoho.in' )
            ),
            'zohorecruit' => array(
                'class' => 'ADFOIN_ZohoRecruit',
                'domains' => array( 'recruit.zoho.com', 'recruit.zoho.eu', 'recruit.zoho.in' )
            ),
            'zohosheet' => array(
                'class' => 'ADFOIN_Zohosheet',
                'domains' => array( 'sheet.zoho.com', 'sheet.zoho.eu', 'sheet.zoho.in' )
            ),
            'zohodesk' => array(
                'class' => 'ADFOIN_ZohoDesk',
                'domains' => array( 'desk.zoho.com', 'desk.zoho.eu', 'desk.zoho.in' )
            )
        );

        if ( ! isset( $platform_map[ $platform ] ) ) {
            return false;
        }

        $info = $platform_map[ $platform ];
        
        // Check if the URL matches any of the platform's API domains
        foreach ( $info['domains'] as $domain ) {
            if ( strpos( $url, $domain ) !== false ) {
                return $info;
            }
        }

        return false;
    }

    /**
     * Enables or disables an integration.
     */
    public function adfoin_enable_integration() {
        // Capability check first — defense-in-depth even though the
        // endpoint is wp-admin only. Reach this branch and you're an
        // unauthenticated/under-privileged user, fail closed before
        // touching $wpdb.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ) ),
                403
            );
        }

        // Nonce check. Returns JSON (not wp_die-rendered HTML) so the
        // jQuery toggle handler's .fail() path can read .data.message
        // consistently across every error case below.
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Security check failed.', 'advanced-form-integration' ) ),
                403
            );
        }

        $id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $enabled = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '';

        if ( $id <= 0 || ! in_array( $enabled, array( '0', '1' ), true ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Invalid input data.', 'advanced-form-integration' ) ),
                400
            );
        }

        global $wpdb;

        $table  = $wpdb->prefix . 'adfoin_integration';
        $status = ( $enabled === '1' ) ? 1 : 0;

        $action_status = $wpdb->update(
            $table,
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );

        if ( false === $action_status ) {
            wp_send_json_error(
                array( 'message' => __( 'Failed to update integration status.', 'advanced-form-integration' ) ),
                500
            );
        }

        wp_send_json_success(
            array( 'message' => __( 'Integration status updated successfully.', 'advanced-form-integration' ) )
        );
    }
}