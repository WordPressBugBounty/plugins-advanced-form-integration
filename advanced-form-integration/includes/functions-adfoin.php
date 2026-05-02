<?php

/**
 * Assert that the current user can manage plugin settings.
 * Terminates the request with a 403 response if the user lacks the 'manage_options' capability.
 *
 * This function should be called at the beginning of AJAX handlers that modify plugin settings
 * or store sensitive data such as API credentials, to prevent unauthorized access.
 *
 * @since 1.126.13
 * @return void Terminates execution with wp_send_json_error() if authorization fails.
 */
function adfoin_require_manage_options() {
    if ( !current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ),
        ), 403 );
    }
}

/**
* Redirects the user to a given URL.
* @param string $url The URL to redirect the user to.
* @return void
*/
function advanced_form_integration_redirect(  $url  ) {
    $string = '<script type="text/javascript">';
    $string .= 'window.location = "' . $url . '"';
    $string .= '</script>';
    echo $string;
}

/**
* Retrieves an array of supported form providers for the plugin.
* @return array An array containing the names and display labels of supported form providers.
*/
function adfoin_get_form_providers() {
    $providers = array(
        'academylms'                 => 'Academy LMS',
        'advancedcoupons'            => 'Advanced Coupons for WooCommerce',
        'affiliatewp'                => 'AffiliateWP',
        'amelia'                     => 'Amelia',
        'anspress'                   => 'AnsPress',
        'appointmenthourbooking'     => 'Appointment Hour Booking',
        'arforms'                    => 'ARForms',
        'armember'                   => 'ARMember',
        'asgarosforum'               => 'Asgaros Forum',
        'avadaforms'                 => 'Avada Forms',
        'awesomesupport'             => 'Awesome Support',
        'bbpress'                    => 'bbPress',
        'beaver'                     => 'Beaver Form',
        'bitform'                    => 'BitForm',
        'bookly'                     => 'Bookly',
        'breakdance'                 => 'Breakdance Form',
        'bricks'                     => 'Bricks Form',
        'buddyboss'                  => 'BuddyBoss',
        'buddypress'                 => 'BuddyPress',
        'calderaforms'               => 'Caldera Forms',
        'cartflows'                  => 'CartFlows',
        'charitable'                 => 'Charitable',
        'cf7'                        => 'Contact Form 7',
        'coolformkit'                => 'Cool FormKit',
        'convertpro'                 => 'ConvertPro Forms',
        'crowdsignalforms'           => 'Crowdsignal Forms',
        'customerreviewswoocommerce' => 'Customer Reviews for WooCommerce',
        'digimember'                 => 'DigiMember',
        'diviform'                   => 'Divi Form',
        'dokan'                      => 'Dokan',
        'easyaffiliate'              => 'Easy Affiliate',
        'easyappointments'           => 'Easy Appointments',
        'edd'                        => 'Easy Digital Downloads',
        'eform'                      => 'eForm',
        'elementorpro'               => 'Elementor Pro Form',
        'eventespressodecaf'         => 'Event Espresso Decaf',
        'eventin'                    => 'Eventin',
        'eventtickets'               => 'Event Tickets',
        'eventsmanager'              => 'Events Manager',
        'everestforms'               => 'Everest Forms',
        'fooevents'                  => 'FooEvents',
        'fluentaffiliate'            => 'Fluent Affiliate',
        'fluentboards'               => 'Fluent Boards',
        'fluentbooking'              => 'Fluent Booking',
        'fluentcart'                 => 'FluentCart',
        'fluentcommunity'            => 'Fluent Community',
        'fluentforms'                => 'Fluent Forms',
        'fluentsmtp'                 => 'FluentSMTP',
        'fluentsecurity'             => 'FluentAuth',
        'formcraft'                  => 'FormCraft 3',
        'formcraftb'                 => 'FormCraft Basic',
        'formidable'                 => 'Formidable Forms',
        'forminator'                 => 'Forminator (forms only)',
        'formmaker'                  => 'Form Maker by 10Web',
        'fundraisingforwoocommerce'  => 'Fundraising for WooCommerce',
        'gamipress'                  => 'GamiPress',
        'givewp'                     => 'GiveWP',
        'gravityforms'               => 'Gravity Forms',
        'groundhogg'                 => 'Groundhogg',
        'gutenverseform'             => 'Gutenverse Form',
        'gutenaforms'                => 'Gutena Forms',
        'hashform'                   => 'Hash Form',
        'happyforms'                 => 'Happy Forms',
        'htmlforms'                  => 'HTML Forms',
        'htcontactform'              => 'HT Contact Form',
        'jetformbuilder'             => 'JetFormBuilder',
        'jetpackcrm'                 => 'Jetpack CRM',
        'kadence'                    => 'Kadence Blocks Form',
        'kaliforms'                  => 'Kali Forms',
        'latepoint'                  => 'LatePoint',
        'learndash'                  => 'LearnDash',
        'learnpress'                 => 'LearnPress',
        'lifterlms'                  => 'LifterLMS',
        'liveforms'                  => 'Live Forms',
        'mailpoet'                   => 'MailPoet',
        'masterstudy'                => 'MasterStudy LMS',
        'memberpress'                => 'MemberPress',
        'metform'                    => 'Metform',
        'mptimetable'                => 'Timetable and Event Schedule',
        'mwwpform'                   => 'MW WP Form',
        'mycred'                     => 'myCred',
        'mystickyelements'           => 'My Sticky Elements',
        'nexforms'                   => 'NEX-Forms',
        'ninjaforms'                 => 'Ninja Forms',
        'ninjatables'                => 'Ninja Tables',
        'newsletter'                 => 'Newsletter',
        'paidmembershippro'          => 'Paid Memberships Pro',
        'peepso'                     => 'PeepSo',
        'qsm'                        => 'Quiz And Survey Master (QSM)',
        'quform'                     => 'Quform',
        'quillforms'                 => 'QuillForms',
        'rafflepress'                => 'RafflePress',
        'registrationmagic'          => 'RegistrationMagic',
        'restriccontentpro'          => 'Restrict Content Pro',
        'romethemeform'              => 'RomeTheme Form',
        'senseilms'                  => 'Sensei LMS',
        'siteorigincontact'          => 'SiteOrigin Contact Form',
        'sitereviews'                => 'Site Reviews',
        'simplebasiccontactform'     => 'Simple Basic Contact Form',
        'simplyscheduleappointments' => 'Simply Schedule Appointments',
        'slicewp'                    => 'SliceWP',
        'smartforms'                 => 'Smart Forms',
        'snowmonkeyforms'            => 'Snow Monkey Forms',
        'spectraproforms'            => 'Spectra Pro Forms',
        'surecart'                   => 'SureCart',
        'sureforms'                  => 'SureForms',
        'suremembers'                => 'SureMembers',
        'tawkto'                     => 'Tawk.to Live Chat',
        'theeventscalendar'          => 'The Events Calendar',
        'thivequizbuilder'           => 'Thrive Quiz Builder',
        'thriveapprentice'           => 'Thrive Apprentice',
        'thriveleads'                => 'Thrive Leads',
        'tutorlms'                   => 'Tutor LMS',
        'ultimatemember'             => 'Ultimate Member',
        'userregistration'           => 'User Registration',
        'verysimplecontactform'      => 'VS Contact Form',
        'weforms'                    => 'weForms',
        'webbabookinglite'           => 'Webba Booking Lite',
        'woocommerce'                => 'WooCommerce',
        'woocommerceanalytics'       => 'WooCommerce Analytics',
        'woocommercebookings'        => 'WooCommerce Bookings',
        'woocommercememberships'     => 'WooCommerce Memberships',
        'woocommercesubscriptions'   => 'WooCommerce Subscriptions',
        'wpbookingcalendar'          => 'WP Booking Calendar',
        'wpforms'                    => 'WPForms',
        'wpforo'                     => 'wpForo',
        'wpmembers'                  => 'WP-Members',
        'wppizza'                    => 'WP Pizza',
        'wppostratings'              => 'WP Post Ratings',
        'wpsimplepay'                => 'WP Simple Pay',
        'wpulike'                    => 'WP ULike',
        'wsform'                     => 'WS Form',
        'wptravelengine'             => 'WP Travel Engine',
        'wpzoomforms'                => 'WPZOOM Forms',
    );
    return apply_filters( 'adfoin_form_providers', $providers );
}

/**
* Generates the HTML options for the form integration providers dropdown.
*
* @return string The HTML options for the form integration providers dropdown.
*/
function adfoin_get_form_providers_html() {
    $form_providers = adfoin_get_form_providers();
    $providers_html = '';
    foreach ( $form_providers as $key => $provider ) {
        $providers_html .= '<option value="' . $key . '">' . $provider . '</option>';
    }
    return $providers_html;
}

function adfoin_get_trigger_keys() {
    $form_providers = adfoin_get_form_providers();
    $provider_keys = array_keys( $form_providers );
    return $provider_keys;
}

/**
* Retrieves the available form integration action providers.
*
* @return array The available form integration action providers.
*/
function adfoin_get_actions() {
    $actions = array();
    return apply_filters( 'adfoin_action_providers', $actions );
}

/**
* Retrieves the available form integration action providers as an associative array with provider key as key and provider title as value.
*
* @return array The available form integration action providers as an associative array with provider key as key and provider title as value.
*/
function adfoin_get_action_porviders() {
    $actions = adfoin_get_actions();
    $providers = array();
    foreach ( $actions as $key => $value ) {
        $providers[$key] = $value['title'];
    }
    return $providers;
}

/**
 * Returns form providers as an alphabetically sorted (by visible label,
 * case-insensitive, locale aware) list of { value, label } items suitable
 * for the <afi-searchable-select> Vue component.
 *
 * @since 1.128.1
 * @return array
 */
function adfoin_get_form_providers_array() {
    $providers = adfoin_get_form_providers();
    if ( !is_array( $providers ) ) {
        return array();
    }
    natcasesort( $providers );
    $list = array();
    foreach ( $providers as $key => $label ) {
        $list[] = array(
            'value' => (string) $key,
            'label' => (string) $label,
        );
    }
    return $list;
}

/**
 * Returns action providers as an alphabetically sorted (by visible label,
 * case-insensitive, locale aware) list of { value, label } items suitable
 * for the <afi-searchable-select> Vue component.
 *
 * @since 1.128.1
 * @return array
 */
function adfoin_get_action_providers_array() {
    $providers = adfoin_get_action_porviders();
    if ( !is_array( $providers ) ) {
        return array();
    }
    natcasesort( $providers );
    $list = array();
    foreach ( $providers as $key => $label ) {
        $list[] = array(
            'value' => (string) $key,
            'label' => (string) $label,
        );
    }
    return $list;
}

/**
 * Compute health stats for a single integration over the last $days days.
 *
 * Returns:
 *   total          int    submissions counted in window
 *   success        int    2xx responses in window
 *   failure        int    non-2xx responses in window
 *   success_rate   int|null  percentage 0–100 (null if total==0)
 *   series         int[]  per-day counts oldest→newest, length = $days
 *   last_run_time  string|null  MySQL datetime of most recent log
 *   last_run_ok    bool   true if most recent log was 2xx
 *   last_log_id    int|null  id of most recent log row (for Resend)
 *   last_request   string|null  raw request_data JSON for Resend
 *
 * @since 1.128.1
 * @param int $integration_id
 * @param int $days  Window length in days (default 30).
 * @return array|null
 */
