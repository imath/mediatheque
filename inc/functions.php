<?php
/**
 * WP User Media Functions.
 *
 * @package WP User Media\inc
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
function wp_user_media_version() {
	return wp_user_media()->version;
}

/**
 * Get the plugin's JS Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's JS Url.
 */
function wp_user_media_js_url() {
	return wp_user_media()->js_url;
}

/**
 * Get the plugin's Assets Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's Assets Url.
 */
function wp_user_media_assets_url() {
	return wp_user_media()->assets_url;
}

/**
 * Get the User Media Post type root slug.
 *
 * @since  1.0.0
 *
 * @return string the User Media Post type root slug.
 */
function wp_user_media_get_root_slug() {
	/**
	 * Filter here to edit the root slug for the User Media Post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The root slug for the User Media Post type.
	 */
	return apply_filters( 'wp_user_media_get_root_slug', 'user-media' );
}

/**
 * Get the download rewrite tag for the User Media Post type.
 *
 * @since  1.0.0
 *
 * @return string The download rewrite tag for the User Media Post type.
 */
function wp_user_media_get_download_rewrite_tag() {
	/**
	 * Filter here to edit the download action rewrite tag for the User Media Post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The download action rewrite tag for the User Media Post type.
	 */
	return apply_filters( 'wp_user_media_get_download_rewrite_tag', 'download' );
}

/**
 * Get the download rewrite slug for the User Media Post type.
 *
 * @since  1.0.0
 *
 * @return string The download rewrite slug for the User Media Post type.
 */
function wp_user_media_get_download_rewrite_slug() {
	/**
	 * Filter here to edit the download action rewrite slug for the User Media Post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The download action rewrite slug for the User Media Post type.
	 */
	return apply_filters( 'wp_user_media_get_download_rewrite_slug', 'download' );
}

/**
 * Get the download url for a User Media Item.
 *
 * @since 1.0.0
 *
 * @param  WP_Post|int The User Media object or the ID of the User Media item.
 * @return string      The download url for the User Media Item.
 */
function wp_user_media_get_download_url( $user_media = null ) {
	$user_media = get_post( $user_media );
	$url        = '#';

	if ( ! is_a( $user_media, 'WP_Post' ) || 'user_media' !== $user_media->post_type ) {
		return $url;
	}

	return sprintf( '%1$s/%2$s/', trim( get_post_permalink( $user_media ), '/' ), wp_user_media_get_download_rewrite_slug() );
}

/**
 * Get the JS minified suffix.
 *
 * @since  1.0.0
 *
 * @return string the JS minified suffix.
 */
function wp_user_media_min_suffix() {
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
	return apply_filters( 'wp_user_media_min_suffix', $min );
}

/**
 * Get the Debug mode setting.
 *
 * @since  1.0.0
 *
 * @return bool True if debug mode is on. False otherwise.
 */
function wp_user_media_is_debug() {
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
	return apply_filters( 'wp_user_media_is_debug', $debug );
}

/**
 * Get the capabilities for the User media post type.
 *
 * @since  1.0.0
 *
 * @return array The capabilities for the User media post type.
 */
function wp_user_media_capabilities() {
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
function wp_user_media_types_capabilities() {
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
function wp_user_media_get_all_caps() {
	return array_merge( wp_user_media_capabilities(), wp_user_media_types_capabilities() );
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
function wp_user_media_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	if ( in_array( $cap, wp_user_media_get_all_caps(), true ) ) {
		$caps = array( 'manage_options' );
	}

	return $caps;
}

/**
 * Register the post type and the taxonomy used by User Media.
 *
 * @since 1.0.0
 */
function wp_user_media_register_objects() {
	register_post_type( 'user_media', array(
		'labels'  => array(
			'name'                  => __( 'User Media',                    'wp-user-media' ),
			'menu_name'             => _x( 'User Media', 'Plugin submenu',  'wp-user-media' ),
			'all_items'             => __( 'All User Media files',          'wp-user-media' ),
			'singular_name'         => __( 'User Media',                    'wp-user-media' ),
			'add_new'               => __( 'New User Media file',           'wp-user-media' ),
			'add_new_item'          => __( 'Add new User Media file',       'wp-user-media' ),
			'edit_item'             => __( 'Edit User Media file',          'wp-user-media' ),
			'new_item'              => __( 'New User Media',                'wp-user-media' ),
			'view_item'             => __( 'View User Media',               'wp-user-media' ),
			'search_items'          => __( 'Search User Media files',       'wp-user-media' ),
			'not_found'             => __( 'User Media not found',          'wp-user-media' ),
			'not_found_in_trash'    => __( 'User Media not found in trash', 'wp-user-media' ),
			'insert_into_item'      => __( 'Add User Media to content',     'wp-user-media' ),
			'uploaded_to_this_item' => __( 'Attached to this content',      'wp-user-media' ),
			'filter_items_list'     => __( 'Filter User Media',             'wp-user-media' ),
			'items_list_navigation' => __( 'User Media navigation',         'wp-user-media' ),
			'items_list'            => __( 'User Media List',               'wp-user-media' ),
		),
		'public'                => true,
		'query_var'             => 'wp_user_media',
		'rewrite'               => array(
			'slug'              => wp_user_media_get_root_slug(),
			'with_front'        => false
		),
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'show_in_nav_menus'     => false,
		'show_ui'               => wp_user_media_is_debug(),
		'supports'              => array( 'title', 'editor', 'comments' ),
		'taxonomies'            => array( 'user_media_type' ),
		'capability_type'       => array( 'user_upload', 'user_uploads' ),
		'capabilities'          => wp_user_media_capabilities(),
		'delete_with_user'      => true,
		'can_export'            => true,
		'show_in_rest'          => true,
		'rest_controller_class' => 'WP_User_Media_REST_Controller',
	) );

	register_taxonomy( 'user_media_types', 'user_media', array(
		'public'                => false,
		'hierarchical'          => true,
		'label'                 => 'User Media Types',
		'labels'                => array(
			'name'              => _x( 'Types', 'taxonomy general name', 'wp-user-media' ),
			'singular_name'     => _x( 'Type', 'taxonomy singular name', 'wp-user-media' ),
		),
		'show_ui'               => wp_user_media_is_debug(),
		'show_admin_column'     => false,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => false,
		'rewrite'               => false,
		'capabilities'          => wp_user_media_types_capabilities(),
		'show_in_rest'          => true,
	) );

	/** Rewrites *************************************************************/

	add_rewrite_tag(
		'%' . wp_user_media_get_download_rewrite_tag() . '%',
		'([1]{1,})'
	);

	add_rewrite_rule(
		wp_user_media_get_root_slug() . '/([^/]+)/'  . wp_user_media_get_download_rewrite_slug() . '/?$',
		'index.php?wp_user_media=$matches[1]&' . wp_user_media_get_download_rewrite_tag()  . '=1',
		'top'
	);
}

/**
 * Get the plugin's templates dir.
 *
 * @since  1.0.0
 *
 * @return string the plugin's templates dir.
 */
function wp_user_media_templates() {
	return wp_user_media()->templates;
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
function wp_user_media_get_template_part( $template = '', $id = '', $load = true ) {
	if ( empty( $template ) || empty( $id ) ) {
		return '';
	}

	$template = trim( $template, '.html' );
	$located  = '';

	$template_locations = (array) apply_filters( 'wp_user_media_get_template_part', array(
		trailingslashit( get_stylesheet_directory() ) . 'wp-user-media/' . $template . '.html',
		trailingslashit( get_template_directory() ) . 'wp-user-media/' . $template . '.html',
		wp_user_media_templates() . $template . '.html',
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
