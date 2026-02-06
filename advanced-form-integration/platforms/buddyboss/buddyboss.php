<?php

add_filter( 'adfoin_action_providers', 'adfoin_buddyboss_actions', 10, 1 );

function adfoin_buddyboss_actions( $actions ) {
    $actions['buddyboss'] = array(
        'title' => __( 'BuddyBoss', 'advanced-form-integration' ),
        'tasks' => array(
            'create_member' => __( 'Create/Update Member', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_buddyboss_action_fields' );

function adfoin_buddyboss_action_fields() {
    ?>
    <script type="text/template" id="buddyboss-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_member'">
                <th scope="row">
                    <?php esc_attr_e( 'Member Fields', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <p><?php esc_html_e( 'Map your form fields to create or update a BuddyBoss member. Usernames and emails will be created inside WordPress.', 'advanced-form-integration' ); ?></p>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            v-bind:key="field.value"
                            v-bind:field="field"
                            v-bind:trigger="trigger"
                            v-bind:action="action"
                            v-bind:fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_buddyboss_fields', 'adfoin_get_buddyboss_fields' );

function adfoin_get_buddyboss_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_buddyboss_collect_fields() );
}

add_action( 'adfoin_buddyboss_job_queue', 'adfoin_buddyboss_job_queue', 10, 1 );

function adfoin_buddyboss_job_queue( $data ) {
    adfoin_buddyboss_send_data( $data['record'], $data['posted_data'] );
}

if ( ! function_exists( 'adfoin_buddyboss_collect_fields' ) ) {
    /**
     * Prepare the list of BuddyBoss fields.
     *
     * @param bool $include_extended Whether to load extended BuddyBoss fields.
     *
     * @return array
     */
    function adfoin_buddyboss_collect_fields( $include_extended = false ) {
        $fields = array(
            array(
                'key'         => 'user_id',
                'value'       => __( 'User ID', 'advanced-form-integration' ),
                'description' => __( 'Provide an existing user ID to update that member. Leave empty to create a new user.', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'user_login',
                'value'       => __( 'Username', 'advanced-form-integration' ),
                'required'    => false,
                'description' => __( 'Username for new members. Required when creating a user.', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'user_email',
                'value'       => __( 'Email', 'advanced-form-integration' ),
                'required'    => false,
                'description' => __( 'Email address for the member. Required when creating a user.', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'user_pass',
                'value'       => __( 'Password', 'advanced-form-integration' ),
                'description' => __( 'Password for the member. Leave empty to auto-generate.', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'first_name',
                'value'       => __( 'First Name', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'last_name',
                'value'       => __( 'Last Name', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'nickname',
                'value'       => __( 'Nickname', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'display_name',
                'value'       => __( 'Display Name', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'user_url',
                'value'       => __( 'Website', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'description',
                'value'       => __( 'Bio/Description', 'advanced-form-integration' ),
                'description' => __( 'Long bio for the member profile.', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'role',
                'value'       => __( 'User Role', 'advanced-form-integration' ),
                'description' => __( 'Role to assign to the member (for example subscriber, contributor).', 'advanced-form-integration' ),
            ),
        );

        if ( $include_extended ) {
            $fields = array_merge(
                $fields,
                adfoin_buddyboss_collect_extended_fields()
            );
        }

        return $fields;
    }
}

if ( ! function_exists( 'adfoin_buddyboss_collect_extended_fields' ) ) {
    /**
     * Collect BuddyBoss extended fields (profile fields, member types, groups).
     *
     * @return array
     */
    function adfoin_buddyboss_collect_extended_fields() {
        $extended_fields = array(
            array(
                'key'         => 'member_type',
                'value'       => __( 'Member Type', 'advanced-form-integration' ),
                'description' => __( 'BuddyBoss/BuddyPress member type slug.', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'group_ids',
                'value'       => __( 'Group IDs', 'advanced-form-integration' ),
                'description' => __( 'Comma-separated list of group IDs to add the member to.', 'advanced-form-integration' ),
            ),
        );

        if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {
            if ( ! class_exists( 'BP_XProfile_Group' ) && function_exists( 'buddypress' ) ) {
                $bp = buddypress();
                if ( isset( $bp->plugin_dir ) ) {
                    $file = trailingslashit( $bp->plugin_dir ) . 'bp-xprofile/classes/class-bp-xprofile-group.php';
                    if ( file_exists( $file ) ) {
                        include_once $file;
                    }
                }
            }

            if ( ! class_exists( 'BP_XProfile_Group' ) ) {
                return $extended_fields;
            }

            $groups = BP_XProfile_Group::get(
                array(
                    'fetch_fields' => true,
                )
            );

            if ( ! empty( $groups ) ) {
                foreach ( $groups as $group ) {
                    if ( empty( $group->fields ) ) {
                        continue;
                    }

                    foreach ( $group->fields as $field ) {
                        $extended_fields[] = array(
                            'key'         => 'xprofile_' . absint( $field->id ),
                            'value'       => sprintf(
                                /* translators: 1: profile field label, 2: profile group label */
                                __( '%1$s (Profile Group: %2$s)', 'advanced-form-integration' ),
                                $field->name,
                                $group->name
                            ),
                            'description' => __( 'BuddyBoss profile field', 'advanced-form-integration' ),
                        );
                    }
                }
            }
        }

        return $extended_fields;
    }
}

if ( ! function_exists( 'adfoin_buddyboss_send_data' ) ) {
    /**
     * Send data to BuddyBoss to create or update a member.
     *
     * @param array $record       Record meta.
     * @param array $posted_data  Trigger data.
     * @param bool  $allow_extended Whether extended fields (profile, groups) are allowed.
     *
     * @return void
     */
    function adfoin_buddyboss_send_data( $record, $posted_data, $allow_extended = false ) {
        $record_data = json_decode( $record['data'], true );

        if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }

        $data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $task = isset( $record['task'] ) ? $record['task'] : '';

        if ( 'create_member' !== $task ) {
            return;
        }

        $prepared_fields = array();

        foreach ( $data as $key => $value ) {
            if ( '' === $key || null === $value ) {
                continue;
            }

            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

            if ( '' === $parsed_value || null === $parsed_value ) {
                continue;
            }

            $prepared_fields[ $key ] = $parsed_value;
        }

        $result = adfoin_buddyboss_process_member( $prepared_fields, $allow_extended );

        $log_request = $prepared_fields;
        if ( isset( $log_request['user_pass'] ) ) {
            $log_request['user_pass'] = '********';
        }

        $log_response = array(
            'response' => array(
                'code'    => $result['success'] ? 200 : 400,
                'message' => $result['message'],
            ),
            'body'     => $result,
        );

        $log_args = array(
            'method' => 'LOCAL',
            'body'   => $log_request,
        );

        adfoin_add_to_log( $log_response, 'buddyboss', $log_args, $record );
    }
}

if ( ! function_exists( 'adfoin_buddyboss_process_member' ) ) {
    /**
     * Create or update a BuddyBoss/BuddyPress member.
     *
     * @param array $fields          Parsed fields.
     * @param bool  $allow_extended  Allow extended BuddyBoss fields.
     *
     * @return array
     */
    function adfoin_buddyboss_process_member( $fields, $allow_extended = false ) {
        $fields    = is_array( $fields ) ? $fields : array();
        $user_id   = 0;
        $creating  = false;
        $messages  = array();
        $raw_email = isset( $fields['user_email'] ) ? sanitize_email( $fields['user_email'] ) : '';
        $raw_login = isset( $fields['user_login'] ) ? sanitize_user( $fields['user_login'], true ) : '';

        if ( ! empty( $fields['user_id'] ) ) {
            $user = get_user_by( 'id', absint( $fields['user_id'] ) );
            if ( $user ) {
                $user_id = (int) $user->ID;
            }
        }

        if ( ! $user_id && $raw_email ) {
            $user = get_user_by( 'email', $raw_email );
            if ( $user ) {
                $user_id = (int) $user->ID;
            }
        }

        if ( ! $user_id && $raw_login ) {
            $user = get_user_by( 'login', $raw_login );
            if ( $user ) {
                $user_id = (int) $user->ID;
            }
        }

        if ( ! $user_id ) {
            if ( ! $raw_login || ! $raw_email ) {
                return array(
                    'success' => false,
                    'message' => __( 'Username and email are required to create a new BuddyBoss member.', 'advanced-form-integration' ),
                );
            }

            $creating   = true;
            $user_array = array(
                'user_login' => $raw_login,
                'user_email' => $raw_email,
                'user_pass'  => ! empty( $fields['user_pass'] ) ? $fields['user_pass'] : wp_generate_password( 12 ),
            );
        } else {
            $user_array = array(
                'ID' => $user_id,
            );

            if ( $raw_email ) {
                $user_array['user_email'] = $raw_email;
            }

            if ( ! empty( $fields['user_pass'] ) ) {
                $user_array['user_pass'] = $fields['user_pass'];
            }
        }

        if ( ! empty( $fields['display_name'] ) ) {
            $user_array['display_name'] = sanitize_text_field( $fields['display_name'] );
        }

        if ( ! empty( $fields['user_url'] ) ) {
            $user_array['user_url'] = esc_url_raw( $fields['user_url'] );
        }

        if ( ! empty( $fields['role'] ) ) {
            $user_array['role'] = sanitize_text_field( $fields['role'] );
        }

        if ( $creating ) {
            $result = wp_insert_user( $user_array );
        } else {
            $result = wp_update_user( $user_array );
        }

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
            );
        }

        $user_id = (int) $result;

        $meta_fields = array(
            'first_name' => 'first_name',
            'last_name'  => 'last_name',
            'nickname'   => 'nickname',
        );

        foreach ( $meta_fields as $field_key => $meta_key ) {
            if ( empty( $fields[ $field_key ] ) ) {
                continue;
            }

            update_user_meta( $user_id, $meta_key, sanitize_text_field( $fields[ $field_key ] ) );
        }

        if ( ! empty( $fields['description'] ) ) {
            wp_update_user(
                array(
                    'ID'          => $user_id,
                    'description' => wp_kses_post( $fields['description'] ),
                )
            );
        }

        if ( $allow_extended ) {
            if ( ! empty( $fields['member_type'] ) && function_exists( 'bp_set_member_type' ) ) {
                bp_set_member_type( $user_id, sanitize_key( $fields['member_type'] ) );
            }

            if ( ! empty( $fields['group_ids'] ) && function_exists( 'groups_join_group' ) ) {
                $group_items = preg_split( '/[,|]+/', $fields['group_ids'] );
                if ( is_array( $group_items ) ) {
                    foreach ( $group_items as $group_item ) {
                        $group_id = absint( trim( $group_item ) );
                        if ( $group_id > 0 ) {
                            groups_join_group( $group_id, $user_id );
                        }
                    }
                }
            }

            if ( function_exists( 'xprofile_set_field_data' ) ) {
                foreach ( $fields as $key => $value ) {
                    if ( 0 !== strpos( $key, 'xprofile_' ) || '' === $value ) {
                        continue;
                    }

                    $field_id = absint( substr( $key, 9 ) );

                    if ( $field_id > 0 ) {
                        xprofile_set_field_data( $field_id, $user_id, $value );
                    }
                }
            }
        }

        $message = $creating
            ? __( 'BuddyBoss member created successfully.', 'advanced-form-integration' )
            : __( 'BuddyBoss member updated successfully.', 'advanced-form-integration' );

        return array(
            'success' => true,
            'user_id' => $user_id,
            'message' => $message,
        );
    }
}