function adfoin_get_integration_stats(  $integration_id, $days = 30  ) {
    global $wpdb;
    $integration_id = (int) $integration_id;
    $days = max( 1, (int) $days );
    if ( $integration_id <= 0 ) {
        return null;
    }
    $log_table = $wpdb->prefix . 'adfoin_log';
    // Total + success counts in window.
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS total,\n                    SUM( CASE WHEN SUBSTRING( response_code, 1, 1 ) = '2' THEN 1 ELSE 0 END ) AS success\n             FROM {$log_table}\n             WHERE integration_id = %d\n               AND time >= DATE_SUB( NOW(), INTERVAL %d DAY )", $integration_id, $days ), ARRAY_A );
    $total = ( $row ? (int) $row['total'] : 0 );
    $success = ( $row ? (int) $row['success'] : 0 );
    $failure = max( 0, $total - $success );
    $success_rate = ( $total > 0 ? (int) round( $success / $total * 100 ) : null );
    // Per-day counts.
    $daily = $wpdb->get_results( $wpdb->prepare( "SELECT DATE( time ) AS day, COUNT(*) AS cnt\n             FROM {$log_table}\n             WHERE integration_id = %d\n               AND time >= DATE_SUB( NOW(), INTERVAL %d DAY )\n             GROUP BY DATE( time )\n             ORDER BY DATE( time ) ASC", $integration_id, $days ), ARRAY_A );
    $by_day = array();
    if ( is_array( $daily ) ) {
        foreach ( $daily as $d ) {
            $by_day[$d['day']] = (int) $d['cnt'];
        }
    }
    $series = array();
    for ($i = $days - 1; $i >= 0; $i--) {
        $key = date( 'Y-m-d', strtotime( "-{$i} days", current_time( 'timestamp' ) ) );
        $series[] = ( isset( $by_day[$key] ) ? $by_day[$key] : 0 );
    }
    // Last run + raw request data (for Resend).
    $last = $wpdb->get_row( $wpdb->prepare( "SELECT id, response_code, time, request_data\n             FROM {$log_table}\n             WHERE integration_id = %d\n             ORDER BY id DESC\n             LIMIT 1", $integration_id ), ARRAY_A );
    $last_run_time = ( $last ? $last['time'] : null );
    $last_run_ok = $last && !empty( $last['response_code'] ) && '2' === substr( (string) $last['response_code'], 0, 1 );
    $last_log_id = ( $last ? (int) $last['id'] : null );
    $last_request = ( $last ? $last['request_data'] : null );
    return array(
        'total'         => $total,
        'success'       => $success,
        'failure'       => $failure,
        'success_rate'  => $success_rate,
        'series'        => $series,
        'last_run_time' => $last_run_time,
        'last_run_ok'   => $last_run_ok,
        'last_log_id'   => $last_log_id,
        'last_request'  => $last_request,
        'window_days'   => $days,
    );
}

/**
 * Return integration IDs that recorded at least one non-2xx response
 * in the last N days. Used by the "Failing" tab on the integrations
 * list table so the user can jump straight to the integrations that
 * need attention.
 *
 * Definition of "failing" here is intentionally broad: any non-2xx
 * (or empty) response within the window. We don't restrict to "last
 * run failed" because a single transient blip would mask earlier
 * failures the user still wants to investigate.
 *
 * @since 1.128.3
 * @param int $days Window size, default 7. Clamped to >= 1.
 * @return int[]    Distinct integration IDs.
 */
function adfoin_get_failing_integration_ids(  $days = 7  ) {
    global $wpdb;
    $days = max( 1, (int) $days );
    $log_table = $wpdb->prefix . 'adfoin_log';
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT integration_id\n             FROM {$log_table}\n             WHERE time >= DATE_SUB( NOW(), INTERVAL %d DAY )\n               AND ( response_code IS NULL\n                     OR response_code = ''\n                     OR SUBSTRING( response_code, 1, 1 ) != '2' )", $days ) );
    if ( empty( $ids ) ) {
        return array();
    }
    return array_values( array_filter( array_map( 'intval', $ids ) ) );
}

/**
 * Bulk version of the health portion of adfoin_get_integration_stats.
 * Used by the integrations list table to render the per-row Health
 * column without firing N+1 queries — one aggregate query for the
 * window totals and one for each integration's most recent log row.
 *
 * Returned shape (keyed by integration_id):
 *   array(
 *     12 => array(
 *       'total'         => 7,
 *       'success'       => 6,
 *       'failure'       => 1,
 *       'success_rate'  => 86,        // null when total=0
 *       'last_run_time' => '2026-04-29 04:01:33',  // null when never run
 *       'last_run_ok'   => true,      // null when never run
 *       'window_days'   => 7,
 *     ),
 *     ...
 *   )
 *
 * Integrations with no log activity in the window appear in the
 * result with zero totals + null last_run_*; callers can render the
 * "Never run" state without separate lookups.
 *
 * @since 1.128.3
 * @param int[] $ids  Integration IDs.
 * @param int   $days Window size for totals/success rate (default 7).
 * @return array<int, array>
 */
function adfoin_get_integration_health_bulk(  $ids, $days = 7  ) {
    global $wpdb;
    $ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
    $days = max( 1, (int) $days );
    // Pre-seed every requested ID so callers can rely on isset()
    // without checking. Empty rows render as "Never run" downstream.
    $out = array();
    foreach ( $ids as $id ) {
        $out[$id] = array(
            'total'         => 0,
            'success'       => 0,
            'failure'       => 0,
            'success_rate'  => null,
            'last_run_time' => null,
            'last_run_ok'   => null,
            'window_days'   => $days,
        );
    }
    if ( empty( $ids ) ) {
        return $out;
    }
    $log_table = $wpdb->prefix . 'adfoin_log';
    $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
    // ---- Window totals + successes per integration ----
    $totals_sql = $wpdb->prepare( "SELECT integration_id,\n                COUNT(*) AS total,\n                SUM( CASE WHEN SUBSTRING( response_code, 1, 1 ) = '2' THEN 1 ELSE 0 END ) AS success\n         FROM {$log_table}\n         WHERE integration_id IN ({$placeholders})\n           AND time >= DATE_SUB( NOW(), INTERVAL %d DAY )\n         GROUP BY integration_id", array_merge( $ids, array($days) ) );
    $totals = $wpdb->get_results( $totals_sql, ARRAY_A );
    if ( is_array( $totals ) ) {
        foreach ( $totals as $row ) {
            $iid = (int) $row['integration_id'];
            if ( !isset( $out[$iid] ) ) {
                continue;
            }
            $total = (int) $row['total'];
            $success = (int) $row['success'];
            $out[$iid]['total'] = $total;
            $out[$iid]['success'] = $success;
            $out[$iid]['failure'] = max( 0, $total - $success );
            $out[$iid]['success_rate'] = ( $total > 0 ? (int) round( $success / $total * 100 ) : null );
        }
    }
    // ---- Most recent log row per integration ----
    // Latest log id per integration via correlated subquery; works on
    // older MySQLs that don't support window functions.
    $last_sql = $wpdb->prepare( "SELECT l.integration_id, l.response_code, l.time\n         FROM {$log_table} l\n         INNER JOIN (\n             SELECT integration_id, MAX(id) AS max_id\n             FROM {$log_table}\n             WHERE integration_id IN ({$placeholders})\n             GROUP BY integration_id\n         ) m ON m.max_id = l.id", $ids );
    $last_rows = $wpdb->get_results( $last_sql, ARRAY_A );
    if ( is_array( $last_rows ) ) {
        foreach ( $last_rows as $row ) {
            $iid = (int) $row['integration_id'];
            if ( !isset( $out[$iid] ) ) {
                continue;
            }
            $out[$iid]['last_run_time'] = $row['time'];
            $out[$iid]['last_run_ok'] = !empty( $row['response_code'] ) && '2' === substr( (string) $row['response_code'], 0, 1 );
        }
    }
    return $out;
}

/**
 * Render an inline SVG sparkline for a numeric series.
 * Uses currentColor so the host can theme it via CSS.
 *
 * @since 1.128.1
 * @param int[] $series
 * @param int   $width
 * @param int   $height
 * @return string  HTML
 */
function adfoin_render_sparkline(  $series, $width = 120, $height = 28  ) {
    if ( empty( $series ) || !is_array( $series ) ) {
        return '';
    }
    $count = count( $series );
    $max = max( $series );
    $width = max( 1, (int) $width );
    $height = max( 8, (int) $height );
    $points = array();
    if ( $max <= 0 ) {
        // All zero — flat baseline.
        $y = $height - 2;
        $points[] = '0,' . $y;
        $points[] = $width . ',' . $y;
    } else {
        for ($i = 0; $i < $count; $i++) {
            $x = ( $count > 1 ? round( $i / ($count - 1) * $width, 2 ) : 0 );
            $y = $height - round( $series[$i] / $max * ($height - 4) ) - 2;
            $points[] = $x . ',' . $y;
        }
    }
    return sprintf(
        '<svg class="afi-sparkline" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><polyline fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" points="%3$s" /></svg>',
        $width,
        $height,
        esc_attr( implode( ' ', $points ) )
    );
}

/**
* Retrieves the available form integration action tasks for the specified provider.
*
* @param string $provider The provider key for which the action tasks should be retrieved.
*
* @return array The available form integration action tasks for the specified provider.
*/
function adfoin_get_action_tasks(  $provider = ''  ) {
    $actions = adfoin_get_actions();
    $tasks = array();
    if ( $provider ) {
        foreach ( $actions as $key => $value ) {
            if ( $provider == $key ) {
                $tasks = $value['tasks'];
            }
        }
    }
    return $tasks;
}

/**
* Returns an array of available settings tabs for the Advanced Form Integration plugin.
*
* @return array An array of available settings tabs.
*/
function adfoin_get_settings_tabs() {
    $tabs = array(
        'general' => __( 'General', 'advanced-form-integration' ),
    );
    return apply_filters( 'adfoin_settings_tabs', $tabs );
}

