<?php
/**
 * MediaThèque Users.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the capabilities for the User media post type.
 *
 * @since  1.0.0
 *
 * @return array The capabilities for the User media post type.
 */
function mediatheque_capabilities() {
	return array(
		'edit_post'              => 'edit_user_upload',
		'read_post'              => 'read_user_upload',
		'delete_post'            => 'delete_user_upload',
		'edit_posts'             => 'edit_user_uploads',
		'edit_others_posts'	     => 'edit_others_user_uploads',
		'publish_posts'          => 'publish_user_uploads',
		'read_private_posts'     => 'read_private_user_uploads',
		'read'                   => 'read_user_upload',
		'delete_posts'           => 'delete_user_uploads',
		'delete_private_posts'   => 'delete_private_user_uploads',
		'delete_published_posts' => 'delete_published_user_uploads',
		'delete_others_posts'    => 'delete_others_user_uploads',
		'edit_private_posts'     => 'edit_private_user_uploads',
		'edit_published_posts'   => 'edit_published_user_uploads',
		'create_posts'           => 'create_user_uploads',
	);
}

/**
 * Get the capabilities for the User media types.
 *
 * @since  1.0.0
 *
 * @return array The capabilities for the User media types.
 */
function mediatheque_types_capabilities() {
	return array(
		'manage_terms' => 'manage_upload_types',
		'edit_terms'   => 'edit_upload_types',
		'delete_terms' => 'delete_upload_types',
		'assign_terms' => 'assign_upload_types',
	);
}

/**
 * Get All capabilities for User Media Objects.
 *
 * @since  1.0.0
 *
 * @return array All capabilities for User Media Objects.
 */
function mediatheque_get_all_caps() {
	return array_merge( mediatheque_capabilities(), mediatheque_types_capabilities() );
}

/**
 * Map capabilities for User Media
 *
 * @since 1.0.0
 *
 * @param  array  $caps    Capabilities for meta capability
 * @param  string $cap     Capability name
 * @param  int    $user_id User id
 * @param  mixed  $args    Arguments
 * @return array           Actual capabilities for meta capability
 */
function mediatheque_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	if ( in_array( $cap, mediatheque_get_all_caps(), true ) ) {
		if ( $user_id ) {
			$required_cap = mediatheque_get_required_cap();
			$admin_caps   = array_diff_key( mediatheque_types_capabilities(), array( 'assign_terms' => false ) );
			$admin_caps   = array_merge( $admin_caps, array(
				'edit_user_uploads',
				'edit_others_user_uploads',
				'delete_user_uploads',
				'delete_private_user_uploads',
				'delete_published_user_uploads',
				'delete_others_user_uploads',
				'edit_private_user_uploads',
				'edit_published_user_uploads',
			) );

			$admin_cap = 'manage_options';
			if ( is_multisite() ) {
				$admin_cap = 'manage_network_options';
			}

			if ( in_array( $cap, $admin_caps, true ) ) {
				$caps = array( $admin_cap );
			} else {
				$caps = array( $required_cap );
				$manage_caps = array(
					'edit_user_upload',
					'delete_user_upload',
				);

				$author = 0;
				if ( ! empty( $args[0] ) ) {
					$author = get_post_field( 'post_author', $args[0] );
				}

				if ( in_array( $cap, $manage_caps, true ) && ( ! $author || (int) $author !== (int) $user_id ) ) {
					$caps = array( $admin_cap );
				}
			}
		}

	// Allow regular users to set the User Media display preference if WP Editor is used from front-end.
	} elseif ( wp_doing_ajax() && isset( $_POST['action'] ) && 'parse-embed' === $_POST['action'] ) {
		if ( $user_id && ! empty( $_POST['shortcode'] ) ) {
			$url = str_replace( array( '[embed]', '[/embed]' ), '', $_POST['shortcode'] );

			if ( 0 === strpos( $url, trailingslashit( network_site_url() ) . mediatheque_get_root_slug() ) ) {
				$caps = array( mediatheque_get_required_cap() );
			}
		}
	}

	/**
	 * Filter here to edit the capabilities map.
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $caps    Capabilities for meta capability
	 * @param  string $cap     Capability name
	 * @param  int    $user_id User id
	 * @param  mixed  $args    Arguments
	 */
	return apply_filters( 'mediatheque_map_meta_caps', $caps, $cap, $user_id, $args );
}

