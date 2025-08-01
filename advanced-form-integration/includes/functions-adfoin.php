<?php

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
        'academylms'        => 'Academy LMS',
        'affiliatewp'       => 'AffiliateWP',
        'amelia'            => 'Amelia',
        'anspress'          => 'AnsPress',
        'arforms'           => 'ARForms',
        'armember'          => 'ARMember',
        'asgarosforum'      => 'Asgaros Forum',
        'avadaforms'        => 'Avada Forms',
        'awesomesupport'    => 'Awesome Support',
        'bbpress'           => 'bbPress',
        'beaver'            => 'Beaver Form',
        'bitform'           => 'BitForm',
        'bricks'            => 'Bricks Form',
        'buddyboss'         => 'BuddyBoss',
        'calderaforms'      => 'Caldera Forms',
        'cf7'               => 'Contact Form 7',
        'charitable'        => 'Charitable',
        'convertpro'        => 'ConvertPro Forms',
        'digimember'        => 'DigiMember',
        'diviform'          => 'Divi Form',
        'easyaffiliate'     => 'Easy Affiliate',
        'edd'               => 'Easy Digital Downloads',
        'eform'             => 'eForm',
        'elementorpro'      => 'Elementor Pro Form',
        'eventin'           => 'Eventin',
        'eventsmanager'     => 'Events Manager',
        'eventtickets'      => 'Event Tickets',
        'everestforms'      => 'Everest Forms',
        'fluentbooking'     => 'Fluent Booking',
        'fooevents'         => 'FooEvents',
        'fluentforms'       => 'Fluent Forms',
        'formcraft'         => 'FormCraft 3',
        'formcraftb'        => 'FormCraft Basic',
        'formidable'        => 'Formidable Forms',
        'forminator'        => 'Forminator (forms only)',
        'gamipress'         => 'GamiPress',
        'givewp'            => 'GiveWP',
        'gravityforms'      => 'Gravity Forms',
        'groundhogg'        => 'Groundhogg',
        'happyforms'        => 'Happy Forms',
        'jetformbuilder'    => 'JetFormBuilder',
        'jetpackcrm'        => 'Jetpack CRM',
        'kadence'           => 'Kadence Blocks Form',
        'latepoint'         => 'LatePoint',
        'learndash'         => 'LearnDash',
        'learnpress'        => 'LearnPress',
        'lifterlms'         => 'LifterLMS',
        'liveforms'         => 'Live Forms',
        'mailpoet'          => 'MailPoet',
        'masterstudy'       => 'MasterStudy LMS',
        'memberpress'       => 'MemberPress',
        'metform'           => 'Metform',
        'mycred'            => 'myCred',
        'newsletter'        => 'Newsletter',
        'ninjaforms'        => 'Ninja Forms',
        'paidmembershippro' => 'Paid Memberships Pro',
        'peepso'            => 'PeepSo',
        'qsm'               => 'Quiz and Survey Master',
        'quform'            => 'Quform',
        'rafflepress'       => 'RafflePress',
        'restriccontentpro' => 'Restrict Content Pro',
        'senseilms'         => 'Sensei LMS',
        'slicewp'           => 'SliceWP',
        'smartforms'        => 'Smart Forms',
        'surecart'          => 'SureCart',
        'suremembers'       => 'SureMembers',
        'theeventscalendar' => 'The Events Calendar',
        'thriveapprentice'  => 'Thrive Apprentice',
        'thriveleads'       => 'Thrive Leads',
        'thivequizbuilder'  => 'Thrive Quiz Builder',
        'tutorlms'          => 'Tutor LMS',
        'ultimatemember'    => 'Ultimate Member',
        'userregistration'  => 'User Registration',
        'weforms'           => 'weForms',
        'wpbookingcalendar' => 'WP Booking Calendar',
        'wpforms'           => 'WPForms',
        'wpforo'            => 'wpForo',
        'wpmembers'         => 'WP-Members',
        'wppizza'           => 'WP Pizza',
        'wppostratings'     => 'WP Post Ratings',
        'wpsimplepay'       => 'WP Simple Pay',
        'wpulike'           => 'WP ULike',
        'woocommerce'       => 'WooCommerce',
        'wsform'            => 'WS Form',
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
        'academylms'       => array(
            'title' => __( 'Academy LMS', 'advanced-form-integration' ),
            'basic' => 'academylms',
        ),
        'acelle'           => array(
            'title' => __( 'Acelle Mail', 'advanced-form-integration' ),
            'basic' => 'acelle',
        ),
        'activecampaign'   => array(
            'title' => __( 'ActiveCampaign', 'advanced-form-integration' ),
            'basic' => 'activecampaign',
        ),
        'acumbamail'       => array(
            'title' => __( 'Acumbamail', 'advanced-form-integration' ),
            'basic' => 'acumbamail',
        ),
        'agilecrm'         => array(
            'title' => __( 'Agile CRM', 'advanced-form-integration' ),
            'basic' => 'agilecrm',
        ),
        'airtable'         => array(
            'title' => __( 'Airtable', 'advanced-form-integration' ),
            'basic' => 'airtable',
        ),
        'apollo'           => array(
            'key'   => 'apollo',
            'title' => __( 'Apollo.io', 'advanced-form-integration' ),
            'basic' => 'apollo',
        ),
        'apptivo'          => array(
            'key'   => 'apptivo',
            'title' => __( 'Apptivo', 'advanced-form-integration' ),
            'basic' => 'apptivo',
        ),
        'asana'            => array(
            'key'   => 'asana',
            'title' => __( 'Asana', 'advanced-form-integration' ),
            'basic' => 'asana',
        ),
        'attio'            => array(
            'key'   => 'attio',
            'title' => __( 'Attio CRM', 'advanced-form-integration' ),
            'basic' => 'attio',
        ),
        'audienceful'      => array(
            'title' => __( 'Audienceful', 'advanced-form-integration' ),
            'basic' => 'audienceful',
        ),
        'autopilot'        => array(
            'title' => __( 'Autopilot', 'advanced-form-integration' ),
            'basic' => 'autopilot',
        ),
        'aweber'           => array(
            'title' => __( 'Aweber', 'advanced-form-integration' ),
            'basic' => 'aweber',
        ),
        'beehiiv'          => array(
            'title' => __( 'beehiiv', 'advanced-form-integration' ),
            'basic' => 'beehiiv',
        ),
        'benchmark'        => array(
            'title' => __( 'Benchmark', 'advanced-form-integration' ),
            'basic' => 'benchmark',
        ),
        'bigin'            => array(
            'title' => __( 'Bigin', 'advanced-form-integration' ),
            'basic' => 'bigin',
        ),
        'bombbomb'         => array(
            'title' => __( 'BombBomb', 'advanced-form-integration' ),
            'basic' => 'bombbomb',
        ),
        'brevo'            => array(
            'title' => __( 'Brevo', 'advanced-form-integration' ),
            'basic' => 'brevo',
        ),
        'cakemail'         => array(
            'title' => __( 'Cakemail', 'advanced-form-integration' ),
            'basic' => 'cakemail',
        ),
        'campaigner'       => array(
            'title' => __( 'Campaigner', 'advanced-form-integration' ),
            'basic' => 'campaigner',
        ),
        'campaignmonitor'  => array(
            'title' => __( 'Campaign Monitor', 'advanced-form-integration' ),
            'basic' => 'campaignmonitor',
        ),
        'campayn'          => array(
            'title' => __( 'Campayn', 'advanced-form-integration' ),
            'basic' => 'campayn',
        ),
        'capsulecrm'       => array(
            'title' => __( 'Capsule CRM', 'advanced-form-integration' ),
            'basic' => 'capsulecrm',
        ),
        'civicrm'          => array(
            'title' => __( 'CiviCRM', 'advanced-form-integration' ),
            'basic' => 'civicrm',
        ),
        'cleverreach'      => array(
            'title' => __( 'CleverReach', 'advanced-form-integration' ),
            'basic' => 'cleverreach',
        ),
        'clickup'          => array(
            'title' => __( 'Clickup', 'advanced-form-integration' ),
            'basic' => 'clickup',
        ),
        'clinchpad'        => array(
            'title' => __( 'ClinchPad', 'advanced-form-integration' ),
            'basic' => 'clinchpad',
        ),
        'close'            => array(
            'title' => __( 'Close', 'advanced-form-integration' ),
            'basic' => 'close',
        ),
        'companyhub'       => array(
            'title' => __( 'CompanyHub', 'advanced-form-integration' ),
            'basic' => 'companyhub',
        ),
        'constantcontact'  => array(
            'title' => __( 'Constant Contact', 'advanced-form-integration' ),
            'basic' => 'constantcontact',
        ),
        'convertkit'       => array(
            'title' => __( 'ConvertKit', 'advanced-form-integration' ),
            'basic' => 'convertkit',
        ),
        'copernica'        => array(
            'title' => __( 'Copernica', 'advanced-form-integration' ),
            'basic' => 'copernica',
        ),
        'copper'           => array(
            'title' => __( 'Copper', 'advanced-form-integration' ),
            'basic' => 'copper',
        ),
        'curated'          => array(
            'title' => __( 'Curated', 'advanced-form-integration' ),
            'basic' => 'curated',
        ),
        'demio'            => array(
            'title' => __( 'Demio', 'advanced-form-integration' ),
            'basic' => 'demio',
        ),
        'directiq'         => array(
            'title' => __( 'DirectIQ', 'advanced-form-integration' ),
            'basic' => 'directiq',
        ),
        'discord'          => array(
            'title' => __( 'Discord', 'advanced-form-integration' ),
            'basic' => 'discord',
        ),
        'doppler'          => array(
            'title' => __( 'Doppler', 'advanced-form-integration' ),
            'basic' => 'doppler',
        ),
        'drip'             => array(
            'title' => __( 'Drip', 'advanced-form-integration' ),
            'basic' => 'drip',
        ),
        'dropbox'          => array(
            'title' => __( 'Dropbox', 'advanced-form-integration' ),
            'basic' => 'dropbox',
        ),
        'easysendy'        => array(
            'title' => __( 'EasySendy', 'advanced-form-integration' ),
            'basic' => 'easysendy',
        ),
        'elasticemail'     => array(
            'title' => __( 'Elastic Email', 'advanced-form-integration' ),
            'basic' => 'elasticemail',
        ),
        'emailchef'        => array(
            'title' => __( 'Emailchef', 'advanced-form-integration' ),
            'basic' => 'emailchef',
        ),
        'emailoctopus'     => array(
            'title' => __( 'EmailOctopus', 'advanced-form-integration' ),
            'basic' => 'emailoctopus',
        ),
        'encharge'         => array(
            'title' => __( 'Encharge', 'advanced-form-integration' ),
            'basic' => 'encharge',
        ),
        'engagebay'        => array(
            'title' => __( 'EngageBay', 'advanced-form-integration' ),
            'basic' => 'engagebay',
        ),
        'enormail'         => array(
            'title' => __( 'Enormail', 'advanced-form-integration' ),
            'basic' => 'enormail',
        ),
        'everwebinar'      => array(
            'title' => __( 'EverWebinar', 'advanced-form-integration' ),
            'basic' => 'everwebinar',
        ),
        'flodesk'          => array(
            'title' => __( 'Flodesk', 'advanced-form-integration' ),
            'basic' => 'flodesk',
        ),
        'flowlu'           => array(
            'title' => __( 'Flowlu', 'advanced-form-integration' ),
            'basic' => 'flowlu',
        ),
        'fluentcrm'        => array(
            'title' => __( 'Fluent CRM', 'advanced-form-integration' ),
            'basic' => 'fluentcrm',
        ),
        'fluentsupport'    => array(
            'title' => __( 'Fluent Support', 'advanced-form-integration' ),
            'basic' => 'fluentsupport',
        ),
        'freshdesk'        => array(
            'title' => __( 'Freshdesk', 'advanced-form-integration' ),
            'basic' => 'freshdesk',
        ),
        'freshsales'       => array(
            'title' => __( 'Freshworks CRM', 'advanced-form-integration' ),
            'basic' => 'freshsales',
        ),
        'getresponse'      => array(
            'title' => __( 'GetResponse', 'advanced-form-integration' ),
            'basic' => 'getresponse',
        ),
        'gist'             => array(
            'title' => __( 'Gist', 'advanced-form-integration' ),
            'basic' => 'gist',
        ),
        'googlecalendar'   => array(
            'title' => __( 'Google Calendar', 'advanced-form-integration' ),
            'basic' => 'googlecalendar',
        ),
        'googledrive'      => array(
            'title' => __( 'Google Drive', 'advanced-form-integration' ),
            'basic' => 'googledrive',
        ),
        'googlesheets'     => array(
            'title' => __( 'Google Sheets', 'advanced-form-integration' ),
            'basic' => 'googlesheets',
        ),
        'highlevel'        => array(
            'title' => __( 'HighLevel', 'advanced-form-integration' ),
            'basic' => 'highlevel',
        ),
        'hubspot'          => array(
            'title' => __( 'Hubspot', 'advanced-form-integration' ),
            'basic' => 'hubspot',
        ),
        'icontact'         => array(
            'title' => __( 'iContact', 'advanced-form-integration' ),
            'basic' => 'icontact',
        ),
        'insightly'        => array(
            'title' => __( 'Insightly CRM', 'advanced-form-integration' ),
            'basic' => 'insightly',
        ),
        'instantly'        => array(
            'title' => __( 'Instantly', 'advanced-form-integration' ),
            'basic' => 'instantly',
        ),
        'jumplead'         => array(
            'title' => __( 'Jumplead', 'advanced-form-integration' ),
            'basic' => 'jumplead',
        ),
        'keap'             => array(
            'title' => __( 'Keap', 'advanced-form-integration' ),
            'basic' => 'keap',
        ),
        'keila'            => array(
            'title' => __( 'Keila', 'advanced-form-integration' ),
            'basic' => 'keila',
        ),
        'kit'              => array(
            'title' => __( 'Kit', 'advanced-form-integration' ),
            'basic' => 'kit',
        ),
        'klaviyo'          => array(
            'title' => __( 'Klaviyo', 'advanced-form-integration' ),
            'basic' => 'klaviyo',
        ),
        'laposta'          => array(
            'title' => __( 'Laposta', 'advanced-form-integration' ),
            'basic' => 'laposta',
        ),
        'lemlist'          => array(
            'title' => __( 'lemlist', 'advanced-form-integration' ),
            'basic' => 'lemlist',
        ),
        'lacrm'            => array(
            'title' => __( 'Less Annoying CRM', 'advanced-form-integration' ),
            'basic' => 'lacrm',
        ),
        'liondesk'         => array(
            'title' => __( 'LionDesk', 'advanced-form-integration' ),
            'basic' => 'liondesk',
        ),
        'livestorm'        => array(
            'title' => __( 'Livestorm', 'advanced-form-integration' ),
            'basic' => 'livestorm',
        ),
        'loops'            => array(
            'title' => __( 'Loops', 'advanced-form-integration' ),
            'basic' => 'loops',
        ),
        'mailbluster'      => array(
            'title' => __( 'MailBluster', 'advanced-form-integration' ),
            'basic' => 'mailbluster',
        ),
        'mailchimp'        => array(
            'title' => __( 'Mailchimp', 'advanced-form-integration' ),
            'basic' => 'mailchimp',
        ),
        'mailcoach'        => array(
            'title' => __( 'Mailcoach', 'advanced-form-integration' ),
            'basic' => 'mailcoach',
        ),
        'maileon'          => array(
            'title' => __( 'Maileon', 'advanced-form-integration' ),
            'basic' => 'maileon',
        ),
        'mailercloud'      => array(
            'title' => __( 'Mailercloud', 'advanced-form-integration' ),
            'basic' => 'mailercloud',
        ),
        'mailerlite'       => array(
            'title' => __( 'MailerLite Classic', 'advanced-form-integration' ),
            'basic' => 'mailerlite',
        ),
        'mailerlite2'      => array(
            'title' => __( 'MailerLite', 'advanced-form-integration' ),
            'basic' => 'mailerlite2',
        ),
        'mailify'          => array(
            'title' => __( 'Mailify', 'advanced-form-integration' ),
            'basic' => 'mailify',
        ),
        'mailjet'          => array(
            'title' => __( 'Mailjet', 'advanced-form-integration' ),
            'basic' => 'mailjet',
        ),
        'mailmint'         => array(
            'title' => __( 'Mail Mint', 'advanced-form-integration' ),
            'basic' => 'mailmint',
        ),
        'mailmodo'         => array(
            'title' => __( 'Mailmodo', 'advanced-form-integration' ),
            'basic' => 'mailmodo',
        ),
        'mailpoet'         => array(
            'title' => __( 'MailPoet', 'advanced-form-integration' ),
            'basic' => 'mailpoet',
        ),
        'mailrelay'        => array(
            'title' => __( 'MailRelay', 'advanced-form-integration' ),
            'basic' => 'mailrelay',
        ),
        'mailster'         => array(
            'title' => __( 'Mailster', 'advanced-form-integration' ),
            'basic' => 'mailster',
        ),
        'mailup'           => array(
            'title' => __( 'MailUp', 'advanced-form-integration' ),
            'basic' => 'mailup',
        ),
        'mailwizz'         => array(
            'title' => __( 'MailWizz', 'advanced-form-integration' ),
            'basic' => 'mailwizz',
        ),
        'mautic'           => array(
            'title' => __( 'Mautic', 'advanced-form-integration' ),
            'basic' => 'mautic',
        ),
        'monday'           => array(
            'title' => __( 'Monday.com', 'advanced-form-integration' ),
            'basic' => 'monday',
        ),
        'moosend'          => array(
            'title' => __( 'Moosend', 'advanced-form-integration' ),
            'basic' => 'moosend',
        ),
        'newsletter'       => array(
            'title' => __( 'Newsletter', 'advanced-form-integration' ),
            'basic' => 'newsletter',
        ),
        'nimble'           => array(
            'title' => __( 'Nimble', 'advanced-form-integration' ),
            'basic' => 'nimble',
        ),
        'nutshell'         => array(
            'title' => __( 'Nutshell CRM', 'advanced-form-integration' ),
            'basic' => 'nutshell',
        ),
        'omnisend'         => array(
            'title' => __( 'Omnisend', 'advanced-form-integration' ),
            'basic' => 'omnisend',
        ),
        'onehash'          => array(
            'title' => __( 'Onehash', 'advanced-form-integration' ),
            'basic' => 'onehash',
        ),
        'autopilotnew'     => array(
            'title' => __( 'Ortto', 'advanced-form-integration' ),
            'basic' => 'autopilotnew',
        ),
        'pabbly'           => array(
            'title' => __( 'Pabbly', 'advanced-form-integration' ),
            'basic' => 'pabbly',
        ),
        'pipedrive'        => array(
            'title' => __( 'Pipedrive', 'advanced-form-integration' ),
            'basic' => 'pipedrive',
        ),
        'pushover'         => array(
            'title' => __( 'Pushover', 'advanced-form-integration' ),
            'basic' => 'pushover',
        ),
        'quickbase'        => array(
            'title' => __( 'Quickbase', 'advanced-form-integration' ),
            'basic' => 'quickbase',
        ),
        'ragic'            => array(
            'title' => __( 'Ragic', 'advanced-form-integration' ),
            'basic' => 'ragic',
        ),
        'rapidmail'        => array(
            'title' => __( 'Rapidmail', 'advanced-form-integration' ),
            'basic' => 'rapidmail',
        ),
        'resend'           => array(
            'title' => __( 'Resend', 'advanced-form-integration' ),
            'basic' => 'resend',
        ),
        'revue'            => array(
            'title' => __( 'Revue', 'advanced-form-integration' ),
            'basic' => 'revue',
        ),
        'robly'            => array(
            'title' => __( 'Robly', 'advanced-form-integration' ),
            'basic' => 'robly',
        ),
        'salesflare'       => array(
            'title' => __( 'Salesflare', 'advanced-form-integration' ),
            'basic' => 'salesflare',
        ),
        'salesforce'       => array(
            'title' => __( 'Salesforce', 'advanced-form-integration' ),
            'basic' => 'salesforce',
        ),
        'saleshandy'       => array(
            'title' => __( 'SalesHandy', 'advanced-form-integration' ),
            'basic' => 'saleshandy',
        ),
        'salesrocks'       => array(
            'title' => __( 'Sales Rocks', 'advanced-form-integration' ),
            'basic' => 'salesrocks',
        ),
        'sarbacane'        => array(
            'title' => __( 'Sarbacane', 'advanced-form-integration' ),
            'basic' => 'sarbacane',
        ),
        'selzy'            => array(
            'title' => __( 'Selzy', 'advanced-form-integration' ),
            'basic' => 'selzy',
        ),
        'sender'           => array(
            'title' => __( 'Sender', 'advanced-form-integration' ),
            'basic' => 'sender',
        ),
        'sendfox'          => array(
            'title' => __( 'Sendfox', 'advanced-form-integration' ),
            'basic' => 'sendfox',
        ),
        'sendinblue'       => array(
            'title' => __( 'Sendinblue', 'advanced-form-integration' ),
            'basic' => 'sendinblue',
        ),
        'sendpulse'        => array(
            'title' => __( 'Sendpulse', 'advanced-form-integration' ),
            'basic' => 'sendpulse',
        ),
        'sendx'            => array(
            'title' => __( 'SendX', 'advanced-form-integration' ),
            'basic' => 'sendx',
        ),
        'sendy'            => array(
            'title' => __( 'Sendy', 'advanced-form-integration' ),
            'basic' => 'sendy',
        ),
        'slack'            => array(
            'title' => __( 'Slack', 'advanced-form-integration' ),
            'basic' => 'slack',
        ),
        'smartlead'        => array(
            'title' => __( 'Smartlead', 'advanced-form-integration' ),
            'basic' => 'smartlead',
        ),
        'smartrmail'       => array(
            'title' => __( 'SmartrMail', 'advanced-form-integration' ),
            'basic' => 'smartrmail',
        ),
        'smartsheet'       => array(
            'title' => __( 'Smartsheet', 'advanced-form-integration' ),
            'basic' => 'smartsheet',
        ),
        'snovio'           => array(
            'title' => __( 'Snov.io', 'advanced-form-integration' ),
            'basic' => 'snovio',
        ),
        'systemeio'        => array(
            'title' => __( 'Systeme.io', 'advanced-form-integration' ),
            'basic' => 'systemeio',
        ),
        'trello'           => array(
            'title' => __( 'Trello', 'advanced-form-integration' ),
            'basic' => 'trello',
        ),
        'twilio'           => array(
            'title' => __( 'Twilio', 'advanced-form-integration' ),
            'basic' => 'twilio',
        ),
        'verticalresponse' => array(
            'title' => __( 'Vertical Response', 'advanced-form-integration' ),
            'basic' => 'verticalresponse',
        ),
        'vtiger'           => array(
            'title' => __( 'Vtiger CRM', 'advanced-form-integration' ),
            'basic' => 'vtiger',
        ),
        'wealthbox'        => array(
            'title' => __( 'Wealthbox', 'advanced-form-integration' ),
            'basic' => 'wealthbox',
        ),
        'webhook'          => array(
            'title' => __( 'Webhook', 'advanced-form-integration' ),
            'basic' => 'webhook',
        ),
        'webinarjam'       => array(
            'title' => __( 'WebinarJam', 'advanced-form-integration' ),
            'basic' => 'webinarjam',
        ),
        'woodpecker'       => array(
            'title' => __( 'Woodpecker.co', 'advanced-form-integration' ),
            'basic' => 'woodpecker',
        ),
        'wordpress'        => array(
            'title' => __( 'WordPress', 'advanced-form-integration' ),
            'basic' => 'wordpress',
        ),
        'zapier'           => array(
            'title' => __( 'Zapier', 'advanced-form-integration' ),
            'basic' => 'zapier',
        ),
        'zendesksell'      => array(
            'title' => __( 'Zendesk Sell', 'advanced-form-integration' ),
            'basic' => 'zendesksell',
        ),
        'zohocampaigns'    => array(
            'title' => __( 'Zoho Campaigns', 'advanced-form-integration' ),
            'basic' => 'zohocampaigns',
        ),
        'zohocrm'          => array(
            'title' => __( 'Zoho CRM', 'advanced-form-integration' ),
            'basic' => 'zohocrm',
        ),
        'zohodesk'         => array(
            'title' => __( 'Zoho Desk', 'advanced-form-integration' ),
            'basic' => 'zohodesk',
        ),
        'zohoma'           => array(
            'title' => __( 'Zoho Marketing Automation', 'advanced-form-integration' ),
            'basic' => 'zohoma',
        ),
        'zohosheet'        => array(
            'title' => __( 'Zoho Sheet', 'advanced-form-integration' ),
            'basic' => 'zohosheet',
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
    if ( $current_tab != 'general' ) {
        return;
    }
    $nonce = wp_create_nonce( "adfoin_general_settings" );
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
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_general_settings">
        <input type="hidden" name="_nonce" value="<?php 
    echo $nonce;
    ?>"/>

        <div class="afi-row">
        <div class="afi-col-full">
            <h3><?php 
    _e( 'Activate Platforms', 'advacned-form-integration' );
    ?></h3>
            <div class="afi-checkbox-container">
                <?php 
    foreach ( $platforms as $key => $platform ) {
        $status = ( isset( $platform_settings[$key] ) ? $platform_settings[$key] : '' );
        ?>
                    <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="<?php 
        echo esc_attr( $key );
        ?>"><?php 
        echo esc_html( $platform['title'] );
        ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="<?php 
        echo esc_attr( $key );
        ?>" name="platforms[<?php 
        echo esc_attr( $key );
        ?>]" <?php 
        checked( $status, 1 );
        ?>>
                    <span class="afi-slider round"></span></label>
                </div>
                <?php 
    }
    ?>
                
            </div>

            <h3><?php 
    _e( 'General Settings', 'advacned-form-integration' );
    ?></h3>
            <div class="afi-checkbox-container">
                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_disable_log"><?php 
    _e( 'Disable Log', 'advanced-form-integration' );
    ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_disable_log" name="adfoin_disable_log" <?php 
    checked( $log_settings, 1 );
    ?>>
                    <span class="afi-slider round"></span></label>
                </div>
                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_disable_st"><?php 
    _e( 'Disable Special Tags', 'advanced-form-integration' );
    ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_disable_st" name="adfoin_disable_st" <?php 
    checked( $st_settings, 1 );
    ?>>
                    <span class="afi-slider round"></span></label>
                </div>
                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_enable_utm"><?php 
    _e( 'Send UTM Variables', 'advanced-form-integration' );
    ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_enable_utm" name="adfoin_enable_utm" <?php 
    checked( $utm_settings, 1 );
    ?>>
                    <span class="afi-slider round"></span></label>
                </div>
                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_job_queue"><?php 
    _e( 'Enable Job Queue', 'advanced-form-integration' );
    ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                    <input type="checkbox" value="1" id="adfoin_job_queue" name="adfoin_job_queue" <?php 
    checked( $job_queue, 1 );
    ?>>
                    <span class="afi-slider round"></span></label>
                </div>
                <div class="afi-checkbox">
                    <div class="afi-elements-info">
                        <p class="afi-el-title">
                            <label for="adfoin_error_email"><?php 
    _e( 'Send Error Email', 'advanced-form-integration' );
    ?></label>
                        </p>
                    </div>
                    <label class="adfoin-toggle-form form-enabled">
                        <input type="checkbox" value="1" id="adfoin_error_email" name="adfoin_error_email" <?php 
    checked( $error_email, 1 );
    ?>>
                        <span class="afi-slider round"></span></label>
                </div>
            </div>
        </div>
    </div>
        
    <?php 
    submit_button();
    ?>
    </form>

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
        $data = array(
            'response_code'    => $return["response"]["code"],
            'response_message' => $return["response"]["message"],
            'integration_id'   => $record["id"],
            'request_data'     => $request_data,
            'response_data'    => $return["body"],
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
    // Get real visitor IP behind CloudFlare network
    if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = ( $_SERVER["HTTP_CF_CONNECTING_IP"] ? $_SERVER["HTTP_CF_CONNECTING_IP"] : '' );
    }
    $client = ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ? $_SERVER['HTTP_CLIENT_IP'] : '' );
    $forward = ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '' );
    $remote = $_SERVER['REMOTE_ADDR'];
    if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
        $ip = $client;
    } elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }
    $ip_list = explode( ',', $ip );
    $first_ip = ( isset( $ip_list[0] ) ? $ip_list[0] : '' );
    return $first_ip;
}

