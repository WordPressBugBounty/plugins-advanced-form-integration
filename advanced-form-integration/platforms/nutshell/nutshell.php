<?php

class ADFOIN_Nutshell {
    private static $instance;

    protected $user_email = '';

    protected $api_key = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->set_credentials();
        add_filter(
            'adfoin_action_providers',
            array($this, 'adfoin_nutshell_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_nutshell_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_nutshell_settings_view'),
            10,
            1
        );
        add_action( 'admin_post_adfoin_save_nutshell_keys', array($this, 'adfoin_save_nutshell_keys') );
        add_action( 'wp_ajax_adfoin_get_nutshell_fields', array($this, 'get_fields') );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action( 'wp_ajax_adfoin_get_nutshell_owners', array($this, 'get_owners') );
    }

    public function get_owners() {
        if ( !adfoin_verify_nonce() ) {
            return;
        }
        $response = $this->request( 'users', 'GET' );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        $owners = [];
        if ( isset( $data['users'] ) && !empty( $data['users'] ) ) {
            foreach ( $data['users'] as $user ) {
                $owners[$user['id']] = $user['firstName'] . ' ' . $user['lastName'];
            }
        }
        wp_send_json_success( $owners );
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='nutshell-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'add_contact'">
                    <th scope='row'>
                        <?php 
        esc_attr_e( 'Map Fields', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope='row'>
                        <div class='spinner' v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_lead' || action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='owner'>
                            <?php 
        esc_attr_e( 'Owner', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[ownerId]" v-model="fielddata.ownerId">
                            <option value=''><?php 
        _e( 'Select Owner...', 'advanced-form-integration' );
        ?></option>
                            <option v-for='(name, id) in fielddata.owners' :value='id'>{{name}}</option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': ownerLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>


                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>

                <?php 
        ?>

                <?php 
        if ( adfoin_fs()->is_not_paying() ) {
            ?>
                    <tr valign="top" v-if="action.task == 'add_contact'">
                        <th scope="row">
                            <?php 
            esc_attr_e( 'Go Pro', 'advanced-form-integration' );
            ?>
                        </th>
                        <td scope="row">
                            <span><?php 
            printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
            ?></span>
                        </td>
                    </tr>
                <?php 
        }
        ?>
            </table>
        </script>
        <?php 
    }

    public function adfoin_nutshell_actions( $actions ) {
        $actions['nutshell'] = [
            'title' => __( 'Nutshell CRM', 'advanced-form-integration' ),
            'tasks' => [
                'add_contact' => __( 'Add Contact', 'advanced-form-integration' ),
            ],
        ];
        return $actions;
    }

    public function adfoin_nutshell_settings_tab( $providers ) {
        $providers['nutshell'] = __( 'Nutshell CRM', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_nutshell_settings_view( $current_tab ) {
        if ( $current_tab !== 'nutshell' ) {
            return;
        }
        $option = (array) maybe_unserialize( get_option( 'adfoin_nutshell_keys' ) );
        $nonce = wp_create_nonce( "adfoin_nutshell_settings" );
        $user_email = ( isset( $option['user_email'] ) ? $option['user_email'] : "" );
        $api_key = ( isset( $option['api_key'] ) ? $option['api_key'] : "" );
        ?>

        <form name="nutshell_save_form" action="<?php 
        echo esc_url( admin_url( 'admin-post.php' ) );
        ?>"
              method="post" class="container">

            <input type="hidden" name="action" value="adfoin_save_nutshell_keys">
            <input type="hidden" name="_nonce" value="<?php 
        echo $nonce;
        ?>"/>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"> <?php 
        _e( 'User Email', 'advanced-form-integration' );
        ?></th>
                    <td>
                        <input type="text" name="adfoin_nutshell_user_email"
                               value="<?php 
        echo esc_attr( $user_email );
        ?>" placeholder="<?php 
        _e( 'Enter User Email', 'advanced-form-integration' );
        ?>"
                               class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"> <?php 
        _e( 'API Key', 'advanced-form-integration' );
        ?></th>
                    <td>
                        <input type="text" name="adfoin_nutshell_api_key"
                               value="<?php 
        echo esc_attr( $api_key );
        ?>" placeholder="<?php 
        _e( 'Enter API Key', 'advanced-form-integration' );
        ?>"
                               class="regular-text"/>
                    </td>
                </tr>
            </table>
            <?php 
        submit_button( __( 'Save', 'advanced-form-integration' ) );
        ?>
        </form>

        <?php 
    }

    public function adfoin_save_nutshell_keys() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_nutshell_settings' ) ) {
            die( __( 'Security check failed', 'advanced-form-integration' ) );
        }
        $user_email = ( isset( $_POST["adfoin_nutshell_user_email"] ) ? sanitize_text_field( $_POST["adfoin_nutshell_user_email"] ) : "" );
        $api_key = ( isset( $_POST["adfoin_nutshell_api_key"] ) ? sanitize_text_field( $_POST["adfoin_nutshell_api_key"] ) : "" );
        $this->user_email = trim( $user_email );
        $this->api_key = trim( $api_key );
        $this->save_data();
        wp_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=nutshell' ) );
        exit;
    }

    protected function save_data() {
        $data = [
            'user_email' => $this->user_email,
            'api_key'    => $this->api_key,
        ];
        update_option( 'adfoin_nutshell_keys', maybe_serialize( $data ) );
    }

    public function get_fields() {
        if ( !adfoin_verify_nonce() ) {
            return;
        }
        $task = ( isset( $_POST['task'] ) ? sanitize_text_field( $_POST['task'] ) : '' );
        $fields = [];
        if ( $task == 'add_contact' ) {
            $account_fields = [
                [
                    'key'   => 'account__name',
                    'value' => 'Name [Account]',
                ],
                [
                    'key'   => 'account__description',
                    'value' => 'Description [Account]',
                ],
                [
                    'key'   => 'account__phone',
                    'value' => 'Phone [Account]',
                ],
                [
                    'key'   => 'account__email',
                    'value' => 'Email [Account]',
                ],
                [
                    'key'   => 'account__url',
                    'value' => 'URL [Account]',
                ],
                [
                    'key'   => 'account__address_1',
                    'value' => 'Address 1 [Account]',
                ],
                [
                    'key'   => 'account__city',
                    'value' => 'City [Account]',
                ],
                [
                    'key'   => 'account__state',
                    'value' => 'State [Account]',
                ]
            ];
            $contact_fields = [
                [
                    'key'   => 'contact__name',
                    'value' => 'Name [Contact]',
                ],
                [
                    'key'   => 'contact__email',
                    'value' => 'Email [Contact]',
                ],
                [
                    'key'   => 'contact__bio',
                    'value' => 'Bio [Contact]',
                ],
                [
                    'key'   => 'contact__phone',
                    'value' => 'Phone [Contact]',
                ],
                [
                    'key'   => 'contact__address_1',
                    'value' => 'Address 1 [Contact]',
                ],
                [
                    'key'   => 'contact__city',
                    'value' => 'City [Contact]',
                ],
                [
                    'key'   => 'contact__state',
                    'value' => 'State [Contact]',
                ],
                [
                    'key'   => 'contact__postalCode',
                    'value' => 'Postal Code [Contact]',
                ],
                [
                    'key'   => 'contact__country',
                    'value' => 'Country [Contact]',
                ],
                [
                    'key'   => 'contact__url',
                    'value' => 'URL [Contact]',
                ]
            ];
            $fields = array_merge( $fields, $account_fields, $contact_fields );
        }
        wp_send_json_success( $fields );
    }

    public function create_account( $account_data, $record ) {
        $response = $this->request(
            'accounts',
            'POST',
            $account_data,
            $record
        );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['accounts'], $response_body['accounts'][0], $response_body['accounts'][0]['id'] ) ) {
            return $response_body['accounts'][0]['id'];
        }
        return $response;
    }

    public function find_account( $name ) {
        $endpoint = 'accounts?q=' . urlencode( $name );
        $response = $this->request( $endpoint, 'GET' );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( isset( $data['accounts'] ) && !empty( $data['accounts'] ) ) {
            if ( $name == $data['accounts'][0]['name'] ) {
                return $data['accounts'][0];
            }
        }
        return null;
    }

    public function update_account( $account_id, $account_data, $record ) {
        $patch_data = [];
        foreach ( $account_data['accounts'][0] as $field => $value ) {
            if ( is_array( $value ) ) {
                $patch_data[] = [
                    'op'    => 'replace',
                    'path'  => '/accounts/0/' . $field,
                    'value' => $value,
                ];
            } else {
                $patch_data[] = [
                    'op'    => 'replace',
                    'path'  => '/accounts/0/' . $field,
                    'value' => $value,
                ];
            }
        }
        $endpoint = 'accounts/' . $account_id;
        $response = $this->request(
            $endpoint,
            'PATCH',
            $patch_data,
            $record
        );
        return $response;
    }

    public function create_contact( $contact_data, $record ) {
        $response = $this->request(
            'contacts',
            'POST',
            $contact_data,
            $record
        );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $response_body['id'] ) ) {
            return $response_body['id'];
        }
        return $response;
    }

    public function find_contact( $email ) {
        $endpoint = 'contacts?email=' . urlencode( $email );
        $response = $this->request( $endpoint, 'GET' );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( isset( $data['contacts'] ) && !empty( $data['contacts'] ) ) {
            return $data['contacts'][0];
        }
        return null;
    }

    public function update_contact( $contact_id, $contact_data, $record ) {
        $endpoint = 'contacts/' . $contact_id;
        $response = $this->request(
            $endpoint,
            'PUT',
            $contact_data,
            $record
        );
        return $response;
    }

    public function request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array(),
        $cred_id = ''
    ) {
        $base_url = "https://app.nutshell.com/rest/";
        $url = $base_url . $endpoint;
        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type'  => ( $method === 'PATCH' ? 'application/json-patch+json' : 'application/json' ),
                'Authorization' => 'Basic ' . base64_encode( $this->user_email . ':' . $this->api_key ),
            ),
        );
        if ( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
            $args['body'] = json_encode( $data );
        }
        $response = wp_remote_request( $url, $args );
        if ( $record ) {
            adfoin_add_to_log(
                $response,
                $url,
                $args,
                $record
            );
        }
        return $response;
    }

    public function set_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_nutshell_keys' ) );
        if ( isset( $option['user_email'] ) ) {
            $this->user_email = $option['user_email'];
        }
        if ( isset( $option['api_key'] ) ) {
            $this->api_key = $option['api_key'];
        }
    }

}

