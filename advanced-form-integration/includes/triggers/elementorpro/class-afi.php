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
        // Two providers feed this trigger: 'elementorpro' (all forms) and
        // 'elementorpro2' (only form_id = 1, the legacy single-form mode).
        // Fetch each via the canonical class method and merge.
        $integration   = new Advanced_Form_Integration_Integration();
        $saved_records = array_merge(
            $integration->get_by_trigger( 'elementorpro' ),
            $integration->get_by_trigger( 'elementorpro2', 1 )
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
        $post_id                        = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : '';
        $post                           = adfoin_get_post_object( $post_id );
        $special_tag_values             = adfoin_get_special_tags_values( $post );

        if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
            $posted_data = $posted_data + $special_tag_values;
        }

        // The form's settings carry a comma-list of integration ids the user
        // chose. Filter $saved_records down to those before dispatching, so
        // the helper picks up the Job Queue toggle for this trigger like every
        // other one — and each match fires exactly once.
        $integrations_ids = $settings['afi_integrations_id'] ? explode( ',', $settings['afi_integrations_id'] ) : array();

        if ( is_array( $saved_records ) && is_array( $integrations_ids ) && ! empty( $integrations_ids ) ) {
            $wanted  = array_map( 'trim', $integrations_ids );
            $matched = array();
            foreach ( $saved_records as $record ) {
                if ( in_array( $record['id'], $wanted, true ) ) {
                    $matched[] = $record;
                }
            }
            adfoin_dispatch_integrations( $matched, $posted_data );
        }
    }
}