/**
* Returns an array of supported integrations and their titles.
* @return array An array of integrations and their titles.
*/
function adfoin_get_action_platform_list() {
    return array(
        'academylms'             => array(
            'title' => __( 'Academy LMS', 'advanced-form-integration' ),
            'basic' => 'academylms',
        ),
        'acelle'                 => array(
            'title' => __( 'Acelle Mail', 'advanced-form-integration' ),
            'basic' => 'acelle',
        ),
        'activecampaign'         => array(
            'title' => __( 'ActiveCampaign', 'advanced-form-integration' ),
            'basic' => 'activecampaign',
        ),
        'acumbamail'             => array(
            'title' => __( 'Acumbamail', 'advanced-form-integration' ),
            'basic' => 'acumbamail',
        ),
        'acuity'                 => array(
            'title' => __( 'Acuity Scheduling', 'advanced-form-integration' ),
            'basic' => 'acuity',
        ),
        'addcal'                 => array(
            'title' => __( 'AddCal', 'advanced-form-integration' ),
            'basic' => 'addcal',
        ),
        'appointmenthourbooking' => array(
            'title' => __( 'Appointment Hour Booking', 'advanced-form-integration' ),
            'basic' => 'appointmenthourbooking',
        ),
        'affiliatewp'            => array(
            'title' => __( 'AffiliateWP', 'advanced-form-integration' ),
            'basic' => 'affiliatewp',
        ),
        'agilecrm'               => array(
            'title' => __( 'Agile CRM', 'advanced-form-integration' ),
            'basic' => 'agilecrm',
        ),
        'airtable'               => array(
            'title' => __( 'Airtable', 'advanced-form-integration' ),
            'basic' => 'airtable',
        ),
        'apollo'                 => array(
            'key'   => 'apollo',
            'title' => __( 'Apollo.io', 'advanced-form-integration' ),
            'basic' => 'apollo',
        ),
        'apptivo'                => array(
            'key'   => 'apptivo',
            'title' => __( 'Apptivo', 'advanced-form-integration' ),
            'basic' => 'apptivo',
        ),
        'asana'                  => array(
            'key'   => 'asana',
            'title' => __( 'Asana', 'advanced-form-integration' ),
            'basic' => 'asana',
        ),
        'attio'                  => array(
            'key'   => 'attio',
            'title' => __( 'Attio CRM', 'advanced-form-integration' ),
            'basic' => 'attio',
        ),
        'attentive'              => array(
            'title' => __( 'Attentive', 'advanced-form-integration' ),
            'basic' => 'attentive',
        ),
        'audienceful'            => array(
            'title' => __( 'Audienceful', 'advanced-form-integration' ),
            'basic' => 'audienceful',
        ),
        'airmeet'                => array(
            'title' => __( 'Airmeet', 'advanced-form-integration' ),
            'basic' => 'airmeet',
        ),
        'autopilot'              => array(
            'title' => __( 'Autopilot', 'advanced-form-integration' ),
            'basic' => 'autopilot',
        ),
        'aweber'                 => array(
            'title' => __( 'Aweber', 'advanced-form-integration' ),
            'basic' => 'aweber',
        ),
        'bbpress'                => array(
            'title' => __( 'bbPress', 'advanced-form-integration' ),
            'basic' => 'bbpress',
        ),
        'beehiiv'                => array(
            'title' => __( 'beehiiv', 'advanced-form-integration' ),
            'basic' => 'beehiiv',
        ),
        'benchmark'              => array(
            'title' => __( 'Benchmark', 'advanced-form-integration' ),
            'basic' => 'benchmark',
        ),
        'bigin'                  => array(
            'title' => __( 'Bigin', 'advanced-form-integration' ),
            'basic' => 'bigin',
        ),
        'bigmarker'              => array(
            'title' => __( 'BigMarker', 'advanced-form-integration' ),
            'basic' => 'bigmarker',
        ),
        'bombbomb'               => array(
            'title' => __( 'BombBomb', 'advanced-form-integration' ),
            'basic' => 'bombbomb',
        ),
        'braze'                  => array(
            'title' => __( 'Braze', 'advanced-form-integration' ),
            'basic' => 'braze',
        ),
        'brevo'                  => array(
            'title' => __( 'Brevo', 'advanced-form-integration' ),
            'basic' => 'brevo',
        ),
        'buddyboss'              => array(
            'title' => __( 'BuddyBoss', 'advanced-form-integration' ),
            'basic' => 'buddyboss',
        ),
        'fluentbooking'          => array(
            'title' => __( 'Fluent Booking', 'advanced-form-integration' ),
            'basic' => 'fluentbooking',
        ),
        'cakemail'               => array(
            'title' => __( 'Cakemail', 'advanced-form-integration' ),
            'basic' => 'cakemail',
        ),
        'campaigner'             => array(
            'title' => __( 'Campaigner', 'advanced-form-integration' ),
            'basic' => 'campaigner',
        ),
        'campaignmonitor'        => array(
            'title' => __( 'Campaign Monitor', 'advanced-form-integration' ),
            'basic' => 'campaignmonitor',
        ),
        'charitable'             => array(
            'title' => __( 'Charitable', 'advanced-form-integration' ),
            'basic' => 'charitable',
        ),
        'campayn'                => array(
            'title' => __( 'Campayn', 'advanced-form-integration' ),
            'basic' => 'campayn',
        ),
        'capsulecrm'             => array(
            'title' => __( 'Capsule CRM', 'advanced-form-integration' ),
            'basic' => 'capsulecrm',
        ),
        'civicrm'                => array(
            'title' => __( 'CiviCRM', 'advanced-form-integration' ),
            'basic' => 'civicrm',
        ),
        'cleverreach'            => array(
            'title' => __( 'CleverReach', 'advanced-form-integration' ),
            'basic' => 'cleverreach',
        ),
        'clickup'                => array(
            'title' => __( 'Clickup', 'advanced-form-integration' ),
            'basic' => 'clickup',
        ),
        'clinchpad'              => array(
            'title' => __( 'ClinchPad', 'advanced-form-integration' ),
            'basic' => 'clinchpad',
        ),
        'close'                  => array(
            'title' => __( 'Close', 'advanced-form-integration' ),
            'basic' => 'close',
        ),
        'companyhub'             => array(
            'title' => __( 'CompanyHub', 'advanced-form-integration' ),
            'basic' => 'companyhub',
        ),
        'constantcontact'        => array(
            'title' => __( 'Constant Contact', 'advanced-form-integration' ),
            'basic' => 'constantcontact',
        ),
        'convertkit'             => array(
            'title' => __( 'ConvertKit', 'advanced-form-integration' ),
            'basic' => 'convertkit',
        ),
        'copernica'              => array(
            'title' => __( 'Copernica', 'advanced-form-integration' ),
            'basic' => 'copernica',
        ),
        'copper'                 => array(
            'title' => __( 'Copper', 'advanced-form-integration' ),
            'basic' => 'copper',
        ),
        'curated'                => array(
            'title' => __( 'Curated', 'advanced-form-integration' ),
            'basic' => 'curated',
        ),
        'customerio'             => array(
            'title' => __( 'Customer.io', 'advanced-form-integration' ),
            'basic' => 'customerio',
        ),
        'demio'                  => array(
            'title' => __( 'Demio', 'advanced-form-integration' ),
            'basic' => 'demio',
        ),
        'directiq'               => array(
            'title' => __( 'DirectIQ', 'advanced-form-integration' ),
            'basic' => 'directiq',
        ),
        'doppler'                => array(
            'title' => __( 'Doppler', 'advanced-form-integration' ),
            'basic' => 'doppler',
        ),
        'drip'                   => array(
            'title' => __( 'Drip', 'advanced-form-integration' ),
            'basic' => 'drip',
        ),
        'dropbox'                => array(
            'title' => __( 'Dropbox', 'advanced-form-integration' ),
            'basic' => 'dropbox',
        ),
        'easysendy'              => array(
            'title' => __( 'EasySendy', 'advanced-form-integration' ),
            'basic' => 'easysendy',
        ),
        'elasticemail'           => array(
            'title' => __( 'Elastic Email', 'advanced-form-integration' ),
            'basic' => 'elasticemail',
        ),
        'emailchef'              => array(
            'title' => __( 'Emailchef', 'advanced-form-integration' ),
            'basic' => 'emailchef',
        ),
        'emailit'                => array(
            'title' => __( 'Emailit', 'advanced-form-integration' ),
            'basic' => 'emailit',
        ),
        'emailoctopus'           => array(
            'title' => __( 'EmailOctopus', 'advanced-form-integration' ),
            'basic' => 'emailoctopus',
        ),
        'encharge'               => array(
            'title' => __( 'Encharge', 'advanced-form-integration' ),
            'basic' => 'encharge',
            'pro'   => 'enchargepro',
        ),
        'engagebay'              => array(
            'title' => __( 'EngageBay', 'advanced-form-integration' ),
            'basic' => 'engagebay',
        ),
        'enormail'               => array(
            'title' => __( 'Enormail', 'advanced-form-integration' ),
            'basic' => 'enormail',
        ),
        'eventsmanager'          => array(
            'title' => __( 'Events Manager', 'advanced-form-integration' ),
            'basic' => 'eventsmanager',
        ),
        'everwebinar'            => array(
            'title' => __( 'EverWebinar', 'advanced-form-integration' ),
            'basic' => 'everwebinar',
        ),
        'flodesk'                => array(
            'title' => __( 'Flodesk', 'advanced-form-integration' ),
            'basic' => 'flodesk',
        ),
        'flowlu'                 => array(
            'title' => __( 'Flowlu', 'advanced-form-integration' ),
            'basic' => 'flowlu',
        ),
        'fluentcrm'              => array(
            'title' => __( 'Fluent CRM', 'advanced-form-integration' ),
            'basic' => 'fluentcrm',
        ),
        'fluentaffiliate'        => array(
            'title' => __( 'Fluent Affiliate', 'advanced-form-integration' ),
            'basic' => 'fluentaffiliate',
        ),
        'fluentboards'           => array(
            'title' => __( 'Fluent Boards', 'advanced-form-integration' ),
            'basic' => 'fluentboards',
        ),
        'fluentcommunity'        => array(
            'title' => __( 'Fluent Community', 'advanced-form-integration' ),
            'basic' => 'fluentcommunity',
        ),
        'gamipress'              => array(
            'title' => __( 'GamiPress', 'advanced-form-integration' ),
            'basic' => 'gamipress',
        ),
        'fluentsupport'          => array(
            'title' => __( 'Fluent Support', 'advanced-form-integration' ),
            'basic' => 'fluentsupport',
        ),
        'followupboss'           => array(
            'title' => __( 'FollowUpBoss', 'advanced-form-integration' ),
            'basic' => 'followupboss',
        ),
        'freshdesk'              => array(
            'title' => __( 'Freshdesk', 'advanced-form-integration' ),
            'basic' => 'freshdesk',
        ),
        'freshsales'             => array(
            'title' => __( 'Freshworks CRM', 'advanced-form-integration' ),
            'basic' => 'freshsales',
        ),
        'getresponse'            => array(
            'title' => __( 'GetResponse', 'advanced-form-integration' ),
            'basic' => 'getresponse',
        ),
        'givewp'                 => array(
            'title' => __( 'GiveWP', 'advanced-form-integration' ),
            'basic' => 'givewp',
        ),
        'gravityformsac'         => array(
            'title' => __( 'Gravity Forms', 'advanced-form-integration' ),
            'basic' => 'gravityformsac',
        ),
        'wpformsac'              => array(
            'title' => __( 'WPForms', 'advanced-form-integration' ),
            'basic' => 'wpformsac',
        ),
        'googlecalendar'         => array(
            'title' => __( 'Google Calendar', 'advanced-form-integration' ),
            'basic' => 'googlecalendar',
        ),
        'googledrive'            => array(
            'title' => __( 'Google Drive', 'advanced-form-integration' ),
            'basic' => 'googledrive',
        ),
        'googlesheets'           => array(
            'title' => __( 'Google Sheets', 'advanced-form-integration' ),
            'basic' => 'googlesheets',
        ),
        'highlevel'              => array(
            'title' => __( 'HighLevel', 'advanced-form-integration' ),
            'basic' => 'highlevel',
        ),
        'hubspot'                => array(
            'title' => __( 'Hubspot', 'advanced-form-integration' ),
            'basic' => 'hubspot',
        ),
        'icontact'               => array(
            'title' => __( 'iContact', 'advanced-form-integration' ),
            'basic' => 'icontact',
        ),
        'insightly'              => array(
            'title' => __( 'Insightly CRM', 'advanced-form-integration' ),
            'basic' => 'insightly',
        ),
        'intercom'               => array(
            'title' => __( 'Intercom', 'advanced-form-integration' ),
            'basic' => 'intercom',
        ),
        'instantly'              => array(
            'title' => __( 'Instantly', 'advanced-form-integration' ),
            'basic' => 'instantly',
        ),
        'jumplead'               => array(
            'title' => __( 'Jumplead', 'advanced-form-integration' ),
            'basic' => 'jumplead',
        ),
        'keila'                  => array(
            'title' => __( 'Keila', 'advanced-form-integration' ),
            'basic' => 'keila',
        ),
        'kit'                    => array(
            'title' => __( 'Kit', 'advanced-form-integration' ),
            'basic' => 'kit',
        ),
        'klaviyo'                => array(
            'title' => __( 'Klaviyo', 'advanced-form-integration' ),
            'basic' => 'klaviyo',
        ),
        'laposta'                => array(
            'title' => __( 'Laposta', 'advanced-form-integration' ),
            'basic' => 'laposta',
        ),
        'lemlist'                => array(
            'title' => __( 'lemlist', 'advanced-form-integration' ),
            'basic' => 'lemlist',
        ),
        'lacrm'                  => array(
            'title' => __( 'Less Annoying CRM', 'advanced-form-integration' ),
            'basic' => 'lacrm',
        ),
        'liondesk'               => array(
            'title' => __( 'LionDesk', 'advanced-form-integration' ),
            'basic' => 'liondesk',
        ),
        'livestorm'              => array(
            'title' => __( 'Livestorm', 'advanced-form-integration' ),
            'basic' => 'livestorm',
        ),
        'loops'                  => array(
            'title' => __( 'Loops', 'advanced-form-integration' ),
            'basic' => 'loops',
        ),
        'mailbluster'            => array(
            'title' => __( 'MailBluster', 'advanced-form-integration' ),
            'basic' => 'mailbluster',
        ),
        'mailchimp'              => array(
            'title' => __( 'Mailchimp', 'advanced-form-integration' ),
            'basic' => 'mailchimp',
        ),
        'mailcoach'              => array(
            'title' => __( 'Mailcoach', 'advanced-form-integration' ),
            'basic' => 'mailcoach',
        ),
        'maileon'                => array(
            'title' => __( 'Maileon', 'advanced-form-integration' ),
            'basic' => 'maileon',
        ),
        'mailercloud'            => array(
            'title' => __( 'Mailercloud', 'advanced-form-integration' ),
            'basic' => 'mailercloud',
        ),
        'mailerlite'             => array(
            'title' => __( 'MailerLite Classic', 'advanced-form-integration' ),
            'basic' => 'mailerlite',
        ),
        'mailerlite2'            => array(
            'title' => __( 'MailerLite', 'advanced-form-integration' ),
            'basic' => 'mailerlite2',
        ),
        'mailify'                => array(
            'title' => __( 'Mailify', 'advanced-form-integration' ),
            'basic' => 'mailify',
        ),
        'mailjet'                => array(
            'title' => __( 'Mailjet', 'advanced-form-integration' ),
            'basic' => 'mailjet',
        ),
        'mailmint'               => array(
            'title' => __( 'Mail Mint', 'advanced-form-integration' ),
            'basic' => 'mailmint',
        ),
        'mailmodo'               => array(
            'title' => __( 'Mailmodo', 'advanced-form-integration' ),
            'basic' => 'mailmodo',
        ),
        'mailpoet'               => array(
            'title' => __( 'MailPoet', 'advanced-form-integration' ),
            'basic' => 'mailpoet',
        ),
        'mailrelay'              => array(
            'title' => __( 'MailRelay', 'advanced-form-integration' ),
            'basic' => 'mailrelay',
        ),
        'mailster'               => array(
            'title' => __( 'Mailster', 'advanced-form-integration' ),
            'basic' => 'mailster',
        ),
        'mailup'                 => array(
            'title' => __( 'MailUp', 'advanced-form-integration' ),
            'basic' => 'mailup',
        ),
        'mailwizz'               => array(
            'title' => __( 'MailWizz', 'advanced-form-integration' ),
            'basic' => 'mailwizz',
        ),
        'mautic'                 => array(
            'title' => __( 'Mautic', 'advanced-form-integration' ),
            'basic' => 'mautic',
        ),
        'monday'                 => array(
            'title' => __( 'Monday.com', 'advanced-form-integration' ),
            'basic' => 'monday',
        ),
        'moosend'                => array(
            'title' => __( 'Moosend', 'advanced-form-integration' ),
            'basic' => 'moosend',
        ),
        'newsletter'             => array(
            'title' => __( 'Newsletter', 'advanced-form-integration' ),
            'basic' => 'newsletter',
        ),
        'nimble'                 => array(
            'title' => __( 'Nimble', 'advanced-form-integration' ),
            'basic' => 'nimble',
        ),
        'nutshell'               => array(
            'title' => __( 'Nutshell CRM', 'advanced-form-integration' ),
            'basic' => 'nutshell',
        ),
        'omnisend'               => array(
            'title' => __( 'Omnisend', 'advanced-form-integration' ),
            'basic' => 'omnisend',
        ),
        'onehash'                => array(
            'title' => __( 'Onehash', 'advanced-form-integration' ),
            'basic' => 'onehash',
        ),
        'autopilotnew'           => array(
            'title' => __( 'Ortto', 'advanced-form-integration' ),
            'basic' => 'autopilotnew',
        ),
        'pabbly'                 => array(
            'title' => __( 'Pabbly', 'advanced-form-integration' ),
            'basic' => 'pabbly',
        ),
        'pipedrive'              => array(
            'title' => __( 'Pipedrive', 'advanced-form-integration' ),
            'basic' => 'pipedrive',
        ),
        'pushover'               => array(
            'title' => __( 'Pushover', 'advanced-form-integration' ),
            'basic' => 'pushover',
        ),
        'ragic'                  => array(
            'title' => __( 'Ragic', 'advanced-form-integration' ),
            'basic' => 'ragic',
        ),
        'rapidmail'              => array(
            'title' => __( 'Rapidmail', 'advanced-form-integration' ),
            'basic' => 'rapidmail',
        ),
        'resend'                 => array(
            'title' => __( 'Resend', 'advanced-form-integration' ),
            'basic' => 'resend',
        ),
        'revue'                  => array(
            'title' => __( 'Revue', 'advanced-form-integration' ),
            'basic' => 'revue',
        ),
        'robly'                  => array(
            'title' => __( 'Robly', 'advanced-form-integration' ),
            'basic' => 'robly',
        ),
        'salesflare'             => array(
            'title' => __( 'Salesflare', 'advanced-form-integration' ),
            'basic' => 'salesflare',
        ),
        'salesforce'             => array(
            'title' => __( 'Salesforce', 'advanced-form-integration' ),
            'basic' => 'salesforce',
        ),
        'saleshandy'             => array(
            'title' => __( 'SalesHandy', 'advanced-form-integration' ),
            'basic' => 'saleshandy',
        ),
        'salesrocks'             => array(
            'title' => __( 'Sales Rocks', 'advanced-form-integration' ),
            'basic' => 'salesrocks',
        ),
        'salesmate'              => array(
            'title' => __( 'Salesmate', 'advanced-form-integration' ),
            'basic' => 'salesmate',
        ),
        'sarbacane'              => array(
            'title' => __( 'Sarbacane', 'advanced-form-integration' ),
            'basic' => 'sarbacane',
        ),
        'selzy'                  => array(
            'title' => __( 'Selzy', 'advanced-form-integration' ),
            'basic' => 'selzy',
        ),
        'sender'                 => array(
            'title' => __( 'Sender', 'advanced-form-integration' ),
            'basic' => 'sender',
        ),
        'sendfox'                => array(
            'title' => __( 'Sendfox', 'advanced-form-integration' ),
            'basic' => 'sendfox',
        ),
        'sendinblue'             => array(
            'title' => __( 'Sendinblue', 'advanced-form-integration' ),
            'basic' => 'sendinblue',
        ),
        'sendlane'               => array(
            'title' => __( 'Sendlane', 'advanced-form-integration' ),
            'basic' => 'sendlane',
        ),
        'sendpulse'              => array(
            'title' => __( 'Sendpulse', 'advanced-form-integration' ),
            'basic' => 'sendpulse',
        ),
        'sendx'                  => array(
            'title' => __( 'SendX', 'advanced-form-integration' ),
            'basic' => 'sendx',
        ),
        'sendy'                  => array(
            'title' => __( 'Sendy', 'advanced-form-integration' ),
            'basic' => 'sendy',
        ),
        'slack'                  => array(
            'title' => __( 'Slack', 'advanced-form-integration' ),
            'basic' => 'slack',
        ),
        'smartrmail'             => array(
            'title' => __( 'SmartrMail', 'advanced-form-integration' ),
            'basic' => 'smartrmail',
        ),
        'smartsheet'             => array(
            'title' => __( 'Smartsheet', 'advanced-form-integration' ),
            'basic' => 'smartsheet',
        ),
        'snovio'                 => array(
            'title' => __( 'Snov.io', 'advanced-form-integration' ),
            'basic' => 'snovio',
        ),
        'suitedash'              => array(
            'title' => __( 'SuiteDash', 'advanced-form-integration' ),
            'basic' => 'suitedash',
        ),
        'systemeio'              => array(
            'title' => __( 'Systeme.io', 'advanced-form-integration' ),
            'basic' => 'systemeio',
        ),
        'trello'                 => array(
            'title' => __( 'Trello', 'advanced-form-integration' ),
            'basic' => 'trello',
        ),
        'twilio'                 => array(
            'title' => __( 'Twilio', 'advanced-form-integration' ),
            'basic' => 'twilio',
        ),
        'verticalresponse'       => array(
            'title' => __( 'Vertical Response', 'advanced-form-integration' ),
            'basic' => 'verticalresponse',
        ),
        'vtiger'                 => array(
            'title' => __( 'Vtiger CRM', 'advanced-form-integration' ),
            'basic' => 'vtiger',
        ),
        'wealthbox'              => array(
            'title' => __( 'Wealthbox', 'advanced-form-integration' ),
            'basic' => 'wealthbox',
        ),
        'webhook'                => array(
            'title' => __( 'Webhook', 'advanced-form-integration' ),
            'basic' => 'webhook',
        ),
        'webinarjam'             => array(
            'title' => __( 'WebinarJam', 'advanced-form-integration' ),
            'basic' => 'webinarjam',
        ),
        'woodpecker'             => array(
            'title' => __( 'Woodpecker.co', 'advanced-form-integration' ),
            'basic' => 'woodpecker',
        ),
        'woocommerce'            => array(
            'title' => __( 'WooCommerce', 'advanced-form-integration' ),
            'basic' => 'woocommerce',
        ),
        'wordpress'              => array(
            'title' => __( 'WordPress', 'advanced-form-integration' ),
            'basic' => 'wordpress',
        ),
        'zapier'                 => array(
            'title' => __( 'Zapier', 'advanced-form-integration' ),
            'basic' => 'zapier',
        ),
        'zendesk'                => array(
            'title' => __( 'Zendesk Support', 'advanced-form-integration' ),
            'basic' => 'zendesk',
        ),
        'zendesksell'            => array(
            'title' => __( 'Zendesk Sell', 'advanced-form-integration' ),
            'basic' => 'zendesksell',
        ),
        'zohopeople'             => array(
            'title' => __( 'Zoho People', 'advanced-form-integration' ),
            'basic' => 'zohopeople',
        ),
        'zohobooks'              => array(
            'title' => __( 'Zoho Books', 'advanced-form-integration' ),
            'basic' => 'zohobooks',
        ),
        'zohocampaigns'          => array(
            'title' => __( 'Zoho Campaigns', 'advanced-form-integration' ),
            'basic' => 'zohocampaigns',
        ),
        'zohocrm'                => array(
            'title' => __( 'Zoho CRM', 'advanced-form-integration' ),
            'basic' => 'zohocrm',
        ),
        'zohodesk'               => array(
            'title' => __( 'Zoho Desk', 'advanced-form-integration' ),
            'basic' => 'zohodesk',
        ),
        'zohoma'                 => array(
            'title' => __( 'Zoho Marketing Automation', 'advanced-form-integration' ),
            'basic' => 'zohoma',
        ),
        'zohosheet'              => array(
            'title' => __( 'Zoho Sheet', 'advanced-form-integration' ),
            'basic' => 'zohosheet',
        ),
        'zoomwebinar'            => array(
            'title' => __( 'Zoom Webinar', 'advanced-form-integration' ),
            'basic' => 'zoomwebinar',
        ),
    );
}

