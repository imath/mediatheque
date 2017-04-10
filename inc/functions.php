<?php
/**
 * MediaThèque Functions.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get plugin's version.
 *
 * @since  1.0.0
 *
 * @return string the plugin's version.
 */
function mediatheque_version() {
	return mediatheque()->version;
}

/**
 * Get the plugin's JS Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's JS Url.
 */
function mediatheque_js_url() {
	return mediatheque()->js_url;
}

/**
 * Get the plugin's Assets Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's Assets Url.
 */
function mediatheque_assets_url() {
	return mediatheque()->assets_url;
}

/**
 * Get the User Media Post type root slug.
 *
 * @since  1.0.0
 *
 * @return string the User Media Post type root slug.
 */
function mediatheque_get_root_slug() {
	/**
	 * Filter here to edit the root slug for the User Media Post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The root slug for the User Media Post type.
	 */
	return apply_filters( 'mediatheque_get_root_slug', 'user-media' );
}

/**
 * Get the download rewrite tag for the User Media Post type.
 *
 * @since  1.0.0
 *
 * @return string The download rewrite tag for the User Media Post type.
 */
function mediatheque_get_download_rewrite_tag() {
	/**
	 * Filter here to edit the download action rewrite tag for the User Media Post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The download action rewrite tag for the User Media Post type.
	 */
	return apply_filters( 'mediatheque_get_download_rewrite_tag', 'download' );
}

/**
 * Get the download rewrite slug for the User Media Post type.
 *
 * @since  1.0.0
 *
 * @return string The download rewrite slug for the User Media Post type.
 */
function mediatheque_get_download_rewrite_slug() {
	/**
	 * Filter here to edit the download action rewrite slug for the User Media Post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The download action rewrite slug for the User Media Post type.
	 */
	return apply_filters( 'mediatheque_get_download_rewrite_slug', 'download' );
}

/**
 * Get the download url for a User Media Item.
 *
 * @since 1.0.0
 *
 * @param  WP_Post|int The User Media object or the ID of the User Media item.
 * @return string      The download url for the User Media Item.
 */
function mediatheque_get_download_url( $user_media = null ) {
	if ( null === $user_media && isset( mediatheque()->user_media_link ) ) {
		return mediatheque()->user_media_link;
	} else {
		$user_media = get_post( $user_media );
	}

	$url = '#';

	if ( ! is_a( $user_media, 'WP_Post' ) || 'user_media' !== $user_media->post_type ) {
		return $url;
	}

	return sprintf( '%1$s/%2$s/', trim( get_post_permalink( $user_media ), '/' ), mediatheque_get_download_rewrite_slug() );
}

/**
 * Filter the Attachment Link for the User Media Download one when necessary.
 *
 * @since  1.0.0
 *
 * @param  string  $link The Attachment Link.
 * @return string        The User Media link.
 */
function mediatheque_attachment_link( $link = '' ) {
	$user_media_link = mediatheque_get_download_url( null );

	if ( '#' !== $user_media_link ) {
		$link = preg_replace( '/(?<=href=\').+(?=\')/', $user_media_link, $link );
	}

	return $link;
}

/**
 * Get the JS minified suffix.
 *
 * @since  1.0.0
 *
 * @return string the JS minified suffix.
 */
function mediatheque_min_suffix() {
	$min = '.min';

	if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG )  {
		$min = '';
	}

	/**
	 * Filter here to edit the minified suffix.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $min The minified suffix.
	 */
	return apply_filters( 'mediatheque_min_suffix', $min );
}

/**
 * Get the Debug mode setting.
 *
 * @since  1.0.0
 *
 * @return bool True if debug mode is on. False otherwise.
 */
