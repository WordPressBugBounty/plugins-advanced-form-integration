<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UserFeedback (Lite) trigger — fires when a visitor submits a survey/
 * feedback-form response.
 *
 * Confirmed against the plugin's own source
 * (includes/frontend/class-userfeedback-frontend.php, save_survey_response()):
 *
 *     do_action( 'userfeedback_survey_response', $survey_id, $response_id, $request->get_json_params() );
 *
 * fires right after the response is persisted via UserFeedback_Response::create(),
 * from a public REST route (`permission_callback => '__return_true'`) — works
 * for anonymous visitors. Each submitted answer is `{question_id, value, extra}`;
 * question_id is resolved to its human-readable title via the survey's own
 * `questions` array (UserFeedback_Survey::find($survey_id)->questions, each with
 * ->id/->title/->type), matching how the plugin's own admin results page
 * (class-userfeedback-results.php) resolves the same data.
 *
 * @link https://plugins.trac.wordpress.org/browser/userfeedback-lite/trunk/includes/frontend/class-userfeedback-frontend.php
 * @link https://plugins.trac.wordpress.org/browser/userfeedback-lite/trunk/includes/db/class-userfeedback-survey.php
 */

add_action( 'plugins_loaded', 'adfoin_userfeedback_register_hooks', 20 );

function adfoin_userfeedback_register_hooks() {
    if ( ! class_exists( 'UserFeedback' ) ) {
        return;
    }

    add_action( 'userfeedback_survey_response', 'adfoin_userfeedback_handle_response', 10, 3 );
}

// Get UserFeedback Surveys
function adfoin_userfeedback_get_forms( $form_provider ) {
    if ( $form_provider !== 'userfeedback' ) {
        return;
    }

    if ( ! class_exists( 'UserFeedback_Survey' ) ) {
        return array();
    }

    $forms   = array();
    $surveys = UserFeedback_Survey::where( array( 'status' => 'publish' ) )->get();

    if ( is_array( $surveys ) ) {
        foreach ( $surveys as $survey ) {
            if ( is_object( $survey ) && isset( $survey->id ) ) {
                $forms[ $survey->id ] = isset( $survey->title ) ? $survey->title : ( 'Survey ' . $survey->id );
            }
        }
    }

    return $forms;
}

// Get UserFeedback Fields
function adfoin_userfeedback_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'userfeedback' || ! $form_id ) {
        return;
    }

    if ( ! class_exists( 'UserFeedback_Survey' ) ) {
        return array();
    }

    $fields = array();
    $survey = UserFeedback_Survey::find( $form_id );

    if ( $survey && ! empty( $survey->questions ) && is_array( $survey->questions ) ) {
        foreach ( $survey->questions as $question ) {
            if ( ! is_object( $question ) || empty( $question->id ) ) {
                continue;
            }
            $fields[ 'q_' . $question->id ] = ! empty( $question->title ) ? $question->title : ( 'Question ' . $question->id );
        }
    }

    $fields['response_id']    = __( 'Response ID', 'advanced-form-integration' );
    $fields['page_submitted'] = __( 'Page Submitted On', 'advanced-form-integration' );
    $fields['user_ip']        = __( 'Visitor IP', 'advanced-form-integration' );
    $fields['user_browser']   = __( 'Visitor Browser', 'advanced-form-integration' );
    $fields['user_os']        = __( 'Visitor OS', 'advanced-form-integration' );
    $fields['user_device']    = __( 'Visitor Device Type', 'advanced-form-integration' );

    return $fields;
}

// Handle Survey Response Submitted
function adfoin_userfeedback_handle_response( $survey_id, $response_id, $json_params ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'userfeedback', $survey_id );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'response_id' => $response_id,
    );

    if ( ! empty( $json_params['answers'] ) && is_array( $json_params['answers'] ) ) {
        foreach ( $json_params['answers'] as $answer ) {
            if ( empty( $answer['question_id'] ) ) {
                continue;
            }
            $value = isset( $answer['value'] ) ? $answer['value'] : '';
            $posted_data[ 'q_' . $answer['question_id'] ] = is_array( $value ) ? implode( ', ', $value ) : $value;
        }
    }

    if ( ! empty( $json_params['page_submitted']['name'] ) ) {
        $posted_data['page_submitted'] = $json_params['page_submitted']['name'];
    }

    // Recomputed within the same request that triggered the response (not a
    // delayed/cron context), so these match exactly what the plugin's own
    // save_survey_response() just stored for this visitor.
    if ( class_exists( 'UserFeedback_Device_Detect' ) ) {
        $posted_data['user_ip']      = UserFeedback_Device_Detect::ip();
        $posted_data['user_browser'] = UserFeedback_Device_Detect::browser();
        $posted_data['user_os']      = UserFeedback_Device_Detect::os();
        $posted_data['user_device']  = UserFeedback_Device_Detect::deviceType();
    }

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
