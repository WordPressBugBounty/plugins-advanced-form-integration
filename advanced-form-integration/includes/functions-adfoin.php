<?php

/**
 * Validate that a string is a syntactically-valid http(s) URL.
 *
 * Used to harden user-supplied webhook URLs (Slack, Zapier, generic Webhook
 * action) before they're handed to wp_remote_post(). Catches placeholders,
 * relative paths, and non-web schemes like javascript: / file: / data:
 * before they reach the HTTP transport layer.
 *
 * Pure syntactic check — does NOT verify reachability or DNS resolution.
 *
 * @since 1.131.2
 * @param string $url Candidate URL.
 * @return bool       True if $url is a non-empty http(s) URL with a host.
 */
function adfoin_is_valid_http_url(  $url  ) {
    if ( !is_string( $url ) || '' === $url ) {
        return false;
    }
    if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return false;
    }
    $parts = wp_parse_url( $url );
    if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
        return false;
    }
    return in_array( strtolower( $parts['scheme'] ), array('http', 'https'), true );
}

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
* Redirects the user to a given URL via inline JavaScript.
*
* All in-tree callers pass admin-built URLs, but we double-escape on the way
* out anyway: esc_url_raw() canonicalizes the URL string, esc_js() neutralizes
* any quote/newline characters that would otherwise break out of the string
* literal in the inline <script>. The result is safe to interpolate even if a
* future caller passes user-influenced input.
*
* @param string $url The URL to redirect the user to.
* @return void
*/
function advanced_form_integration_redirect(  $url  ) {
    // Enforce the same-origin allowlist wp_safe_redirect() uses, so a future
    // caller that passes user-influenced input cannot turn this into an open
    // redirect. Every in-tree caller passes an admin_url()-built URL, which
    // wp_validate_redirect() returns unchanged.
    $url = wp_validate_redirect( (string) $url, admin_url() );
    $safe_url = esc_js( esc_url_raw( $url ) );
    $string = '<script type="text/javascript">';
    $string .= 'window.location = "' . $safe_url . '"';
    $string .= '</script>';
    echo $string;
}

/**
 * Site-local "now" as a Unix-style timestamp.
 *
 * Behaviour-identical, WPCS-clean drop-in for the no-longer-recommended
 * current_time( 'timestamp' ): real epoch time shifted by the site's
 * configured UTC offset. Use only where a *local* wall-clock value is
 * needed — for a true UTC timestamp use time().
 *
 * @return int
 */
