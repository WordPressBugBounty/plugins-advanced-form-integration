<?php

// Get forms list
function adfoin_breakdance_get_forms( $form_provider ) {

    if( $form_provider != 'breakdance' ) {
        return;
    }

    global $wpdb, $only_forms;

    // $result = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'breakdance_data' AND  meta_value LIKE '%form_name%'", ARRAY_A );

    $result = $wpdb->get_results(
        "SELECT p.ID, m.meta_key, m.meta_value
        FROM {$wpdb->prefix}posts AS p
        INNER JOIN {$wpdb->prefix}postmeta AS m ON p.ID = m.post_id
        WHERE p.post_status = 'publish'
        AND m.meta_key = 'breakdance_data'
        AND m.meta_value LIKE '%form_name%';",
        ARRAY_A
    );    

    foreach( $result as $single_post ) {
        $meta_data = json_decode( $single_post['meta_value'], true );
        $meta_data = isset( $meta_data['tree_json_string'] ) ? json_decode( $meta_data['tree_json_string'], true ) : null;

        if( isset( $meta_data['root']['children'] ) ) {
            adfoin_breakdance_find_element_recursive( $meta_data['root']['children'], $single_post['ID'] );
        }

    }

    $only_forms = array_filter( $only_forms );

    $form_list = array();

    foreach( $only_forms as $single ) {
        if( isset( $single['data'], $single['data']['properties'], $single['data']['properties']['content'], $single['data']['properties']['content']['form'], $single['data']['properties']['content']['form']['form_name'] ) ) {
            $form_name = $single['data']['properties']['content']['form']['form_name'];
            $form_list[$single['ID'] . '_' . $single['id']] = $single['ID'] . ' ' . $form_name;
        }
    }

    return $form_list;
}

function adfoin_breakdance_find_element_recursive( $elements, $post_id ) {
    global $only_forms;
    
    foreach ( $elements as $element ) {
        if( isset( $element['data'], $element['data']['type'] ) ) {
            if ( 'EssentialElements\\FormBuilder' === $element['data']['type'] ) {
                $element['ID'] = $post_id;

                $only_forms[] = $element;
            }
        }

        if ( isset( $element['children'] ) ) {
            adfoin_breakdance_find_element_recursive( $element['children'], $post_id );
        }
    }
}

// Get form fields
function adfoin_breakdance_get_form_fields( $form_provider, $form_id ) {

    if( $form_provider != 'breakdance' ) {
        return;
    }

    $form_id_main = explode( '_', $form_id )[0];
    $post_id = intval( explode( '_', $form_id )[0] );
    $forms = array();
    $content = get_post_meta( $post_id, 'breakdance_data', true );

    if( empty( $content ) ) {
        return;
    }

    $content = json_decode( $content );

    if( empty( $content ) ) {
        return;
    }

    $forms = adfoin_breakdance_extract_all_forms( $content, $post_id );

    if( empty( $forms ) ) {
        return;
    }

    $fields = array();

    foreach ($forms as $form) {
        if ($form_id == explode('-', $form['form_id'])[0]) {
            foreach ( $form['fields'] as $field ) {
                $fields[$field->advanced->id] = $field->label;
            }
        }
    }

    return $fields;
}
  
//   add_action( 'wp_ajax_breakdance_form_custom', 'adfoin_breakdance_submission', 10, 1 );
  
//   function adfoin_breakdance_submission( $params ) {
//       $post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
//       $form_id = isset( $_POST['form_id'] ) ? sanitize_text_field( $_POST['form_id'] ) : '';
  
//       $integration = new Advanced_Form_Integration_Integration();
//       $saved_records = $integration->get_by_trigger( 'breakdance', $post_id . '_' . $form_id );
  
//       // if( empty( $saved_records ) ) {
//       //     return;
//       // }
  
//       $posted_data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['fields'] );
  
//       global $post;
  
//   }



// add_action( 'init', function() {
//     if ( class_exists( 'Breakdance\Forms\Actions\Action' ) ) {
//         class AFIBreakdanceAction extends Breakdance\Forms\Actions\Action{
//             public static function name() {
//                 return 'Advanced Form Integration';
//             }

//             /**
//              * @return string
//              */
//             public static function slug() {
//                 return 'advanced-form-integration';
//             }

//             /**
//              * @param FormData     $form
//              * @param FormSettings $settings
//              * @param FormExtra    $extra
//              *
//              * @return ActionSuccess|ActionError|array<array-key, ActionSuccess|ActionError>
//              */
//             public function run( $form, $settings, $extra ) {
                
//                 global $wpdb;
//             }
//         }

//         \Breakdance\Forms\Actions\registerAction( new AFIBreakdanceAction() );
//     }
// });