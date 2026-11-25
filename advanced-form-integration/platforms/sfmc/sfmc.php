<?php

/**
 * Salesforce Marketing Cloud (Marketing Cloud Engagement, formerly
 * ExactTarget) — add or update a row in a Data Extension.
 *
 * Auth is a server-to-server Installed Package: OAuth2 client_credentials
 * against the tenant's own auth subdomain. The token response carries the
 * tenant's REST and SOAP base URLs, which are cached with the token.
 *
 * - Token:    POST https://{subdomain}.auth.marketingcloudapis.com/v2/token
 * - Upsert:   POST {rest}/hub/v1/dataevents/key:{externalKey}/rowset
 *             (Data Extensions with a primary key)
 * - Insert:   POST {rest}/data/v1/async/dataextensions/key:{externalKey}/rows
 *             (Data Extensions without a primary key)
 * - Listing Data Extensions and their fields is only documented on the SOAP
 *   API (Retrieve DataExtension / DataExtensionField), so the admin
 *   dropdowns use SOAP with the same OAuth token (fueloauth header).
 *
 * Field keys are `pk__<Name>` for primary-key columns and `de__<Name>` for
 * the rest, so the send path knows which columns go in "keys" without an
 * extra API call per submission.
 *
 * @link https://developer.salesforce.com/docs/marketing/marketing-cloud/guide/access-token-s2s.html
 * @link https://developer.salesforce.com/docs/marketing/marketing-cloud/references/mc_rest_data_events/postDataExtensionRowsetByKey.html
 * @link https://developer.salesforce.com/docs/marketing/marketing-cloud/guide/dataextension.html
 */

add_filter( 'adfoin_action_providers', 'adfoin_sfmc_actions', 10, 1 );

