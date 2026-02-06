<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register MP Timetable trigger list.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_mptimetable_get_forms( $form_provider ) {
	if ( $form_provider !== 'mptimetable' ) {
		return;
	}

	return array(
		'eventSaved' => __( 'Event Created/Updated', 'advanced-form-integration' ),
	);
}

/**
 * Register MP Timetable field map.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 * @return array<string,string>|void
 */
function adfoin_mptimetable_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'mptimetable' ) {
		return;
	}

	if ( $form_id !== 'eventSaved' ) {
		return array();
	}

	return array(
		'event_id'              => __( 'Event ID', 'advanced-form-integration' ),
		'event_title'           => __( 'Event Title', 'advanced-form-integration' ),
		'event_slug'            => __( 'Event Slug', 'advanced-form-integration' ),
		'event_status'          => __( 'Event Status', 'advanced-form-integration' ),
		'event_permalink'       => __( 'Event Permalink', 'advanced-form-integration' ),
		'event_excerpt'         => __( 'Event Excerpt', 'advanced-form-integration' ),
		'event_content'         => __( 'Event Content', 'advanced-form-integration' ),
		'event_subtitle'        => __( 'Event Subtitle', 'advanced-form-integration' ),
		'event_color'           => __( 'Event Color', 'advanced-form-integration' ),
		'event_hover_color'     => __( 'Event Hover Color', 'advanced-form-integration' ),
		'event_text_color'      => __( 'Event Text Color', 'advanced-form-integration' ),
		'event_hover_text_color'=> __( 'Event Hover Text Color', 'advanced-form-integration' ),
		'event_custom_url'      => __( 'Custom Event URL', 'advanced-form-integration' ),
		'event_disable_url'     => __( 'Disable Event Link', 'advanced-form-integration' ),
		'event_categories'      => __( 'Event Categories', 'advanced-form-integration' ),
		'event_category_ids'    => __( 'Event Category IDs', 'advanced-form-integration' ),
		'event_tags'            => __( 'Event Tags', 'advanced-form-integration' ),
		'event_tag_ids'         => __( 'Event Tag IDs', 'advanced-form-integration' ),
		'event_featured_image'  => __( 'Featured Image URL', 'advanced-form-integration' ),
		'event_created_at'      => __( 'Event Created At', 'advanced-form-integration' ),
		'event_modified_at'     => __( 'Event Modified At', 'advanced-form-integration' ),
		'author_id'             => __( 'Author ID', 'advanced-form-integration' ),
		'author_name'           => __( 'Author Display Name', 'advanced-form-integration' ),
		'author_email'          => __( 'Author Email', 'advanced-form-integration' ),
		'timeslot_count'        => __( 'Timeslot Count', 'advanced-form-integration' ),
		'instructor_ids'        => __( 'Instructor IDs', 'advanced-form-integration' ),
		'instructor_names'      => __( 'Instructor Names', 'advanced-form-integration' ),
		'first_start_time'      => __( 'First Start Time', 'advanced-form-integration' ),
		'last_end_time'         => __( 'Last End Time', 'advanced-form-integration' ),
		'timeslots_json'        => __( 'Timeslots (JSON)', 'advanced-form-integration' ),
		'timeslots_plain'       => __( 'Timeslots (Plain Text)', 'advanced-form-integration' ),
		'columns_json'          => __( 'Columns (JSON)', 'advanced-form-integration' ),
		'columns_plain'         => __( 'Columns (Plain Text)', 'advanced-form-integration' ),
		'post_id'               => __( 'Post ID', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_mptimetable_bootstrap', 20 );

/**
 * Register MP Timetable hooks.
 */
function adfoin_mptimetable_bootstrap() {
	if ( ! class_exists( 'Mp_Time_Table' ) ) {
		return;
	}

	add_action( 'save_post_mp-event', 'adfoin_mptimetable_handle_event_save', 100, 3 );
}

/**
 * Handle MP Timetable event save.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post update.
 */
function adfoin_mptimetable_handle_event_save( $post_id, $post, $update ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! $post instanceof WP_Post ) {
		$post = get_post( $post_id );
	}

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( $post->post_type !== 'mp-event' || $post->post_status !== 'publish' ) {
		return;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'mptimetable', 'eventSaved' );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_mptimetable_prepare_payload( $post );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build payload for MP Timetable events.
 *
 * @param WP_Post $post Event post.
 * @return array<string,mixed>
 */
function adfoin_mptimetable_prepare_payload( WP_Post $post ) {
	$timeslots = adfoin_mptimetable_get_timeslots( $post->ID );
	$columns   = adfoin_mptimetable_columns_from_timeslots( $timeslots );

	$category_terms = adfoin_mptimetable_extract_terms( $post->ID, 'mp-event_category' );
	$tag_terms      = adfoin_mptimetable_extract_terms( $post->ID, 'mp-event_tag' );

	$author = get_userdata( $post->post_author );
	$meta   = array(
		'sub_title'             => get_post_meta( $post->ID, 'sub_title', true ),
		'color'                 => get_post_meta( $post->ID, 'color', true ),
		'hover_color'           => get_post_meta( $post->ID, 'hover_color', true ),
		'text_color'            => get_post_meta( $post->ID, 'text_color', true ),
		'hover_text_color'      => get_post_meta( $post->ID, 'hover_text_color', true ),
		'timetable_custom_url'  => get_post_meta( $post->ID, 'timetable_custom_url', true ),
		'timetable_disable_url' => get_post_meta( $post->ID, 'timetable_disable_url', true ),
	);

	$instructors = adfoin_mptimetable_collect_instructors( $timeslots );
	$time_bounds = adfoin_mptimetable_time_bounds( $timeslots );

	$data = array(
		'post_id'              => $post->ID,
		'event_id'             => $post->ID,
		'event_title'          => $post->post_title,
		'event_slug'           => $post->post_name,
		'event_status'         => $post->post_status,
		'event_permalink'      => get_permalink( $post ),
		'event_excerpt'        => $post->post_excerpt,
		'event_content'        => $post->post_content,
		'event_subtitle'       => $meta['sub_title'],
		'event_color'          => $meta['color'],
		'event_hover_color'    => $meta['hover_color'],
		'event_text_color'     => $meta['text_color'],
		'event_hover_text_color' => $meta['hover_text_color'],
		'event_custom_url'     => $meta['timetable_custom_url'],
		'event_disable_url'    => adfoin_mptimetable_bool_to_string( $meta['timetable_disable_url'] ),
		'event_categories'     => implode( ', ', $category_terms['names'] ),
		'event_category_ids'   => implode( ', ', $category_terms['ids'] ),
		'event_tags'           => implode( ', ', $tag_terms['names'] ),
		'event_tag_ids'        => implode( ', ', $tag_terms['ids'] ),
		'event_featured_image' => get_the_post_thumbnail_url( $post, 'full' ),
		'event_created_at'     => $post->post_date,
		'event_modified_at'    => $post->post_modified,
		'author_id'            => $author ? $author->ID : '',
		'author_name'          => $author ? $author->display_name : '',
		'author_email'         => $author ? $author->user_email : '',
		'timeslot_count'       => count( $timeslots ),
		'instructor_ids'       => implode( ', ', array_keys( $instructors ) ),
		'instructor_names'     => implode( ', ', array_filter( $instructors ) ),
		'first_start_time'     => $time_bounds['first'],
		'last_end_time'        => $time_bounds['last'],
		'timeslots_json'       => wp_json_encode( $timeslots ),
		'timeslots_plain'      => adfoin_mptimetable_timeslots_plain( $timeslots ),
		'columns_json'         => wp_json_encode( $columns ),
		'columns_plain'        => adfoin_mptimetable_columns_plain( $columns ),
	);

	return apply_filters( 'adfoin_mptimetable_payload', $data, $post, $timeslots, $columns );
}

/**
 * Fetch normalized timeslots for an event.
 *
 * @param int $event_id Event ID.
 * @return array<int,array<string,mixed>>
 */
function adfoin_mptimetable_get_timeslots( $event_id ) {
	if ( ! class_exists( '\mp_timetable\classes\models\Events' ) ) {
		return array();
	}

	$events_model = \mp_timetable\classes\models\Events::get_instance();
	$records      = $events_model->get_event_data(
		array(
			'field' => 'event_id',
			'id'    => (int) $event_id,
		),
		'event_start',
		false
	);

	$timeslots = array();

	if ( empty( $records ) ) {
		return $timeslots;
	}

	foreach ( $records as $record ) {
		$timeslots[] = adfoin_mptimetable_normalize_timeslot( $record );
	}

	return $timeslots;
}

/**
 * Normalize single timeslot record.
 *
 * @param object $record Raw record from MP Timetable.
 * @return array<string,mixed>
 */
function adfoin_mptimetable_normalize_timeslot( $record ) {
	$column_id    = isset( $record->column_id ) ? (int) $record->column_id : 0;
	$column_post  = $column_id ? get_post( $column_id ) : null;
	$column_title = $column_post ? $column_post->post_title : '';

	$timeslot = array(
		'timeslot_id'     => isset( $record->id ) ? (int) $record->id : 0,
		'event_id'        => isset( $record->event_id ) ? (int) $record->event_id : 0,
		'column_id'       => $column_id,
		'column_title'    => $column_title,
		'column_option'   => $column_id ? get_post_meta( $column_id, 'column_option', true ) : '',
		'column_weekday'  => $column_id ? get_post_meta( $column_id, 'weekday', true ) : '',
		'column_date'     => $column_id ? get_post_meta( $column_id, 'option_day', true ) : '',
		'start_time'      => isset( $record->event_start ) ? $record->event_start : '',
		'end_time'        => isset( $record->event_end ) ? $record->event_end : '',
		'instructor_id'   => isset( $record->user_id ) ? (int) $record->user_id : 0,
		'instructor_name' => adfoin_mptimetable_identify_instructor( $record ),
		'description'     => isset( $record->description ) ? wp_strip_all_tags( $record->description ) : '',
	);

	return $timeslot;
}

/**
 * Determine instructor display name.
 *
 * @param object $record Timeslot record.
 * @return string
 */
function adfoin_mptimetable_identify_instructor( $record ) {
	if ( isset( $record->user ) && is_object( $record->user ) && ! empty( $record->user->display_name ) ) {
		return $record->user->display_name;
	}

	if ( ! empty( $record->user_id ) ) {
		$user = get_userdata( (int) $record->user_id );
		if ( $user ) {
			return $user->display_name;
		}
	}

	return '';
}

/**
 * Extract unique column data from timeslots.
 *
 * @param array<int,array<string,mixed>> $timeslots Timeslot data.
 * @return array<int,array<string,mixed>>
 */
function adfoin_mptimetable_columns_from_timeslots( $timeslots ) {
	if ( empty( $timeslots ) ) {
		return array();
	}

	$columns = array();

	foreach ( $timeslots as $slot ) {
		$column_id = isset( $slot['column_id'] ) ? (int) $slot['column_id'] : 0;
		if ( ! $column_id || isset( $columns[ $column_id ] ) ) {
			continue;
		}

		$columns[ $column_id ] = array(
			'column_id'      => $column_id,
			'column_title'   => isset( $slot['column_title'] ) ? $slot['column_title'] : '',
			'column_option'  => isset( $slot['column_option'] ) ? $slot['column_option'] : '',
			'column_weekday' => isset( $slot['column_weekday'] ) ? $slot['column_weekday'] : '',
			'column_date'    => isset( $slot['column_date'] ) ? $slot['column_date'] : '',
		);
	}

	return array_values( $columns );
}

/**
 * Convert columns array to readable lines.
 *
 * @param array<int,array<string,mixed>> $columns Column data.
 * @return string
 */
function adfoin_mptimetable_columns_plain( $columns ) {
	if ( empty( $columns ) ) {
		return '';
	}

	$lines = array();
	foreach ( $columns as $column ) {
		$parts   = array();
		$parts[] = ! empty( $column['column_title'] )
			? $column['column_title']
			: sprintf( __( 'Column #%d', 'advanced-form-integration' ), $column['column_id'] );

		if ( ! empty( $column['column_option'] ) ) {
			$parts[] = sprintf( __( 'Type: %s', 'advanced-form-integration' ), $column['column_option'] );
		}

		if ( ! empty( $column['column_weekday'] ) ) {
			$parts[] = sprintf( __( 'Weekday: %s', 'advanced-form-integration' ), ucfirst( $column['column_weekday'] ) );
		}

		if ( ! empty( $column['column_date'] ) ) {
			$parts[] = sprintf( __( 'Date: %s', 'advanced-form-integration' ), $column['column_date'] );
		}

		$lines[] = implode( ' | ', array_filter( $parts ) );
	}

	return implode( "\n", array_filter( $lines ) );
}

/**
 * Convert timeslot array to readable lines.
 *
 * @param array<int,array<string,mixed>> $timeslots Timeslot data.
 * @return string
 */
function adfoin_mptimetable_timeslots_plain( $timeslots ) {
	if ( empty( $timeslots ) ) {
		return '';
	}

	$lines = array();
	foreach ( $timeslots as $slot ) {
		$parts = array();

		if ( ! empty( $slot['column_title'] ) ) {
			$parts[] = $slot['column_title'];
		}

		$time_range = trim( $slot['start_time'] . ' - ' . $slot['end_time'] );
		if ( $time_range ) {
			$parts[] = $time_range;
		}

		if ( ! empty( $slot['instructor_name'] ) ) {
			$parts[] = sprintf( __( 'Instructor: %s', 'advanced-form-integration' ), $slot['instructor_name'] );
		}

		if ( ! empty( $slot['description'] ) ) {
			$parts[] = sprintf( __( 'Notes: %s', 'advanced-form-integration' ), $slot['description'] );
		}

		$lines[] = implode( ' | ', array_filter( $parts ) );
	}

	return implode( "\n", array_filter( $lines ) );
}

/**
 * Gather instructor map.
 *
 * @param array<int,array<string,mixed>> $timeslots Timeslot data.
 * @return array<int,string>
 */
function adfoin_mptimetable_collect_instructors( $timeslots ) {
	$instructors = array();

	foreach ( $timeslots as $slot ) {
		if ( empty( $slot['instructor_id'] ) ) {
			continue;
		}

		$instructors[ (int) $slot['instructor_id'] ] = isset( $slot['instructor_name'] ) ? $slot['instructor_name'] : '';
	}

	return $instructors;
}

/**
 * Calculate first/last slot times.
 *
 * @param array<int,array<string,mixed>> $timeslots Timeslot data.
 * @return array{first:string,last:string}
 */
function adfoin_mptimetable_time_bounds( $timeslots ) {
	$starts = array();
	$ends   = array();

	foreach ( $timeslots as $slot ) {
		if ( ! empty( $slot['start_time'] ) ) {
			$starts[] = $slot['start_time'];
		}

		if ( ! empty( $slot['end_time'] ) ) {
			$ends[] = $slot['end_time'];
		}
	}

	sort( $starts );
	sort( $ends );

	return array(
		'first' => $starts ? reset( $starts ) : '',
		'last'  => $ends ? end( $ends ) : '',
	);
}

/**
 * Normalize yes/no flag.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adfoin_mptimetable_bool_to_string( $value ) {
	return empty( $value ) ? 'no' : 'yes';
}

/**
 * Get taxonomy data.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy key.
 * @return array{names:array<int,string>,ids:array<int,string>}
 */
function adfoin_mptimetable_extract_terms( $post_id, $taxonomy ) {
	$result = array(
		'names' => array(),
		'ids'   => array(),
	);

	$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'all' ) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $result;
	}

	foreach ( $terms as $term ) {
		$result['names'][] = $term->name;
		$result['ids'][]   = (string) $term->term_id;
	}

	return $result;
}
