<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get forms.
 *
 * @param string $form_provider Provider name.
 *
 * @return array|void
 */
function adfoin_wordpress_get_forms( $form_provider ) {
	if ( 'wordpress' !== $form_provider ) {
		return;
	}

	return array(
		'user_registered'      => __( 'New User Registration', 'advanced-form-integration' ),
		'user_profile_updated' => __( 'User Profile Updated', 'advanced-form-integration' ),
		'post_published'       => __( 'Post Published', 'advanced-form-integration' ),
		'user_logs_in'         => __( 'User Logs In', 'advanced-form-integration' ),
		'new_comment'          => __( 'New Comment Posted', 'advanced-form-integration' ),
		'new_media'            => __( 'New Media Uploaded', 'advanced-form-integration' ),
	);
}

/**
 * Get form fields.
 *
 * @param string $form_provider Provider name.
 * @param string $form_id       Form ID.
 *
 * @return array|void
 */
function adfoin_wordpress_get_form_fields( $form_provider, $form_id ) {
	if ( 'wordpress' !== $form_provider ) {
		return;
	}

	switch ( $form_id ) {
		case 'user_registered':
		case 'user_profile_updated':
		case 'user_logs_in':
			return adfoin_wordpress_get_user_fields();
		case 'post_published':
			return adfoin_wordpress_get_post_fields();
		case 'new_comment':
			return adfoin_wordpress_get_comment_fields();
		case 'new_media':
			return adfoin_wordpress_get_media_fields();
	}
}

/**
 * Get user fields.
 *
 * @return array
 */
function adfoin_wordpress_get_user_fields() {
	return array(
		'ID'           => __( 'User ID', 'advanced-form-integration' ),
		'user_login'   => __( 'Username', 'advanced-form-integration' ),
		'user_email'   => __( 'Email', 'advanced-form-integration' ),
		'display_name' => __( 'Display Name', 'advanced-form-integration' ),
		'first_name'   => __( 'First Name', 'advanced-form-integration' ),
		'last_name'    => __( 'Last Name', 'advanced-form-integration' ),
		'roles'        => __( 'Roles', 'advanced-form-integration' ),
	);
}

/**
 * Get post fields.
 *
 * @return array
 */
function adfoin_wordpress_get_post_fields() {
	return array(
		'ID'           => __( 'Post ID', 'advanced-form-integration' ),
		'post_title'   => __( 'Post Title', 'advanced-form-integration' ),
		'post_content' => __( 'Post Content', 'advanced-form-integration' ),
		'post_excerpt' => __( 'Post Excerpt', 'advanced-form-integration' ),
		'post_status'  => __( 'Post Status', 'advanced-form-integration' ),
		'post_type'    => __( 'Post Type', 'advanced-form-integration' ),
		'permalink'    => __( 'Permalink', 'advanced-form-integration' ),
		'author_ID'    => __( 'Author User ID', 'advanced-form-integration' ),
		'author_email' => __( 'Author Email', 'advanced-form-integration' ),
		'author_name'  => __( 'Author Name', 'advanced-form-integration' ),
	);
}

/**
 * Get comment fields.
 *
 * @return array
 */
function adfoin_wordpress_get_comment_fields() {
	return array(
		'comment_ID'           => __( 'Comment ID', 'advanced-form-integration' ),
		'comment_post_ID'      => __( 'Post ID', 'advanced-form-integration' ),
		'comment_author'       => __( 'Author Name', 'advanced-form-integration' ),
		'comment_author_email' => __( 'Author Email', 'advanced-form-integration' ),
		'comment_author_url'   => __( 'Author URL', 'advanced-form-integration' ),
		'comment_author_IP'    => __( 'Author IP', 'advanced-form-integration' ),
		'comment_date'         => __( 'Date', 'advanced-form-integration' ),
		'comment_content'      => __( 'Content', 'advanced-form-integration' ),
		'comment_approved'     => __( 'Approved Status', 'advanced-form-integration' ),
		'comment_agent'        => __( 'User Agent', 'advanced-form-integration' ),
		'comment_type'         => __( 'Type', 'advanced-form-integration' ),
		'comment_parent'       => __( 'Parent Comment ID', 'advanced-form-integration' ),
		'user_id'              => __( 'User ID (if logged in)', 'advanced-form-integration' ),
	);
}

/**
 * Get media fields.
 *
 * @return array
 */
function adfoin_wordpress_get_media_fields() {
	return array(
		'attachment_id'    => __( 'Attachment ID', 'advanced-form-integration' ),
		'url'              => __( 'File URL', 'advanced-form-integration' ),
		'title'            => __( 'Title', 'advanced-form-integration' ),
		'caption'          => __( 'Caption', 'advanced-form-integration' ),
		'alt_text'         => __( 'Alt Text', 'advanced-form-integration' ),
		'description'      => __( 'Description', 'advanced-form-integration' ),
		'mime_type'        => __( 'MIME Type', 'advanced-form-integration' ),
		'file_name'        => __( 'File Name', 'advanced-form-integration' ),
		'uploader_user_id' => __( 'Uploader User ID', 'advanced-form-integration' ),
	);
}

add_action( 'user_register', 'adfoin_wordpress_handle_user_registered', 10, 1 );

