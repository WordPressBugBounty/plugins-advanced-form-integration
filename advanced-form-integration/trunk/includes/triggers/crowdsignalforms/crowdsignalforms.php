<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve Crowdsignal forms.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_crowdsignalforms_get_forms( $form_provider ) {
	if ( 'crowdsignalforms' !== $form_provider ) {
		return;
	}

	$forms = adfoin_crowdsignalforms_fetch_forms();

	if ( empty( $forms ) ) {
		return array();
	}

	asort( $forms, SORT_NATURAL | SORT_FLAG_CASE );

	return $forms;
}

/**
 * Retrieve fields for a Crowdsignal poll block.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Compound identifier.
 * @return array<string,string>|void
 */
function adfoin_crowdsignalforms_get_form_fields( $form_provider, $form_id ) {
	if ( 'crowdsignalforms' !== $form_provider ) {
		return;
	}

	list( $post_id, $client_id ) = adfoin_crowdsignalforms_parse_form_id( $form_id );

	if ( empty( $post_id ) || empty( $client_id ) ) {
		return array();
	}

	$fields = array(
		'post_id'         => __( 'Post ID', 'advanced-form-integration' ),
		'poll_client_id'  => __( 'Poll Client ID', 'advanced-form-integration' ),
		'poll_id'         => __( 'Poll ID', 'advanced-form-integration' ),
		'poll_question'   => __( 'Poll Question', 'advanced-form-integration' ),
		'poll_type'       => __( 'Poll Type', 'advanced-form-integration' ),
		'poll_source_url' => __( 'Poll Source URL', 'advanced-form-integration' ),
	);

	$answers = adfoin_crowdsignalforms_fetch_poll_answers( $post_id, $client_id );

	foreach ( $answers as $answer ) {
		$key = 'answer_' . sanitize_key( $answer['id'] );
		$fields[ $key ] = sprintf( __( 'Answer: %s', 'advanced-form-integration' ), $answer['text'] );
	}

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

add_action( 'rest_api_init', 'adfoin_crowdsignalforms_listen_results_endpoint', 99 );

/**
 * Attach handler to poll results endpoint.
 *
 * @return void
 */
function adfoin_crowdsignalforms_listen_results_endpoint() {
	if ( ! class_exists( '\Crowdsignal_Forms\REST_API\Controllers\Polls_Controller' ) ) {
		return;
	}

	$namespace  = 'crowdsignal-forms/v1';
	$rest_base  = 'polls';

	try {
		$reflection = new \ReflectionClass( '\Crowdsignal_Forms\REST_API\Controllers\Polls_Controller' );
		if ( $reflection->hasConstant( 'REST_BASE' ) ) {
			$rest_base = $reflection->getConstant( 'REST_BASE' );
		} else {
			$instance = new \Crowdsignal_Forms\REST_API\Controllers\Polls_Controller();
			if ( property_exists( $instance, 'rest_base' ) && ! empty( $instance->rest_base ) ) {
				$rest_base = $instance->rest_base;
			}
		}
	} catch ( \Throwable $e ) {
		// Keep default fallback.
	}

	$route = '/' . trim( $rest_base, '/' ) . '/(?P<poll_id>[a-zA-Z0-9\-\_]+)/results';

	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) use ( $namespace, $route ) {
			if (
				$request instanceof \WP_REST_Request &&
				$request->get_route() === '/' . trim( $namespace, '/' ) . $route &&
				$request->get_method() === \WP_REST_Server::READABLE
			) {
				add_filter( 'rest_post_dispatch', 'adfoin_crowdsignalforms_capture_response', 10, 3 );
			}

			return $result;
		},
		10,
		3
	);
}

/**
 * Capture poll results response.
 *
 * @param WP_HTTP_Response $response Response object.
 * @param WP_REST_Server   $server   Server instance.
 * @param WP_REST_Request  $request  Current request.
 * @return WP_HTTP_Response
 */