/**
*
* Retrieves the action platform settings.
*
* @global object $wpdb WordPress database access object.
*
* @return array The action platform settings.
*/
function adfoin_get_action_platform_settings() {
    global $wpdb;
    $settings = ( get_option( 'adfoin_general_settings_platforms' ) ? get_option( 'adfoin_general_settings_platforms' ) : array() );
    $saved_records = $wpdb->get_results( "SELECT form_provider, action_provider FROM {$wpdb->prefix}adfoin_integration WHERE status = 1", ARRAY_A );
    if ( !is_wp_error( $saved_records ) ) {
        if ( $saved_records && is_array( $saved_records ) ) {
            $action_providers = wp_list_pluck( $saved_records, 'action_provider' );
            if ( $action_providers ) {
                $action_providers = array_unique( $action_providers );
                foreach ( $action_providers as $single ) {
                    $settings[$single] = true;
                }
            }
        }
    }
    return $settings;
}

/**
 * Build a map of action-provider key => component-script URL.
 *
 * Each platform's Vue component lives at
 * `platforms/<provider>/<provider>-component.js`. The map is consumed by
 * `adfoinComponentLoader.loadPlatform()` in core.js to fetch only the
 * file for the provider the user is currently editing.
 *
 * Pro versions of a platform may override the basic file by providing
 * `pro/<provider>pro/<provider>pro-component.js` (the basic mapping is kept
 * if no pro file exists).
 *
 * Plugins can extend or modify the map via the `adfoin_platform_scripts`
 * filter.
 *
 * @return array<string,string> map of provider key to component script URL.
 */
