<?php

class ADFOIN_Acuity {
    const api_base = 'https://acuityscheduling.com/api/v1/';
    private static $instance;
    private $user_id;
    private $api_key;
    private $cred_id;

    /**
     * Retrieve singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_acuity_appointment_types', array( $this, 'ajax_get_appointment_types' ) );
        add_action( 'wp_ajax_adfoin_get_acuity_calendars', array( $this, 'ajax_get_calendars' ) );
        // New AJAX hooks
        add_action( 'wp_ajax_adfoin_get_acuity_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_acuity_credentials', array( $this, 'save_credentials' ) );
        add_action( 'wp_ajax_adfoin_get_acuity_credentials_list', array( $this, 'ajax_get_credentials_list' ) );
    }

    /**
     * Register Acuity provider/tasks.
     *
     * @param array $actions Existing providers.
     *
     * @return array
     */
    public function register_actions( $actions ) {
        $actions['acuity'] = array(
            'title' => __( 'Acuity Scheduling', 'advanced-form-integration' ),
            'tasks' => array(
                'create_appointment' => __( 'Create Appointment', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    /**
     * Add settings tab entry.
     *
     * @param array $providers Current tabs.
     *
     * @return array
     */
    public function register_settings_tab( $providers ) {
        $providers['acuity'] = __( 'Acuity Scheduling', 'advanced-form-integration' );

        return $providers;
    }

    /**
     * Render Acuity settings screen.
     *
     * @param string $current_tab Current tab slug.
     */
    public function settings_view( $current_tab ) {
        if ( 'acuity' !== $current_tab ) {
            return;
        }

        // Load Account Manager
        if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }

        $fields = array(
            array(
                'name'          => 'user_id',
                'label'         => __( 'User ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'placeholder'   => __( 'Enter your Acuity User ID', 'advanced-form-integration' ),
                'show_in_table' => true,
            ),
            array(
                'name'          => 'api_key',
                'label'         => __( 'API Key', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'placeholder'   => __( 'Enter your Acuity API Key', 'advanced-form-integration' ),
                'mask'          => true,  // Mask API key in table
                'show_in_table' => true,
            ),
        );

        $instructions = '<ol class="afi-instructions-list">
                            <li>' . esc_html__( 'Log in to your Acuity Scheduling account.', 'advanced-form-integration' ) . '</li>
                            <li>' . esc_html__( 'Go to Integrations > API > View Credentials.', 'advanced-form-integration' ) . '</li>
                            <li>' . esc_html__( 'Copy your User ID and API Key.', 'advanced-form-integration' ) . '</li>
                            <li>' . esc_html__( 'Click "Add Account" and enter your credentials.', 'advanced-form-integration' ) . '</li>
                        </ol>';

        ADFOIN_Account_Manager::render_settings_view( 'acuity', 'Acuity Scheduling', $fields, $instructions );
    }

    public function get_credentials() {
        if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }
        ADFOIN_Account_Manager::ajax_get_credentials( 'acuity' );
    }

    public function save_credentials() {
        if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }
        ADFOIN_Account_Manager::ajax_save_credentials( 'acuity', array( 'user_id', 'api_key' ) );
    }

    public function set_credentials( $cred_id ) {
        $credentials = adfoin_read_credentials( 'acuity' );
        
        // Backward compatibility: If no credId, use the first available credential
        if ( empty( $cred_id ) && ! empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = isset( $first_credential['id'] ) ? $first_credential['id'] : '';
        }
        
        foreach( $credentials as $single ) {
            if( $cred_id && $cred_id == $single['id'] ) {
                $this->cred_id = $single['id'];
                $this->user_id = isset( $single['user_id'] ) ? $single['user_id'] : '';
                $this->api_key = isset( $single['api_key'] ) ? $single['api_key'] : '';
                return;
            }
        }
    }

    /**
     * AJAX: fetch appointment types.
     */
    public function ajax_get_appointment_types() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        if ( ! $cred_id ) {
            wp_send_json_error();
        }

        $this->set_credentials( $cred_id );
        $response = $this->api_request( 'appointment-types', 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $types = array();

        if ( is_array( $body ) ) {
            foreach ( $body as $type ) {
                if ( isset( $type['id'], $type['name'] ) ) {
                    $types[ $type['id'] ] = $type['name'];
                }
            }
        }

        wp_send_json_success( $types );
    }

    /**
     * AJAX: fetch calendars.
     */
    public function ajax_get_calendars() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
        $this->set_credentials( $cred_id );
        $response = $this->api_request( 'calendars', 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $body      = json_decode( wp_remote_retrieve_body( $response ), true );
        $calendars = array();

        if ( is_array( $body ) ) {
            foreach ( $body as $calendar ) {
                if ( isset( $calendar['id'], $calendar['name'] ) ) {
                    $calendars[ $calendar['id'] ] = $calendar['name'];
                }
            }
        }

        wp_send_json_success( $calendars );
    }

    public function ajax_get_credentials_list() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }

        $fields = array(
            array( 'name' => 'user_id', 'mask' => false ),
            array( 'name' => 'api_key', 'mask' => true ),
        );

        ADFOIN_Account_Manager::ajax_get_credentials_list( 'acuity', $fields );
    }

    /**
     * Print action template markup.
     */
    public function action_fields() {
        ?>
        <script type="text/template" id="acuity-action-template">
            <div>
                <table class="form-table" v-if="action.task == 'create_appointment'">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Acuity Account', 'advanced-form-integration' ); ?></th>
                        <td>
                            <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchAppointmentTypes">
                                <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                                <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                            </select>
                            <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=acuity' ); ?>" 
                               target="_blank" 
                               style="margin-left: 10px; text-decoration: none;">
                                <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                                <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Appointment Type', 'advanced-form-integration' ); ?></th>
                        <td>
                            <select name="fieldData[appointmentTypeId]" v-model="fielddata.appointmentTypeId">
                                <option value=""><?php esc_html_e( 'Select appointment type…', 'advanced-form-integration' ); ?></option>
                                <option v-for="(label, id) in appointmentTypes" :value="id">{{ label }}</option>
                            </select>
                            <button type="button" class="button" @click="fetchAppointmentTypes" :disabled="appointmentTypeLoading">
                                <?php esc_html_e( 'Refresh', 'advanced-form-integration' ); ?>
                            </button>
                            <span class="spinner" :class="{ 'is-active': appointmentTypeLoading }"></span>
                            <p class="description"><?php esc_html_e( 'Required. Types are loaded from Acuity after connecting.', 'advanced-form-integration' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Calendar', 'advanced-form-integration' ); ?></th>
                        <td>
                            <select name="fieldData[calendarId]" v-model="fielddata.calendarId">
                                <option value=""><?php esc_html_e( 'Auto-select available calendar', 'advanced-form-integration' ); ?></option>
                                <option v-for="(label, id) in calendars" :value="id">{{ label }}</option>
                            </select>
                            <button type="button" class="button" @click="fetchCalendars" :disabled="calendarLoading">
                                <?php esc_html_e( 'Refresh', 'advanced-form-integration' ); ?>
                            </button>
                            <span class="spinner" :class="{ 'is-active': calendarLoading }"></span>
                            <p class="description"><?php esc_html_e( 'Required when booking as an admin or when you need to force a specific calendar.', 'advanced-form-integration' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Booking Mode', 'advanced-form-integration' ); ?></th>
                        <td>
                            <select name="fieldData[adminMode]" v-model="fielddata.adminMode">
                                <option value="client"><?php esc_html_e( 'Book like a client (checks availability)', 'advanced-form-integration' ); ?></option>
                                <option value="admin"><?php esc_html_e( 'Book as admin (bypasses availability, requires calendar)', 'advanced-form-integration' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Send Notifications', 'advanced-form-integration' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" v-model="fielddata.noEmail">
                                <?php esc_html_e( 'Disable confirmation e-mails/SMS (adds ?noEmail=true)', 'advanced-form-integration' ); ?>
                            </label>
                        </td>
                    </tr>

                    <editable-field
                        v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
                    </editable-field>
                </table>
            </div>
        </script>
        <?php
    }

    /**
     * Perform HTTP request.
     *
     * @param string $endpoint API endpoint.
     * @param string $method   HTTP method.
     * @param array  $data     Payload.
     * @param array  $query    Query args.
     * @param array  $record   Record data for logging.
     *
     * @return array|WP_Error
     */
    public function api_request( $endpoint, $method = 'GET', $data = array(), $query = array(), $record = array() ) {
        if ( empty( $this->user_id ) || empty( $this->api_key ) ) {
            return new WP_Error( 'adfoin_acuity_missing_creds', __( 'Connect Acuity Scheduling before running this action.', 'advanced-form-integration' ) );
        }

        $url = self::api_base . ltrim( $endpoint, '/' );

        if ( ! empty( $query ) ) {
            $url = add_query_arg( $query, $url );
        }

        $args = array(
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->user_id . ':' . $this->api_key ),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'timeout' => 20,
        );

        if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );

        if ( ! empty( $record ) ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 ) {
            $message = wp_remote_retrieve_body( $response );

            if ( $message ) {
                $decoded = json_decode( $message, true );

                if ( isset( $decoded['message'] ) ) {
                    $message = $decoded['message'];
                }
            }

            return new WP_Error( 'adfoin_acuity_http_error', $message ? $message : __( 'Acuity API request failed.', 'advanced-form-integration' ) );
        }

        return $response;
    }
}

$adfoin_acuity = ADFOIN_Acuity::get_instance();

/**
 * Return field definition for Vue renderer.
 *
 * @return array
 */
function adfoin_acuity_fields() {
    return array(
        array(
            'key'         => 'datetime',
            'value'       => __( 'Appointment Date & Time', 'advanced-form-integration' ),
            'description' => __( 'ISO 8601 format, e.g. 2024-05-12T14:00:00-0500', 'advanced-form-integration' ),
            'required'    => true,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'firstName',
            'value'       => __( 'First Name', 'advanced-form-integration' ),
            'required'    => true,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'lastName',
            'value'       => __( 'Last Name', 'advanced-form-integration' ),
            'required'    => true,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'email',
            'value'       => __( 'Email', 'advanced-form-integration' ),
            'required'    => true,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'phone',
            'value'       => __( 'Phone', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'timezone',
            'value'       => __( 'Timezone', 'advanced-form-integration' ),
            'description' => __( 'IANA timezone such as America/New_York. Defaults to the calendar timezone.', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'certificate',
            'value'       => __( 'Certificate / Coupon Code', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'notes',
            'value'       => __( 'Notes', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'textarea',
        ),
        array(
            'key'         => 'price',
            'value'       => __( 'Price Override', 'advanced-form-integration' ),
            'description' => __( 'Optional price override for paid appointments.', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'fieldDefinitions',
            'value'       => __( 'Form Fields (JSON)', 'advanced-form-integration' ),
            'description' => __( 'JSON array [{"id":123,"value":"Answer"}] to populate Acuity intake forms.', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'textarea',
        ),
        array(
            'key'         => 'addonIds',
            'value'       => __( 'Addon IDs (comma separated)', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'text',
        ),
        array(
            'key'         => 'labelId',
            'value'       => __( 'Label ID', 'advanced-form-integration' ),
            'required'    => false,
            'controlType' => 'text',
        ),
    );
}

/**
 * Map field keys to API payload keys.
 *
 * @return array
 */
function adfoin_acuity_field_map() {
    return array(
        'datetime'         => 'datetime',
        'firstName'        => 'firstName',
        'lastName'         => 'lastName',
        'email'            => 'email',
        'phone'            => 'phone',
        'timezone'         => 'timezone',
        'certificate'      => 'certificate',
        'notes'            => 'notes',
        'price'            => 'price',
        'fieldDefinitions' => 'fields',
        'addonIds'         => 'addonIDs',
        'labelId'          => 'labels',
    );
}

/**
 * Job queue listener.
 *
 * @param array $data Job payload.
 */
function adfoin_acuity_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'acuity' || ( $data['task'] ?? '' ) !== 'create_appointment' ) {
        return;
    }

    adfoin_acuity_send_data( $data['record'], $data['posted_data'] );
}

add_action( 'adfoin_job_queue', 'adfoin_acuity_job_queue', 10, 1 );

/**
 * Main dispatcher for Acuity action.
 *
 * @param array $record      Log record.
 * @param array $posted_data Trigger payload.
 */
function adfoin_acuity_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();

    $payload = adfoin_acuity_prepare_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, 'acuity', array(), $record );
        return;
    }

    $query_args = array();

    if ( isset( $field_data['adminMode'] ) && 'admin' === $field_data['adminMode'] ) {
        $query_args['admin'] = 'true';
    }

    if ( ! empty( $field_data['noEmail'] ) ) {
        $query_args['noEmail'] = 'true';
    }

    $acuity = ADFOIN_Acuity::get_instance();
    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $acuity->set_credentials( $cred_id );
    $acuity->api_request( 'appointments', 'POST', $payload, $query_args, $record );
}

/**
 * Build request payload.
 *
 * @param array $field_data Field configuration.
 * @param array $posted_data Trigger data.
 *
 * @return array|WP_Error
 */
function adfoin_acuity_prepare_payload( $field_data, $posted_data ) {
    $appointment_type = isset( $field_data['appointmentTypeId'] ) ? sanitize_text_field( $field_data['appointmentTypeId'] ) : '';

    if ( ! $appointment_type ) {
        return new WP_Error( 'adfoin_acuity_missing_type', __( 'Appointment type is required.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'appointmentTypeID' => (int) $appointment_type,
    );

    if ( isset( $field_data['calendarId'] ) && '' !== $field_data['calendarId'] ) {
        $payload['calendarID'] = (int) $field_data['calendarId'];
    }

    $field_map = adfoin_acuity_field_map();

    foreach ( $field_map as $field_key => $api_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        switch ( $field_key ) {
            case 'price':
                $payload[ $api_key ] = (float) $value;
                break;
            case 'fieldDefinitions':
                $decoded = json_decode( $value, true );

                if ( is_array( $decoded ) ) {
                    $payload[ $api_key ] = $decoded;
                }
                break;
            case 'addonIds':
                $ids = array_filter(
                    array_map(
                        'absint',
                        array_map( 'trim', explode( ',', $value ) )
                    )
                );

                if ( ! empty( $ids ) ) {
                    $payload[ $api_key ] = $ids;
                }
                break;
            case 'labelId':
                $label_id = absint( $value );

                if ( $label_id ) {
                    $payload[ $api_key ] = array(
                        array(
                            'id' => $label_id,
                        ),
                    );
                }
                break;
            default:
                $payload[ $api_key ] = $value;
                break;
        }
    }

    $required = array( 'datetime', 'firstName', 'lastName', 'email' );

    foreach ( $required as $required_key ) {
        $api_key = $field_map[ $required_key ];

        if ( empty( $payload[ $api_key ] ) ) {
            return new WP_Error(
                'adfoin_acuity_missing_required',
                sprintf(
                    /* translators: %s field name. */
                    __( '%s is required for Acuity appointments.', 'advanced-form-integration' ),
                    $api_key
                )
            );
        }
    }

    return $payload;
}