function adfoin_local_timestamp() {
    return time() + (int) (get_option( 'gmt_offset' ) * HOUR_IN_SECONDS);
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
function adfoin_get_action_providers() {
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
    $providers = adfoin_get_action_providers();
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
        $key = date( 'Y-m-d', strtotime( "-{$i} days", adfoin_local_timestamp() ) );
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
    // Memoized per request — the integrations list table reads the same window
    // twice per pageload (the "Failing" tab count and the per-row badges), and
    // each call is a date-range scan + DISTINCT.
    static $cache = array();
    if ( isset( $cache[$days] ) ) {
        return $cache[$days];
    }
    $log_table = $wpdb->prefix . 'adfoin_log';
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT integration_id\n             FROM {$log_table}\n             WHERE time >= DATE_SUB( NOW(), INTERVAL %d DAY )\n               AND ( response_code IS NULL\n                     OR response_code = ''\n                     OR SUBSTRING( response_code, 1, 1 ) != '2' )", $days ) );
    $result = ( empty( $ids ) ? array() : array_values( array_filter( array_map( 'intval', $ids ) ) ) );
    $cache[$days] = $result;
    return $result;
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
        'academylms'                 => 'Academy LMS',
        'acelle'                     => 'Acelle Mail',
        'activecampaign'             => 'ActiveCampaign',
        'acumbamail'                 => 'Acumbamail',
        'acuity'                     => 'Acuity Scheduling',
        'addcal'                     => 'AddCal',
        'appointmenthourbooking'     => 'Appointment Hour Booking',
        'affiliatewp'                => 'AffiliateWP',
        'agilecrm'                   => 'Agile CRM',
        'airtable'                   => 'Airtable',
        'mstodo'                     => 'Microsoft To Do',
        'apollo'                     => 'Apollo.io',
        'apptivo'                    => 'Apptivo',
        'asana'                      => 'Asana',
        'attio'                      => 'Attio CRM',
        'attentive'                  => 'Attentive',
        'audienceful'                => 'Audienceful',
        'airmeet'                    => 'Airmeet',
        'autopilot'                  => 'Autopilot',
        'aweber'                     => 'Aweber',
        'bbpress'                    => 'bbPress',
        'beehiiv'                    => 'beehiiv',
        'benchmark'                  => 'Benchmark',
        'bigin'                      => 'Bigin',
        'bigmarker'                  => 'BigMarker',
        'bombbomb'                   => 'BombBomb',
        'braze'                      => 'Braze',
        'brevo'                      => 'Brevo',
        'buddyboss'                  => 'BuddyBoss',
        'fluentbooking'              => 'Fluent Booking',
        'cakemail'                   => 'Cakemail',
        'calcom'                     => 'Cal.com',
        'calendly'                   => 'Calendly',
        'campaigner'                 => 'Campaigner',
        'campaignmonitor'            => 'Campaign Monitor',
        'charitable'                 => 'Charitable',
        'campayn'                    => 'Campayn',
        'capsulecrm'                 => 'Capsule CRM',
        'civicrm'                    => 'CiviCRM',
        'cleverreach'                => 'CleverReach',
        'clickup'                    => 'Clickup',
        'clinchpad'                  => 'ClinchPad',
        'close'                      => 'Close',
        'companyhub'                 => 'CompanyHub',
        'constantcontact'            => 'Constant Contact',
        'convertkit'                 => 'ConvertKit',
        'copernica'                  => 'Copernica',
        'copper'                     => 'Copper',
        'curated'                    => 'Curated',
        'customerio'                 => 'Customer.io',
        'knack'                      => 'Knack',
        'demio'                      => 'Demio',
        'directiq'                   => 'DirectIQ',
        'discord'                    => 'Discord',
        'doppler'                    => 'Doppler',
        'dotdigital'                 => 'Dotdigital',
        'drip'                       => 'Drip',
        'dropbox'                    => 'Dropbox',
        'dataverse'                  => 'Dataverse (Generic)',
        'dynamics365'                => 'Dynamics 365 CRM',
        'dynamics365customerservice' => 'Dynamics 365 Customer Service',
        'dynamics365fieldservice'    => 'Dynamics 365 Field Service',
        'dynamics365marketing'       => 'Dynamics 365 Marketing',
        'dynamics365sales'           => 'Dynamics 365 Sales',
        'easysendy'                  => 'EasySendy',
        'elasticemail'               => 'Elastic Email',
        'emailchef'                  => 'Emailchef',
        'emailit'                    => 'Emailit',
        'emailoctopus'               => 'EmailOctopus',
        'encharge'                   => 'Encharge',
        'engagebay'                  => 'EngageBay',
        'enormail'                   => 'Enormail',
        'eventsmanager'              => 'Events Manager',
        'everwebinar'                => 'EverWebinar',
        'flodesk'                    => 'Flodesk',
        'flowlu'                     => 'Flowlu',
        'fluentcrm'                  => 'Fluent CRM',
        'fluentaffiliate'            => 'Fluent Affiliate',
        'fluentboards'               => 'Fluent Boards',
        'fluentcommunity'            => 'Fluent Community',
        'gamipress'                  => 'GamiPress',
        'fluentsupport'              => 'Fluent Support',
        'followupboss'               => 'FollowUpBoss',
        'freshdesk'                  => 'Freshdesk',
        'freshsales'                 => 'Freshworks CRM',
        'getresponse'                => 'GetResponse',
        'givewp'                     => 'GiveWP',
        'gravityformsac'             => 'Gravity Forms',
        'wpformsac'                  => 'WPForms',
        'googlecalendar'             => 'Google Calendar',
        'googledrive'                => 'Google Drive',
        'googlesheets'               => 'Google Sheets',
        'highlevel'                  => 'HighLevel',
        'hubspot'                    => 'Hubspot',
        'icontact'                   => 'iContact',
        'insightly'                  => 'Insightly CRM',
        'intercom'                   => 'Intercom',
        'instantly'                  => 'Instantly',
        'keila'                      => 'Keila',
        'kit'                        => 'Kit',
        'klaviyo'                    => 'Klaviyo',
        'laposta'                    => 'Laposta',
        'lemlist'                    => 'lemlist',
        'lacrm'                      => 'Less Annoying CRM',
        'liondesk'                   => 'LionDesk',
        'livestorm'                  => 'Livestorm',
        'loops'                      => 'Loops',
        'mailbluster'                => 'MailBluster',
        'mailchimp'                  => 'Mailchimp',
        'mailcoach'                  => 'Mailcoach',
        'maileon'                    => 'Maileon',
        'mailercloud'                => 'Mailercloud',
        'mailerlite'                 => 'MailerLite Classic',
        'mailerlite2'                => 'MailerLite',
        'mailify'                    => 'Mailify',
        'mailjet'                    => 'Mailjet',
        'mailmint'                   => 'Mail Mint',
        'mailmodo'                   => 'Mailmodo',
        'mailpoet'                   => 'MailPoet',
        'mailrelay'                  => 'MailRelay',
        'mailster'                   => 'Mailster',
        'mailup'                     => 'MailUp',
        'mailwizz'                   => 'MailWizz',
        'mautic'                     => 'Mautic',
        'monday'                     => 'Monday.com',
        'moosend'                    => 'Moosend',
        'msteams'                    => 'Microsoft Teams',
        'newsletter'                 => 'Newsletter',
        'nimble'                     => 'Nimble',
        'nutshell'                   => 'Nutshell CRM',
        'omnisend'                   => 'Omnisend',
        'onehash'                    => 'Onehash',
        'autopilotnew'               => 'Ortto',
        'pabbly'                     => 'Pabbly',
        'pipedrive'                  => 'Pipedrive',
        'pushover'                   => 'Pushover',
        'quickbase'                  => 'Quickbase',
        'ragic'                      => 'Ragic',
        'rapidmail'                  => 'Rapidmail',
        'resend'                     => 'Resend',
        'robly'                      => 'Robly',
        'salesflare'                 => 'Salesflare',
        'salesforce'                 => 'Salesforce',
        'saleshandy'                 => 'SalesHandy',
        'salesrocks'                 => 'Sales Rocks',
        'salesmate'                  => 'Salesmate',
        'sarbacane'                  => 'Sarbacane',
        'selzy'                      => 'Selzy',
        'sender'                     => 'Sender',
        'sendfox'                    => 'Sendfox',
        'sendlane'                   => 'Sendlane',
        'sendpulse'                  => 'Sendpulse',
        'sendx'                      => 'SendX',
        'sendy'                      => 'Sendy',
        'slack'                      => 'Slack',
        'smartlead'                  => 'Smartlead',
        'smartrmail'                 => 'SmartrMail',
        'smartsheet'                 => 'Smartsheet',
        'snovio'                     => 'Snov.io',
        'suitedash'                  => 'SuiteDash',
        'systemeio'                  => 'Systeme.io',
        'trello'                     => 'Trello',
        'twilio'                     => 'Twilio',
        'verticalresponse'           => 'Vertical Response',
        'vtiger'                     => 'Vtiger CRM',
        'wealthbox'                  => 'Wealthbox',
        'webhook'                    => 'Webhook',
        'webinarjam'                 => 'WebinarJam',
        'woodpecker'                 => 'Woodpecker.co',
        'wordpress'                  => 'WordPress',
        'zapier'                     => 'Zapier',
        'zendesk'                    => 'Zendesk Support',
        'zendesksell'                => 'Zendesk Sell',
        'zohopeople'                 => 'Zoho People',
        'zohobooks'                  => 'Zoho Books',
        'zohocampaigns'              => 'Zoho Campaigns',
        'zohocrm'                    => 'Zoho CRM',
        'zohodesk'                   => 'Zoho Desk',
        'zohoma'                     => 'Zoho Marketing Automation',
        'zohosheet'                  => 'Zoho Sheet',
        'zoomwebinar'                => 'Zoom Webinar',
    );
}

/**
* Cache key for the merged platform-settings result.
*
* Bumped manually when the cached payload's shape changes (so old serialized
* values from a prior plugin version are ignored without needing a manual
* purge).
*/
if ( !defined( 'ADFOIN_PLATFORM_SETTINGS_CACHE_KEY' ) ) {
    define( 'ADFOIN_PLATFORM_SETTINGS_CACHE_KEY', 'adfoin_action_platform_settings_v1' );
}
/**
*
* Retrieves the action platform settings.
*
* The merged settings array (option-stored toggles + active integrations'
* providers) is memoized within the request and cached in a transient
* across requests. The cache is invalidated by
* adfoin_clear_action_platform_settings_cache() — wired to the
* `adfoin_general_settings_platforms` option's add/update hooks, and called
* directly from the integration save/update/toggle/delete paths.
*
* @global object $wpdb WordPress database access object.
*
* @return array The action platform settings.
*/
function adfoin_get_action_platform_settings() {
    static $memo = null;
    if ( null !== $memo ) {
        return $memo;
    }
    $cached = get_transient( ADFOIN_PLATFORM_SETTINGS_CACHE_KEY );
    if ( is_array( $cached ) ) {
        $memo = $cached;
        return $memo;
    }
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
    if ( !is_array( $settings ) ) {
        $settings = array();
    }
    set_transient( ADFOIN_PLATFORM_SETTINGS_CACHE_KEY, $settings, HOUR_IN_SECONDS );
    $memo = $settings;
    return $memo;
}