function adfoin_sfmc_actions( $actions ) {
    $actions['sfmc'] = array(
        'title' => __( 'Salesforce Marketing Cloud', 'advanced-form-integration' ),
        'tasks' => array( 'upsert_row' => __( 'Add/Update Data Extension Row', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sfmc_settings_tab', 10, 1 );

function adfoin_sfmc_settings_tab( $providers ) {
    $providers['sfmc'] = __( 'Salesforce Marketing Cloud', 'advanced-form-integration' );
    return $providers;
}

if ( ! function_exists( 'adfoin_sfmc_credential_fields' ) ) :
function adfoin_sfmc_credential_fields() {
    return array(
        array( 'name' => 'authBaseUri', 'label' => __( 'Authentication Base URI', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'show_in_table' => true, 'placeholder' => 'https://mcxxxxxxxxxxxxxxxxxxxxxxxxxx.auth.marketingcloudapis.com/' ),
        array( 'name' => 'clientId', 'label' => __( 'Client ID', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true ),
        array( 'name' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true ),
        array( 'name' => 'accountId', 'label' => __( 'Account ID (MID)', 'advanced-form-integration' ), 'type' => 'text', 'required' => false, 'show_in_table' => true, 'description' => __( 'Optional. The business unit to connect to. Leave blank to use the package\'s default business unit.', 'advanced-form-integration' ) ),
    );
}
endif;

add_action( 'adfoin_settings_view', 'adfoin_sfmc_settings_view', 10, 1 );

function adfoin_sfmc_settings_view( $current_tab ) {
    if ( 'sfmc' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Marketing Cloud, go to Setup > Apps > Installed Packages and create a new package.', 'advanced-form-integration' ),
        esc_html__( 'Add an API Integration component, choose Server-to-Server, and give it at least: Data Extensions (Read, Write), and Journeys (Read, Execute) if you will fire journey entry events.', 'advanced-form-integration' ),
        esc_html__( 'Copy the Client ID, Client Secret and Authentication Base URI from the component.', 'advanced-form-integration' ),
        esc_html__( 'Paste them below. Add the MID only if the data extensions live in a business unit other than the package\'s default.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'sfmc', __( 'Salesforce Marketing Cloud', 'advanced-form-integration' ), adfoin_sfmc_credential_fields(), $instructions );
}

add_action( 'wp_ajax_adfoin_get_sfmc_credentials', 'adfoin_get_sfmc_credentials', 10, 0 );

function adfoin_get_sfmc_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sfmc' );
}

add_action( 'wp_ajax_adfoin_save_sfmc_credentials', 'adfoin_save_sfmc_credentials', 10, 0 );

function adfoin_save_sfmc_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sfmc', array(
        'authBaseUri'  => 'text',
        'clientId'     => 'token',
        'clientSecret' => 'token',
        'accountId'    => 'text',
    ) );
}

function adfoin_sfmc_credentials_list() {
    foreach ( adfoin_read_credentials( 'sfmc' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_sfmc_action_fields' );

function adfoin_sfmc_action_fields() {
    ?>
    <script type="text/template" id="sfmc-action-template">
        <table class="form-table" v-if="action.task == 'upsert_row'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Marketing Cloud Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getDataExtensions">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sfmc' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                </td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Data Extension', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[dataExtensionKey]" v-model="fielddata.dataExtensionKey" required="required" @change="getFields">
                        <option value=""><?php esc_html_e( 'Select Data Extension...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, key) in fielddata.dataExtensions" :value="key">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                    <p class="description" v-if="listError">{{ listError }}</p>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'upsert_row', 'Salesforce Marketing Cloud [PRO]', 'journey entry events' ); ?>
        </table>
    </script>
    <?php
}

/**
 * Resolve the tenant subdomain from the saved Authentication Base URI.
 * Accepts the full URI or the bare subdomain. Anything that is not a plain
 * hostname label is rejected, so a saved value can't point requests at
 * another host.
 *
 * @return string Subdomain, or '' when invalid.
 */
if ( ! function_exists( 'adfoin_sfmc_subdomain' ) ) :
function adfoin_sfmc_subdomain( $auth_base_uri ) {
    $value = strtolower( trim( (string) $auth_base_uri ) );

    if ( preg_match( '#^(?:https?://)?([a-z0-9-]+)\.auth\.marketingcloudapis\.com/?#', $value, $m ) ) {
        return $m[1];
    }

    return preg_match( '/^[a-z0-9-]+$/', $value ) ? $value : '';
}
endif;

/**
 * Whether a base URL returned by the token endpoint is a Marketing Cloud host.
 */
if ( ! function_exists( 'adfoin_sfmc_is_valid_instance_url' ) ) :
function adfoin_sfmc_is_valid_instance_url( $url ) {
    $host = wp_parse_url( (string) $url, PHP_URL_HOST );
    return 'https' === wp_parse_url( (string) $url, PHP_URL_SCHEME )
        && is_string( $host )
        && (bool) preg_match( '/^[a-z0-9-]+\.(rest|soap)\.marketingcloudapis\.com$/i', $host );
}
endif;

/**
 * Get a cached access token plus the tenant's REST and SOAP base URLs.
 *
 * @return array|WP_Error { token, rest, soap }
 */
if ( ! function_exists( 'adfoin_sfmc_get_token' ) ) :
function adfoin_sfmc_get_token( $cred_id, $force_refresh = false ) {
    if ( empty( $cred_id ) ) {
        return new WP_Error( 'sfmc_missing_cred', __( 'Salesforce Marketing Cloud account is not selected.', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_get_credentials_by_id( 'sfmc', $cred_id );
    $subdomain   = adfoin_sfmc_subdomain( $credentials['authBaseUri'] ?? '' );

    if ( ! $subdomain || empty( $credentials['clientId'] ) || empty( $credentials['clientSecret'] ) ) {
        return new WP_Error( 'sfmc_missing_credentials', __( 'Salesforce Marketing Cloud credentials are missing or the Authentication Base URI is invalid.', 'advanced-form-integration' ) );
    }

    $account_id = trim( (string) ( $credentials['accountId'] ?? '' ) );

    // Keyed on the credential values too, so editing an account drops the old token.
    $cache_key = 'adfoin_sfmc_token_' . md5( $cred_id . '|' . $subdomain . '|' . $credentials['clientId'] . '|' . $account_id );

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) && ! empty( $cached['token'] ) ) {
            return $cached;
        }
    }

    $body = array(
        'grant_type'    => 'client_credentials',
        'client_id'     => $credentials['clientId'],
        'client_secret' => $credentials['clientSecret'],
    );

    if ( '' !== $account_id ) {
        $body['account_id'] = $account_id;
    }

    // Not logged: the request body carries the client secret.
    $response = wp_remote_post( 'https://' . $subdomain . '.auth.marketingcloudapis.com/v2/token', array(
        'timeout' => 30,
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $body ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code || empty( $data['access_token'] ) ) {
        $message = $data['error_description'] ?? ( $data['error'] ?? sprintf( 'HTTP %d', $code ) );
        return new WP_Error( 'sfmc_auth_failed', sprintf( __( 'Marketing Cloud authentication failed: %s', 'advanced-form-integration' ), $message ) );
    }

    $rest = $data['rest_instance_url'] ?? '';
    $soap = $data['soap_instance_url'] ?? '';

    if ( ! adfoin_sfmc_is_valid_instance_url( $rest ) || ! adfoin_sfmc_is_valid_instance_url( $soap ) ) {
        return new WP_Error( 'sfmc_bad_instance', __( 'Marketing Cloud returned an unexpected API address.', 'advanced-form-integration' ) );
    }

    $token = array(
        'token' => (string) $data['access_token'],
        'rest'  => trailingslashit( $rest ),
        'soap'  => trailingslashit( $soap ),
    );

    // Tokens last ~20 minutes; refresh a minute early.
    $expires = isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 1080;
    set_transient( $cache_key, $token, max( 60, $expires - 60 ) );

    return $token;
}
endif;

/**
 * Drop the Authorization header before a request is written to the log.
 */
if ( ! function_exists( 'adfoin_sfmc_log_args' ) ) :
function adfoin_sfmc_log_args( $args ) {
    if ( isset( $args['headers']['Authorization'] ) ) {
        $args['headers']['Authorization'] = 'Bearer ***';
    }
    return $args;
}
endif;

/**
 * REST request against the tenant's REST base URL. Retries once with a fresh
 * token on 401.
 *
 * @param string $endpoint Path relative to the REST base, e.g. 'hub/v1/...'.
 *
 * @return array|WP_Error
 */
if ( ! function_exists( 'adfoin_sfmc_request' ) ) :
function adfoin_sfmc_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $token = adfoin_sfmc_get_token( $cred_id );

    if ( is_wp_error( $token ) ) {
        if ( $record ) {
            adfoin_add_to_log( $token, '', array(), $record );
        }
        return $token;
    }

    $method = strtoupper( $method );

    for ( $attempt = 0; $attempt < 2; $attempt++ ) {
        $url  = $token['rest'] . ltrim( $endpoint, '/' );
        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token['token'],
                'Content-Type'  => 'application/json',
            ),
        );

        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( 0 === $attempt && ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $fresh = adfoin_sfmc_get_token( $cred_id, true );
            if ( ! is_wp_error( $fresh ) ) {
                $token = $fresh;
                continue;
            }
        }

        break;
    }

    if ( $record ) {
        adfoin_add_to_log( $response, $url, adfoin_sfmc_log_args( $args ), $record );
    }

    return $response;
}
endif;

/**
 * SOAP Retrieve, following MoreDataAvailable pages.
 *
 * @param string $object_type e.g. 'DataExtension'.
 * @param array  $properties  Properties to retrieve.
 * @param array  $filter      Optional array( property, operator, value ).
 * @param int    $max_pages   Page cap (2,500 rows per page).
 *
 * @return array|WP_Error List of associative arrays (property => value).
 */
if ( ! function_exists( 'adfoin_sfmc_soap_retrieve' ) ) :
function adfoin_sfmc_soap_retrieve( $cred_id, $object_type, $properties, $filter = array(), $max_pages = 10 ) {
    $token = adfoin_sfmc_get_token( $cred_id );

    if ( is_wp_error( $token ) ) {
        return $token;
    }

    $x       = function ( $value ) { return htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' ); };
    $rows    = array();
    $request = '';

    for ( $page = 0; $page < $max_pages; $page++ ) {
        if ( '' === $request ) {
            $inner = '<ObjectType>' . $x( $object_type ) . '</ObjectType>';
            foreach ( $properties as $property ) {
                $inner .= '<Properties>' . $x( $property ) . '</Properties>';
            }
            if ( $filter ) {
                $inner .= '<Filter xsi:type="SimpleFilterPart"><Property>' . $x( $filter[0] ) . '</Property><SimpleOperator>' . $x( $filter[1] ) . '</SimpleOperator><Value>' . $x( $filter[2] ) . '</Value></Filter>';
            }
        } else {
            $inner = '<ContinueRequest>' . $x( $request ) . '</ContinueRequest>';
        }

        $envelope = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<soapenv:Header><fueloauth xmlns="http://exacttarget.com">' . $x( $token['token'] ) . '</fueloauth></soapenv:Header>'
            . '<soapenv:Body><RetrieveRequestMsg xmlns="http://exacttarget.com/wsdl/partnerAPI"><RetrieveRequest>' . $inner . '</RetrieveRequest></RetrieveRequestMsg></soapenv:Body>'
            . '</soapenv:Envelope>';

        $response = wp_remote_post( $token['soap'] . 'Service.asmx', array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'text/xml; charset=UTF-8', 'SOAPAction' => 'Retrieve' ),
            'body'    => $envelope,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $dom = new DOMDocument();
        if ( ! @$dom->loadXML( (string) wp_remote_retrieve_body( $response ), LIBXML_NONET ) ) {
            return new WP_Error( 'sfmc_soap_parse', sprintf( 'HTTP %d', (int) wp_remote_retrieve_response_code( $response ) ) );
        }

        $status_node = $dom->getElementsByTagNameNS( '*', 'OverallStatus' )->item( 0 );
        $status      = $status_node ? trim( $status_node->textContent ) : '';

        if ( 'OK' !== $status && 'MoreDataAvailable' !== $status ) {
            $fault = $dom->getElementsByTagNameNS( '*', 'faultstring' )->item( 0 );
            return new WP_Error( 'sfmc_soap_error', $fault ? trim( $fault->textContent ) : ( $status ? $status : __( 'Unknown SOAP error', 'advanced-form-integration' ) ) );
        }

        foreach ( $dom->getElementsByTagNameNS( '*', 'Results' ) as $result ) {
            $row = array();
            foreach ( $result->childNodes as $child ) {
                if ( XML_ELEMENT_NODE === $child->nodeType && in_array( $child->localName, $properties, true ) ) {
                    $row[ $child->localName ] = trim( $child->textContent );
                }
            }
            $rows[] = $row;
        }

        if ( 'MoreDataAvailable' !== $status ) {
            break;
        }

        $request_node = $dom->getElementsByTagNameNS( '*', 'RequestID' )->item( 0 );
        $request      = $request_node ? trim( $request_node->textContent ) : '';

        if ( '' === $request ) {
            break;
        }
    }

    return $rows;
}
endif;

/**
 * Field list for a Data Extension, in mapping-UI shape.
 *
 * @return array|WP_Error
 */
if ( ! function_exists( 'adfoin_sfmc_get_de_fields' ) ) :
function adfoin_sfmc_get_de_fields( $cred_id, $de_key ) {
    $rows = adfoin_sfmc_soap_retrieve(
        $cred_id,
        'DataExtensionField',
        array( 'Name', 'FieldType', 'IsPrimaryKey', 'IsRequired', 'Ordinal', 'MaxLength' ),
        array( 'DataExtension.CustomerKey', 'equals', $de_key ),
        2
    );

    if ( is_wp_error( $rows ) ) {
        return $rows;
    }

    usort( $rows, function ( $a, $b ) {
        return (int) ( $a['Ordinal'] ?? 0 ) - (int) ( $b['Ordinal'] ?? 0 );
    } );

    $fields = array();

    foreach ( $rows as $row ) {
        if ( empty( $row['Name'] ) ) {
            continue;
        }

        $is_pk       = 'true' === strtolower( $row['IsPrimaryKey'] ?? '' );
        $is_required = $is_pk || 'true' === strtolower( $row['IsRequired'] ?? '' );
        $type        = $row['FieldType'] ?? '';
        $description = $type;

        if ( ! empty( $row['MaxLength'] ) && in_array( $type, array( 'Text', 'EmailAddress', 'Phone', 'Locale' ), true ) ) {
            $description .= ' (' . (int) $row['MaxLength'] . ')';
        }
        if ( $is_pk ) {
            $description .= ' · ' . __( 'Primary key', 'advanced-form-integration' );
        }
        if ( 'Date' === $type ) {
            $description .= ' · ' . __( 'e.g. 2026-11-25 or 2026-11-25T14:30:00', 'advanced-form-integration' );
        }
        if ( 'Boolean' === $type ) {
            $description .= ' · true / false';
        }

        $fields[] = array(
            'key'         => ( $is_pk ? 'pk__' : 'de__' ) . $row['Name'],
            'value'       => $row['Name'],
            'required'    => $is_required,
            'description' => $description,
        );
    }

    return $fields;
}
endif;

add_action( 'wp_ajax_adfoin_get_sfmc_data_extensions', 'adfoin_get_sfmc_data_extensions', 10, 0 );

function adfoin_get_sfmc_data_extensions() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $rows    = adfoin_sfmc_soap_retrieve( $cred_id, 'DataExtension', array( 'CustomerKey', 'Name' ) );

    if ( is_wp_error( $rows ) ) {
        wp_send_json_error( array( 'message' => $rows->get_error_message() ) );
    }

    $list = array();
    foreach ( $rows as $row ) {
        if ( ! empty( $row['CustomerKey'] ) ) {
            $list[ $row['CustomerKey'] ] = ! empty( $row['Name'] ) ? $row['Name'] : $row['CustomerKey'];
        }
    }

    natcasesort( $list );

    wp_send_json_success( $list );
}

add_action( 'wp_ajax_adfoin_get_sfmc_fields', 'adfoin_get_sfmc_fields', 10, 0 );

function adfoin_get_sfmc_fields() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $de_key  = isset( $_POST['dataExtensionKey'] ) ? sanitize_text_field( wp_unslash( $_POST['dataExtensionKey'] ) ) : '';

    if ( '' === $de_key ) {
        wp_send_json_error( array( 'message' => __( 'Select a Data Extension.', 'advanced-form-integration' ) ) );
    }

    $fields = adfoin_sfmc_get_de_fields( $cred_id, $de_key );

    if ( is_wp_error( $fields ) ) {
        wp_send_json_error( array( 'message' => $fields->get_error_message() ) );
    }

    wp_send_json_success( $fields );
}

/**
 * Split parsed `pk__` / `de__` values into primary keys and other columns.
 *
 * @return array array( keys, values )
 */
if ( ! function_exists( 'adfoin_sfmc_split_row' ) ) :
function adfoin_sfmc_split_row( $field_data, $posted_data ) {
    $keys   = array();
    $values = array();

    foreach ( $field_data as $field_key => $template ) {
        $is_pk = 0 === strpos( $field_key, 'pk__' );

        if ( ! $is_pk && 0 !== strpos( $field_key, 'de__' ) ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $template, $posted_data );

        if ( '' === $parsed || null === $parsed ) {
            continue;
        }

        $name = substr( $field_key, 4 );

        if ( $is_pk ) {
            $keys[ $name ] = $parsed;
        } else {
            $values[ $name ] = $parsed;
        }
    }

    return array( $keys, $values );
}
endif;

/**
 * Whether the saved mapping belongs to a Data Extension with a primary key.
 */
if ( ! function_exists( 'adfoin_sfmc_has_pk_mapping' ) ) :
function adfoin_sfmc_has_pk_mapping( $field_data ) {
    foreach ( array_keys( (array) $field_data ) as $field_key ) {
        if ( 0 === strpos( (string) $field_key, 'pk__' ) ) {
            return true;
        }
    }
    return false;
}
endif;

/**
 * Write one row to a Data Extension: upsert when it has a primary key,
 * insert otherwise.
 *
 * @return array|WP_Error|null Null when nothing was sent.
 */
if ( ! function_exists( 'adfoin_sfmc_write_row' ) ) :
function adfoin_sfmc_write_row( $de_key, $keys, $values, $record, $cred_id ) {
    if ( '' === $de_key || ( ! $keys && ! $values ) ) {
        return null;
    }

    $encoded = rawurlencode( $de_key );

    if ( $keys ) {
        $row = array( 'keys' => $keys );
        if ( $values ) {
            $row['values'] = $values;
        }
        return adfoin_sfmc_request( 'hub/v1/dataevents/key:' . $encoded . '/rowset', 'POST', array( $row ), $record, $cred_id );
    }

    return adfoin_sfmc_request( 'data/v1/async/dataextensions/key:' . $encoded . '/rows', 'POST', array( 'items' => array( $values ) ), $record, $cred_id );
}
endif;

add_action( 'adfoin_sfmc_job_queue', 'adfoin_sfmc_job_queue', 10, 1 );

function adfoin_sfmc_job_queue( $data ) {
    adfoin_sfmc_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sfmc_send_data( $record, $posted_data ) {
    if ( 'upsert_row' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $de_key     = $field_data['dataExtensionKey'] ?? '';

    if ( ! $cred_id || ! $de_key ) {
        return;
    }

    list( $keys, $values ) = adfoin_sfmc_split_row( $field_data, $posted_data );

    if ( ! $keys && adfoin_sfmc_has_pk_mapping( $field_data ) ) {
        adfoin_log_skipped_action( $record, 'Salesforce Marketing Cloud', __( 'Row not sent: the Data Extension\'s primary key field had no value.', 'advanced-form-integration' ), array( 'dataExtensionKey' => $de_key ) );
        return;
    }

    adfoin_sfmc_write_row( $de_key, $keys, $values, $record, $cred_id );
}