function adfoin_crowdsignalforms_capture_response( $response, $server, $request ) {
	remove_filter( 'rest_post_dispatch', 'adfoin_crowdsignalforms_capture_response', 10 );

	if ( ! $response instanceof \WP_REST_Response ) {
		return $response;
	}

	$data = $response->get_data();

	if ( empty( $data['poll'] ) || empty( $data['poll']['id'] ) || empty( $data['poll']['answers'] ) ) {
		return $response;
	}

	$poll      = $data['poll'];
	$poll_id   = (string) $poll['id'];
	$client_id = isset( $poll['client_id'] ) ? $poll['client_id'] : '';
	$post_id   = adfoin_crowdsignalforms_find_post_id_by_poll_client( $client_id );

	if ( empty( $post_id ) ) {
		return $response;
	}

	$form_id      = adfoin_crowdsignalforms_build_form_id( $post_id, $client_id );
	$integration  = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'crowdsignalforms', $form_id );

	if ( empty( $saved_records ) ) {
		return $response;
	}

	$payload = array(
		'post_id'         => $post_id,
		'poll_id'         => $poll_id,
		'poll_client_id'  => $client_id,
		'poll_question'   => $poll['question'],
		'poll_type'       => isset( $poll['poll_type'] ) ? $poll['poll_type'] : '',
		'poll_source_url' => isset( $poll['source_link'] ) ? $poll['source_link'] : '',
	);

	foreach ( $poll['answers'] as $answer ) {
		$key             = 'answer_' . sanitize_key( $answer['id'] );
		$payload[ $key ] = isset( $answer['votes'] ) ? (int) $answer['votes'] : 0;
	}

	$post               = adfoin_get_post_object();
	$special_tag_values = adfoin_get_special_tags_values( $post );

	if ( is_array( $special_tag_values ) ) {
		$payload = array_merge( $payload, $special_tag_values );
	}

	$integration->send( $saved_records, $payload );

	return $response;
}

/**
 * Fetch blocks to build mapping choices.
 *
 * @return array<string,string>
 */
function adfoin_crowdsignalforms_fetch_forms() {
	$forms = array();

	$posts = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => array( 'publish', 'draft', 'future' ),
			'posts_per_page' => -1,
			'suppress_filters' => false,
		)
	);

	foreach ( $posts as $post ) {
		$blocks = parse_blocks( $post->post_content );
		adfoin_crowdsignalforms_collect_from_blocks( $forms, $blocks, $post );
	}

	return $forms;
}

/**
 * Collect poll blocks recursively.
 *
 * @param array<string,string> $forms  Accumulator.
 * @param array                $blocks Gutenberg blocks.
 * @param WP_Post              $post   Parent post.
 * @return void
 */
function adfoin_crowdsignalforms_collect_from_blocks( &$forms, $blocks, $post ) {
	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}

		if ( strpos( $block['blockName'], 'crowdsignal-forms/' ) === 0 ) {
			$attrs     = isset( $block['attrs'] ) ? $block['attrs'] : array();
			$client_id = isset( $attrs['pollId'] ) ? $attrs['pollId'] : '';

			if ( $client_id ) {
				$key            = adfoin_crowdsignalforms_build_form_id( $post->ID, $client_id );
				$forms[ $key ]  = sprintf( __( '%1$s (%2$s)', 'advanced-form-integration' ), get_the_title( $post ), $client_id );
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			adfoin_crowdsignalforms_collect_from_blocks( $forms, $block['innerBlocks'], $post );
		}
	}
}

/**
 * Build compound form identifier.
 *
 * @param int    $post_id   Post ID.
 * @param string $client_id Client poll ID.
 * @return string
 */
function adfoin_crowdsignalforms_build_form_id( $post_id, $client_id ) {
	return absint( $post_id ) . '|' . sanitize_key( $client_id );
}

/**
 * Split compound identifier.
 *
 * @param string $form_id Compound ID.
 * @return array{0:int,1:string}
 */
function adfoin_crowdsignalforms_parse_form_id( $form_id ) {
	$parts = explode( '|', $form_id );
	$post_id   = isset( $parts[0] ) ? absint( $parts[0] ) : 0;
	$client_id = isset( $parts[1] ) ? sanitize_key( $parts[1] ) : '';

	return array( $post_id, $client_id );
}

/**
 * Find post ID by poll client ID.
 *
 * @param string $client_id Client identifier.
 * @return int
 */
function adfoin_crowdsignalforms_find_post_id_by_poll_client( $client_id ) {
	if ( empty( $client_id ) ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => array( 'publish', 'draft', 'future' ),
			'meta_key'       => '_crowdsignal_forms_poll_id',
			'meta_value'     => $client_id,
			'posts_per_page' => 1,
		)
	);

	if ( ! empty( $posts ) ) {
		return (int) $posts[0]->ID;
	}

	return 0;
}

/**
 * Fetch poll answers from stored meta.
 *
 * @param int    $post_id   Post ID.
 * @param string $client_id Poll client key.
 * @return array<int,array{id:string,text:string}>
 */
function adfoin_crowdsignalforms_fetch_poll_answers( $post_id, $client_id ) {
	$answers = array();

	$post = get_post( $post_id );
	if ( ! $post ) {
		return $answers;
	}

	$blocks = parse_blocks( $post->post_content );

	foreach ( $blocks as $block ) {
		$attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();
		if ( isset( $attrs['pollId'] ) && $attrs['pollId'] === $client_id && isset( $attrs['answers'] ) ) {
			foreach ( (array) $attrs['answers'] as $answer ) {
				if ( isset( $answer['answerId'], $answer['text'] ) ) {
					$answers[] = array(
						'id'   => $answer['answerId'],
						'text' => $answer['text'],
					);
				}
			}
		}
	}

	return $answers;
}