/**
 * Invalidate the cached action-platform settings.
 *
 * Call this any time the `adfoin_integration` table is mutated in a way that
 * could change which platforms have an active integration, or when the
 * `adfoin_general_settings_platforms` option changes.
 *
 * @return void
 */
function adfoin_clear_action_platform_settings_cache() {
    delete_transient( ADFOIN_PLATFORM_SETTINGS_CACHE_KEY );
}

add_action( 'add_option_adfoin_general_settings_platforms', 'adfoin_clear_action_platform_settings_cache' );
add_action( 'update_option_adfoin_general_settings_platforms', 'adfoin_clear_action_platform_settings_cache' );
add_action( 'delete_option_adfoin_general_settings_platforms', 'adfoin_clear_action_platform_settings_cache' );
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
    // Memoize the expensive scandir part within a request — the platforms
    // and pro directories don't change during a single pageload, but the
    // filter is left out of the cache so filters added between calls still
    // apply (e.g., a platform that hooks 'adfoin_platform_scripts' on its
    // own bootstrap path later in the request).
    static $scanned_map = null;
    if ( null === $scanned_map ) {
        $scanned_map = array();
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
                        $scanned_map[$entry] = $platforms_url . '/' . $entry . '/' . $entry . '-component.js';
                    }
                }
            }
        }
    }
    return apply_filters( 'adfoin_platform_scripts', $scanned_map );
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
    if ( 'general' !== $current_tab ) {
        return;
    }
    $nonce = wp_create_nonce( 'adfoin_general_settings' );
    $reset_nonce = wp_create_nonce( 'adfoin_reset_general_settings' );
    $log_settings = get_option( 'adfoin_general_settings_log', '' );
    $log_retention = absint( get_option( 'adfoin_general_settings_log_retention', 0 ) );
    $error_email = get_option( 'adfoin_general_settings_error_email', '' );
    $st_settings = get_option( 'adfoin_general_settings_st', '' );
    $utm_settings = get_option( 'adfoin_general_settings_utm', '' );
    $job_queue = get_option( 'adfoin_general_settings_job_queue', '' );
    $job_queue_stats = adfoin_get_job_queue_stats();
    $platform_settings = adfoin_get_action_platform_settings();
    $platforms = adfoin_get_action_platform_list();
    include ADVANCED_FORM_INTEGRATION_VIEWS . '/partials/general-settings.php';
}