function mediatheque_is_debug() {
	$debug = false;

	if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG && current_user_can( 'manage_options' ) )  {
		$debug = true;
	}

	/**
	 * Filter here to edit the debug value.
	 *
	 * @since  1.0.0
	 *
	 * @param  bool $debug The minified suffix.
	 */
	return apply_filters( 'mediatheque_is_debug', $debug );
}

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
		'create_posts'           => 'edit_user_uploads',
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
		$caps = array( 'read' );
	}

	return $caps;
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
	$unit = ' KB';

	if ( empty( $value ) ) {
		return 0 . $unit;
	}

	$value     = absint( $value );
	$megabytes = $value / 1000;
	$gigabytes = $megabytes / 1000;

	if ( 1 < $gigabytes ) {
		$unit  = ' GB';
		$value = $gigabytes;
	} elseif ( 1 < $megabytes ) {
		$unit  = ' MB';
		$value = $megabytes;
	}

	return number_format_i18n( $value, 2 ) . $unit;
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
		// Regular users can't browse or edit other users files.
		if ( ! current_user_can( 'list_users' ) ) {
			return array( 'id' => 0 );

		// Authorized users can browse and edit other users files.
		} else {
			// We are only listing the users who uploaded at least one file.
			$prepared_args = array_merge( $prepared_args, array(
				'meta_key'     => '_mediatheque_disk_usage',
				'meta_compare' => 'EXISTS',
			) );

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

/**
 * Get a specific (or nearest) intermediate size for a User Media.
 *
 * @since 1.0.0
 *
 * @param  int     $user_media_id The User Media ID.
 * @param  array   $size          The width and height in pixels.
 * @return array                  The User Media intermediate size data.
 */
function mediatheque_image_get_intermediate_size( $user_media_id = 0, $size = array() ) {
	$user_media = get_post( $user_media_id );
	$size_data  = array();

	if ( empty( $user_media->post_type ) || 'user_media' !== $user_media->post_type ) {
		return $size_data;
	}

	if ( empty( $size ) ) {
		$size = 'full';
	}

	$size_data = image_get_intermediate_size( $user_media->ID, $size );

	if ( empty( $size_data['path'] ) ) {
		return $size_data;
	}

	$uploads = mediatheque_get_upload_dir();
	$size_data['url']  = trailingslashit( $uploads['baseurl'] ) . $size_data['path'];
	$size_data['path'] = trailingslashit( $uploads['basedir'] ) . $size_data['path'];

	return $size_data;
}

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
 * Returns the MediaThèque Avatar data.
 *
 * @since 1.0.0
 *
 * @param  array $args        Default data.
 * @param  mixed $id_or_email A user ID, email, a User, a Post or a Comment object.
 * @return array              Avatar data.
 */
function mediatheque_get_avatar_data( $args = array(), $id_or_email ) {
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

/**
 * Get the User Media base Uploads dir data.
 *
 * @since 1.0.0
 *
 * @return array The User Media base Uploads dir data.
 */
function mediatheque_get_upload_dir() {
	$mediatheque = mediatheque();

	if ( ! isset( $mediatheque->upload_dir ) ) {
		mediatheque_register_upload_dir();
	}

	return $mediatheque->upload_dir;
}

/**
 * Set the User Media base Uploads dir data.
 *
 * @since  1.0.0
 *
 * @param  array  $dir The wp_upload_dir() data.
 * @return array       The User Media base Uploads dir data.
 */
function mediatheque_set_upload_base_dir( $dir = array() ) {
	if ( empty( $dir['basedir'] ) || empty( $dir['baseurl'] ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Paramètres manquants.', 'mediatheque' ) );
		return $dir;
	}

	return array_merge( $dir, array(
		'path'   => $dir['basedir'] . '/mediatheque',
		'url'    => $dir['baseurl'] . '/mediatheque',
		'subdir' => '/mediatheque',
	) );
}

/**
 * Register the User Media Uploads dir into the plugin's global.
 *
 * @since 1.0.0
 */
function mediatheque_register_upload_dir() {
	$needs_switch = false;
	$network_id   = (int) get_current_network_id();

	if ( is_multisite() && (int) get_current_blog_id() !== $network_id ) {
		$needs_switch = true;
		switch_to_blog( $network_id );
	}

	add_filter( 'upload_dir', 'mediatheque_set_upload_base_dir', 10, 1 );

	mediatheque()->upload_dir = wp_upload_dir( null, true );

	remove_filter( 'upload_dir', 'mediatheque_set_upload_base_dir', 10, 1 );

	if ( $needs_switch ) {
		restore_current_blog();
	}
}

/**
 * Register Post Statuses for User Media.
 *
 * @since  1.0.0
 */
function mediatheque_register_post_statuses() {
	$supported     = (array) apply_filters( 'mediatheque_supported_post_statuses', array( 'publish', 'private' ) );
	$mediatheque = mediatheque();
	$mediatheque->statuses = array();

	foreach ( $supported as $status ) {
		$mediatheque->statuses[ $status ] = get_post_status_object( $status );

		// Override the Publish status label
		if ( 'publish' === $status ) {
			$mediatheque->statuses[ $status ]->label = __( 'Public', 'mediatheque' );
		}

		if ( null === $mediatheque->statuses[ $status ] ) {
			unset( $mediatheque->statuses[ $status ] );
		}
	}
}

/**
 * Get all User Media Post statuses or a specific one.
 *
 * @since  1.0.0
 *
 * @param  string       $status Name of the status (or All to get all post statuses).
 * @return object|array         A specific status object or all status objects.
 */
function mediatheque_get_post_statuses( $status = '' ) {
	$mediatheque = mediatheque();

	if ( 'all' === $status ) {
		return $mediatheque->statuses;
	} elseif ( ! isset( $mediatheque->statuses[ $status ] ) ) {
		return null;
	}

	return $mediatheque->statuses[ $status ];
}

function mediatheque_localize_script( $handle = 'mediatheque-views' ) {
	$post_type_object = get_post_type_object( 'user_media' );

	wp_localize_script( $handle, 'mediaThequeSettings', array(
		'params' => array(
			'container' => 'mediatheque-ui',
			'browser'   => 'mediatheque-browse',
			'dropzone'  => 'drag-drop-area',
			'dropHelp'  => __( 'Déposez vos fichiers pour les mettre en ligne', 'mediatheque' ),
			'dropOr'    => __( 'ou', 'mediatheque' ),
			'btnBrowse' => __( 'Choisissez des fichiers', 'mediatheque' ),
		),
		'toolbarItems' => array_merge( array(
				'users'    => __( 'Choisissez un utilisateur', 'mediatheque' )
			),
			wp_list_pluck( mediatheque_get_post_statuses( 'all' ), 'label', 'name' ),
			array(
				'upload'    => __( 'Nouveau(x) fichier(s)', 'mediatheque' ),
				'directory' => __( 'Nouveau répertoire', 'mediatheque' ),
			)
		),
		'dirmaker' => array(
			'label'   => __( 'Nom de votre répertoire', 'mediatheque' ),
			'saveBtn' => __( 'Créer', 'mediatheque' ),
		),
		'common' => array(
			'downloadSlug'    => mediatheque_get_download_rewrite_slug(),
			'closeBtn'        => __( 'Fermer', 'mediatheque' ),
			'noUserMedia'     => __( 'Aucun media utilisateur ne correspond à votre requête.', 'mediatheque' ),
			'dismissibleText' => __( 'Rejeter', 'mediatheque' ),
			'insertBtn'       => $post_type_object->labels->insert_into_item,
			'avatarBtn'       => $post_type_object->labels->use_as_avatar,
			'frameTitle'      => $post_type_object->labels->menu_name,
		),
	) );
}

function mediatheque_register_scripts() {
	$url = mediatheque_js_url();
	$min = mediatheque_min_suffix();
	$v   = mediatheque_version();

	$scripts = array(
		'mediatheque-uploader' => array(
			'location' => sprintf( '%1$suploader%2$s.js', $url, $min ),
			'deps'     => array( 'wp-api', 'wp-backbone', 'wp-plupload' ),
		),
		'mediatheque-views' => array(
			'location' => sprintf( '%1$sviews%2$s.js', $url, $min ),
			'deps'     => array( 'wp-api', 'wp-backbone', 'underscore' ),
		),
		'mediatheque-editor' => array(
			'location' => sprintf( '%1$sscript%2$s.js', $url, $min ),
			'deps'     => array( 'media-editor', 'mediatheque-uploader', 'mediatheque-views' ),
		),
		'mediatheque-manage' => array(
			'location' => sprintf( '%1$smanage%2$s.js', $url, $min ),
			'deps'     => array( 'mediatheque-uploader', 'mediatheque-views' ),
		),
	);

	foreach ( $scripts as $handle => $script ) {
		wp_register_script( $handle, $script['location'], $script['deps'], $v, true );
	}

	wp_register_style(
		'mediatheque-uploader',
		sprintf( '%1$suploader%2$s.css', mediatheque_assets_url(), $min ),
		array(),
		$v
	);
}

/**
 * Register the post type and the taxonomy used by User Media.
 *
 * @since 1.0.0
 */
function mediatheque_register_objects() {

	/** Post Type ************************************************************/

	register_post_type( 'user_media', array(
		'labels'  => array(
			'name'                  => __( 'Media utilisateurs',                              'mediatheque' ),
			'menu_name'             => _x( 'Ma MediaThèque', 'Plugin submenu',                'mediatheque' ),
			'all_items'             => __( 'Tous les media utilisateurs',                     'mediatheque' ),
			'singular_name'         => __( 'Media utilisateur',                               'mediatheque' ),
			'add_new'               => __( 'Nouveau media utilisateur',                       'mediatheque' ),
			'add_new_item'          => __( 'Ajouter un media utilisateur',                    'mediatheque' ),
			'edit_item'             => __( 'Modifier le media utilisateur',                   'mediatheque' ),
			'new_item'              => __( 'Nouveau media utilisateur',                       'mediatheque' ),
			'view_item'             => __( 'Afficher le media utilisateur',                   'mediatheque' ),
			'search_items'          => __( 'Rechercher un media utilisateur',                 'mediatheque' ),
			'not_found'             => __( 'Media utilisateur introuvable',                   'mediatheque' ),
			'not_found_in_trash'    => __( 'Media utilisateur introuvable dans la corbeille', 'mediatheque' ),
			'insert_into_item'      => __( 'Insérer dans le contenu',                         'mediatheque' ),
			'use_as_avatar'         => __( 'Utiliser comme avatar',                           'mediatheque' ),
			'uploaded_to_this_item' => __( 'Attaché à ce contenu',                            'mediatheque' ),
			'filter_items_list'     => __( 'Filtrer les Media utilisateurs',                  'mediatheque' ),
			'items_list_navigation' => __( 'Navigation des Media utilisateurs',               'mediatheque' ),
			'items_list'            => __( 'Liste des Media utilisateurs',                    'mediatheque' ),
		),
		'public'                => true,
		'query_var'             => 'mediatheque',
		'rewrite'               => array(
			'slug'              => mediatheque_get_root_slug(),
			'with_front'        => false
		),
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'show_in_nav_menus'     => false,
		'show_ui'               => false,
		'supports'              => array( 'title', 'editor', 'comments' ),
		'taxonomies'            => array( 'user_media_type' ),
		'capability_type'       => array( 'user_upload', 'user_uploads' ),
		'capabilities'          => mediatheque_capabilities(),
		'delete_with_user'      => true,
		'can_export'            => true,
		'show_in_rest'          => true,
		'rest_controller_class' => 'MediaTheque_REST_Controller',
		'rest_base'             => 'user-media',
	) );

	/** Taxonomy *************************************************************/

	register_taxonomy( 'user_media_types', 'user_media', array(
		'public'                => false,
		'hierarchical'          => true,
		'label'                 => 'User Media Types',
		'labels'                => array(
			'name'              => _x( 'Types', 'taxonomy general name', 'mediatheque' ),
			'singular_name'     => _x( 'Type', 'taxonomy singular name', 'mediatheque' ),
		),
		'show_ui'               => mediatheque_is_debug(),
		'show_admin_column'     => false,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => false,
		'rewrite'               => false,
		'capabilities'          => mediatheque_types_capabilities(),
		'show_in_rest'          => true,
	) );

	/** Post statuses ********************************************************/

	mediatheque_register_post_statuses();

	/** Rewrites *************************************************************/

	add_rewrite_tag(
		'%' . mediatheque_get_download_rewrite_tag() . '%',
		'([1]{1,})'
	);

	add_rewrite_rule(
		mediatheque_get_root_slug() . '/([^/]+)/'  . mediatheque_get_download_rewrite_slug() . '/?$',
		'index.php?mediatheque=$matches[1]&' . mediatheque_get_download_rewrite_tag()  . '=1',
		'top'
	);

	/** User Meta ************************************************************/

	register_meta(
		'user',
		'_mediatheque_disk_usage',
		array(
			'sanitize_callback' => 'mediatheque_meta_sanitize_value',
			'type'              => 'integer',
			'description'       => 'The disk usage of the user in KB.',
			'single'            => true,
			'show_in_rest'      => array(
				'name'             => 'disk_usage',
				'prepare_callback' => 'mediatheque_disk_usage_prepare',
			)
		)
	);

	register_meta(
		'user',
		'personal_avatar',
		array(
			'sanitize_callback' => 'mediatheque_meta_sanitize_value',
			'auth_callback'     => 'mediatheque_meta_auth_personal_avatar',
			'type'              => 'integer',
			'description'       => 'The User Media ID to use as an avatar.',
			'single'            => true,
			'show_in_rest'      => true,
		)
	);

	/** Uploads dir **********************************************************/

	mediatheque_register_upload_dir();

	/** Scripts & Css *******************************************************/

	mediatheque_register_scripts();

	/** Avatar image sizes **************************************************/

	foreach ( (array) rest_get_avatar_sizes() as $size ) {
		add_image_size( sprintf( 'avatat-%s', $size ), $size, $size, true );
	}
}

/**
 * Get the plugin's templates dir.
 *
 * @since  1.0.0
 *
 * @return string the plugin's templates dir.
 */
function mediatheque_templates() {
	return mediatheque()->templates;
}

/**
 * Add a custom default template for embedded User Media.
 *
 * @since  1.0.0
 *
 * @param  string $template  Path to the template. See locate_template().
 * @return string            Path to the template. See locate_template().
 */
function mediatheque_embed_template( $template = '' ) {
	$object = get_queried_object();

	// Only Apply the template override on Embedded User Media
	if ( 'user_media' !== $object->post_type ) {
		return $template;
	}

	$filename = pathinfo( $template, PATHINFO_FILENAME );

	/**
	 * If the theme is not overriding the template yet
	 * override it with the plugin's default template.
	 */
	if ( 'embed-user_media' !== $filename ) {
		$template = mediatheque_templates() . 'embed-user_media.php';
	}

	return $template;
}

/**
 * Print the User Media excerpt for the embed template.
 *
 * @since  1.0.0
 */
function mediatheque_embed_excerpt() {
	$excerpt = apply_filters( 'the_excerpt_embed', get_the_excerpt() );
	$excerpt = mediatheque_prepend_user_media( $excerpt );

	echo apply_filters( 'mediatheque_embed_excerpt', $excerpt );
}

/**
 * Prints the necessary markup for the embed download button.
 *
 * @since 1.0.0
 */
function mediatheque_embed_download_button() {
	if ( 'private' === get_post_status() ) {
		return;
	}

	printf(
		'<div class="wp-embed-download">
			<a href="%1$s" target="_top">
				<span class="dashicons dashicons-download"></span>
				%2$s
			</a>
		</div>',
		esc_url( mediatheque_get_download_url() ),
		sprintf(
			__( 'Télécharger<span class="screen-reader-text"> %s</span>', 'mediatheque' ),
			esc_html( get_the_title() )
		)
	);
}

/**
 * Enqueue the Embed styles.
 *
 * @since 1.0.0
 */
function mediatheque_embed_style() {
	wp_enqueue_style(
		'mediatheque-embed',
		sprintf( '%1$sembed%2$s.css', mediatheque_assets_url(), mediatheque_min_suffix() ),
		array(),
		mediatheque_version()
	);
}

/**
 * Retrieve the path of the highest priority template file that exists.
 *
 * @since  1.0.0
 *
 * @param  string  $template The template file name.
 * @param  string  $name     The Undersore template ID.
 * @param  bool    $load     Whether to load or return the found template.
 * @return string            The template path.
 */
function mediatheque_get_template_part( $template = '', $id = '', $load = true ) {
	if ( empty( $template ) || empty( $id ) ) {
		return '';
	}

	$template = str_replace( '.html', '', $template );
	$located  = '';

	$template_locations = (array) apply_filters( 'mediatheque_get_template_part', array(
		trailingslashit( get_stylesheet_directory() ) . 'mediatheque/' . $template . '.html',
		trailingslashit( get_template_directory() ) . 'mediatheque/' . $template . '.html',
		mediatheque_templates() . $template . '.html',
	) );

	foreach ( $template_locations as $template_location ) {
		if ( ! $template_location ) {
			continue;
		}

		if ( file_exists( $template_location ) ) {
			$located = $template_location;
			break;
		}
	}

	if ( $load && $located ) {
		printf( '<script type="text/html" id="tmpl-%1$s">%2$s', esc_attr( $id ), "\n" );

		load_template( $located, true );

		print( "</script>\n" );
	}

	return $located;
}

/**
 * Get all or some Underscore templates.
 *
 * @since 1.0.0
 *
 * @param array $list An associative array containing all template Ids to keep.
 */
function mediatheque_get_template_parts( $list = array() ) {
	$template_parts = (array) apply_filters( 'mediatheque_get_template_parts', array(
		'toolbar-item'     => 'mediatheque-toolbar-item',
		'feedback'         => 'mediatheque-feedback',
		'user'             => 'mediatheque-user',
		'user-media'       => 'mediatheque-media',
		'user-media-trail' => 'mediatheque-trail',
		'uploader'         => 'mediatheque-uploader',
		'progress'         => 'mediatheque-progress',
		'dirmaker'         => 'mediatheque-dirmaker',
	) );

	if ( ! empty( $list ) ) {
		return array_intersect_key( $template_parts, $list );
	}

	return $template_parts;
}

/**
 * Set some WP_Query parameters so that the Attachment template is used.
 *
 * @since  1.0.0
 *
 * @param  WP_Query $query The WordPress Main Query
 */
function mediatheque_parse_query( WP_Query $query ) {
	$bail = false;

	if ( ! $query->is_main_query() || true === $query->get( 'suppress_filters' ) ) {
		$bail = true;
	}

	if ( ! $bail && is_admin() ) {
		$bail = ! wp_doing_ajax();
	}

	if ( $bail ) {
		return;
	}

	if ( 'user_media' !== $query->get( 'post_type' ) || 1 === (int) $query->get( mediatheque_get_download_rewrite_tag() ) || true === $query->is_embed ) {
		return;
	}

	$query->is_attachment = true;
	add_filter( 'the_content', 'mediatheque_prepend_user_media', 11 );
}

/**
 * Make sure the User Media file is prepended to its description.
 *
 * @since  1.0.0
 *
 * @param  string $content The User Media description.
 * @return string          The User Media description.
 */
function mediatheque_prepend_user_media( $content = '' ) {
	if ( 'user_media' !== get_post_type() || empty( $GLOBALS['post'] ) ) {
		return $content;
	}

	mediatheque()->user_media_link = mediatheque_get_download_url( $GLOBALS['post'] );

	// Overrides
	$reset_post = clone $GLOBALS['post'];
	$GLOBALS['post']->post_type = 'attachment';
	wp_cache_set( $reset_post->ID, $GLOBALS['post'], 'posts' );
	add_filter( 'wp_get_attachment_link', 'mediatheque_attachment_link', 10, 1 );

	$content = prepend_attachment( $content );

	// Resets
	$GLOBALS['post'] = $reset_post;
	wp_cache_set( $reset_post->ID, $reset_post, 'posts' );
	remove_filter( 'the_content',            'mediatheque_prepend_user_media', 11    );
	remove_filter( 'wp_get_attachment_link', 'mediatheque_attachment_link',    10, 1 );

	unset( mediatheque()->user_media_link );

	return $content;
}

/**
 * Delete a directory of User Media.
 *
 * @since 1.0.0
 *
 * @param  int|WP_Post         $dir  Required. The User Media Directory ID or Object.
 * @return array|false|WP_Post       False on failure.
 */
function mediatheque_delete_dir( $dir = null ) {
	if ( empty( $dir ) ) {
		return false;
	}

	$dir = get_post( $dir );

	if ( empty( $dir->post_type ) || 'user_media' !== $dir->post_type ) {
		return false;
	}

	$uploadpath = mediatheque_get_upload_dir();
	$dirpath    = get_post_meta( $dir->ID, '_mediatheque_relative_path', true );
	$dirpath    = trailingslashit( $uploadpath['basedir'] ) . $dirpath;

	if ( ! is_dir( $dirpath ) ) {
		return false;
	}

	$children = get_children( array(
		'post_type' => 'user_media',
		'post_parent' => $dir->ID,
	) );

	/**
	 * Fires before a directory of User Media is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $dir      The User Media Directory Object.
	 * @param array   $children The list of included User Media.
	 */
	do_action( 'mediatheque_delete_dir', $dir, $children );

	// Empty the directory's content.
	if ( ! empty( $children ) ) {
		foreach ( $children as $child ) {
			mediatheque_delete_media( $child );
		}
	}

	// Remove the empty directory
	@ rmdir( $dirpath );

	$dir = wp_delete_post( $dir->ID, true );

	/**
	 * Fires after a directory of User Media is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $dir The User Media Directory Object.
	 */
	do_action( 'mediatheque_deleted_dir', $dir );

	return $dir;
}

/**
 * Delete a User Media.
 *
 * @since 1.0.0
 *
 * @param  int|WP_Post         $media  Required. The User Media ID or Object.
 * @return array|false|WP_Post         False on failure.
 */
function mediatheque_delete_media( $media = null ) {
	if ( empty( $media ) ) {
		return false;
	}

	$user_media = get_post( $media );

	if ( empty( $user_media->post_type ) || 'user_media' !== $user_media->post_type ) {
		return false;
	}

	$user_media_type = wp_get_object_terms( $user_media->ID, 'user_media_types',  array( 'fields' => 'id=>slug' ) );
	$user_media_type = reset( $user_media_type );

	if ( 'mediatheque-directory' === $user_media_type ) {
		return mediatheque_delete_dir( $user_media );
	}

	$meta = wp_get_attachment_metadata( $user_media->ID );
	$file = get_attached_file( $user_media->ID );

	if ( is_multisite() ) {
		delete_transient( 'dirsize_cache' );
	}

	/**
	 * Fires before a User Media is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $user_media The User Media Object.
	 * @param string  $file       The file absolute path.
	 */
	do_action( 'mediatheque_delete_media', $user_media, $file );

	$uploadpath = mediatheque_get_upload_dir();

	// Remove intermediate and backup images if there are any.
	if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $size => $sizeinfo ) {
			$intermediate_file = str_replace( basename( $file ), $sizeinfo['file'], $file );
			/** This filter is documented in wp-includes/functions.php */
			$intermediate_file = apply_filters( 'wp_delete_file', $intermediate_file );
			@ unlink( path_join( $uploadpath['basedir'], $intermediate_file ) );
		}
	}

	wp_delete_file( $file );

	$user_media = wp_delete_post( $user_media->ID, true );

	/**
	 * Fires after a User Media is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $value The User Media Object.
	 */
	do_action( 'mediatheque_deleted_media', $user_media );

	return $user_media;
}

function mediatheque_move( $media = null, $parent = null ) {
	if ( empty( $media ) || is_null( $parent ) ) {
		return false;
	}

	$user_media = get_post( $media );

	if ( empty( $user_media->post_type ) || 'user_media' !== $user_media->post_type ) {
		return false;
	}

	$uploadpath = mediatheque_get_upload_dir();

	if ( 0 === $parent ) {
		$path = sprintf( '%1$s/%2$s/%3$s', $uploadpath['subdir'], get_post_status( $user_media ), $user_media->post_author );
	} else {
		$path = '/' . get_post_meta( $parent, '_mediatheque_relative_path', true );
	}

	if ( ! $path ) {
		return false;
	}

	$meta       = wp_get_attachment_metadata( $user_media->ID );
	$file       = get_attached_file( $user_media->ID );

	$newdir     = $uploadpath['basedir'] . $path;
	$filename   = wp_unique_filename( $newdir, basename( $file ) );
	$new_file   = trailingslashit( $newdir ) . $filename;

	$moved = @ copy( $file, $new_file );

	if ( false === $moved ) {
		return false;
	}

	unlink( $file );

	if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $size => $sizeinfo ) {
			$intermediate_file = str_replace( basename( $file ), $sizeinfo['file'], $file );
			/** This filter is documented in wp-includes/functions.php */
			$intermediate_file = apply_filters( 'wp_delete_file', $intermediate_file );
			@ unlink( path_join( $uploadpath['basedir'], $intermediate_file ) );
		}
	}

	return $new_file;
}