function adfoin_get_platform_scripts() {
    $map = array();
    $platforms_dir = ADVANCED_FORM_INTEGRATION_PLATFORMS;
    $platforms_url = ADVANCED_FORM_INTEGRATION_URL . '/platforms';
    if ( is_dir( $platforms_dir ) ) {
        $entries = scandir( $platforms_dir );
        if ( is_array( $entries ) ) {
            foreach ( $entries as $entry ) {
                if ( '.' === $entry || '..' === $entry ) {
                    continue;
                }
                $component_file = $platforms_dir . '/' . $entry . '/' . $entry . '-component.js';
                if ( file_exists( $component_file ) ) {
                    $map[$entry] = $platforms_url . '/' . $entry . '/' . $entry . '-component.js';
                }
            }
        }
    }
    // The 'zoho_meeting' Vue component lives in platforms/zohomeeting/ but
    // its registered name uses an underscore; expose both keys so the
    // loader can resolve either form.
    if ( isset( $map['zohomeeting'] ) ) {
        $map['zoho_meeting'] = $map['zohomeeting'];
    }
    // Allow pro/<name>pro/<name>pro-component.js to override or add entries.
    if ( defined( 'ADVANCED_FORM_INTEGRATION_PRO' ) && is_dir( ADVANCED_FORM_INTEGRATION_PRO ) ) {
        $pro_dir = ADVANCED_FORM_INTEGRATION_PRO;
        $pro_url = ADVANCED_FORM_INTEGRATION_URL . '/pro';
        $pro_entries = scandir( $pro_dir );
        if ( is_array( $pro_entries ) ) {
            foreach ( $pro_entries as $entry ) {
                if ( '.' === $entry || '..' === $entry ) {
                    continue;
                }
                $component_file = $pro_dir . '/' . $entry . '/' . $entry . '-component.js';
                if ( file_exists( $component_file ) ) {
                    // Strip trailing 'pro' to derive the provider key, e.g. aweberpro -> aweber.
                    $provider = ( substr( $entry, -3 ) === 'pro' ? substr( $entry, 0, -3 ) : $entry );
                    $map[$provider] = $pro_url . '/' . $entry . '/' . $entry . '-component.js';
                }
            }
        }
    }
    return apply_filters( 'adfoin_platform_scripts', $map );
}

/**
* Renders the general settings view for Adfo.in plugin.
*/
add_action(
    'adfoin_settings_view',
    'adfoin_general_settings_view',
    10,
    1
);
/**
* Displays the view for general settings of Advanced Form Integration plugin
*
* @param string $current_tab The current tab name
*
* @return void
*/
function adfoin_general_settings_view(  $current_tab  ) {
    if ( $current_tab !== 'general' ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_general_settings' );
    $log_settings = ( get_option( 'adfoin_general_settings_log' ) ? get_option( 'adfoin_general_settings_log' ) : '' );
    $error_email = ( get_option( 'adfoin_general_settings_error_email' ) ? get_option( 'adfoin_general_settings_error_email' ) : '' );
    $st_settings = ( get_option( 'adfoin_general_settings_st' ) ? get_option( 'adfoin_general_settings_st' ) : '' );
    $utm_settings = ( get_option( 'adfoin_general_settings_utm' ) ? get_option( 'adfoin_general_settings_utm' ) : '' );
    $job_queue = ( get_option( 'adfoin_general_settings_job_queue' ) ? get_option( 'adfoin_general_settings_job_queue' ) : '' );
    $platform_settings = adfoin_get_action_platform_settings();
    $platforms = adfoin_get_action_platform_list();
    ?>

    <form name="general_save_form" action="<?php 
    echo esc_url( admin_url( 'admin-post.php' ) );
    ?>"
          method="post" class="afi-container">

        <input type="hidden" name="action" value="adfoin_save_general_settings">
        <input type="hidden" name="_nonce" value="<?php 
    echo esc_attr( $nonce );
    ?>"/>

        <!-- ── Card 1: Activate Platforms ── -->
        <div class="afi-card">
            <div class="afi-card-header afi-settings-card-header">
                <h3 class="afi-card-title"><?php 
    esc_html_e( 'Activate Platforms', 'advanced-form-integration' );
    ?></h3>

                <div class="afi-filter-controls">
                    <div class="afi-filter-links">
                        <button type="button" class="afi-filter-btn active" data-filter="all"><?php 
    esc_html_e( 'All', 'advanced-form-integration' );
    ?></button>
                        <button type="button" class="afi-filter-btn" data-filter="active"><?php 
    esc_html_e( 'Active', 'advanced-form-integration' );
    ?></button>
                        <button type="button" class="afi-filter-btn" data-filter="inactive"><?php 
    esc_html_e( 'Inactive', 'advanced-form-integration' );
    ?></button>
                    </div>
                    <div class="afi-search-wrapper">
                        <input type="search"
                               id="adfoin-platform-search"
                               class="afi-input afi-platform-search"
                               placeholder="<?php 
    esc_attr_e( 'Search platforms...', 'advanced-form-integration' );
    ?>"
                               autocomplete="off">
                    </div>
                    <input type="submit" name="submit" class="afi-save-button" value="<?php 
    esc_attr_e( 'Save Changes', 'advanced-form-integration' );
    ?>">
                </div>
            </div>

            <div class="afi-checkbox-container" data-platform-list>
                <?php 
    foreach ( $platforms as $key => $platform ) {
        $status = ( isset( $platform_settings[$key] ) ? $platform_settings[$key] : '' );
        $is_active = ( 1 == $status ? 'active' : 'inactive' );
        $safe_key = esc_attr( $key );
        ?>
                    <div class="afi-checkbox" data-status="<?php 
        echo esc_attr( $is_active );
        ?>">
                        <div class="afi-elements-info">
                            <p class="afi-el-title">
                                <label for="<?php 
        echo $safe_key;
        ?>"><?php 
        echo esc_html( $platform['title'] );
        ?></label>
                            </p>
                        </div>
                        <label class="adfoin-toggle-form form-enabled">
                            <input type="checkbox" value="1" id="<?php 
        echo $safe_key;
        ?>" name="platforms[<?php 
        echo $safe_key;
        ?>]" <?php 
        checked( $status, 1 );
        ?>>
                            <span class="afi-slider round"></span>
                        </label>
                    </div>
                <?php 
    }
    ?>
            </div>

            <div id="afi-no-results" class="afi-no-results" hidden>
                <p class="afi-no-results-text"><?php 
    esc_html_e( 'No platforms found matching your criteria.', 'advanced-form-integration' );
    ?></p>
            </div>
        </div>

        <!-- ── Card 2: General Settings ── -->
        <div class="afi-card">
            <div class="afi-card-header">
                <h3 class="afi-card-title"><?php 
    esc_html_e( 'General Settings', 'advanced-form-integration' );
    ?></h3>
            </div>

            <div class="afi-checkbox-container afi-settings-toggles">

                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_disable_log"><?php 
    esc_html_e( 'Disable Log', 'advanced-form-integration' );
    ?></label>
                        </p>
                        <p class="afi-helper-text"><?php 
    esc_html_e( 'Stop recording integration activity. Useful on high-traffic sites where log storage is a concern.', 'advanced-form-integration' );
    ?></p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="adfoin_disable_log" name="adfoin_disable_log" <?php 
    checked( $log_settings, 1 );
    ?>>
                        <span class="afi-slider round"></span>
                    </label>
                </div>

                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_disable_st"><?php 
    esc_html_e( 'Disable Special Tags', 'advanced-form-integration' );
    ?></label>
                        </p>
                        <p class="afi-helper-text"><?php 
    esc_html_e( 'Turn off built-in special tags such as {all_fields} and {date} from being processed on submissions.', 'advanced-form-integration' );
    ?></p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="adfoin_disable_st" name="adfoin_disable_st" <?php 
    checked( $st_settings, 1 );
    ?>>
                        <span class="afi-slider round"></span>
                    </label>
                </div>

                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_enable_utm"><?php 
    esc_html_e( 'Send UTM Variables', 'advanced-form-integration' );
    ?></label>
                        </p>
                        <p class="afi-helper-text"><?php 
    esc_html_e( 'Automatically append UTM tracking parameters from the visitor\'s URL to each form submission sent to integrations.', 'advanced-form-integration' );
    ?></p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="adfoin_enable_utm" name="adfoin_enable_utm" <?php 
    checked( $utm_settings, 1 );
    ?>>
                        <span class="afi-slider round"></span>
                    </label>
                </div>

                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_job_queue"><?php 
    esc_html_e( 'Enable Job Queue', 'advanced-form-integration' );
    ?></label>
                        </p>
                        <p class="afi-helper-text"><?php 
    esc_html_e( 'Process integrations asynchronously in the background instead of during the form submission request, improving response time.', 'advanced-form-integration' );
    ?></p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="adfoin_job_queue" name="adfoin_job_queue" <?php 
    checked( $job_queue, 1 );
    ?>>
                        <span class="afi-slider round"></span>
                    </label>
                </div>

                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_error_email"><?php 
    esc_html_e( 'Send Error Email', 'advanced-form-integration' );
    ?></label>
                        </p>
                        <p class="afi-helper-text"><?php 
    esc_html_e( 'Receive an email notification at the site admin address whenever an integration encounters an error.', 'advanced-form-integration' );
    ?></p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="adfoin_error_email" name="adfoin_error_email" <?php 
    checked( $error_email, 1 );
    ?>>
                        <span class="afi-slider round"></span>
                    </label>
                </div>

            </div>
        </div>

    </form>

    <script>
    (function () {
        function adfoinInitPlatformFilters() {
            var searchInput = document.getElementById( 'adfoin-platform-search' );
            var filterBtns  = document.querySelectorAll( '.afi-filter-btn' );
            var container   = document.querySelector( '.afi-checkbox-container[data-platform-list]' );
            var noResults   = document.getElementById( 'afi-no-results' );

            if ( ! container || ! searchInput ) { return; }

            var items         = Array.prototype.slice.call( container.querySelectorAll( '.afi-checkbox' ) );
            var currentFilter = 'all';

            function filterItems() {
                var term         = searchInput.value.toLowerCase();
                var visibleCount = 0;

                items.forEach( function ( item ) {
                    var label        = item.querySelector( '.afi-el-title label' );
                    var text         = label ? label.textContent.toLowerCase() : '';
                    var checkbox     = item.querySelector( 'input[type="checkbox"]' );
                    var dynStatus    = checkbox && checkbox.checked ? 'active' : 'inactive';
                    var matchSearch  = ! term || text.indexOf( term ) !== -1;
                    var matchFilter  = currentFilter === 'all' || dynStatus === currentFilter;

                    if ( matchSearch && matchFilter ) {
                        item.style.display = '';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                } );

                noResults.hidden = ( visibleCount !== 0 );
            }

            searchInput.addEventListener( 'input', filterItems );

            filterBtns.forEach( function ( btn ) {
                btn.addEventListener( 'click', function () {
                    filterBtns.forEach( function ( b ) { b.classList.remove( 'active' ); } );
                    this.classList.add( 'active' );
                    currentFilter = this.getAttribute( 'data-filter' );
                    filterItems();
                } );
            } );

            items.forEach( function ( item ) {
                var cb = item.querySelector( 'input[type="checkbox"]' );
                if ( cb ) {
                    cb.addEventListener( 'change', function () {
                        if ( currentFilter !== 'all' ) { filterItems(); }
                    } );
                }
            } );
        }

        if ( document.readyState !== 'loading' ) {
            adfoinInitPlatformFilters();
        } else {
            document.addEventListener( 'DOMContentLoaded', adfoinInitPlatformFilters );
        }
    }());
    </script>

    <?php 
}

add_action(
    'admin_post_adfoin_save_general_settings',
    'adfoin_save_general_settings',
    10,
    0
);
function adfoin_save_general_settings() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_general_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $log_settings = ( isset( $_POST['adfoin_disable_log'] ) ? sanitize_text_field( $_POST['adfoin_disable_log'] ) : '' );
    $error_email = ( isset( $_POST['adfoin_error_email'] ) ? sanitize_text_field( $_POST['adfoin_error_email'] ) : '' );
    $st_settings = ( isset( $_POST['adfoin_disable_st'] ) ? sanitize_text_field( $_POST['adfoin_disable_st'] ) : '' );
    $utm_settings = ( isset( $_POST['adfoin_enable_utm'] ) ? sanitize_text_field( $_POST['adfoin_enable_utm'] ) : '' );
    $job_queue = ( isset( $_POST['adfoin_job_queue'] ) ? sanitize_text_field( $_POST['adfoin_job_queue'] ) : '' );
    $default_platforms = array_fill_keys( array_keys( adfoin_get_action_platform_list() ), false );
    $activated_platforms = ( isset( $_POST['platforms'] ) ? adfoin_sanitize_text_or_array_field( $_POST['platforms'] ) : array() );
    $all_platforms = array_merge( $default_platforms, array_fill_keys( array_keys( array_intersect_key( $activated_platforms, $default_platforms ) ), true ) );
    // Save
    update_option( 'adfoin_general_settings_platforms', $all_platforms );
    update_option( 'adfoin_general_settings_log', $log_settings );
    update_option( 'adfoin_general_settings_error_email', $error_email );
    update_option( 'adfoin_general_settings_st', $st_settings );
    update_option( 'adfoin_general_settings_utm', $utm_settings );
    update_option( 'adfoin_general_settings_job_queue', $job_queue );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings' );
}

/**
 * Sanitize text or array field.
 *
 * @param mixed $array_or_string The string or array to sanitize.
 * @return mixed The sanitized string or array.
 */
function adfoin_sanitize_text_or_array_field(  $array_or_string  ) {
    if ( is_string( $array_or_string ) ) {
        $array_or_string = stripslashes( $array_or_string );
    } elseif ( is_array( $array_or_string ) ) {
        foreach ( $array_or_string as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = adfoin_sanitize_text_or_array_field( $value );
            } else {
                $value = stripslashes( $value );
            }
        }
    }
    return $array_or_string;
}