function adfoin_get_cl_conditions() {
    return array(
        "equal_to"         => __( 'Equal to', 'advanced-form-integration' ),
        "not_equal_to"     => __( 'Not equal to', 'advanced-form-integration' ),
        "contains"         => __( 'Contains', 'advanced-form-integration' ),
        "does_not_contain" => __( 'Does not Contain', 'advanced-form-integration' ),
        "starts_with"      => __( 'Starts with', 'advanced-form-integration' ),
        "ends_with"        => __( 'Ends with', 'advanced-form-integration' ),
        "greater_than"     => __( 'Greater Than (number)', 'advanced-form-integration' ),
        "less_than"        => __( 'Less Than (number)', 'advanced-form-integration' ),
    );
}

function adfoin_match_conditional_logic(  $cl, $posted_data  ) {
    if ( $cl["active"] != "yes" ) {
        return true;
    }
    $match = 0;
    $length = count( $cl["conditions"] );
    foreach ( $cl["conditions"] as $condition ) {
        if ( !$condition["field"] && $condition["field"] != 0 ) {
            continue;
        }
        $field = ( strpos( $condition["field"], '{{' ) !== false && strpos( $condition["field"], '}}' ) !== false ? $condition["field"] : '{{' . trim( $condition["field"] ) . '}}' );
        $field_value = adfoin_get_parsed_values( $field, $posted_data );
        if ( adfoin_match_single_logic( $field_value, $condition["operator"], $condition["value"] ) ) {
            $match++;
        }
    }
    if ( $cl["match"] == "any" && $match > 0 ) {
        return true;
    }
    if ( $cl["match"] == "all" && $match == $length ) {
        return true;
    }
    return false;
}

