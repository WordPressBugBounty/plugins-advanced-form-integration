<?php

use function Avifinfo\read;

add_filter( 'adfoin_action_providers', 'adfoin_fluentcrm_actions', 10, 1 );

function adfoin_fluentcrm_actions( $actions ) {

    $actions['fluentcrm'] = array(
        'title' => __( 'Fluent CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'addContact' => __( 'Add Contact to a list', 'advanced-form-integration' ),
            'removeContact' => __( 'Remove Contact from a list', 'advanced-form-integration' ),
            'addTag' => __( 'Add Tag to a contact', 'advanced-form-integration' ),
            'removeTag' => __( 'Remove Tag from a contact', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_fluentcrm_settings_tab', 10, 1 );

function adfoin_fluentcrm_settings_tab( $providers ) {
    $providers['fluentcrm'] = __( 'Fluent CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_fluentcrm_settings_view', 10, 1 );

function adfoin_fluentcrm_settings_view( $current_tab ) {
    if( $current_tab != 'fluentcrm' ) {
        return;
    }

    $nonce        = wp_create_nonce( 'adfoin_fluentcrm_settings' );
    ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					
						<h2 class="hndle"><span><?php esc_attr_e( 'Fluent CRM', 'advanced-form-integration' ); ?></span></h2>
						<div class="inside">
                            <p>
                                Make sure that the plugin is activated. Then, set up a new integration and proceed.
                            </p>
						</div>
						<!-- .inside -->
					
				</div>
				<!-- .meta-box-sortables .ui-sortable -->
			</div>
			<!-- post-body-content -->


		</div>
		<!-- #post-body .metabox-holder .columns-2 -->
		<br class="clear">
	</div>
    <?php
}

add_action( 'adfoin_action_fields', 'adfoin_fluentcrm_action_fields' );

function adfoin_fluentcrm_action_fields() {
    ?>
    <script type="text/template" id="fluentcrm-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'addContact' || action.task == 'removeContact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.lists" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_fluentcrm_lists', 'adfoin_get_fluentcrm_lists', 10, 0 );

function adfoin_get_fluentcrm_lists() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $raw_lists = FluentCrm\App\Models\Lists::get();
    $lists = array();

    foreach( $raw_lists as $list ) {
        $lists[$list->id] = $list->title;
    }

    wp_send_json_success( $lists );
}

add_action( 'wp_ajax_adfoin_get_fluentcrm_fields', 'adfoin_get_fluentcrm_fields', 10, 0 );

function adfoin_get_fluentcrm_fields() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $task = sanitize_text_field( $_POST['task'] );
    $tags = adfoin_get_fluentcrm_tags();
    $fields = array();

    if( $task == 'addContact' ) {
        $fields = array(
            array( 'key' => 'prefix', 'value' => 'Prefix', 'description' => '' ),
            array( 'key' => 'email', 'value' => 'Email', 'description' => '' ),
            array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'last_name', 'value' => 'Last Name', 'description' => '' ),
            array( 'key' => 'phone', 'value' => 'Phone', 'description' => '' ),
            array( 'key' => 'date_of_birth', 'value' => 'Date of Birth', 'description' => '' ),
            array( 'key' => 'address_line_1', 'value' => 'Address Line 1', 'description' => '' ),
            array( 'key' => 'address_line_2', 'value' => 'Address Line 2', 'description' => '' ),
            array( 'key' => 'city', 'value' => 'City', 'description' => '' ),
            array( 'key' => 'state', 'value' => 'State', 'description' => '' ),
            array( 'key' => 'postal_code', 'value' => 'ZIP Code', 'description' => '' ),
            array( 'key' => 'country', 'value' => 'Country', 'description' => '' ),
            array( 'key' => 'ip', 'value' => 'IP Address', 'description' => '' ),
            array( 'key' => 'source', 'value' => 'Source', 'description' => '' ),
            array( 'key' => 'avatar', 'value' => 'Avatar', 'description' => '' ),
            array( 'key' => 'status', 'value' => 'Status', 'description' => '' ),
            array( 'key' => 'tags', 'value' => 'Tag IDs', 'description' => implode( ',', $tags ) )
        );

        $custom_fields = adfoin_get_fluentcrm_custom_fields();

        if( !empty( $custom_fields ) ) {
            foreach( $custom_fields as $key => $value ) {
                array_push( $fields, array( 'key' => $key, 'value' => $value, 'description' => '' ) );
            }
        }


    } else if( $task == 'removeContact' ) {
        $fields = array(
            array( 'key' => 'email', 'value' => 'Email', 'description' => '' )
        );
    } else if( $task == 'addTag' ) {
        $fields = array(
            array( 'key' => 'email', 'value' => 'Email', 'description' => '' ),
            array( 'key' => 'tags', 'value' => 'Tag IDs', 'description' => implode( ',', $tags ) )
        );
    } else if( $task == 'removeTag' ) {
        $fields = array(
            array( 'key' => 'email', 'value' => 'Email', 'description' => '' ),
            array( 'key' => 'tags', 'value' => 'Tag IDs', 'description' => implode( ',', $tags ) )
        );
    }

    wp_send_json_success( $fields );
}

function adfoin_get_fluentcrm_custom_field_raw() {
    global $wpdb;
    $table = $wpdb->prefix . 'fc_meta';
    $raw_custom_fields = $wpdb->get_row( "SELECT * FROM $table WHERE `key` = 'contact_custom_fields'" );
    $raw_custom_fields = maybe_unserialize( $raw_custom_fields->value );

    return $raw_custom_fields;
}

function adfoin_get_fluentcrm_custom_fields() {
    $raw_custom_fields = adfoin_get_fluentcrm_custom_field_raw();
    $custom_fields = wp_list_pluck( $raw_custom_fields, 'label', 'slug' );

    return $custom_fields;
}

function adfoin_get_custom_field_types() {
    $raw_custom_fields = adfoin_get_fluentcrm_custom_field_raw();
    $custom_fields_types = wp_list_pluck( $raw_custom_fields, 'type', 'slug' );

    return $custom_fields_types;
}

function adfoin_get_fluentcrm_tags() {

    $raw_tags = FluentCrm\App\Models\Tag::get();
    $tags = array();

    foreach( $raw_tags as $tag ) {
        array_push( $tags, $tag->title . ': ' . $tag->id );
    }

    return $tags;
}

add_action( 'adfoin_fluentcrm_job_queue', 'adfoin_fluentcrm_job_queue', 10, 1 );

function adfoin_fluentcrm_job_queue( $data ) {
    adfoin_fluentcrm_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to fluentcrm
 */
function adfoin_fluentcrm_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data['field_data'];
    $list_id = empty( $data['listId'] ) ? '' : adfoin_get_parsed_values( $data['listId'], $posted_data );
    $task    = $record['task'];

    unset( $data['listId'] );

    $parsed_data = array();

    foreach( $data as $key => $value ) {
        if( $key == 'tags' ) {
            $tags = explode( ',', $value );
            $parsed_tags = array();

            foreach( $tags as $tag ) {
                $parsed_tag = adfoin_get_parsed_values( $tag, $posted_data );

                if( !empty( $parsed_tag ) ) {
                    $parsed_tags[] = $parsed_tag;
                }
            }

            if( !empty( $parsed_tags ) ) {
                $parsed_data['tags'] = $parsed_tags;
            }

            continue;
        }

        $parsed_data[$key] = adfoin_get_parsed_values( $value, $posted_data );
    }

    $custom_fields_types = adfoin_get_custom_field_types();

    foreach( $parsed_data as $key => $value ) {
        if( array_key_exists( $key, $custom_fields_types ) ) {
            $type = $custom_fields_types[$key];

            $array_types = array( 'select-multi', 'checkbox' );

            if( in_array( $type, $array_types ) && !empty( $value ) ) {
                $parsed_data[$key] = explode( ',', $value );
            }
        }
    }

    $parsed_data = array_filter( $parsed_data );
    $parsed_data['lists'] = array( $list_id );

    if( $task == 'addContact' ) {
        adfoin_fluentcrm_add_contact( $parsed_data );
    } else if( $task == 'removeContact' ) {
        adfoin_fluentcrm_remove_contact( $parsed_data );
    } else if( $task == 'addTag' ) {
        adfoin_fluentcrm_add_tag( $parsed_data );
    } else if( $task == 'removeTag' ) {
        adfoin_fluentcrm_remove_tag( $parsed_data );
    }

    return;
}

function adfoin_fluentcrm_add_contact( $data ) {
    $return = FluentCrmApi('contacts')->createOrUpdate( $data, false, false );
}

function adfoin_fluentcrm_remove_contact( $data ) {
    $subscriber = FluentCrm\App\Models\Subscriber::where( 'email', $data['email'] )->first();
    $return = $subscriber->detachLists( $data['lists'] );
}

function adfoin_fluentcrm_add_tag( $data ) {
    $subscriber = FluentCrm\App\Models\Subscriber::where( 'email', $data['email'] )->first();
    $return = $subscriber->attachTags( $data['tags'] );
}

function adfoin_fluentcrm_remove_tag( $data ) {
    $subscriber = FluentCrm\App\Models\Subscriber::where( 'email', $data['email'] )->first();
    $return = $subscriber->detachTags( $data['tags'] );
}