add_action(
    'admin_post_adfoin_save_general_settings',
    'adfoin_save_general_settings',
    10,
    0
);
function adfoin_save_general_settings() {
    // Capability check — admin-post handlers in this plugin use wp_die() (matches adfoin_clear_all_logs).
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to do this.', 'advanced-form-integration' ) );
    }
    $nonce = ( isset( $_POST['_nonce'] ) ? wp_unslash( $_POST['_nonce'] ) : '' );
    if ( !wp_verify_nonce( $nonce, 'adfoin_general_settings' ) ) {
        wp_die( esc_html__( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $log_settings = ( isset( $_POST['adfoin_disable_log'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_disable_log'] ) ) : '' );
    $log_retention = ( isset( $_POST['adfoin_log_retention'] ) ? absint( wp_unslash( $_POST['adfoin_log_retention'] ) ) : 0 );
    $error_email = ( isset( $_POST['adfoin_error_email'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_error_email'] ) ) : '' );
    $st_settings = ( isset( $_POST['adfoin_disable_st'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_disable_st'] ) ) : '' );
    $utm_settings = ( isset( $_POST['adfoin_enable_utm'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_enable_utm'] ) ) : '' );
    $job_queue = ( isset( $_POST['adfoin_job_queue'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_job_queue'] ) ) : '' );
    $default_platforms = array_fill_keys( array_keys( adfoin_get_action_platform_list() ), false );
    $activated_platforms = ( isset( $_POST['platforms'] ) ? adfoin_sanitize_text_or_array_field( $_POST['platforms'] ) : array() );
    $all_platforms = array_merge( $default_platforms, array_fill_keys( array_keys( array_intersect_key( $activated_platforms, $default_platforms ) ), true ) );
    // Save
    update_option( 'adfoin_general_settings_platforms', $all_platforms );
    update_option( 'adfoin_general_settings_log', $log_settings );
    update_option( 'adfoin_general_settings_log_retention', $log_retention );
    update_option( 'adfoin_general_settings_error_email', $error_email );
    update_option( 'adfoin_general_settings_st', $st_settings );
    update_option( 'adfoin_general_settings_utm', $utm_settings );
    update_option( 'adfoin_general_settings_job_queue', $job_queue );
    adfoin_sync_log_cleanup_schedule( $log_retention );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&settings-updated=true' );
}

/**
 * Reconcile the daily log-cleanup recurring action with the retention setting.
 *
 * Scheduled when retention > 0, unscheduled when 0. Called from the save handler;
 * if the recurring action is ever externally cleared, hitting Save again restores it.
 *
 * @param int $days Number of days to retain log rows. 0 disables auto-cleanup.
 */
function adfoin_sync_log_cleanup_schedule(  $days  ) {
    if ( !function_exists( 'as_schedule_recurring_action' ) ) {
        return;
    }
    if ( $days > 0 ) {
        if ( !as_has_scheduled_action( 'adfoin_log_cleanup' ) ) {
            as_schedule_recurring_action(
                time() + DAY_IN_SECONDS,
                DAY_IN_SECONDS,
                'adfoin_log_cleanup',
                array(),
                'adfoin'
            );
        }
    } else {
        as_unschedule_all_actions( 'adfoin_log_cleanup' );
    }
}

add_action( 'adfoin_log_cleanup', 'adfoin_run_log_cleanup' );
/**
 * Daily Action Scheduler hook — delete log rows older than the retention window.
 */
function adfoin_run_log_cleanup() {
    $days = absint( get_option( 'adfoin_general_settings_log_retention', 0 ) );
    if ( $days <= 0 ) {
        return;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'adfoin_log';
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE time < (NOW() - INTERVAL %d DAY)", $days ) );
}

/**
 * Quick health summary of AFI's Action Scheduler queue, scoped to hooks of the
 * shape `adfoin_<slug>_job_queue` (the dispatch hooks fired by
 * adfoin_dispatch_integrations()). Used to render the Settings → General queue
 * health card under the "Enable Job Queue" toggle so users can spot a stuck
 * cron / backed-up queue without leaving the Settings screen.
 *
 * Returns null when Action Scheduler isn't loaded or its actions table doesn't
 * exist — render code uses that as a "hide the card" signal.
 *
 * @return array{pending:int,failed:int,last_run:?string}|null
 */
function adfoin_get_job_queue_stats() {
    if ( !function_exists( 'as_enqueue_async_action' ) ) {
        return null;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'actionscheduler_actions';
    // Defense: if the AS schema isn't installed (rare upgrade scenarios), bail.
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $exists !== $table ) {
        return null;
    }
    // Match every dispatch hook AFI emits via as_enqueue_async_action.
    // `\_` escapes the underscores so MySQL LIKE treats them as literals
    // (otherwise `_` matches any single char and the pattern would over-match).
    $hook_pattern = 'adfoin\\_%\\_job\\_queue';
    $pending = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s AND hook LIKE %s", 'pending', $hook_pattern ) );
    $failed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s AND hook LIKE %s", 'failed', $hook_pattern ) );
    $last_run = $wpdb->get_var( $wpdb->prepare( "SELECT last_attempt_gmt FROM {$table} WHERE status = %s AND hook LIKE %s ORDER BY last_attempt_gmt DESC LIMIT 1", 'complete', $hook_pattern ) );
    return array(
        'pending'  => $pending,
        'failed'   => $failed,
        'last_run' => $last_run,
    );
}

add_action( 'wp_ajax_adfoin_send_test_email', 'adfoin_send_test_email_ajax' );
/**
 * AJAX handler — send a test of the integration error email so users can verify
 * deliverability without waiting for a real failure.
 */
function adfoin_send_test_email_ajax() {
    adfoin_require_manage_options();
    if ( !wp_verify_nonce( ( isset( $_POST['_nonce'] ) ? wp_unslash( $_POST['_nonce'] ) : '' ), 'adfoin_send_test_email' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Security check failed', 'advanced-form-integration' ),
        ), 403 );
    }
    $admin_email = get_option( 'admin_email' );
    $subject = __( '[TEST] Error with AFI Integration', 'advanced-form-integration' );
    $message = sprintf( 
        /* translators: %s: site URL */
        __( 'This is a test email from Advanced Form Integration on %s. If you received this, the Send Error Email setting is wired up correctly.', 'advanced-form-integration' ),
        get_bloginfo( 'url' )
     );
    $sent = wp_mail( $admin_email, $subject, $message );
    if ( $sent ) {
        wp_send_json_success( array(
            'message' => sprintf( 
                /* translators: %s: admin email address */
                __( 'Test email sent to %s.', 'advanced-form-integration' ),
                $admin_email
             ),
        ) );
    }
    wp_send_json_error( array(
        'message' => __( 'Failed to send test email. Check your site\'s email configuration.', 'advanced-form-integration' ),
    ) );
}

add_action( 'admin_post_adfoin_reset_general_settings', 'adfoin_reset_general_settings' );
/**
 * admin-post handler — reset the five toggle options + log retention to defaults.
 * Platform activation map is NOT touched (that's a deliberate scope choice; users
 * have far more invested in which platforms they've turned on).
 */
function adfoin_reset_general_settings() {
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to do this.', 'advanced-form-integration' ) );
    }
    $nonce = ( isset( $_POST['_nonce'] ) ? wp_unslash( $_POST['_nonce'] ) : '' );
    if ( !wp_verify_nonce( $nonce, 'adfoin_reset_general_settings' ) ) {
        wp_die( esc_html__( 'Security check Failed', 'advanced-form-integration' ) );
    }
    delete_option( 'adfoin_general_settings_log' );
    delete_option( 'adfoin_general_settings_log_retention' );
    delete_option( 'adfoin_general_settings_error_email' );
    delete_option( 'adfoin_general_settings_st' );
    delete_option( 'adfoin_general_settings_utm' );
    delete_option( 'adfoin_general_settings_job_queue' );
    adfoin_sync_log_cleanup_schedule( 0 );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&settings-updated=reset' );
}

/**
 * Dispatch a batch of integration records for a single form submission.
 *
 * Centralizes the Job Queue toggle logic that was previously copy-pasted across
 * ~47 trigger files. Reads the global toggle once and routes each record to
 * either Action Scheduler (async) or `call_user_func` (sync).
 *
 * Adds two safety nets the inline blocks didn't have:
 *   1. If `adfoin_{slug}_job_queue` has no listeners (a future platform forgets
 *      to register one, like the Tier-A Freshdesk/Intercom/Zendesk Sell bugs),
 *      fall back to sync rather than silently dropping the submission.
 *   2. If `adfoin_{slug}_send_data` doesn't exist (broken plugin install),
 *      skip the record instead of emitting a PHP warning.
 *
 * The async payload shape `array( 'data' => array( 'record' => ..., 'posted_data' => ... ) )`
 * is preserved exactly to keep every existing platform's `_job_queue` handler working.
 *
 * @param array $saved_records Integration records matched for this submission.
 * @param array $posted_data   The form's posted-data array.
 * @return void
 */
function adfoin_dispatch_integrations(  $saved_records, $posted_data  ) {
    if ( empty( $saved_records ) || !is_array( $saved_records ) ) {
        return;
    }
    $job_queue_enabled = (bool) get_option( 'adfoin_general_settings_job_queue', '' );
    $can_queue = $job_queue_enabled && function_exists( 'as_enqueue_async_action' );
    foreach ( $saved_records as $record ) {
        if ( empty( $record['action_provider'] ) ) {
            continue;
        }
        $action_provider = $record['action_provider'];
        $sync_fn = "adfoin_{$action_provider}_send_data";
        $hook = "adfoin_{$action_provider}_job_queue";
        /**
         * Whether this specific record should run via Action Scheduler.
         * Pro extensions or third parties can force sync for platforms that
         * need synchronous response handling (e.g. webhook-style integrations
         * that mutate the parent form's response).
         *
         * @param bool   $should_queue Default: the global Job Queue toggle.
         * @param array  $record       The integration record.
         * @param array  $posted_data  Form-submission payload.
         */
        $should_queue = (bool) apply_filters(
            'adfoin_should_queue',
            $can_queue,
            $record,
            $posted_data
        );
        if ( $should_queue && has_action( $hook ) ) {
            $action_id = as_enqueue_async_action( $hook, array(
                'data' => array(
                    'record'      => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
            // Action Scheduler silently REJECTS a job whose JSON-encoded args
            // exceed its column limit (8000 chars for the DB store): it throws
            // internally, the as_* wrapper swallows the exception and returns 0,
            // so the integration would just vanish — no run, no log. A real
            // WooCommerce order easily exceeds this (full items JSON, taxes,
            // order-attribution meta, merged line-item arrays, ~80+ fields). When
            // queuing fails for ANY reason (oversized payload, AS not ready),
            // fall through to the synchronous path so the submission is never
            // lost. A truthy action id means it was queued successfully.
            if ( $action_id ) {
                continue;
            }
        }
        // Sync path (and async-fallback when no listeners are registered for
        // $hook, or when the queue rejected the job above).
        if ( function_exists( $sync_fn ) ) {
            call_user_func( $sync_fn, $record, $posted_data );
        }
    }
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
    // Bridge canonical `_form_id` / `_form_name` tags to the legacy `form_id` /
    // `form_name` keys triggers have always pushed into $posted_data. Lets users
    // pick {{_form_id}} from the dropdown without having to migrate every trigger
    // to push the underscored key directly.
    if ( is_array( $posted_data ) ) {
        $aliases = array(
            '_form_id'   => 'form_id',
            '_form_name' => 'form_name',
        );
        foreach ( $aliases as $tag => $legacy_key ) {
            if ( !array_key_exists( $tag, $posted_data ) && !empty( $posted_data[$legacy_key] ) ) {
                $posted_data[$tag] = $posted_data[$legacy_key];
            }
        }
    }
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
                $value = wp_json_encode( $value );
            } else {
                $value = @implode( ",", $value );
            }
        }
        if ( $value ) {
            $field = str_replace( '{{' . $key . '}}', $value, $field );
        }
    }
    $field = preg_replace( "/{{.+?}}/", "", $field );
    /**
     * Whether to run do_shortcode() on the parsed field value before sending
     * to the integration. Defaults to true to preserve existing behavior.
     *
     * Opt out (recommended for sites that don't deliberately use shortcodes
     * in tag templates) to avoid two real footguns:
     *   1. User-submitted form values that happen to contain `[X]` patterns
     *      will be shortcode-evaluated, possibly executing arbitrary
     *      side-effecting shortcodes registered by other plugins.
     *   2. In Action Scheduler / cron context there's no live request, so
     *      shortcodes that depend on `is_singular()`, `$_POST`, or current
     *      query state may error or return nonsense.
     *
     *     add_filter( 'adfoin_parse_shortcodes', '__return_false' );
     *
     * @param bool   $run_shortcodes Default true.
     * @param string $field          The parsed field value (after tag substitution).
     * @param array  $posted_data    The full posted-data context.
     */
    $run_shortcodes = apply_filters(
        'adfoin_parse_shortcodes',
        true,
        $field,
        $posted_data
    );
    if ( $run_shortcodes && strpos( $field, '[' ) !== false && strpos( $field, ']' ) !== false ) {
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
    $log_settings = get_option( 'adfoin_general_settings_log', '' );
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
    /**
     * Allow sites to redact PII before request payloads hit the log table.
     * Hook here if you store contact data in your forms and need to keep
     * the integration log GDPR-friendly. Receives the args array and the
     * platform-side URL; return a modified args array.
     *
     * @param array  $args   Request args (body, headers, etc.).
     * @param string $url    Outgoing URL.
     * @param array  $record Integration record (for context).
     */
    $args = apply_filters(
        'adfoin_log_request_args',
        $args,
        $url,
        $record
    );
    $request_data = wp_json_encode( array(
        'url'  => $url,
        'args' => $args,
    ) );
    if ( is_wp_error( $return ) ) {
        $data = array(
            'response_code'    => 0,
            'response_message' => 'WP Error',
            'integration_id'   => $record["id"],
            'request_data'     => $request_data,
            'response_data'    => wp_json_encode( $return ),
        );
    } else {
        $response_body = $return["body"];
        if ( is_array( $response_body ) || is_object( $response_body ) ) {
            $response_body = wp_json_encode( $response_body );
        }
        if ( is_string( $response_body ) ) {
            $trimmed_body = trim( $response_body );
            if ( '' !== $trimmed_body ) {
                // Skip tag-stripping for an obviously-JSON body — running
                // wp_strip_all_tags() over a large JSON payload is pure
                // overhead, and JSON has no markup that needs scrubbing.
                $first = $trimmed_body[0];
                if ( '{' !== $first && '[' !== $first && '"' !== $first ) {
                    $stripped = wp_strip_all_tags( $trimmed_body, true );
                    if ( $stripped !== $trimmed_body ) {
                        $trimmed_body = preg_replace( '/\\s+/', ' ', $stripped );
                    }
                }
                $response_body = $trimmed_body;
            }
        } else {
            $response_body = '';
        }
        /**
         * Allow sites to redact PII in the response body before it hits the
         * log table. Receives the (string) body — typically JSON — and the
         * integration record; return the modified body string.
         *
         * @param string $response_body Body content about to be stored.
         * @param array  $record        Integration record (for context).
         * @param string $url           Outgoing URL.
         */
        $response_body = apply_filters(
            'adfoin_log_response_body',
            $response_body,
            $record,
            $url
        );
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
        $effective_log_id = (int) $log_id;
    } else {
        $log->insert( $data );
        global $wpdb;
        $effective_log_id = (int) $wpdb->insert_id;
    }
    // Fire the error-email action for both transport failures (WP_Error) and
    // non-2xx HTTP responses. Previously only WP_Error fired the email, which
    // meant 401/403/422/5xx — the cases users actually want notifications for —
    // were silently logged but never alerted on.
    if ( get_option( 'adfoin_general_settings_error_email', '' ) ) {
        $response_code = ( is_wp_error( $return ) ? 0 : (( isset( $return['response']['code'] ) ? (int) $return['response']['code'] : 0 )) );
        $is_failure = is_wp_error( $return ) || $response_code < 200 || $response_code >= 300;
        if ( $is_failure ) {
            $context = array(
                'log_id'           => $effective_log_id,
                'response_code'    => $response_code,
                'response_message' => ( is_wp_error( $return ) ? $return->get_error_message() : (( isset( $return['response']['message'] ) ? $return['response']['message'] : '' )) ),
            );
            do_action(
                'adfoin_send_api_error_email',
                $return,
                $record,
                $request_data,
                $context
            );
        }
    }
    return;
}

add_action(
    'adfoin_send_api_error_email',
    'adfoin_send_api_error_email',
    10,
    4
);
/**
 * Send an email notification for an API error.
 *
 * Composes a contextual email with the integration title, platform, form name,
 * HTTP status, and a direct link to the log entry. Throttles per
 * (integration_id, response_code) to avoid spamming the inbox when one broken
 * credential causes hundreds of submissions to fail.
 *
 * @param array  $return       The response from wp_remote_post (WP_Error or array).
 * @param array  $record       The integration record (id, title, action_provider, form_name, ...).
 * @param string $request_data JSON-encoded request payload (kept for back-compat / hookers).
 * @param array  $context      Extra fields: log_id, response_code, response_message.
 * @return void
 */
function adfoin_send_api_error_email(
    $return,
    $record,
    $request_data,
    $context = array()
) {
    $context = array_merge( array(
        'log_id'           => 0,
        'response_code'    => 0,
        'response_message' => '',
    ), (array) $context );
    $integration_id = ( isset( $record['id'] ) ? (int) $record['id'] : 0 );
    $integration_title = ( isset( $record['title'] ) ? (string) $record['title'] : '' );
    $action_provider = ( isset( $record['action_provider'] ) ? (string) $record['action_provider'] : '' );
    $form_name = ( isset( $record['form_name'] ) ? (string) $record['form_name'] : '' );
    $response_code = (int) $context['response_code'];
    $response_message = (string) $context['response_message'];
    // Throttle: at most one email per (integration_id, response_code) per hour.
    // A broken credential firing on every form submission must not spam the inbox.
    $throttle_key = 'adfoin_err_email_' . md5( $integration_id . '|' . $response_code );
    if ( get_transient( $throttle_key ) ) {
        return;
    }
    set_transient( $throttle_key, 1, HOUR_IN_SECONDS );
    $admin_email = get_option( 'admin_email' );
    $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $site_url = get_bloginfo( 'url' );
    $code_label = ( $response_code > 0 ? (string) $response_code : __( 'Transport error', 'advanced-form-integration' ) );
    $log_url = ( $context['log_id'] ? admin_url( 'admin.php?page=advanced-form-integration-log&action=view&id=' . $context['log_id'] ) : admin_url( 'admin.php?page=advanced-form-integration-log' ) );
    $integration_label = ( '' !== $integration_title ? sprintf( '%s (#%d)', $integration_title, $integration_id ) : '#' . $integration_id );
    $subject = sprintf(
        /* translators: 1: site name, 2: action provider slug, 3: HTTP status code or "Transport error" */
        __( '[%1$s] AFI integration error: %2$s (%3$s)', 'advanced-form-integration' ),
        $site_name,
        ( '' !== $action_provider ? $action_provider : __( 'integration', 'advanced-form-integration' ) ),
        $code_label
    );
    $lines = array();
    $lines[] = sprintf( 
        /* translators: %s: site URL */
        __( 'An integration on %s failed.', 'advanced-form-integration' ),
        $site_url
     );
    $lines[] = '';
    $lines[] = sprintf( __( 'Integration: %s', 'advanced-form-integration' ), $integration_label );
    $lines[] = sprintf( __( 'Platform: %s', 'advanced-form-integration' ), ( '' !== $action_provider ? $action_provider : '—' ) );
    $lines[] = sprintf( __( 'Form: %s', 'advanced-form-integration' ), ( '' !== $form_name ? $form_name : '—' ) );
    $lines[] = sprintf( 
        /* translators: 1: HTTP status code or "Transport error", 2: response message */
        __( 'Status: %1$s %2$s', 'advanced-form-integration' ),
        $code_label,
        $response_message
     );
    $lines[] = '';
    $lines[] = sprintf( __( 'Log entry: %s', 'advanced-form-integration' ), $log_url );
    $message = implode( "\n", $lines );
    /**
     * Override the recipient for the integration-error email.
     *
     * @param string $to       Default: site admin_email.
     * @param array  $context  log_id, response_code, response_message.
     * @param array  $record   The integration record.
     * @param mixed  $return   The wp_remote_post response (WP_Error or array).
     */
    $to = apply_filters(
        'adfoin_error_email_to',
        $admin_email,
        $context,
        $record,
        $return
    );
    $subject = apply_filters(
        'adfoin_error_email_subject',
        $subject,
        $context,
        $record,
        $return
    );
    $message = apply_filters(
        'adfoin_error_email_message',
        $message,
        $context,
        $record,
        $return
    );
    wp_mail( $to, $subject, $message );
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
        $utm_tags['gclid'] = __( 'GCLID (Google Ads)', 'advanced-form-integration' );
        $utm_tags['gbraid'] = __( 'GBRAID (Google Ads, iOS app)', 'advanced-form-integration' );
        $utm_tags['wbraid'] = __( 'WBRAID (Google Ads, web)', 'advanced-form-integration' );
        $utm_tags['fbclid'] = __( 'FBCLID (Meta Ads)', 'advanced-form-integration' );
        $utm_tags['msclkid'] = __( 'MSCLKID (Microsoft Ads)', 'advanced-form-integration' );
        $utm_tags['ttclid'] = __( 'TTCLID (TikTok Ads)', 'advanced-form-integration' );
        $utm_tags['li_fat_id'] = __( 'LI_FAT_ID (LinkedIn Ads)', 'advanced-form-integration' );
        $utm_tags['dclid'] = __( 'DCLID (Google Display)', 'advanced-form-integration' );
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
        $special_tags['_form_id'] = __( '_Form_ID', 'advanced-form-integration' );
        $special_tags['_form_name'] = __( '_Form_Name', 'advanced-form-integration' );
        // The four "logged-in user" tags below resolve via wp_get_current_user().
        // They were previously labeled `_Admin_*` which was misleading — for
        // public form submissions the current user is the visitor (often anon),
        // not necessarily an admin. Renamed to match `_user_id`'s convention.
        $special_tags['_user_id'] = __( '_Logged_User_ID', 'advanced-form-integration' );
        $special_tags['_user_first_name'] = __( '_Logged_User_First_Name', 'advanced-form-integration' );
        $special_tags['_user_last_name'] = __( '_Logged_User_Last_Name', 'advanced-form-integration' );
        $special_tags['_user_display_name'] = __( '_Logged_User_Display_Name', 'advanced-form-integration' );
        $special_tags['_user_email'] = __( '_Logged_User_Email', 'advanced-form-integration' );
    }
    if ( 'utm' === $cat ) {
        $result = $utm_tags;
    } elseif ( 'st' === $cat ) {
        $result = $special_tags;
    } else {
        $result = array_merge( $utm_tags, $special_tags );
    }
    /**
     * Filter the special-tag list shown in the field-mapping dropdown.
     * Use this to register custom tags from extensions or third-party plugins.
     *
     * @param array<string,string> $result Map of tag-key (e.g. '_post_id') to display label.
     * @param string               $cat    'utm', 'st', or '' for combined.
     */
    return apply_filters( 'adfoin_special_tags', $result, $cat );
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
    $value = '';
    switch ( $tag ) {
        case '_submission_date':
            $value = wp_date( 'Y-m-d H:i:s' );
            break;
        case '_date':
            $value = wp_date( get_option( 'date_format' ) );
            break;
        case '_time':
            $value = wp_date( get_option( 'time_format' ) );
            break;
        case '_weekday':
            $value = wp_date( 'l' );
            break;
        case '_user_ip':
            $value = adfoin_get_user_ip();
            break;
        case '_user_agent':
            $value = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '' );
            break;
        case '_site_title':
            $value = get_bloginfo( 'name' );
            break;
        case '_site_description':
            $value = get_bloginfo( 'description' );
            break;
        case '_site_url':
            $value = get_bloginfo( 'url' );
            break;
        case '_site_admin_email':
            $value = get_bloginfo( 'admin_email' );
            break;
        case '_post_id':
            $value = ( isset( $post ) && is_object( $post ) ? $post->ID : '' );
            break;
        case '_post_name':
            $value = ( isset( $post ) && is_object( $post ) ? $post->post_name : '' );
            break;
        case '_post_title':
            $value = ( isset( $post ) && is_object( $post ) ? $post->post_title : '' );
            break;
        case '_post_url':
            $value = ( isset( $post ) && is_object( $post ) ? get_permalink( $post->ID ) : '' );
            break;
        case '_user_id':
            $value = ( isset( $current_user, $current_user->ID ) ? $current_user->ID : '' );
            break;
        case '_user_first_name':
            $value = ( isset( $current_user, $current_user->user_firstname ) ? $current_user->user_firstname : '' );
            break;
        case '_user_last_name':
            $value = ( isset( $current_user, $current_user->user_lastname ) ? $current_user->user_lastname : '' );
            break;
        case '_user_display_name':
            $value = ( isset( $current_user, $current_user->display_name ) ? $current_user->display_name : '' );
            break;
        case '_user_email':
            $value = ( isset( $current_user, $current_user->user_email ) ? $current_user->user_email : '' );
            break;
    }
    /**
     * Filter the resolved value of a special tag. Use this to override
     * built-in tag values or to provide values for custom tags registered
     * via the `adfoin_special_tags` filter.
     *
     * The previous implementation returned `true` (boolean) for unknown
     * tags, which downstream `str_replace` would coerce to `'1'`. Now
     * unknown tags default to '' and any extension can intercept here.
     *
     * @param mixed        $value         Default value resolved by the switch above ('' if no match).
     * @param string       $tag           Tag key (e.g. '_post_id').
     * @param WP_User|null $current_user  The current user, if available.
     * @param WP_Post|null $post          The current post, if available.
     */
    return apply_filters(
        'adfoin_special_tag_value',
        $value,
        $tag,
        $current_user,
        $post
    );
}

// Checks if a string is in valid md5 format
function adfoin_is_valid_md5(  $md5 = ''  ) {
    return preg_match( '/^[a-f0-9]{32}$/', $md5 );
}

/**
 * Resolve marketing/click-ID values for the current request and persist them
 * in a 30-day cookie when seen for the first time (first-touch attribution).
 *
 * Behavior:
 * - Cookie wins over URL param. Once a value is set on first touch, later
 *   visits with new UTM params do NOT overwrite it. This matches the
 *   matching JS in utm-grabber.js and is what most CRMs expect for
 *   attribution.
 * - Values are sanitized with sanitize_text_field() before being returned
 *   (they get sent to CRMs as plain-text properties — htmlspecialchars
 *   would pollute downstream data with `&amp;` etc.).
 * - setcookie() is skipped when headers have already been sent. This
 *   matters because the function is called from form-submission handlers
 *   that may run via Action Scheduler / cron, where there's no live HTTP
 *   response to attach Set-Cookie to.
 *
 * @return array Map of tag name => value (empty string when unset).
 */
function adfoin_capture_utm_and_url_values() {
    $fields = adfoin_get_special_tags( 'utm' );
    $cookie_fields = array();
    $can_set = !headers_sent();
    $cookie_args = adfoin_utm_cookie_args();
    foreach ( $fields as $field => $title ) {
        $cookie_value = ( isset( $_COOKIE[$field] ) ? sanitize_text_field( wp_unslash( $_COOKIE[$field] ) ) : '' );
        $url_value = ( isset( $_GET[$field] ) ? sanitize_text_field( wp_unslash( $_GET[$field] ) ) : '' );
        if ( $cookie_value !== '' ) {
            // First-touch already captured — preserve it.
            $cookie_fields[$field] = $cookie_value;
            continue;
        }
        if ( $url_value !== '' ) {
            $cookie_fields[$field] = $url_value;
            if ( $can_set ) {
                setcookie( $field, $url_value, array_merge( $cookie_args, array(
                    'expires' => time() + 30 * DAY_IN_SECONDS,
                ) ) );
                // Make the new value visible to downstream code in the same request.
                $_COOKIE[$field] = $url_value;
            }
            continue;
        }
        $cookie_fields[$field] = '';
    }
    return $cookie_fields;
}

/**
 * Cookie args shared between every UTM cookie write. Centralized so SameSite,
 * Secure, and the host/domain logic don't drift between call sites.
 */
function adfoin_utm_cookie_args() {
    $host = '';
    if ( isset( $_SERVER['HTTP_HOST'] ) ) {
        $parsed = wp_parse_url( 'http://' . wp_unslash( $_SERVER['HTTP_HOST'] ), PHP_URL_HOST );
        if ( is_string( $parsed ) ) {
            $host = $parsed;
        }
    }
    if ( 0 === stripos( $host, 'www.' ) ) {
        $host = substr( $host, 4 );
    }
    // Localhost and IP literals can't take a Domain attribute; browsers
    // drop the cookie entirely. Empty domain == host-only, which works
    // everywhere.
    $domain = $host;
    if ( $domain === 'localhost' || filter_var( $domain, FILTER_VALIDATE_IP ) ) {
        $domain = '';
    }
    return array(
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
    );
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

/**
 * Render the standard account-management screen for a platform.
 *
 * Historically rendered the Vue `api-key-management` widget; that widget's
 * Add Account button no longer fires reliably on the settings page, so this
 * function now delegates to the jQuery-based ADFOIN_Account_Manager and
 * translates the legacy field schema on the fly. Existing per-platform
 * `wp_ajax_adfoin_save_<platform>_credentials` handlers continue to work for
 * old-style requests; new-style row submits are intercepted by
 * ADFOIN_Account_Manager::legacy_save_bridge() (admin_init priority 5).
 *
 * @param string $title         Display title (e.g. "SendGrid").
 * @param string $key           Platform slug (used as the settings tab key).
 * @param string $arguments     wp_json_encode-style payload: { platform, fields:[{key,label,hidden}] }
 * @param string $instructions  HTML for the sidebar instructions card.
 * @return string
 */
function adfoin_platform_settings_template(
    $title,
    $key,
    $arguments,
    $instructions
) {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'class-adfoin-account-manager.php';
    }
    $payload = ( is_string( $arguments ) ? json_decode( $arguments, true ) : (array) $arguments );
    $platform = ( isset( $payload['platform'] ) && is_string( $payload['platform'] ) ? $payload['platform'] : $key );
    $fields = ADFOIN_Account_Manager::translate_legacy_fields( ( isset( $payload['fields'] ) ? $payload['fields'] : array() ) );
    ob_start();
    ADFOIN_Account_Manager::render_settings_view(
        $platform,
        $title,
        $fields,
        $instructions
    );
    return ob_get_clean();
}

/**
 * Renders the Pro upsell / "use the Pro action" notice for a platform's Vue
 * action template — a single shared, consistently styled callout that replaces
 * the per-platform copy-pasted "Go Pro" table rows.
 *
 * Call inside a platform's <script type="text/template"> block, e.g.:
 *     <?php adfoin_pro_feature_notice( 'create_contact', 'HighLevel [PRO]' ); ?>
 *
 * @param string $task      Action task the notice belongs to (used in the v-if).
 * @param string $pro_label Label of the [PRO] action to point Pro users toward.
 * @param string $feature   What upgrading unlocks. Default 'custom fields'.
 */
function adfoin_pro_feature_notice(  $task, $pro_label, $feature = 'custom fields'  ) {
    if ( !function_exists( 'adfoin_fs' ) ) {
        return;
    }
    // Paying Professional users only reach this if they picked the free action —
    // point them to the dedicated [PRO] action rather than upselling. Skipped
    // when no $pro_label is given (the platform has no dedicated [PRO] action).
    if ( $pro_label && adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
        adfoin_pro_notice_callout( array(
            'task'        => $task,
            'style'       => 'info',
            'heading'     => __( 'You are using Pro', 'advanced-form-integration' ),
            'message'     => sprintf( 
                /* translators: 1: feature name, 2: Pro action label. */
                __( 'Mapping %1$s is available on the <strong>%2$s</strong> action. Create a new integration to use it.', 'advanced-form-integration' ),
                esc_html( $feature ),
                esc_html( $pro_label )
             ),
            'button_text' => __( 'New Integration', 'advanced-form-integration' ),
            'button_url'  => admin_url( 'admin.php?page=advanced-form-integration-new' ),
        ) );
        return;
    }
    // Non-paying users: upsell.
    if ( adfoin_fs()->is_not_paying() ) {
        adfoin_pro_notice_callout( array(
            'task'        => $task,
            'style'       => 'upsell',
            'heading'     => __( 'Go Pro', 'advanced-form-integration' ),
            'message'     => sprintf( 
                /* translators: %s: feature name, e.g. "custom fields and tags". */
                __( 'Unlock %s with Pro.', 'advanced-form-integration' ),
                esc_html( $feature )
             ),
            'button_text' => __( 'Upgrade to Pro', 'advanced-form-integration' ),
            'button_url'  => admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ),
        ) );
    }
}

/**
 * Outputs one full-width callout row for adfoin_pro_feature_notice().
 * Internal helper — platforms should call adfoin_pro_feature_notice() instead.
 *
 * @param array $args task, style ('upsell'|'info'), heading, message,
 *                    button_text, button_url.
 */
function adfoin_pro_notice_callout(  $args  ) {
    $is_upsell = 'upsell' === $args['style'];
    $accent = ( $is_upsell ? '#2271b1' : '#bd8600' );
    $bg = ( $is_upsell ? '#f0f6fc' : '#fcf9e8' );
    $border = ( $is_upsell ? '#c5d9ed' : '#e8dca6' );
    $icon = ( $is_upsell ? 'dashicons-star-filled' : 'dashicons-info-outline' );
    $btn_class = ( $is_upsell ? 'button-primary' : 'button-secondary' );
    ?>
    <tr valign="top" v-if="action.task == '<?php 
    echo esc_js( $args['task'] );
    ?>'">
        <td colspan="2">
            <div style="display:flex;align-items:center;gap:12px;background:<?php 
    echo esc_attr( $bg );
    ?>;border:1px solid <?php 
    echo esc_attr( $border );
    ?>;border-left:4px solid <?php 
    echo esc_attr( $accent );
    ?>;border-radius:4px;padding:12px 16px;">
                <span class="dashicons <?php 
    echo esc_attr( $icon );
    ?>" style="color:<?php 
    echo esc_attr( $accent );
    ?>;font-size:22px;width:22px;height:22px;"></span>
                <div style="flex:1;line-height:1.5;">
                    <strong><?php 
    echo esc_html( $args['heading'] );
    ?></strong><br>
                    <span><?php 
    echo wp_kses_post( $args['message'] );
    ?></span>
                </div>
                <a href="<?php 
    echo esc_url( $args['button_url'] );
    ?>" class="button <?php 
    echo esc_attr( $btn_class );
    ?>" style="flex-shrink:0;"><?php 
    echo esc_html( $args['button_text'] );
    ?></a>
            </div>
        </td>
    </tr>
    <?php 
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
    $nonce = ( isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '' );
    if ( !wp_verify_nonce( $nonce, 'advanced-form-integration' ) ) {
        wp_send_json_error( __( 'Security check failed', 'advanced-form-integration' ) );
        return false;
    }
    return true;
}

function adfoin_check_conditional_logic(  $cl, $posted_data  ) {
    return isset( $cl['active'] ) && $cl['active'] === "yes" && !adfoin_match_conditional_logic( $cl, $posted_data );
}