function medialibrary_button( $args = array() ) {
	static $instance = 0;
	$instance++;

	$r = wp_parse_args( $args, array(
		'editor_id'           => 'content',
		'editor_btn_classes'  => array( 'mediabrary-insert' ),
		'editor_btn_text'     => __( 'Ajouter un media', 'mediatheque' ),
		'editor_btn_dashicon' => 'dashicons-format-image',
		'echo'                => true,
	) );

	$post = get_post();
	if ( ! $post && ! empty( $GLOBALS['post_ID'] ) ) {
		$post = $GLOBALS['post_ID'];
	}

	wp_enqueue_media( array(
		'post' => $post
	) );

	$img = '';
	$output = '<a href="#"%s class="%s" data-editor="%s">%s</a>';

	if ( false !== $r['editor_btn_dashicon'] ) {
		$img = '<span class="dashicons ' . $r['editor_btn_dashicon']  . '"></span> ';
		$output = '<button type="button"%s class="button %s" data-editor="%s">%s</button>';
	}

	$id_attribute = $instance === 1 ? ' id="insert-mediabrary-item"' : '';

	if ( true === $r['echo' ] ) {
		printf( $output,
			$id_attribute,
			join( ' ', array_map( 'sanitize_html_class', $r['editor_btn_classes'] ) ),
			esc_attr( $r['editor_id'] ),
			$img . $r['editor_btn_text']
		);
	}

	return sprintf( $output,
		$id_attribute,
		join( ' ', array_map( 'sanitize_html_class', $r['editor_btn_classes'] ) ),
		esc_attr( $r['editor_id'] ),
		$img . $r['editor_btn_text']
	);
}

