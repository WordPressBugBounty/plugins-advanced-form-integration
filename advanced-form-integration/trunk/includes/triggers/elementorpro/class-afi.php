<?php
if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
    return;
}

class AFI_Elementor extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    public function get_name() {
        return 'afi';
    }

    public function get_label() {
        return __( 'Advanced Form Integration', 'elementor-pro' );
    }

    public function register_settings_section( $widget ) {
        $widget->start_controls_section(
            'section_afi',
            [
                'label'     => __( 'Advanced Form Integration', 'elementor-pro' ),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $widget->add_control(
            'afi_integrations_id',
            [
                'label'       => __( 'Integration ID', 'elementor-pro' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'separator'   => 'before',
                'description' => __( 'Enter the integration ID here. Use comma for multiple IDs.', 'elementor-pro' ),
                'render_type' => 'none',
            ]
        );

        $widget->end_controls_section();
    }

    public function on_export( $element ) {}

    public function run( $record, $ajax_handler ) {
        global $wpdb;

        $saved_records = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}adfoin_integration 
             WHERE status = 1 AND (form_provider = 'elementorpro' OR (form_provider = 'elementorpro2' AND form_id = 1))",
            ARRAY_A
        );

        $settings = $record->get( 'form_settings' );

        if ( empty( $settings['afi_integrations_id'] ) ) {
            return;
        }
        
        $posted_data['form_id']   = $settings['id'];
        $posted_data['form_name'] = $settings['form_name'];
        $fields                   = $record->get( 'fields' );

        foreach( $fields as $field ) {
            $posted_data[$field['id']] = adfoin_sanitize_text_or_array_field( $field['value'] );
        }

        $posted_data['submission_date'] = date( 'Y-m-d H:i:s' );
        $posted_data['user_ip']         = adfoin_get_user_ip();
        $post_id                        = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
        $post                           = adfoin_get_post_object( $post_id );
        $special_tag_values             = adfoin_get_special_tags_values( $post );

        if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
            $posted_data = $posted_data + $special_tag_values;
        }

        $integrations_ids = $settings['afi_integrations_id'] ? explode( ',', $settings['afi_integrations_id'] ) : '';

        if( is_array( $saved_records ) && is_array( $integrations_ids ) ) {
            foreach( $integrations_ids as $integrations_id ) {
                foreach ( $saved_records as $record ) {
                    if( trim( $integrations_id ) == $record['id'] ) {
                        $action_provider = $record['action_provider'];
                        call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
                    }
                }
            }
        }
    }
}