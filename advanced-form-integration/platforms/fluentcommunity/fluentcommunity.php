<?php

add_filter( 'adfoin_action_providers', 'adfoin_fluentcommunity_actions', 10, 1 );

function adfoin_fluentcommunity_actions( $actions ) {

    $actions['fluentcommunity'] = array(
        'title' => __( 'Fluent Community', 'advanced-form-integration' ),
        'tasks' => array(
            'create_space'      => __( 'Create Space', 'advanced-form-integration' ),
            'invite_member'     => __( 'Invite Member', 'advanced-form-integration' ),
            'create_space_group'=> __( 'Create Space Group', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_fluentcommunity_action_fields' );

function adfoin_fluentcommunity_action_fields() {
    ?>
    <script type="text/template" id="fluentcommunity-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_space'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Title and privacy are required. Slug defaults from the title if left blank. Settings accept a JSON string matching Fluent Community schema.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'invite_member'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Space ID, inviter user ID, and invitee email are required. The invited user must not already exist in the space.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'create_space_group'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a group title and optionally description or parent folder ID. The current user will become group admin.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            :key="field.value"
                            :field="field"
                            :trigger="trigger"
                            :action="action"
                            :fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_fluentcommunity_job_queue', 'adfoin_fluentcommunity_job_queue', 10, 1 );

function adfoin_fluentcommunity_job_queue( $data ) {
    adfoin_fluentcommunity_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_fluentcommunity_send_data( $record, $posted_data ) {
    if ( ! class_exists( '\FluentCommunity\App\Models\Space' ) ) {
        adfoin_fluentcommunity_log( $record, __( 'Fluent Community is not active.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $parsed = array();

    if ( is_array( $field_data ) ) {
        foreach ( $field_data as $key => $value ) {
            $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
        }
    }

    if ( 'create_space' === $task ) {
        adfoin_fluentcommunity_create_space( $record, $parsed );
    } elseif ( 'invite_member' === $task ) {
        adfoin_fluentcommunity_invite_member( $record, $parsed );
    } elseif ( 'create_space_group' === $task ) {
        adfoin_fluentcommunity_create_group( $record, $parsed );
    }
}

function adfoin_fluentcommunity_create_space( $record, $parsed ) {
    if ( ! class_exists( '\FluentCommunity\App\Http\Controllers\SpaceController' ) ) {
        adfoin_fluentcommunity_log( $record, __( 'Space controller unavailable.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $title   = isset( $parsed['title'] ) ? sanitize_text_field( $parsed['title'] ) : '';
    $privacy = isset( $parsed['privacy'] ) ? sanitize_text_field( $parsed['privacy'] ) : '';

    if ( '' === $title || '' === $privacy ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Title and privacy are required for a space.', 'advanced-form-integration' ),
            array(
                'title'   => $title,
                'privacy' => $privacy,
            ),
            false
        );
        return;
    }

    $allowed_privacy = array( 'public', 'private', 'secret' );
    if ( ! in_array( $privacy, $allowed_privacy, true ) ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Privacy must be public, private, or secret.', 'advanced-form-integration' ),
            array( 'privacy' => $privacy ),
            false
        );
        return;
    }

    $slug = isset( $parsed['slug'] ) ? sanitize_title( $parsed['slug'] ) : '';
    if ( '' === $slug ) {
        $slug = sanitize_title( $title );
    }

    $description = isset( $parsed['description'] ) ? sanitize_textarea_field( $parsed['description'] ) : '';
    $parent_id   = isset( $parsed['parent_id'] ) && $parsed['parent_id'] !== '' ? absint( $parsed['parent_id'] ) : null;
    $settings    = isset( $parsed['settings_json'] ) ? adfoin_fluentcommunity_decode_json( $parsed['settings_json'] ) : array();

    if ( $parent_id ) {
        $group = \FluentCommunity\App\Models\SpaceGroup::find( $parent_id );
        if ( ! $group ) {
            adfoin_fluentcommunity_log(
                $record,
                __( 'Parent space group was not found.', 'advanced-form-integration' ),
                array( 'parent_id' => $parent_id ),
                false
            );
            return;
        }
    }

    $space_payload = array_filter(
        array(
            'title'       => $title,
            'slug'        => $slug,
            'privacy'     => $privacy,
            'description' => $description,
            'settings'    => $settings,
            'parent_id'   => $parent_id,
        ),
        static function ( $value ) {
            return null !== $value && '' !== $value;
        }
    );

    \FluentCommunity\App\App::make( 'request' )->merge( array( 'space' => $space_payload ) );

    try {
        $controller = new \FluentCommunity\App\Http\Controllers\SpaceController();
        $response   = $controller->create( \FluentCommunity\App\App::make( 'request' ) );
    } catch ( \Exception $e ) {
        adfoin_fluentcommunity_log(
            $record,
            sprintf(
                /* translators: %s error message */
                __( 'Failed to create space: %s', 'advanced-form-integration' ),
                $e->getMessage()
            ),
            $space_payload,
            false
        );
        return;
    }

    if ( is_array( $response ) && isset( $response['space'] ) ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Space created successfully.', 'advanced-form-integration' ),
            array(
                'space_id' => $response['space']->id ?? null,
                'title'    => $response['space']->title ?? '',
            ),
            true
        );
    } else {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Space creation did not return a valid response.', 'advanced-form-integration' ),
            $space_payload,
            false
        );
    }
}

function adfoin_fluentcommunity_invite_member( $record, $parsed ) {
    if ( ! class_exists( '\FluentCommunity\Modules\Auth\Classes\InvitationService' ) ) {
        adfoin_fluentcommunity_log( $record, __( 'Invitation service is unavailable.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $space_id = isset( $parsed['space_id'] ) ? absint( $parsed['space_id'] ) : 0;
    $user_id  = isset( $parsed['user_id'] ) ? absint( $parsed['user_id'] ) : 0;
    $email    = isset( $parsed['invitee_email'] ) ? sanitize_email( $parsed['invitee_email'] ) : '';

    if ( ! $space_id || ! $user_id || '' === $email ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Space ID, inviter user ID, and invitee email are required.', 'advanced-form-integration' ),
            array(
                'space_id' => $space_id,
                'user_id'  => $user_id,
                'email'    => $email,
            ),
            false
        );
        return;
    }

    $space = \FluentCommunity\App\Models\Space::find( $space_id );
    if ( ! $space ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Space not found.', 'advanced-form-integration' ),
            array( 'space_id' => $space_id ),
            false
        );
        return;
    }

    $inviter = get_user_by( 'id', $user_id );
    if ( ! $inviter ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Inviter user not found.', 'advanced-form-integration' ),
            array( 'user_id' => $user_id ),
            false
        );
        return;
    }

    $existing_membership = \FluentCommunity\App\Models\SpaceUserPivot::where( 'space_id', $space_id )
        ->where( 'user_id', $user_id )
        ->first();

    if ( ! $existing_membership ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Inviter must be member of the space to invite others.', 'advanced-form-integration' ),
            array(
                'user_id'  => $user_id,
                'space_id' => $space_id,
            ),
            false
        );
        return;
    }

    $data = array_filter(
        array(
            'email'        => $email,
            'user_id'      => $user_id,
            'space_id'     => $space_id,
            'invitee_name' => isset( $parsed['invitee_name'] ) ? sanitize_text_field( $parsed['invitee_name'] ) : '',
        ),
        static function ( $value ) {
            return null !== $value && '' !== $value;
        }
    );

    $result = \FluentCommunity\Modules\Auth\Classes\InvitationService::invite( $data );

    if ( is_wp_error( $result ) ) {
        adfoin_fluentcommunity_log(
            $record,
            sprintf(
                /* translators: %s error message */
                __( 'Failed to invite member: %s', 'advanced-form-integration' ),
                $result->get_error_message()
            ),
            $data,
            false
        );
        return;
    }

    adfoin_fluentcommunity_log(
        $record,
        __( 'Invitation sent successfully.', 'advanced-form-integration' ),
        array(
            'invitation_id' => $result->id ?? null,
            'email'         => $email,
        ),
        true
    );
}

function adfoin_fluentcommunity_create_group( $record, $parsed ) {
    if ( ! class_exists( '\FluentCommunity\App\Models\SpaceGroup' ) ) {
        adfoin_fluentcommunity_log( $record, __( 'Space group model not available.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $title = isset( $parsed['title'] ) ? sanitize_text_field( $parsed['title'] ) : '';
    if ( '' === $title ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Title is required to create a space group.', 'advanced-form-integration' ),
            array(),
            false
        );
        return;
    }

    $description = isset( $parsed['description'] ) ? sanitize_textarea_field( $parsed['description'] ) : '';
    $parent_id   = isset( $parsed['parent_id'] ) && $parsed['parent_id'] !== '' ? absint( $parsed['parent_id'] ) : null;

    if ( $parent_id ) {
        $parent_group = \FluentCommunity\App\Models\SpaceGroup::find( $parent_id );
        if ( ! $parent_group ) {
            adfoin_fluentcommunity_log(
                $record,
                __( 'Parent group not found.', 'advanced-form-integration' ),
                array( 'parent_id' => $parent_id ),
                false
            );
            return;
        }
    }

    $group = \FluentCommunity\App\Models\SpaceGroup::create(
        array_filter(
            array(
                'title'       => $title,
                'description' => $description,
                'parent_id'   => $parent_id,
            ),
            static function ( $value ) {
                return null !== $value && '' !== $value;
            }
        )
    );

    if ( ! $group ) {
        adfoin_fluentcommunity_log(
            $record,
            __( 'Failed to create space group.', 'advanced-form-integration' ),
            array(
                'title' => $title,
            ),
            false
        );
        return;
    }

    adfoin_fluentcommunity_log(
        $record,
        __( 'Space group created successfully.', 'advanced-form-integration' ),
        array(
            'group_id' => $group->id,
            'title'    => $group->title,
        ),
        true
    );
}

function adfoin_fluentcommunity_decode_json( $value ) {
    if ( '' === $value ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
        return $decoded;
    }

    return array();
}

function adfoin_fluentcommunity_log( $record, $message, $payload, $success ) {
    $log_response = array(
        'response' => array(
            'code'    => $success ? 200 : 400,
            'message' => $message,
        ),
        'body'     => array(
            'success' => $success,
            'message' => $message,
        ),
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $payload,
    );

    adfoin_add_to_log( $log_response, 'fluentcommunity', $log_args, $record );
}