/*
 * Get parsed value
 */
function adfoin_get_parsed_values(  $field, $posted_data  ) {
    foreach ( $posted_data as $key => $value ) {
        if ( is_array( $value ) ) {
            $multi = 0;
            foreach ( $value as $single ) {
                if ( is_array( $single ) ) {
                    $multi = 1;
                    break;
                }
            }
            if ( $multi ) {
                $value = json_encode( $value );
            } else {
                $value = @implode( ",", $value );
            }
        }
        if ( $value ) {
            $field = str_replace( '{{' . $key . '}}', $value, $field );
        }
    }
    $field = preg_replace( "/{{.+?}}/", "", $field );
    if ( strpos( $field, '[' ) !== false && strpos( $field, ']' ) !== false ) {
        $field = do_shortcode( $field );
    }
    return $field;
}

/**
 * Add a log entry for an integration request/response.
 *
 * This function adds a log entry for an integration request and response. The log entry includes
 * information about the URL, arguments, response data, and integration ID.
 *
 * @param mixed       $return   The response data from the integration request.
 * @param string      $url      The URL of the integration request.
 * @param array       $args     The arguments or data sent with the integration request.
 * @param array       $record   An array containing integration record data.
 * @param string|null $log_id   Optional. The ID of the log entry to update. Default is an empty string.
 * @return void
 */
function adfoin_add_to_log(
    $return,
    $url,
    $args,
    $record,
    $log_id = ''
) {
    $log_settings = ( get_option( 'adfoin_general_settings_log' ) ? get_option( 'adfoin_general_settings_log' ) : '' );
    if ( "1" == $log_settings ) {
        return;
    }
    if ( isset( $args['body'] ) ) {
        if ( !is_array( $args['body'] ) ) {
            if ( null != json_decode( $args['body'] ) ) {
                $args['body'] = json_decode( $args['body'] );
            }
        }
    }
    $request_data = json_encode( array(
        'url'  => $url,
        'args' => $args,
    ) );
    if ( is_wp_error( $return ) ) {
        $data = array(
            'response_code'    => 0,
            'response_message' => 'WP Error',
            'integration_id'   => $record["id"],
            'request_data'     => $request_data,
            'response_data'    => json_encode( $return ),
        );
    } else {
        $response_body = $return["body"];
        if ( is_array( $response_body ) || is_object( $response_body ) ) {
            $response_body = wp_json_encode( $response_body );
        }
        if ( is_string( $response_body ) ) {
            $trimmed_body = trim( $response_body );
            if ( '' !== $trimmed_body ) {
                $stripped = wp_strip_all_tags( $trimmed_body, true );
                if ( $stripped !== $trimmed_body ) {
                    $trimmed_body = preg_replace( '/\\s+/', ' ', $stripped );
                }
                $response_body = $trimmed_body;
            }
        } else {
            $response_body = '';
        }
        $data = array(
            'response_code'    => $return["response"]["code"],
            'response_message' => $return["response"]["message"],
            'integration_id'   => $record["id"],
            'request_data'     => $request_data,
            'response_data'    => $response_body,
        );
    }
    $log = new Advanced_Form_Integration_Log();
    if ( $log_id ) {
        $log->update( $data, $log_id );
    } else {
        $log->insert( $data );
    }
    $error_email = ( get_option( 'adfoin_general_settings_error_email' ) ? get_option( 'adfoin_general_settings_error_email' ) : '' );
    if ( $error_email && is_wp_error( $return ) ) {
        do_action(
            'adfoin_send_api_error_email',
            $return,
            $record,
            $request_data
        );
    }
    return;
}

add_action(
    'adfoin_send_api_error_email',
    'adfoin_send_api_error_email',
    10,
    3
);
/**
 * Send an email notification for an API error.
 *
 * This function sends an email notification to the site administrator when an API request returns
 * an error response code. The email includes information about the error, the integration record,
 * and the request data.
 *
 * @param array $return       The response data from the integration request.
 * @param array $record       An array containing integration record data.
 * @param string $request_data The data sent with the integration request.
 * @return void
 */
function adfoin_send_api_error_email(  $return, $record, $request_data  ) {
    $admin_email = get_option( 'admin_email' );
    $subject = __( 'Error with AFI Integration', 'advanced-form-integration' );
    $site_url = get_bloginfo( 'url' );
    $message = __( 'An error has occurred with AFI integration on ', 'advanced-form-integration' ) . $site_url . __( '. Please check the AFI > Log page for more details.', 'advanced-form-integration' );
    wp_mail( $admin_email, $subject, $message );
}

/*
 * Get User IP
 */
function adfoin_get_user_ip() {
    // Respect CloudFlare connecting IP if provided and valid
    if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
        $cf_ip = ( is_string( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? trim( $_SERVER['HTTP_CF_CONNECTING_IP'] ) : '' );
        if ( filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) {
            $_SERVER['REMOTE_ADDR'] = $cf_ip;
            $_SERVER['HTTP_CLIENT_IP'] = $cf_ip;
        } else {
            // don't overwrite with an invalid value
            $_SERVER['HTTP_CLIENT_IP'] = '';
        }
    }
    // Collect candidate IPs in order of preference
    $candidates = array();
    if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $candidates[] = $_SERVER['HTTP_CLIENT_IP'];
    }
    if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        // X-Forwarded-For can contain a list of IPs
        $forward_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
        foreach ( $forward_list as $fip ) {
            $candidates[] = $fip;
        }
    }
    if ( !empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $candidates[] = $_SERVER['REMOTE_ADDR'];
    }
    // Normalize and validate candidates, return first valid IP
    foreach ( $candidates as $candidate ) {
        $candidate = trim( (string) $candidate );
        if ( $candidate === '' ) {
            continue;
        }
        // Remove brackets used in some IPv6 representations
        $candidate = trim( $candidate, "[] \t\n\r\x00\v" );
        // If IPv4:port format (e.g. 1.2.3.4:5678), strip the port.
        // Avoid stripping for IPv6 which contains multiple colons.
        if ( strpos( $candidate, ':' ) !== false && substr_count( $candidate, ':' ) === 1 ) {
            $candidate = strstr( $candidate, ':', true );
        }
        if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
            return $candidate;
        }
    }
    // If no valid IP found, return empty string instead of invalid values like "1"
    return '';
}

function adfoin_get_cl_conditions() {
    return array(
        "equal_to"             => __( 'Equal to', 'advanced-form-integration' ),
        "not_equal_to"         => __( 'Not equal to', 'advanced-form-integration' ),
        "contains"             => __( 'Contains', 'advanced-form-integration' ),
        "does_not_contain"     => __( 'Does not Contain', 'advanced-form-integration' ),
        "starts_with"          => __( 'Starts with', 'advanced-form-integration' ),
        "ends_with"            => __( 'Ends with', 'advanced-form-integration' ),
        "in_list"              => __( 'Is one of', 'advanced-form-integration' ),
        "not_in_list"          => __( 'Is not one of', 'advanced-form-integration' ),
        "matches_regex"        => __( 'Matches regex', 'advanced-form-integration' ),
        "does_not_match_regex" => __( 'Does not match regex', 'advanced-form-integration' ),
        "greater_than"         => __( 'Greater than', 'advanced-form-integration' ),
        "less_than"            => __( 'Less than', 'advanced-form-integration' ),
        "between"              => __( 'Between (inclusive)', 'advanced-form-integration' ),
        "not_between"          => __( 'Not between', 'advanced-form-integration' ),
        "date_before"          => __( 'Date is before', 'advanced-form-integration' ),
        "date_after"           => __( 'Date is after', 'advanced-form-integration' ),
        "date_on"              => __( 'Date is on', 'advanced-form-integration' ),
        "date_not_on"          => __( 'Date is not on', 'advanced-form-integration' ),
        "is_empty"             => __( 'Is empty', 'advanced-form-integration' ),
        "is_not_empty"         => __( 'Is not empty', 'advanced-form-integration' ),
    );
}

/**
 * Operators grouped into <optgroup>-friendly buckets. Used by the
 * conditional-logic row template to render Text / Number / Date / Empty
 * sections so the new operators are easy to find.
 *
 * @since 1.128.1
 * @return array
 */
function adfoin_get_cl_conditions_grouped() {
    return array(
        __( 'Text', 'advanced-form-integration' )    => array(
            'equal_to',
            'not_equal_to',
            'contains',
            'does_not_contain',
            'starts_with',
            'ends_with'
        ),
        __( 'List', 'advanced-form-integration' )    => array('in_list', 'not_in_list'),
        __( 'Pattern', 'advanced-form-integration' ) => array('matches_regex', 'does_not_match_regex'),
        __( 'Number', 'advanced-form-integration' )  => array(
            'greater_than',
            'less_than',
            'between',
            'not_between'
        ),
        __( 'Date', 'advanced-form-integration' )    => array(
            'date_before',
            'date_after',
            'date_on',
            'date_not_on'
        ),
        __( 'Empty', 'advanced-form-integration' )   => array('is_empty', 'is_not_empty'),
    );
}

/**
 * Operators that don't take a value on the right-hand side
 * (the UI hides the value input when one of these is selected).
 *
 * @since 1.128.1
 * @return array
 */
function adfoin_get_cl_valueless_operators() {
    return array('is_empty', 'is_not_empty');
}

/**
 * Operators that compare both sides as dates (parsed via strtotime).
 *
 * @since 1.128.1
 * @return array
 */