/**
 * Sanitize the disk usage or personal avatar id user meta.
 *
 * @since 1.0.0
 *
 * @param  int    $value    The raw value of the disk usage user meta.
 * @param  string $meta_key The user meta key.
 * @return int    $value    The sanitized disk usage user meta.
 */
function mediatheque_meta_sanitize_value( $value = '', $meta_key = '' ) {
	if ( '_mediatheque_disk_usage' === $meta_key || 'personal_avatar' === $meta_key ) {
		$value = (int) $value;
	}

	return $value;
}

/**
 * Auth callback for the Personal avatar usermeta.
 *
 * @since 1.0.0
 *
 * @param boolean  $auth      True to allow edit. False otherwise.
 * @param  string  $meta_key  The usermeta key.
 * @param  integer $object_id The Object Id.
 * @param  integer $user_id   The User ID.
 * @return boolean            True to allow edit. False otherwise.
 */
function mediatheque_meta_auth_personal_avatar( $auth = false, $meta_key = '', $object_id = 0, $user_id = 0 ) {
	if ( 'personal_avatar' !== $meta_key ) {
		return $auth;
	}

	return ! empty( $object_id ) && ( (int) $object_id === (int) $user_id || is_super_admin() );
}

/**
 * Prepare the disk usage user meta for rest requests.
 *
 * @since 1.0.0
 *
 * @param  mixed           $value   Meta value to prepare.
 * @param  WP_REST_Request $request Rest request object.
 * @param  array           $args    Options for the field.
 * @return string          $value   The prepared value.
 */
function mediatheque_disk_usage_prepare( $value, WP_REST_Request $request, $args ) {
	return mediatheque_format_file_size( $value );
}

/**
 * Update a user's disk usage.
 *
 * @since  1.0.0
 *
 * @param  int     $user_id  The ID of the user.
 * @param  int     $bytes    The number of bytes to add to user's disk usage.
 * @return bool              True on success, false otherwise.
 */
function mediatheque_disk_usage_update( $user_id = 0, $bytes = 0, $remove = false ) {
	if ( empty( $user_id ) || empty( $bytes ) ) {
		return false;
	}

	$kilo_bytes = absint( $bytes / 1000 );

	// Do nothing if the file is less than a kilobyte.
	if ( ! $kilo_bytes ) {
		return true;
	}

	// Get the user's disk usage
	$disk_usage = (int) get_user_meta( $user_id, '_mediatheque_disk_usage', true );

	if ( $disk_usage ) {
		if ( true === $remove ) {
			$disk_usage = $disk_usage - $kilo_bytes;
		} else {
			$disk_usage = $disk_usage + $kilo_bytes;
		}

	} elseif ( true !== $remove ) {
		$disk_usage = $kilo_bytes;
	}

	// no negative disk usage!
	if ( $disk_usage < 0 ) {
		delete_user_meta( $user_id, '_mediatheque_disk_usage' );

	// Update user's disk usage.
	} else {
		update_user_meta( $user_id, '_mediatheque_disk_usage', absint( $disk_usage ) );
	}

	return true;
}

/**
 * Add an additionnal rest query params to users.
 *
 * @since  1.0.0
 *
 * @param  array $query_params  The query params for the users collection
 * @return array                The query params for the users collection.
 */
function mediatheque_additionnal_user_rest_param( $query_params = array() ) {
	return array_merge( $query_params, array(
		'has_disk_usage' => array(
			'description' => __( 'True pour limiter les résultats aux utilisateurs ayant soumis des fichiers.', 'mediatheque' ),
			'type'        => 'boolean',
		)
	) );
}

