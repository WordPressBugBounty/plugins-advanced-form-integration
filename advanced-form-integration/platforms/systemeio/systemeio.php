<?php
add_filter( 'adfoin_action_providers', 'adfoin_systemeio_actions', 10, 1 );

function adfoin_systemeio_actions( $actions ) {
    $actions['systemeio'] = array(
        'title' => __( 'Systeme.io', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_systemeio_settings_tab', 10, 1 );

function adfoin_systemeio_settings_tab( $providers ) {
    $providers['systemeio'] = __( 'Systeme.io', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_systemeio_settings_view', 10, 1 );

function adfoin_systemeio_settings_view( $current_tab ) {
    if( $current_tab != 'systemeio' ) {
        return;
    }

    $title = __( 'Systeme.io', 'advanced-form-integration' );
    $key = 'systemeio';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'apiKey',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Go to Profile> Settings > Public API keys.</li>
                    <li>Create and copy the token</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_systemeio_credentials', 'adfoin_get_systemeio_credentials', 10, 0 );

function adfoin_get_systemeio_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'systemeio' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_systemeio_credentials', 'adfoin_save_systemeio_credentials', 10, 0 );

function adfoin_save_systemeio_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'systemeio' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_systemeio_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'systemeio' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_systemeio_action_fields' );

function adfoin_systemeio_action_fields() {
    ?>
    <script type="text/template" id="systemeio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Systeme.io Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_systemeio_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_systemeio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'systemeio', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    $base_url = "https://api.systeme.io/api/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'X-API-Key'     => $api_key
        ),
    );

    if ( 'POST' == $method || 'PATCH' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode($data);
        $args['headers']['Content-Type'] = 'application/merge-patch+json';
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_systemeio_fields', 'adfoin_systemeio_get_fields', 10, 0);

function adfoin_systemeio_get_fields() {

    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );
    $fields = array();
    $tags = adfoin_systemeio_get_tags($cred_id);

    if($tags) {
        $fields[] = ['key' => 'tag', 'value' => 'Tag ID', 'description' => $tags ];
    }

    $response = adfoin_systemeio_request( 'contact_fields', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if (isset($body['items']) && is_array( $body['items'])) {
        foreach ($body['items'] as $field) {
            $fields[] = ['key' => $field['slug'], 'value' => $field['fieldName']];
        }

        wp_send_json_success( $fields );
    } else {
        wp_send_json_error();
    }
}

function adfoin_systemeio_get_tags( $cred_id) {
    $tags = array();
    $response = adfoin_systemeio_request( 'tags', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        return $tags;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if (isset($body['items']) && is_array($body['items'])) {
        foreach ($body['items'] as $tag) {
            $tags[] = $tag['name'] . ': ' . $tag['id'];
        }
    }

    $tags = implode(', ', $tags);

    return $tags;
}

add_action( 'adfoin_systemeio_job_queue', 'adfoin_systemeio_job_queue', 10, 1 );

function adfoin_systemeio_job_queue( $data ) {
    adfoin_systemeio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_systemeio_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];
    $tag_id = isset( $data['tag'] ) ? adfoin_get_parsed_values($data['tag'], $posted_data) : '';

    unset( $data['groupId'], $data['credId'], $data['tag'] );

    if ( $task == 'subscribe' ) {
        $subscriber_data = array();

        foreach ( $data as $key => $value ) {
            $value = adfoin_get_parsed_values( $value, $posted_data );

            if( $value ) {
                $subscriber_data[ $key ] = $value;
            }
        }

        $email = isset($subscriber_data['email']) ? $subscriber_data['email'] : '';

        if ($email) {
            $contact_id = adfoin_systemeio_find_contact($email, $cred_id);

            $subscriber_data_formatted = array(
                'fields' => array()
            );

            foreach ($subscriber_data as $key => $value) {
                if ($key === 'country') {
                    $country_code = adfoin_systemeio_get_country_code($value);
                    if ($country_code) {
                        $subscriber_data_formatted['fields'][] = array(
                            'slug' => $key,
                            'value' => $country_code
                        );
                    }
                } elseif ($key !== 'email') {
                    $subscriber_data_formatted['fields'][] = array(
                        'slug' => $key,
                        'value' => $value
                    );
                }
            }

            if ($contact_id) {
                $response = adfoin_systemeio_request('contacts/' . $contact_id, 'PATCH', $subscriber_data_formatted, $record, $cred_id);
            } else {
                $subscriber_data_formatted['email'] = $email;
                $response = adfoin_systemeio_request('contacts', 'POST', $subscriber_data_formatted, $record, $cred_id);
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
                $contact_id = isset($response_body['id']) ? $response_body['id'] : '';
            }

            // Add tag
            if ($tag_id && $contact_id) {
                $response = adfoin_systemeio_request('contacts/' . $contact_id . '/tags', 'POST', array('tagId' => intval($tag_id)), $record, $cred_id);
            }
        }

    }
}

function adfoin_systemeio_find_contact($email, $cred_id) {
    $endpoint = 'contacts?email=' . urlencode($email);
    $response = adfoin_systemeio_request($endpoint, 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_array($body) && isset($body['items'][0]['id'])) {
        return $body['items'][0]['id'];
    }

    return false;
}

function adfoin_systemeio_get_country_code($country_name) {
    $countries = array(
        'AF' => 'Afghanistan',
        'AX' => 'Aland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CD' => 'Congo, Democratic Republic',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote D\'Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island & Mcdonald Islands',
        'VA' => 'Holy See (Vatican City State)',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran, Islamic Republic Of',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle Of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KR' => 'Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Lao People\'s Democratic Republic',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libyan Arab Jamahiriya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia, Federated States Of',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestinian Territory, Occupied',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And Sandwich Isl.',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela',
        'VN' => 'Viet Nam',
        'VG' => 'Virgin Islands, British',
        'VI' => 'Virgin Islands, U.S.',
        'WF' => 'Wallis And Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
        // Add more as needed
    );
    $country_name = strtolower($country_name);
    foreach ($countries as $code => $name) {
        if (strtolower($name) === $country_name) {
            return $code;
        }
    }
    return null; // Return null if not found
}