/**
 * Catch the WP Media Editor id to allow users without the 'upload_files' capability
 * to use a light version of it.
 *
 * @since  1.0.0
 *
 * @param  array  $settings  The editor settings.
 * @param  string $editor_id The editor selector id.
 * @return array             The editor settings.
 */
function mediatheque_editor_settings( $settings = array(), $editor_id = '' ) {
	// In this case, the User Media UI is a sidebar item of the WP Media Editor
	if ( current_user_can( 'upload_files' ) ) {
		return $settings;
	}

	// If the user can't use User Media, stop here.
	if ( ! current_user_can( 'publish_user_uploads' ) || ( isset( $settings['mediatheque_button'] ) && false === $settings['mediatheque_button'] ) ) {
		return $settings;
	}

	mediatheque()->editor_id = $editor_id;

	return $settings;
}

/**
 * Add a button to open a light WP Media Editor for users without the 'upload_files' capability.
 *
 * @since  1.0.0
 *
 * @param  string $editor The editor HTML Output.
 * @return string $editor The editor HTML Output.
 */
function mediatheque_the_editor( $editor = '' ) {
	$mediatheque = mediatheque();

	if ( empty( $mediatheque->editor_id ) || ! current_user_can( 'publish_user_uploads' ) ) {
		return $editor;
	}

	return sprintf( '<div id="wp-%1$s-media-buttons" class="wp-media-buttons mediatheque-buttons" data-editor="%1$s">%2$s</div>%3$s',
		esc_attr( $mediatheque->editor_id ),
		medialibrary_button( array(
			'editor_id'           => $mediatheque->editor_id,
			'editor_btn_classes'  => array( 'mediatheque-insert' ),
			'echo'                => false,
		) ),
		$editor
	);
}