$nutshell = ADFOIN_Nutshell::get_instance();
add_action(
    'adfoin_nutshell_job_queue',
    'adfoin_nutshell_job_queue',
    10,
    1
);
function adfoin_nutshell_job_queue(  $data  ) {
    adfoin_nutshell_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_nutshell_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $owner_id = ( isset( $data['ownerId'] ) ? $data['ownerId'] : null );
    $nutshell = ADFOIN_Nutshell::get_instance();
    if ( $task == 'add_contact' ) {
        $parsed_account_data = array();
        $parsed_contact_data = array();
        $account_id = null;
        $contact_id = null;
        foreach ( $data as $key => $value ) {
            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
            if ( $parsed_value ) {
                if ( strpos( $key, 'account__' ) === 0 ) {
                    $parsed_account_data[substr( $key, strlen( 'account__' ) )] = $parsed_value;
                } elseif ( strpos( $key, 'contact__' ) === 0 ) {
                    $parsed_contact_data[substr( $key, strlen( 'contact__' ) )] = $parsed_value;
                }
            }
        }
        if ( !empty( $parsed_account_data ) ) {
            $account_data = [
                'accounts' => [array_filter( [
                    'name'        => ( isset( $parsed_account_data['name'] ) ? $parsed_account_data['name'] : '' ),
                    'description' => ( isset( $parsed_account_data['description'] ) ? $parsed_account_data['description'] : '' ),
                ] )],
            ];
            if ( isset( $parsed_account_data['phone'] ) ) {
                $account_data['accounts'][0]['phones'] = [];
                $account_data['accounts'][0]['phones'][] = [
                    'isPrimary' => true,
                    'name'      => 'phone',
                    'value'     => $parsed_account_data['phone'],
                ];
            }
            if ( isset( $parsed_account_data['email'] ) ) {
                $account_data['accounts'][0]['emails'] = [];
                $account_data['accounts'][0]['emails'][] = [
                    'value' => $parsed_account_data['email'],
                ];
            }
            if ( isset( $parsed_account_data['url'] ) ) {
                $account_data['accounts'][0]['urls'] = [];
                $account_data['accounts'][0]['urls'][] = [
                    'value' => $parsed_account_data['url'],
                ];
            }
            if ( isset( $parsed_account_data['address_1'] ) || isset( $parsed_account_data['city'] ) || isset( $parsed_account_data['state'] ) ) {
                $account_data['accounts'][0]['addresses'] = [];
                $account_data['accounts'][0]['addresses'][] = array_filter( [
                    'isPrimary' => true,
                    'name'      => 'address',
                    'value'     => [
                        'address_1' => ( isset( $parsed_account_data['address_1'] ) ? $parsed_account_data['address_1'] : '' ),
                        'city'      => ( isset( $parsed_account_data['city'] ) ? $parsed_account_data['city'] : '' ),
                        'state'     => ( isset( $parsed_account_data['state'] ) ? $parsed_account_data['state'] : '' ),
                    ],
                ] );
            }
            if ( $owner_id ) {
                if ( !isset( $account_data['accounts'][0]['links'] ) ) {
                    $account_data['accounts'][0]['links'] = [];
                }
                $account_data['accounts'][0]['links']['owner'] = $owner_id;
            }
            $existing_account = $nutshell->find_account( $parsed_account_data['name'] );
            if ( $existing_account ) {
                $account_id = $existing_account['id'];
                $nutshell->update_account( $account_id, $account_data, $record );
            } else {
                $account_id = $nutshell->create_account( $account_data, $record );
            }
        }
        if ( !empty( $parsed_contact_data ) ) {
            $contact_data = [
                'contacts' => [array_filter( [
                    'name'        => ( isset( $parsed_contact_data['name'] ) ? $parsed_contact_data['name'] : '' ),
                    'description' => ( isset( $parsed_contact_data['bio'] ) ? $parsed_contact_data['bio'] : '' ),
                ] )],
            ];
            if ( isset( $parsed_contact_data['phone'] ) ) {
                $contact_data['contacts'][0]['phones'] = [];
                $contact_data['contacts'][0]['phones'][] = [
                    'isPrimary' => true,
                    'name'      => 'phone',
                    'value'     => [
                        'number' => $parsed_contact_data['phone'],
                    ],
                ];
            }
            if ( isset( $parsed_contact_data['email'] ) ) {
                $contact_data['contacts'][0]['emails'] = [];
                $contact_data['contacts'][0]['emails'][] = [
                    'isPrimary' => true,
                    'name'      => 'email',
                    'value'     => $parsed_contact_data['email'],
                ];
            }
            if ( isset( $parsed_contact_data['url'] ) ) {
                $contact_data['contacts'][0]['urls'] = [];
                $contact_data['contacts'][0]['urls'][] = [
                    'isPrimary' => true,
                    'name'      => 'url',
                    'value'     => $parsed_contact_data['url'],
                ];
            }
            if ( isset( $parsed_contact_data['address_1'] ) || isset( $parsed_contact_data['city'] ) || isset( $parsed_contact_data['state'] ) || isset( $parsed_contact_data['postalCode'] ) || isset( $parsed_contact_data['country'] ) ) {
                $contact_data['contacts'][0]['addresses'] = [];
                $contact_data['contacts'][0]['addresses'][] = array_filter( [
                    'isPrimary'  => true,
                    'name'       => 'address',
                    'address_1'  => ( isset( $parsed_contact_data['address_1'] ) ? $parsed_contact_data['address_1'] : '' ),
                    'city'       => ( isset( $parsed_contact_data['city'] ) ? $parsed_contact_data['city'] : '' ),
                    'state'      => ( isset( $parsed_contact_data['state'] ) ? $parsed_contact_data['state'] : '' ),
                    'postalCode' => ( isset( $parsed_contact_data['postalCode'] ) ? $parsed_contact_data['postalCode'] : '' ),
                    'country'    => ( isset( $parsed_contact_data['country'] ) ? $parsed_contact_data['country'] : '' ),
                ] );
            }
            if ( $account_id ) {
                $contact_data['contacts'][0]['links'] = [];
                $contact_data['contacts'][0]['links']['accounts'] = [$account_id];
            }
            if ( $owner_id ) {
                if ( !isset( $contact_data['contacts'][0]['links'] ) ) {
                    $contact_data['contacts'][0]['links'] = [];
                }
                $contact_data['contacts'][0]['links']['owner'] = $owner_id;
            }
            $existing_contact = $nutshell->find_contact( $parsed_contact_data['email'] );
            if ( $existing_contact ) {
                $contact_id = $existing_contact['id'];
                $nutshell->update_contact( $contact_id, $contact_data, $record );
            } else {
                $nutshell->create_contact( $contact_data, $record );
            }
        }
    }
}