function adfoin_get_cl_date_operators() {
    return array(
        'date_before',
        'date_after',
        'date_on',
        'date_not_on'
    );
}

/**
 * Evaluate a saved CL ruleset against the current submission.
 *
 * @since 1.128.1 Counts only configured conditions (those with a non-empty
 *                field) so "all" mode can satisfy when the saved ruleset
 *                contains stray blank rows. An empty/all-blank ruleset
 *                pass-throughs (returns true) just like an inactive CL.
 *
 * @param  array $cl          { active, match, conditions } from action_data.
 * @param  array $posted_data Form submission data, used for {{tag}} resolution.
 * @return bool               True if the action should run.
 */
function adfoin_match_conditional_logic(  $cl, $posted_data  ) {
    if ( !is_array( $cl ) || isset( $cl['active'] ) && 'yes' !== $cl['active'] ) {
        return true;
    }
    $conditions = ( isset( $cl['conditions'] ) && is_array( $cl['conditions'] ) ? $cl['conditions'] : array() );
    $match_mode = ( isset( $cl['match'] ) ? $cl['match'] : 'any' );
    $match = 0;
    $total = 0;
    // Per-call memo of parsed field values, keyed by the wrapped
    // `{{name}}` form. adfoin_get_parsed_values walks every key in
    // $posted_data on each call, so caching avoids redoing that work
    // when a CL ruleset references the same field in multiple
    // conditions (e.g. "email contains @" + "email not_in_list spam@…").
    $field_cache = array();
    foreach ( $conditions as $condition ) {
        // Skip rows whose field is blank — they're "not configured" and
        // count toward neither $match nor $total. Previously they were
        // skipped from $match but still counted in $length, which made
        // "all" mode unsatisfiable when any row was blank.
        if ( empty( $condition['field'] ) && $condition['field'] != 0 ) {
            continue;
        }
        $total++;
        $raw_field = $condition['field'];
        $field = ( strpos( $raw_field, '{{' ) !== false && strpos( $raw_field, '}}' ) !== false ? $raw_field : '{{' . trim( $raw_field ) . '}}' );
        if ( !array_key_exists( $field, $field_cache ) ) {
            $field_cache[$field] = adfoin_get_parsed_values( $field, $posted_data );
        }
        $field_value = $field_cache[$field];
        $operator = ( isset( $condition['operator'] ) ? $condition['operator'] : 'equal_to' );
        $value = ( isset( $condition['value'] ) ? $condition['value'] : '' );
        if ( adfoin_match_single_logic( $field_value, $operator, $value ) ) {
            $match++;
        }
    }
    // No effective conditions configured — treat as pass-through, same
    // as `active === "no"`, instead of silently blocking every run.
    if ( 0 === $total ) {
        return true;
    }
    if ( 'any' === $match_mode ) {
        return $match > 0;
    }
    // 'all'
    return $match === $total;
}

/**
 * Evaluate a single (data, operator, value) triple.
 *
 * Every case returns explicitly so future maintainers don't have to
 * reason about a shared `$result` fallthrough variable. Unknown
 * operators fall through to the post-switch `return false;` so the
 * rule fails safely instead of accidentally matching.
 *
 * @since 1.128.1 Refactored from a mixed return / $result pattern.
 * @since 1.128.2 String operands are trimmed before comparison;
 *                numeric operators short-circuit to false when either
 *                side isn't is_numeric().
 *
 * @param  mixed  $data     The form-side value.
 * @param  string $operator One of the keys in adfoin_get_cl_conditions().
 * @param  mixed  $value    The configured comparison value.
 * @return bool             True when the condition is satisfied.
 */
function adfoin_match_single_logic(  $data, $operator, $value  ) {
    // Trim scalar operands for the operators where leading/trailing
    // whitespace is almost always accidental (concatenated `{{first}}
    // {{last}}` style values, copy-pasted strings with stray spaces,
    // etc.). Empty / numeric / date / regex operators handle their own
    // normalization and are intentionally excluded here.
    $string_operators = array(
        'equal_to',
        'not_equal_to',
        'contains',
        'does_not_contain',
        'does_not_contains',
        'starts_with',
        'ends_with',
        'in_list',
        'not_in_list'
    );
    if ( in_array( $operator, $string_operators, true ) ) {
        if ( is_scalar( $data ) ) {
            $data = trim( (string) $data );
        }
        if ( is_scalar( $value ) ) {
            $value = trim( (string) $value );
        }
    }
    switch ( $operator ) {
        case 'equal_to':
            return $data == $value;
        case 'not_equal_to':
            return $data != $value;
        case 'greater_than':
            // is_numeric() guard prevents (float) silently coercing
            // non-numeric strings to 0, which would make "abc"
            // greater_than -1 evaluate to true today.
            if ( !is_numeric( $data ) || !is_numeric( $value ) ) {
                return false;
            }
            return (float) $data > (float) $value;
        case 'less_than':
            if ( !is_numeric( $data ) || !is_numeric( $value ) ) {
                return false;
            }
            return (float) $data < (float) $value;
        case 'contains':
            if ( '' === (string) $value ) {
                return true;
                // every string contains the empty string
            }
            return strpos( (string) $data, (string) $value ) !== false;
        case 'does_not_contain':
        case 'does_not_contains':
            // UI emits the singular form; the legacy alias is kept so
            // any saved integrations with the historical
            // 'does_not_contains' value keep working.
            if ( '' === (string) $value ) {
                return false;
            }
            return strpos( (string) $data, (string) $value ) === false;
        case 'starts_with':
            $length = strlen( (string) $value );
            if ( 0 === $length ) {
                return true;
            }
            return substr( (string) $data, 0, $length ) === (string) $value;
        case 'ends_with':
            $length = strlen( (string) $value );
            if ( 0 === $length ) {
                return true;
            }
            return substr( (string) $data, -$length ) === (string) $value;
        case 'is_empty':
            return null === $data || '' === $data || array() === $data;
        case 'is_not_empty':
            return !(null === $data || '' === $data || array() === $data);
        case 'in_list':
        case 'not_in_list':
            // Comma-separated list. Trimmed entries; case-insensitive compare so
            // "Yes" and "yes" treat as the same. Empty value = no list = no match.
            $items = array_filter( array_map( 'trim', explode( ',', (string) $value ) ), function ( $v ) {
                return $v !== '';
            } );
            if ( empty( $items ) ) {
                return 'not_in_list' === $operator;
            }
            $needle = strtolower( (string) $data );
            $matched = false;
            foreach ( $items as $item ) {
                if ( strtolower( $item ) === $needle ) {
                    $matched = true;
                    break;
                }
            }
            return ( 'in_list' === $operator ? $matched : !$matched );
        case 'matches_regex':
        case 'does_not_match_regex':
            // User-supplied regex. We wrap with `~...~` if the user didn't
            // include their own delimiters, so common patterns like `^foo$`
            // or `\d+` Just Work. Invalid patterns suppress warnings via
            // @-prefix and fail safely (no match).
            $pattern = (string) $value;
            if ( '' === $pattern ) {
                return 'does_not_match_regex' === $operator;
            }
            $first = $pattern[0];
            $delimited_chars = '/#~%@|';
            if ( strpos( $delimited_chars, $first ) === false || strrpos( $pattern, $first ) === 0 ) {
                // Not delimited (or only one occurrence of the would-be
                // delimiter). Wrap in `~` and assume no flags.
                $pattern = '~' . str_replace( '~', '\\~', $pattern ) . '~';
            }
            $result = @preg_match( $pattern, (string) $data );
            if ( false === $result ) {
                // Invalid regex — fail safely.
                return false;
            }
            return ( 'matches_regex' === $operator ? 1 === $result : 0 === $result );
        case 'between':
        case 'not_between':
            // Numeric range, inclusive. Value format: "min,max" (or "min|max").
            // Order is normalized so "100,5" works the same as "5,100". Empty
            // / single-side / non-numeric values fail safely so neither
            // `between` nor `not_between` gets a free pass for a malformed
            // configuration.
            $parts = preg_split( '/[,|]/', (string) $value );
            if ( count( $parts ) < 2 ) {
                return false;
            }
            $min_raw = trim( $parts[0] );
            $max_raw = trim( $parts[1] );
            if ( !is_numeric( $min_raw ) || !is_numeric( $max_raw ) || !is_numeric( $data ) ) {
                return false;
            }
            $min = (float) $min_raw;
            $max = (float) $max_raw;
            if ( $min > $max ) {
                $tmp = $min;
                $min = $max;
                $max = $tmp;
            }
            $num = (float) $data;
            $inside = $num >= $min && $num <= $max;
            return ( 'between' === $operator ? $inside : !$inside );
        case 'date_before':
        case 'date_after':
        case 'date_on':
        case 'date_not_on':
            // Both sides are parsed via strtotime, so absolute dates
            // (2026-04-29, 04/29/2026), datetimes, and relative strings
            // ("today", "now", "now -7 days", "first day of last month")
            // all work. If either side fails to parse, the rule fails
            // safely — never accidentally matches.
            $data_ts = strtotime( (string) $data );
            $value_ts = strtotime( (string) $value );
            if ( false === $data_ts || false === $value_ts ) {
                return false;
            }
            if ( 'date_before' === $operator ) {
                return $data_ts < $value_ts;
            }
            if ( 'date_after' === $operator ) {
                return $data_ts > $value_ts;
            }
            // 'on' / 'not_on' compare calendar day only, ignoring time.
            $data_day = date( 'Y-m-d', $data_ts );
            $value_day = date( 'Y-m-d', $value_ts );
            return ( 'date_on' === $operator ? $data_day === $value_day : $data_day !== $value_day );
    }
    // Unknown operator — fail safely.
    return false;
}

function adfoin_get_special_tags(  $cat = ''  ) {
    $utm_tags = array();
    $special_tags = array();
    if ( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
        $utm_tags['utm_source'] = __( 'UTM Source', 'advanced-form-integration' );
        $utm_tags['utm_medium'] = __( 'UTM Medium', 'advanced-form-integration' );
        $utm_tags['utm_term'] = __( 'UTM Term', 'advanced-form-integration' );
        $utm_tags['utm_content'] = __( 'UTM Content', 'advanced-form-integration' );
        $utm_tags['utm_campaign'] = __( 'UTM Campaign', 'advanced-form-integration' );
        $utm_tags['gclid'] = __( 'GCLID', 'advanced-form-integration' );
    }
    if ( 'utm' == $cat ) {
        return $utm_tags;
    }
    if ( '1' != get_option( 'adfoin_general_settings_st' ) ) {
        $special_tags['_submission_date'] = __( '_Submission_Date', 'advanced-form-integration' );
        $special_tags['_date'] = __( '_Date', 'advanced-form-integration' );
        $special_tags['_time'] = __( '_Time', 'advanced-form-integration' );
        $special_tags['_weekday'] = __( '_Weekday', 'advanced-form-integration' );
        $special_tags['_user_ip'] = __( '_User_IP', 'advanced-form-integration' );
        $special_tags['_user_agent'] = __( '_User_Agent', 'advanced-form-integration' );
        $special_tags['_site_title'] = __( '_Site_Title', 'advanced-form-integration' );
        $special_tags['_site_description'] = __( '_Site_Description', 'advanced-form-integration' );
        $special_tags['_site_url'] = __( '_Site_URL', 'advanced-form-integration' );
        $special_tags['_site_admin_email'] = __( '_Site_Admin_Email', 'advanced-form-integration' );
        $special_tags['_post_id'] = __( '_Post_ID', 'advanced-form-integration' );
        $special_tags['_post_name'] = __( '_Post_Name', 'advanced-form-integration' );
        $special_tags['_post_title'] = __( '_Post_Title', 'advanced-form-integration' );
        $special_tags['_post_url'] = __( '_Post_URL', 'advanced-form-integration' );
        $special_tags['_user_id'] = __( '_Logged_User_ID', 'advanced-form-integration' );
        $special_tags['_user_first_name'] = __( '_Admin_First_Name', 'advanced-form-integration' );
        $special_tags['_user_last_name'] = __( '_Admin_Last_Name', 'advanced-form-integration' );
        $special_tags['_user_display_name'] = __( '_Admin_Display_Name', 'advanced-form-integration' );
        $special_tags['_user_email'] = __( '_Admin_Email', 'advanced-form-integration' );
    }
    if ( 'st' == $cat ) {
        return $special_tags;
    }
    $combined = array_merge( $utm_tags, $special_tags );
    return $combined;
}

