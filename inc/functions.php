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
	if ( null === $user_media && isset( wp_user_media()->user_media_link ) ) {
		return wp_user_media()->user_media_link;
	} else {
		$user_media = get_post( $user_media );
	}

	$url = '#';

	if ( ! is_a( $user_media, 'WP_Post' ) || 'user_media' !== $user_media->post_type ) {
		return $url;
	}

	return sprintf( '%1$s/%2$s/', trim( get_post_permalink( $user_media ), '/' ), wp_user_media_get_download_rewrite_slug() );
}

/**
 * Filter the Attachment Link for the User Media Download one when necessary.
 *
 * @since  1.0.0
 *
 * @param  string  $link The Attachment Link.
 * @return string        The User Media link.
 */
function wp_user_media_attachment_link( $link = '' ) {
	$user_media_link = wp_user_media_get_download_url( null );

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
		$caps = array( 'read' );
	}

	return $caps;
}

/**
 * Sanitize the disk usage user meta.
 *
 * @since 1.0.0
 *
 * @param  int    $value    The raw value of the disk usage user meta.
 * @param  string $meta_key The user meta key.
 * @return int    $value    The sanitized disk usage user meta.
 */
function wp_user_meta_disk_usage_sanitize_value( $value = '', $meta_key = '' ) {
	if ( '_wp_user_meta_disk_usage' === $meta_key ) {
		$value = (int) $value;
	}

	return $value;
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
function wp_user_meta_disk_usage_prepare( $value, WP_REST_Request $request, $args ) {
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
function wp_user_meta_disk_usage_update( $user_id = 0, $bytes = 0, $remove = false ) {
	if ( empty( $user_id ) || empty( $bytes ) ) {
		return false;
	}

	$kilo_bytes = absint( $bytes / 1000 );

	// Do nothing if the file is less than a kilobyte.
	if ( ! $kilo_bytes ) {
		return true;
	}

	// Get the user's disk usage
	$disk_usage = (int) get_user_meta( $user_id, '_wp_user_meta_disk_usage', true );

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
		delete_user_meta( $user_id, '_wp_user_meta_disk_usage' );

	// Update user's disk usage.
	} else {
		update_user_meta( $user_id, '_wp_user_meta_disk_usage', absint( $disk_usage ) );
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
function wp_user_media_additionnal_user_rest_param( $query_params = array() ) {
	return array_merge( $query_params, array(
		'has_disk_usage' => array(
			'description' => __( 'True to limit the users to the ones who uploaded some files.' ),
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
function wp_user_media_rest_user_query( $prepared_args = array(), WP_REST_Request $request ) {
	if ( $request->get_param( 'has_disk_usage' ) ) {
		// Regular users can't browse or edit other users files.
		if ( ! current_user_can( 'list_users' ) ) {
			return array( 'id' => 0 );

		// Authorized users can browse and edit other users files.
		} else {
			// We are only listing the users who uploaded at least one file.
			$prepared_args = array_merge( $prepared_args, array(
				'meta_key'     => '_wp_user_meta_disk_usage',
				'meta_compare' => 'EXISTS',
			) );

			// Make sure the Admin has the meta set to include him in results.
			$user_id = get_current_user_id();

			$disk_usage = get_user_meta( $user_id, '_wp_user_meta_disk_usage', true );
			if ( ! is_numeric( $disk_usage ) ) {
				update_user_meta( $user_id, '_wp_user_meta_disk_usage', 0 );
			}
		}
	}

	return $prepared_args;
}

/**
 * Get the User Media base Uploads dir data.
 *
 * @since 1.0.0
 *
 * @return array The User Media base Uploads dir data.
 */
function wp_user_media_get_upload_dir() {
	$wp_user_media = wp_user_media();

	if ( ! isset( $wp_user_media->upload_dir ) ) {
		wp_user_media_register_upload_dir();
	}

	return $wp_user_media->upload_dir;
}

/**
 * Set the User Media base Uploads dir data.
 *
 * @since  1.0.0
 *
 * @param  array  $dir The wp_upload_dir() data.
 * @return array       The User Media base Uploads dir data.
 */
function wp_user_media_set_upload_base_dir( $dir = array() ) {
	if ( empty( $dir['basedir'] ) || empty( $dir['baseurl'] ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Missing parameters.', 'wp-user-media' ) );
		return $dir;
	}

	return array_merge( $dir, array(
		'path'   => $dir['basedir'] . '/wp-user-media',
		'url'    => $dir['baseurl'] . '/wp-user-media',
		'subdir' => '/wp-user-media',
	) );
}

/**
 * Register the User Media Uploads dir into the plugin's global.
 *
 * @since 1.0.0
 */
function wp_user_media_register_upload_dir() {
	$needs_switch = false;
	$network_id   = (int) get_current_network_id();

	if ( is_multisite() && (int) get_current_blog_id() !== $network_id ) {
		$needs_switch = true;
		switch_to_blog( $network_id );
	}

	add_filter( 'upload_dir', 'wp_user_media_set_upload_base_dir', 10, 1 );

	wp_user_media()->upload_dir = wp_upload_dir( null, true );

	remove_filter( 'upload_dir', 'wp_user_media_set_upload_base_dir', 10, 1 );

	if ( $needs_switch ) {
		restore_current_blog();
	}
}

/**
 * Register Post Statuses for User Media.
 *
 * @since  1.0.0
 */
function wp_user_media_register_post_statuses() {
	$supported     = (array) apply_filters( 'wp_user_media_supported_post_statuses', array( 'publish', 'private' ) );
	$wp_user_media = wp_user_media();
	$wp_user_media->statuses = array();

	foreach ( $supported as $status ) {
		$wp_user_media->statuses[ $status ] = get_post_status_object( $status );

		// Override the Publish status label
		if ( 'publish' === $status ) {
			$wp_user_media->statuses[ $status ]->label = __( 'Public', 'wp-user-media' );
		}

		if ( null === $wp_user_media->statuses[ $status ] ) {
			unset( $wp_user_media->statuses[ $status ] );
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
function wp_user_media_get_post_statuses( $status = '' ) {
	$wp_user_media = wp_user_media();

	if ( 'all' === $status ) {
		return $wp_user_media->statuses;
	} elseif ( ! isset( $wp_user_media->statuses[ $status ] ) ) {
		return null;
	}

	return $wp_user_media->statuses[ $status ];
}

/**
 * Register the post type and the taxonomy used by User Media.
 *
 * @since 1.0.0
 */
function wp_user_media_register_objects() {

	/** Post Type ************************************************************/

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
		'rest_base'             => 'user-media',
	) );

	/** Taxonomy *************************************************************/

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

	/** Post statuses ********************************************************/

	wp_user_media_register_post_statuses();

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

	/** User Meta ************************************************************/

	register_meta(
		'user',
		'_wp_user_meta_disk_usage',
		array(
			'sanitize_callback' => 'wp_user_meta_disk_usage_sanitize_value',
			'type'              => 'integer',
			'description'       => 'The disk usage of the user in KB.',
			'single'            => true,
			'show_in_rest'      => array(
				'name'             => 'disk_usage',
				'prepare_callback' => 'wp_user_meta_disk_usage_prepare',
			)
		)
	);

	/** Uploads dir **********************************************************/

	wp_user_media_register_upload_dir();
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
 * Add a custom default template for embedded User Media.
 *
 * @since  1.0.0
 *
 * @param  string $template  Path to the template. See locate_template().
 * @return string            Path to the template. See locate_template().
 */
function wp_user_media_embed_template( $template = '' ) {
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
		$template = wp_user_media_templates() . 'embed-user_media.php';
	}

	return $template;
}

/**
 * Print the User Media excerpt for the embed template.
 *
 * @since  1.0.0
 */
function wp_user_media_embed_excerpt() {
	$excerpt = apply_filters( 'the_excerpt_embed', get_the_excerpt() );
	$excerpt = wp_user_media_prepend_user_media( $excerpt );

	echo apply_filters( 'wp_user_media_embed_excerpt', $excerpt );
}

/**
 * Prints the necessary markup for the embed download button.
 *
 * @since 1.0.0
 */
function wp_user_media_embed_download_button() {
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
		esc_url( wp_user_media_get_download_url() ),
		sprintf(
			__( 'Download<span class="screen-reader-text"> %s</span>', 'wp-user-media' ),
			esc_html( get_the_title() )
		)
	);
}

/**
 * Enqueue the Embed styles.
 *
 * @since 1.0.0
 */
function wp_user_media_embed_style() {
	wp_enqueue_style(
		'wp-user-media-embed',
		sprintf( '%1$sembed%2$s.css', wp_user_media_assets_url(), wp_user_media_min_suffix() ),
		array(),
		wp_user_media_version()
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
function wp_user_media_get_template_part( $template = '', $id = '', $load = true ) {
	if ( empty( $template ) || empty( $id ) ) {
		return '';
	}

	$template = str_replace( '.html', '', $template );
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

/**
 * Set some WP_Query parameters so that the Attachment template is used.
 *
 * @since  1.0.0
 *
 * @param  WP_Query $query The WordPress Main Query
 */
function wp_user_media_parse_query( WP_Query $query ) {
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

	if ( 'user_media' !== $query->get( 'post_type' ) || 1 === (int) $query->get( wp_user_media_get_download_rewrite_tag() ) || true === $query->is_embed ) {
		return;
	}

	$query->is_attachment = true;
	add_filter( 'the_content', 'wp_user_media_prepend_user_media', 11 );
}

/**
 * Make sure the User Media file is prepended to its description.
 *
 * @since  1.0.0
 *
 * @param  string $content The User Media description.
 * @return string          The User Media description.
 */
function wp_user_media_prepend_user_media( $content = '' ) {
	if ( 'user_media' !== get_post_type() || empty( $GLOBALS['post'] ) ) {
		return $content;
	}

	wp_user_media()->user_media_link = wp_user_media_get_download_url( $GLOBALS['post'] );

	// Overrides
	$reset_post = clone $GLOBALS['post'];
	$GLOBALS['post']->post_type = 'attachment';
	wp_cache_set( $reset_post->ID, $GLOBALS['post'], 'posts' );
	add_filter( 'wp_get_attachment_link', 'wp_user_media_attachment_link', 10, 1 );

	$content = prepend_attachment( $content );

	// Resets
	$GLOBALS['post'] = $reset_post;
	wp_cache_set( $reset_post->ID, $reset_post, 'posts' );
	remove_filter( 'the_content',            'wp_user_media_prepend_user_media', 11    );
	remove_filter( 'wp_get_attachment_link', 'wp_user_media_attachment_link',    10, 1 );

	unset( wp_user_media()->user_media_link );

	return $content;
}