/**
 * Handle user registration.
 *
 * @param int $user_id User ID.
 */
function adfoin_wordpress_handle_user_registered( $user_id ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'wordpress', 'user_registered' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_wordpress_prepare_user_payload( $user_id );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

add_action( 'profile_update', 'adfoin_wordpress_handle_user_profile_updated', 10, 2 );

/**
 * Handle user profile update.
 *
 * @param int     $user_id       User ID.
 * @param WP_User $old_user_data Old user data.
 */
function adfoin_wordpress_handle_user_profile_updated( $user_id, $old_user_data ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'wordpress', 'user_profile_updated' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_wordpress_prepare_user_payload( $user_id );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

add_action( 'transition_post_status', 'adfoin_wordpress_handle_post_published', 10, 3 );

/**
 * Handle post published.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function adfoin_wordpress_handle_post_published( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}

	if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
		return;
	}

	// Ignore revisions and other non-public post types.
	if ( ! is_post_type_viewable( $post->post_type ) ) {
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'wordpress', 'post_published' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_wordpress_prepare_post_payload( $post );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

add_action( 'wp_login', 'adfoin_wordpress_handle_user_login', 10, 2 );

/**
 * Handle user login.
 *
 * @param string  $user_login User login.
 * @param WP_User $user       User object.
 */
function adfoin_wordpress_handle_user_login( $user_login, $user ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'wordpress', 'user_logs_in' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_wordpress_prepare_user_payload( $user->ID );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

add_action( 'wp_insert_comment', 'adfoin_wordpress_handle_new_comment', 10, 2 );

/**
 * Handle new comment.
 *
 * @param int        $id      Comment ID.
 * @param WP_Comment $comment Comment object.
 */
function adfoin_wordpress_handle_new_comment( $id, $comment ) {
	if ( '1' !== $comment->comment_approved ) {
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'wordpress', 'new_comment' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_wordpress_prepare_comment_payload( $comment );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

add_action( 'add_attachment', 'adfoin_wordpress_handle_new_media', 10, 1 );

/**
 * Handle new media upload.
 *
 * @param int $attachment_id Attachment ID.
 */
function adfoin_wordpress_handle_new_media( $attachment_id ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'wordpress', 'new_media' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_wordpress_prepare_media_payload( $attachment_id );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

/**
 * Prepare user payload.
 *
 * @param int $user_id User ID.
 *
 * @return array
 */
function adfoin_wordpress_prepare_user_payload( $user_id ) {
	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return array();
	}

	return array(
		'ID'           => $user->ID,
		'user_login'   => $user->user_login,
		'user_email'   => $user->user_email,
		'display_name' => $user->display_name,
		'first_name'   => $user->first_name,
		'last_name'    => $user->last_name,
		'roles'        => implode( ', ', $user->roles ),
	);
}

/**
 * Prepare post payload.
 *
 * @param WP_Post $post Post object.
 *
 * @return array
 */
function adfoin_wordpress_prepare_post_payload( $post ) {
	$author_id = (int) $post->post_author;
	$author    = get_userdata( $author_id );

	return array(
		'ID'           => $post->ID,
		'post_title'   => $post->post_title,
		'post_content' => $post->post_content,
		'post_excerpt' => $post->post_excerpt,
		'post_status'  => $post->post_status,
		'post_type'    => $post->post_type,
		'permalink'    => get_permalink( $post ),
		'author_ID'    => $author_id,
		'author_email' => $author ? $author->user_email : '',
		'author_name'  => $author ? $author->display_name : '',
	);
}

/**
 * Prepare comment payload.
 *
 * @param WP_Comment $comment Comment object.
 *
 * @return array
 */
function adfoin_wordpress_prepare_comment_payload( $comment ) {
	return array(
		'comment_ID'           => $comment->comment_ID,
		'comment_post_ID'      => $comment->comment_post_ID,
		'comment_author'       => $comment->comment_author,
		'comment_author_email' => $comment->comment_author_email,
		'comment_author_url'   => $comment->comment_author_url,
		'comment_author_IP'    => $comment->comment_author_IP,
		'comment_date'         => $comment->comment_date,
		'comment_content'      => $comment->comment_content,
		'comment_approved'     => $comment->comment_approved,
		'comment_agent'        => $comment->comment_agent,
		'comment_type'         => $comment->comment_type,
		'comment_parent'       => $comment->comment_parent,
		'user_id'              => $comment->user_id,
	);
}

/**
 * Prepare media payload.
 *
 * @param int $attachment_id Attachment ID.
 *
 * @return array
 */
function adfoin_wordpress_prepare_media_payload( $attachment_id ) {
	$attachment = get_post( $attachment_id );

	if ( ! $attachment ) {
		return array();
	}

	$file_path = get_attached_file( $attachment_id );

	return array(
		'attachment_id'    => $attachment_id,
		'url'              => wp_get_attachment_url( $attachment_id ),
		'title'            => $attachment->post_title,
		'caption'          => $attachment->post_excerpt,
		'alt_text'         => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'description'      => $attachment->post_content,
		'mime_type'        => $attachment->post_mime_type,
		'file_name'        => $file_path ? basename( $file_path ) : '',
		'uploader_user_id' => $attachment->post_author,
	);
}