function adfoin_match_single_logic(  $data, $operator, $value  ) {
    $result = false;
    switch ( $operator ) {
        case 'equal_to':
            if ( $data == $value ) {
                $result = true;
            }
            break;
        case 'not_equal_to':
            if ( $data != $value ) {
                return true;
            }
            break;
        case 'greater_than':
            if ( (float) $data > (float) $value ) {
                return true;
            }
            break;
        case 'less_than':
            if ( (float) $data < (float) $value ) {
                return true;
            }
            break;
        case 'contains':
            if ( strpos( $data, $value ) !== false ) {
                return true;
            }
            break;
        case 'does_not_contains':
            if ( strpos( $data, $value ) === false ) {
                return true;
            }
            break;
        case 'starts_with':
            $length = strlen( $value );
            return substr( $data, 0, $length ) === $value;
            break;
        case 'ends_with':
            $length = strlen( $value );
            if ( $length == 0 ) {
                return true;
            }
            if ( substr( $data, -$length ) === $value ) {
                return true;
            }
        default:
            return false;
    }
    return $result;
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
        case "user_ip":
            return adfoin_get_user_ip();
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
				<div class="meta-box-sortables ui-sortable">
                    <h2 class="hndle"><span><?php 
    echo $title . esc_attr__( ' Accounts', 'advanced-form-integration' );
    ?></span></h2>
                    <div class="inside">
                        <div id="<?php 
    echo $key;
    ?>-auth">
                            <div id="api-key-management">
                                <api-key-management>
                                    <?php 
    echo $arguments;
    ?>
                                </api-key-management>
                            </div>
                        </div>
                    </div>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
                    <h2 class="hndle"><span><?php 
    esc_attr_e( 'Instructions', 'advanced-form-integration' );
    ?></span></h2>
                    <div class="inside">
                        <div class="card" style="margin-top: 0px;">
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
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        wp_send_json_error( __( 'Security check failed', 'advanced-form-integration' ) );
        return false;
    }
    return true;
}

function adfoin_check_conditional_logic(  $cl, $posted_data  ) {
    return isset( $cl['active'] ) && $cl['active'] === "yes" && !adfoin_match_conditional_logic( $cl, $posted_data );
}