/**
 * Prepare the disk usage user meta for rest requests.
 *
 * @since 1.0.0
 *
 * @param  array           $prepared_args The prepared params for the users collection.
 * @param  WP_REST_Request $request       Rest request object.
 * @return array           $prepared_args The prepared params for the users collection.
 */
function mediatheque_rest_user_query( $prepared_args = array(), WP_REST_Request $request ) {
	if ( $request->get_param( 'has_disk_usage' ) ) {
		$capacity = 'list_users';

		if ( is_multisite() ) {
			$capacity = 'manage_network_users';
		}

		$headers = $request->get_headers();
		if ( ! empty( $headers['referer'] ) ) {
			$referer          = array_shift( $headers['referer'] );
			$is_network_admin = 0 === strpos( $referer, network_admin_url() );
		}

		// Regular users/site admins can't browse or edit other users files.
		if ( ! current_user_can( $capacity ) || empty( $is_network_admin ) ) {
			return array_merge( $prepared_args, array( 'login' => '0' ) );

		// Authorized users can browse and edit other users files.
		} else {
			$p_args = array(
				'meta_key'     => '_mediatheque_disk_usage',
				'meta_compare' => 'EXISTS',
			);

			// Reset the blog ID to 0 to get all network users.
			if ( ! empty( $is_network_admin ) ) {
				$p_args['blog_id'] = 0;
			}

			// We are only listing the users who uploaded at least one file.
			$prepared_args = array_merge( $prepared_args, $p_args );

			// Make sure the Admin has the meta set to include him in results.
			$user_id = get_current_user_id();

			$disk_usage = get_user_meta( $user_id, '_mediatheque_disk_usage', true );
			if ( ! is_numeric( $disk_usage ) ) {
				update_user_meta( $user_id, '_mediatheque_disk_usage', 0 );
			}
		}
	}

	return $prepared_args;
}

/**
 * Remove all user's data when removed from the site.
 *
 * NB: No reassign is performed for now, it would require to move files and directories
 * and to regenerate all user media metadata. A hook is available if you want to build
 * this reassign.
 *
 * @since 1.0.0
 *
 * @param integer $user_id  The deleted user's ID.
 * @param integer $reassign The reassigned user's ID.
 */
function mediatheque_delete_user_data( $user_id = 0, $reassign = 0 ) {
	if ( ! $user_id ) {
		return;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$mediatheque_statuses = wp_list_pluck( mediatheque_get_post_statuses( 'all' ), 'name' );

	$d_user_media = get_posts( array(
		'post_type'     => 'user_media',
		'author'        => $user_id,
		'nopaging'      => true,
		'no_found_rows' => true,
		'post_status'   => $mediatheque_statuses,
	) );

	/**
	 * Hook here to use your own way of dealing with user deletion.
	 *
	 * NB: $d_user_media is passed by reference, setting it to an empty array
	 * within your function will shortcircuit the rest of the function.
	 *
	 * @param integer $user_id      The deleted user's ID.
	 * @param integer $reassign     The reassigned user's ID.
	 * @param array   $d_user_media The User Media to delete.
	 */
	do_action_ref_array( 'mediatheque_before_delete_user_data', array( $user_id, $reassign, &$d_user_media ) );

	if ( empty( $d_user_media ) ) {
		return;
	}

	foreach ( $d_user_media as $user_media ) {
		mediatheque_delete_media( $user_media );
	}

	$mediatheque_upload_dir = mediatheque_get_upload_dir();

	foreach ( $mediatheque_statuses as $status ) {
		$dirpath = $mediatheque_upload_dir['path'] . '/' . $status . '/' . $user_id;

		if ( ! is_dir( $dirpath ) ) {
			continue;
		}

		// Remove the empty directory
		@ rmdir( $dirpath );
	}

	if ( ! $is_main_site ) {
		restore_current_blog();
	}
}