/**
 * Retrieve values for special tags associated with a post.
 *
 * This function retrieves values for special tags that can be used in various contexts, such as emails
 * or templates, related to a specific post. Special tags may include user-specific information, URL parameters,
 * or other dynamic data.
 *
 * @param WP_Post|null $post The post object for which to retrieve special tag values. Can be null.
 * @return array Associative array containing values for special tags. Keys represent special tag names,
 *               and values represent the corresponding tag values.
 */
function adfoin_get_special_tags_values(  $post  ) {
    $st_data = array();
    $utm_data = array();
    if ( '1' != get_option( 'adfoin_general_settings_st' ) ) {
        if ( !function_exists( 'wp_get_current_user' ) ) {
            include ABSPATH . "wp-includes/pluggable.php";
        }
        $current_user = wp_get_current_user();
        $special_tags = adfoin_get_special_tags( 'st' );
        if ( !empty( $special_tags ) ) {
            foreach ( $special_tags as $key => $value ) {
                $st_data[$key] = adfoin_get_single_special_tag_value( $key, $current_user, $post );
            }
        }
    }
    if ( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
        $utm_data = adfoin_capture_utm_and_url_values();
    }
    $combined = array_merge( $st_data, $utm_data );
    return $combined;
}

/**
 * Retrieves a value for a special tag based on the provided tag name.
 *
 * @param string       $tag           The name of the special tag to retrieve a value for.
 * @param WP_User|null $current_user  The current user object. Can be null.
 * @param WP_Post|null $post          The current post object. Can be null.
 * @return mixed|string|true The value associated with the special tag. Returns true if the tag is not matched.
 */
function adfoin_get_single_special_tag_value(  $tag, $current_user, $post  ) {
    switch ( $tag ) {
        case "submission_date":
            return date( "Y-m-d H:i:s" );
            break;
        case "_submission_date":
            return wp_date( 'Y-m-d H:i:s' );
            break;
        case "_date":
            return wp_date( get_option( 'date_format' ) );
            break;
        case "_time":
            return wp_date( get_option( 'time_format' ) );
            break;
        case "_weekday":
            return wp_date( 'l' );
            break;
        case "_user_ip":
            return adfoin_get_user_ip();
            break;
        case "_user_agent":
            return ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '' );
            break;
        case "_site_title":
            return get_bloginfo( 'name' );
            break;
        case "_site_description":
            return get_bloginfo( 'description' );
            break;
        case "_site_url":
            return get_bloginfo( 'url' );
            break;
        case "_site_admin_email":
            return get_bloginfo( 'admin_email' );
            break;
        case "_post_id":
            return ( isset( $post ) && is_object( $post ) ? $post->ID : "" );
            break;
        case "_post_name":
            return ( isset( $post ) && is_object( $post ) ? $post->post_name : "" );
            break;
        case "_post_title":
            return ( isset( $post ) && is_object( $post ) ? $post->post_title : "" );
            break;
        case "_post_url":
            return ( isset( $post ) && is_object( $post ) ? get_permalink( $post->ID ) : "" );
            break;
        case "_user_id":
            return ( isset( $current_user, $current_user->ID ) ? $current_user->ID : "" );
            break;
        case "_user_first_name":
            return ( isset( $current_user, $current_user->user_firstname ) ? $current_user->user_firstname : "" );
            break;
        case "_user_last_name":
            return ( isset( $current_user, $current_user->user_lastname ) ? $current_user->user_lastname : "" );
            break;
        case "_user_display_name":
            return ( isset( $current_user, $current_user->display_name ) ? $current_user->display_name : "" );
            break;
        case "_user_email":
            return ( isset( $current_user, $current_user->user_email ) ? $current_user->user_email : "" );
            break;
    }
    return true;
}

// Checks if a string is in valid md5 format
function adfoin_is_valid_md5(  $md5 = ''  ) {
    return preg_match( '/^[a-f0-9]{32}$/', $md5 );
}

// Get saved UTM params
function adfoin_capture_utm_and_url_values() {
    $fields = adfoin_get_special_tags( 'utm' );
    $cookie_fields = array();
    foreach ( $fields as $field => $title ) {
        if ( isset( $_GET[$field] ) && $_GET[$field] ) {
            $cookie_fields[$field] = htmlspecialchars( $_GET[$field], ENT_QUOTES, 'UTF-8' );
        } elseif ( isset( $_COOKIE[$field] ) && $_COOKIE[$field] ) {
            $cookie_fields[$field] = $_COOKIE[$field];
        } else {
            $cookie_fields[$field] = '';
        }
        $domain = ( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : '' );
        if ( strtolower( substr( $domain, 0, 4 ) ) == 'www.' ) {
            $domain = substr( $domain, 4 );
        }
        if ( substr( $domain, 0, 1 ) != '.' && $domain != 'localhost' ) {
            $domain = '.' . $domain;
        }
        setcookie(
            $field,
            $cookie_fields[$field],
            time() + 60 * 60 * 24 * 30,
            '/',
            $domain
        );
        $_COOKIE[$field] = $cookie_fields[$field];
    }
    return $cookie_fields;
}

/*
* The main remote request function for the Advanced Form Integration plugin
*/
function adfoin_remote_request(  $url, $args  ) {
    return wp_remote_request( $url, $args );
}

/**
 * Helper function to send an email
 *
 * @since 1.72.0
 *
 * @param array $args   Arguments passed to this function.
 *
 * @return bool         Whether the email contents were sent successfully.
 */
function adfoin_send_email(  $args = array()  ) {
    // Parse the email required args
    $email = wp_parse_args( $args, array(
        'from'        => '',
        'to'          => '',
        'cc'          => '',
        'bcc'         => '',
        'subject'     => '',
        'message'     => '',
        'headers'     => array(),
        'attachments' => array(),
    ) );
    /**
     * Filter available to override the email arguments before process them
     *
     * @since 1.72.0
     *
     * @param array     $email  The email arguments
     * @param array     $args   The original arguments received
     *
     * @return array
     */
    $email = apply_filters( 'adfoin_pre_email_args', $email, $args );
    $email['message'] = wpautop( $email['message'] );
    // Setup headers
    if ( !is_array( $email['headers'] ) ) {
        $email['headers'] = array();
    }
    if ( !empty( $email['from'] ) ) {
        $email['headers'][] = 'From: <' . $email['from'] . '>';
    }
    if ( !empty( $email['cc'] ) ) {
        $email['headers'][] = 'Cc: ' . $email['cc'];
    }
    if ( !empty( $email['bcc'] ) ) {
        $email['headers'][] = 'Bcc: ' . $email['bcc'];
    }
    $email['headers'][] = 'Content-Type: text/html; charset=' . get_option( 'blog_charset' );
    // Setup attachments
    // if( ! is_array( $email['attachments'] ) ) {
    //     $email['attachments'] = array();
    // }
    /**
     * Filter available to override the email arguments after process them
     *
     * @since 1.72.0
     *
     * @param array     $email  The email arguments
     * @param array     $args   The original arguments received
     *
     * @return array
     */
    $email = apply_filters( 'adfoin_email_args', $email, $args );
    add_filter( 'wp_mail_content_type', 'adfoin_set_html_content_type' );
    // Send the email
    $result = wp_mail(
        $email['to'],
        $email['subject'],
        $email['message'],
        $email['headers'],
        $email['attachments']
    );
    remove_filter( 'wp_mail_content_type', 'adfoin_set_html_content_type' );
    return $result;
}

/**
 * Function to set the mail content type
 *
 * @since 1.72.0
 *
 * @param string $content_type
 *
 * @return string
 */
function adfoin_set_html_content_type(  $content_type = 'text/html'  ) {
    return 'text/html';
}

/**
 * Recursively applies a callback function to each element in an array.
 *
 * This function applies the given callback function to each element in the array recursively.
 * If an element is an array, the function is applied to each element of that array as well.
 *
 * @param callable $callback The callback function to apply to each element.
 * @param array $array The array to apply the callback function to.
 * @return array The resulting array after applying the callback function to each element.
 */
function adfoin_array_map_recursive(  $callback, $array  ) {
    $func = function ( $item ) use(&$func, &$callback) {
        return ( is_array( $item ) ? array_map( $func, $item ) : call_user_func( $callback, $item ) );
    };
    return array_map( $func, $array );
}

function adfoin_get_file_path_from_url(  $file_url  ) {
    // Get the upload directory data (URL and path)
    $upload_dir = wp_get_upload_dir();
    // Remove the upload directory URL from the file URL to get the relative path
    $relative_path = str_replace( $upload_dir['baseurl'], '', $file_url );
    // Build the full file path by appending the relative path to the base path
    $file_path = $upload_dir['basedir'] . $relative_path;
    return $file_path;
}

function adfoin_get_post_object(  $post_id = null  ) {
    // 1. Check if a post ID is provided as a parameter
    if ( $post_id ) {
        $post_object = get_post( $post_id );
        if ( $post_object ) {
            return $post_object;
        }
    }
    // 2. Check if global $post is available
    global $post;
    if ( isset( $post ) && is_a( $post, 'WP_Post' ) ) {
        return $post;
    }
    // 3. Try to get the post object using get_the_ID()
    $post_id_from_context = get_the_ID();
    if ( $post_id_from_context ) {
        $post_object = get_post( $post_id_from_context );
        if ( $post_object ) {
            return $post_object;
        }
    }
    // 4. Try to get the post object using HTTP referrer
    if ( !empty( $_SERVER['HTTP_REFERER'] ) ) {
        $referrer_url = $_SERVER['HTTP_REFERER'];
        $referrer_id = url_to_postid( $referrer_url );
        if ( $referrer_id ) {
            $post_object = get_post( $referrer_id );
            if ( $post_object ) {
                return $post_object;
            }
        }
    }
    // Return null if no post object was found
    return null;
}

function adfoin_display_admin_header(  $id = '', $title = ''  ) {
    $nav = new Advanced_Form_Integration_Admin_Header();
    $nav->display( $id, $title );
}

function adfoin_platform_settings_template(
    $title,
    $key,
    $arguments,
    $instructions
) {
    //I want to return the html content
    ob_start();
    ?>
    <div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
                <div id="<?php 
    echo $key;
    ?>-auth">
                    <div id="api-key-management" v-cloak>
                        <api-key-management title="<?php 
    echo esc_attr( $title . ' Accounts' );
    ?>">
                            <?php 
    echo $arguments;
    ?>
                        </api-key-management>
                    </div>
                </div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
                <div class="afi-instructions-card">
                    <div class="afi-instructions-header">
                        <span class="dashicons dashicons-book"></span>
                        <?php 
    esc_html_e( 'Instructions', 'advanced-form-integration' );
    ?>
                    </div>
                    <div class="afi-instructions-body">
                        <div class="afi-instructions-list">
                            <?php 
    echo $instructions;
    ?>
                        </div>
                    </div>
                </div>
			</div>
		</div>
		<br class="clear">
	</div>
    <?php 
    return ob_get_clean();
}

function adfoin_verify_nonce() {
    // Authorization check FIRST
    if ( !current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ),
        ), 403 );
        return false;
    }
    // Then nonce check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        wp_send_json_error( __( 'Security check failed', 'advanced-form-integration' ) );
        return false;
    }
    return true;
}

function adfoin_check_conditional_logic(  $cl, $posted_data  ) {
    return isset( $cl['active'] ) && $cl['active'] === "yes" && !adfoin_match_conditional_logic( $cl, $posted_data );
}
