<?php
/**
 * MediaThèque avatars.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Make sure the avatar sizes used by WordPress are all in Rest Avatar sizes.
 *
 * @since 1.0.0
 *
 * @param  array $sizes The avatar sizes.
 * @return array        The avatar sizes.
 */
function mediatheque_rest_avatar_sizes( $sizes = array() ) {
	$required = apply_filters( 'mediatheque_required_avatar_sizes', array( 96, 192 ) );
	return array_merge( $sizes, $required );
}
add_filter( 'rest_avatar_sizes', 'mediatheque_rest_avatar_sizes', 10, 1 );

/**
 * Get the personal avatar using the User Media ID.
 *
 * @since 1.0.0
 *
 * @param  int     $user_media_id The User Media ID.
 * @param  int     $size          The size in pixels for the avatar.
 * @return string                 The avatar URL.
 */
function mediatheque_get_personal_avatar( $user_media_id = 0, $size = 96 ) {
	$mediatheque = mediatheque();

	if ( ! empty( $mediatheque->personal_avatars[ $user_media_id ][ $size ] ) ) {
		$personal_avatar = $mediatheque->personal_avatars[ $user_media_id ][ $size ];
	} else {
		$mediatheque->personal_avatars[ $user_media_id ][ $size ] = mediatheque_image_get_intermediate_size( $user_media_id, array( $size, $size ) );
		$personal_avatar = $mediatheque->personal_avatars[ $user_media_id ][ $size ];
	}

	if ( empty( $personal_avatar['url'] ) ) {
		return false;
	}

	return $personal_avatar['url'];
}

/**
 * Use the personal avatar url when available.
 *
 * @since 1.0.0
 *
 * @param  array $args        Default data.
 * @param  mixed $id_or_email A user ID, email, a User, a Post or a Comment object.
 * @return array              Avatar data.
 */
function mediatheque_get_avatar_data( $args = array(), $id_or_email = null ) {
	if ( empty( $id_or_email ) ) {
		return $args;
	}

	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', (int) $id_or_email );
	} else if ( is_a( $id_or_email, 'WP_User' ) ) {
		$user = $id_or_email;
	} else if ( is_a( $id_or_email, 'WP_Post' ) ) {
		$user = get_user_by( 'id', (int) $id_or_email->post_author );
	} else if ( is_a( $id_or_email, 'WP_Comment' ) ) {
		$user = get_user_by( 'id', (int) $id_or_email->user_id );
	} else if ( is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}

	if ( empty( $user->ID ) ) {
		return $args;
	}

	$personal_avatar_id = $user->personal_avatar;

	if ( ! $personal_avatar_id ) {
		return $args;
	}

	$personal_avatar_url = mediatheque_get_personal_avatar( $personal_avatar_id, $args['size'] );

	if ( ! $personal_avatar_url ) {
		return $args;
	}

	return array_merge( $args, array( 'url' => $personal_avatar_url ) );
}
add_filter( 'pre_get_avatar_data', 'mediatheque_get_avatar_data', 10, 2 );

/**
 * Restrict the User Media Status to Publish for Avatar selection.
 *
 * @since 1.0.0
 *
 * @param  array $statuses The list of available User Media statuses.
 * @return array           The list of available User Media statuses for the Avatar UI.
 */
function mediatheque_avatar_user_media_statuses( $statuses = array() ) {
	return array_intersect_key( $statuses, array( 'publish' => true ) );
}

/**
 * Output a button on User's dashboard profile to select one of his User Media
 * and set it as his personal avatar.
 *
 * @since  1.0.0
 *
 * @param  WP_User $user The current User object.
 * @return string        HTML Output.
 */
function mediatheque_profile_personal_avatar( $user = null ) {
	$message = '';

	if ( ! current_user_can( 'publish_user_uploads' ) ) {
		return;
	}

	if ( $user->personal_avatar ) {
		$message = sprintf(
			__( 'Pour supprimer votre avatar local, vous pouvez %s.', 'mediatheque' ),
			sprintf( '<a href="#" class="mediabrary-remove">%s</a>', __( 'cliquer ici', 'mediatheque' ) )
		);
	}

	add_filter( 'mediatheque_media_statuses', 'mediatheque_avatar_user_media_statuses', 10, 1 );
	?>
	<div id="personal-avatar-editor">
		<p class="description"><?php printf(
			__( 'Vous pouvez également utiliser une des images de votre %1$s comme avatar pour ce site. %2$s', 'mediatheque' ),
			mediatheque_button( array(
				'editor_id'           => 'personal_avatar',
				'editor_btn_classes'  => array( 'mediabrary-insert' ),
				'editor_btn_text'     => __( 'MediaThèque', 'mediatheque' ),
				'editor_btn_dashicon' => false,
				'echo'                => false,
				'media_type'          => 'image',
			) ),
			'<span id="mediabrary-remove-message">' . $message . '</span>'
		); ?></p>

	</div>
	<?php
	remove_filter( 'mediatheque_media_statuses', 'mediatheque_avatar_user_media_statuses', 10, 1 );
}
add_action( 'profile_personal_options', 'mediatheque_profile_personal_avatar', 10, 1 );
