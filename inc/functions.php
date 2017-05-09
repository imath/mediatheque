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
 * Checks if the current or given site ID is the main site of the network.
 *
 * @since  1.0.0
 *
 * @param  int  $site_id The site ID to check. Optional. Defaults to the current site ID.
 * @return bool True if the site is the main site of the network. False otherwise.
 */
function mediatheque_is_main_site( $site_id = 0 ) {
	$return = ! is_multisite();

	if ( $return ) {
		return $return;
	}

	if ( empty( $site_id ) || ! is_numeric( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	if ( (int) get_current_network_id() === (int) $site_id ) {
		$return = true;
	}

	return $return;
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
	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	if ( null === $user_media && isset( mediatheque()->user_media_link ) ) {
		return mediatheque()->user_media_link;
	} else {
		$user_media = get_post( $user_media );
	}

	$url = '#';

	if ( ! is_a( $user_media, 'WP_Post' ) || 'user_media' !== $user_media->post_type ) {
		return $url;
	}

	$download_url = sprintf( '%1$s/%2$s/', trim( get_post_permalink( $user_media ), '/' ), mediatheque_get_download_rewrite_slug() );

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	return apply_filters( 'mediatheque_get_download_url', $download_url, $user_media );
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
		$link = preg_replace( '/(width|height)="\d*"\s/', '', $link, 2, $count );

		// It's not an image.
		if ( empty( $count ) ) {
			$link = mediatheque_file_shortcode( null, array( 'use_file_name' => true ) );
		} else {
			$link = preg_replace( '/(?<=href=\').+(?=\')/', $user_media_link, $link );
		}
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

function mediatheque_format_file_size( $kb = 0 ) {
	$unit = ' KB';

	if ( empty( $kb ) ) {
		return 0 . $unit;
	}

	$value     = absint( $kb );
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

	return apply_filters( 'mediatheque_get_upload_dir', $mediatheque->upload_dir );
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

	/**
	 * This looks like a WordPress bug on subdomain installs.
	 *
	 * On a subsite, although ms is switched, the baseurl is the one
	 * of the subsite and the url protocol is not set to https when needed.
	 *
	 * @todo explore this issue a bit more.
	 */
	if ( is_multisite() && ms_is_switched() ) {
		$parse_base_url = parse_url( $dir['baseurl'] );

		if ( is_subdomain_install() ) {
			$dir['baseurl'] = trailingslashit( network_site_url() ) . ltrim( $parse_base_url['path'], '/' );
		} else {
			$path = explode( '/', ltrim( $parse_base_url['path'], '/' ) );

			if ( 'wp-content' !== $path[0] ) {
				array_shift( $path );
			}

			$dir['baseurl'] = network_site_url() . join( '/', $path );
		}
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
	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	add_filter( 'upload_dir', 'mediatheque_set_upload_base_dir', 10, 1 );

	mediatheque()->upload_dir = wp_upload_dir( null, true );

	remove_filter( 'upload_dir', 'mediatheque_set_upload_base_dir', 10, 1 );

	if ( ! $is_main_site ) {
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
	$mediatheque = mediatheque();

	$post_type_object     = get_post_type_object( 'user_media' );
	$mediatheque_statuses = apply_filters( 'mediatheque_media_statuses',
		wp_list_pluck( mediatheque_get_post_statuses( 'all' ), 'label', 'name' ),
		$handle
	);

	$network_root_url = '';
	if ( ! mediatheque_is_main_site() ) {
		$network_root_url = trailingslashit( network_site_url() ) . mediatheque_get_root_slug();
	}

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
			$mediatheque_statuses,
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
			'rootSlug'        => mediatheque_get_root_slug(),
			'networkRootUrl'  => $network_root_url,
			'downloadSlug'    => mediatheque_get_download_rewrite_slug(),
			'closeBtn'        => __( 'Fermer', 'mediatheque' ),
			'noUserMedia'     => __( 'Aucun media utilisateur ne correspond à votre requête.', 'mediatheque' ),
			'dismissibleText' => __( 'Rejeter', 'mediatheque' ),
			'insertBtn'       => $post_type_object->labels->insert_into_item,
			'avatarBtn'       => $post_type_object->labels->use_as_avatar,
			'frameTitle'      => $post_type_object->labels->menu_name,
			'embedTitle'      => __( 'Définissez vos préférences d\'affichage du media', 'mediatheque' ),
			'embedBtn'        => __( 'Définir', 'mediatheque' ),
			'alignLabel'      => __( 'Alignement', 'mediatheque' ),
			'alignBtns'       => array(
				'left'   => __( 'Gauche', 'mediatheque' ),
				'center' => __( 'Centre', 'mediatheque' ),
				'right'  => __( 'Right', 'mediatheque' ),
				'none'   => __( 'None', 'mediatheque' ),
			),
			'directory'       => mediatheque_get_displayed_directory(),
		),
		'fields' => array(
			'icon' => array(
				'caption'  => __( 'Masquer l\'icone', 'mediatheque' ),
				'position' => 0,
				'type'     => 'checkbox',
				'classes'  => array( 'setting', 'checkbox-setting' ),
			),
			'media_type' => array(
				'caption'  => __( 'Masquer le type de media.', 'mediatheque' ),
				'position' => 1,
				'type'     => 'checkbox',
				'classes'  => array( 'setting', 'checkbox-setting' ),
			),
			'ext' => array(
				'caption'  => __( 'Masquer l\'extension', 'mediatheque' ),
				'position' => 2,
				'type'     => 'checkbox',
				'classes'  => array( 'setting', 'checkbox-setting' ),
			),
			'file_size' => array(
				'caption'  => __( 'Masquer la taille du media.', 'mediatheque' ),
				'position' => 3,
				'type'     => 'checkbox',
				'classes'  => array( 'setting', 'checkbox-setting' ),
			),
		),
		'editFields' => array(
			'title' => array(
				'name'  => __( 'Titre', 'mediatheque' ),
				'position' => 0,
				'type'     => 'text',
				'classes'  => array( 'title' ),
			),
			'description' => array(
				'name'  => __( 'Description', 'mediatheque' ),
				'position' => 1,
				'type'     => 'contenteditable',
				'classes'  => array( 'description' ),
			),
			'attached_posts' => array(
				'name'     => __( 'Attaché au(x) Contenu(s) :', 'mediatheque' ),
				'position' => 2,
				'type'     => 'list',
				'classes'  => array( 'posts' ),
			),
			'reset' => array(
				'caption'  => __( 'Annuler', 'mediatheque' ),
				'position' => 3,
				'type'     => 'reset',
				'classes'  => array( 'button-secondary', 'reset' ),
			),
			'submit' => array(
				'caption'  => __( 'Modifier', 'mediatheque' ),
				'position' => 4,
				'type'     => 'submit',
				'classes'  => array( 'button-primary', 'submit' ),
			),
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
		'mediatheque-display' => array(
			'location' => sprintf( '%1$sdisplay%2$s.js', $url, $min ),
			'deps'     => array( 'mediatheque-views' ),
		),
	);

	foreach ( $scripts as $handle => $script ) {
		wp_register_script( $handle, $script['location'], $script['deps'], $v, true );
	}

	wp_register_style(
		'mediatheque-uploader',
		sprintf( '%1$suploader%2$s.css', mediatheque_assets_url(), $min ),
		array( 'dashicons' ),
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
		'delete_with_user'      => false,
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
		'show_ui'               => false,
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

	if ( mediatheque_use_personal_avatar() ) {
		foreach ( (array) rest_get_avatar_sizes() as $size ) {
			add_image_size( sprintf( 'avatar-%s', $size ), $size, $size, true );
		}
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
	if ( empty( $object->post_type ) || 'user_media' !== $object->post_type ) {
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

	if ( mediatheque_get_displayed_directory() ) {
		$dashicon = 'files';
		$url      = get_post_permalink();
		$text     = __( 'Afficher', 'mediatheque' );
	} else {
		$dashicon = 'download';
		$url      = mediatheque_get_download_url();
		$text     = __( 'Télécharger', 'mediatheque' );
	}

	printf(
		'<div class="wp-embed-%1$s">
			<a href="%2$s" target="_top">
				<span class="dashicons dashicons-%1$s"></span>
				%3$s
			</a>
		</div>',
		esc_attr( $dashicon ),
		esc_url( $url ),
		sprintf(
			'%1$s<span class="screen-reader-text"> %2$s</span>',
			esc_html( $text ),
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

function mediatheque_locate_template_part( $template = '', $type = 'html' ) {
	if ( empty( $template ) ) {
		return false;
	}

	$template = str_replace( ".{$type}", '', $template );
	$located  = '';

	$template_locations = (array) apply_filters( 'mediatheque_locate_template_part', array(
		trailingslashit( get_stylesheet_directory() ) . 'mediatheque/' . $template . '.' . $type,
		trailingslashit( get_template_directory() ) . 'mediatheque/' . $template . '.' . $type,
		mediatheque_templates() . $template . '.' . $type,
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

	return $located;
}

/**
 * Retrieve the path of the highest priority template file that exists.
 *
 * @since  1.0.0
 *
 * @param  string  $template The template file name.
 * @param  string  $name     The Undersore template ID.
 * @param  bool    $load     Whether to load or return the found template.
 * @param  string  $type     The template type (php or html).
 * @return string            The template path.
 */
function mediatheque_get_template_part( $template = '', $id = '', $load = true, $type = 'html' ) {
	if ( empty( $template ) || empty( $id ) ) {
		return '';
	}

	$located = mediatheque_locate_template_part( $template );

	if ( $load && $located && 'html' === $type ) {
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
		'field-item'       => 'mediatheque-field-item',
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

	if ( ! mediatheque_is_main_site() ) {
		return;
	}

	if ( ! $query->is_main_query() || true === $query->get( 'suppress_filters' ) ) {
		$bail = true;
	}

	if ( ! $bail && is_admin() ) {
		$bail = ! wp_doing_ajax();
	}

	if ( $bail ) {
		return;
	}

	if ( 'user_media' !== $query->get( 'post_type' ) || true === $query->is_embed ) {
		return;
	}

	if ( 1 === (int) $query->get( mediatheque_get_download_rewrite_tag() ) ) {
		$user_media = mediatheque_get_post_by_slug( $query->get( 'mediatheque' ) );

		if ( false === $user_media ) {
			$query->set_404();
			return;
		}

		if ( 'publish' !== get_post_status( $user_media ) && ! current_user_can( 'read_private_user_uploads' ) ) {
			$query->set_404();
			return;
		}

		$media_file = get_attached_file( $user_media->ID );

		if ( ! $media_file ) {
			$query->set_404();
			return;
		} else {
			$user_media->mediatheque_file = $media_file;

			if ( ! mediatheque_download( $user_media ) ) {
				$query->set_404();
				return;
			}
		}
	}

	$query->is_attachment = true;
	add_filter( 'the_content', 'mediatheque_prepend_user_media', 11 );
}

function mediatheque_set_displayed_directory( $id = 0 ) {
	mediatheque()->template_tags->directory = $id;
}

function mediatheque_set_template_tags( $tags = array() ) {
	if ( empty( $tags ) || ! is_array( $tags ) ) {
		return;
	}

	$mediatheque = mediatheque();

	foreach ( $tags as $kt => $vt ) {
		$mediatheque->template_tags->{$kt} = $vt;
	}
}

function mediatheque_get_displayed_directory() {
	return (int) mediatheque_get_tag( 'directory' );
}

function mediatheque_get_tag( $tag = '' ) {
	$content_tag = 0;

	if ( ! $tag ) {
		return $content_tag;
	}

	$mediatheque = mediatheque();

	if ( isset( $mediatheque->template_tags->{$tag} ) ) {
		$content_tag = $mediatheque->template_tags->{$tag};
	}

	return apply_filters( 'mediatheque_get_displayed_' . $tag , $content_tag );
}

function mediatheque_get_display_content( $attr ) {
	$content = '';

	if ( did_action( 'mediatheque_display_content' ) ) {
		return $content;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$atts = shortcode_atts( array(
		'directory' => 0,
		'width'     => '100%',
		'height'    => '450px',
	), $attr, 'mediatheque' );

	// Globalize template tags
	mediatheque_set_template_tags( $atts );

	$template = mediatheque_locate_template_part( 'display', 'php' );

	if ( $template ) {
		wp_enqueue_script( 'mediatheque-display' );
		mediatheque_localize_script();

		wp_enqueue_style( 'mediatheque-uploader' );

		ob_start();

		load_template( $template, true );

		$content = ob_get_clean();
	}

	do_action( 'mediatheque_display_content' );

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	return $content;
}
add_shortcode( 'mediatheque', 'mediatheque_get_display_content' );

function mediatheque_prepend_embed_thumbnail( $excerpt = '', $user_media = null, $type = 'user-media' ) {
	$user_media = get_post( $user_media );

	if ( empty( $user_media->ID ) ) {
		return $excerpt;
	}

	$pattern = '<div style="background: #f1f1f1 url( %1$s ) no-repeat; background-size: %2$s; background-position:%3$s; width:150px; height:150px"></div>';

	$media_icon = '';
	if ( 'directory' === $type ) {
		// Set the displayed directory.
		mediatheque_set_displayed_directory( $user_media->ID );

		$media_icon = sprintf(
			$pattern,
			esc_url_raw( mediatheque_assets_url() . 'folder.svg' ),
			'50%',
			'50%'
		);
	} else {
		$filedata = mediatheque_get_media_info( $user_media, 'all' );

		if ( isset( $filedata['media_type'] ) ) {
			if ( 'image' === $filedata['media_type'] ) {
				$thumb_data = mediatheque_image_get_intermediate_size( $user_media, 'thumbnail' );

				if ( isset( $thumb_data['url'] ) ) {
					$media_icon = sprintf(
						'<img src="%s" width="150" height="150">',
						esc_url_raw( $thumb_data['url'] )
					);
				}

			} else {
				$media_icon = sprintf(
					$pattern,
					esc_url_raw( wp_mime_type_icon( $filedata['media_type'] ) ),
					'48px 64px',
					'50%'
				);
			}
		}
	}

	if ( ! $excerpt && 'user-media' === $type ) {
		$media_type  = mediatheque_get_i18n_media_type( $filedata['media_type'] );
		$media_title = basename( $user_media->guid );
		$media_size  = mediatheque_format_file_size( $filedata['size'] / 1000 );

		$excerpt = sprintf( '<dl>
				<dt><strong>%1$s</strong><dt>
				<dd><small>%2$s (%3$s)</small></dd>
			</dl>',
			esc_html( $media_title ),
			esc_html( $media_type ),
			esc_html( $media_size )
		);
	}

	$thumbnail = sprintf( '<div class="wp-embed-featured-image square">
		<a href="%1$s" target="_top">
			%2$s
		</a>
	</div>', get_post_permalink( $user_media ), $media_icon );

	return $thumbnail . "\n" . $excerpt;
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

	$mediatheque  = mediatheque();
	$term_ids     = wp_get_object_terms( $GLOBALS['post']->ID, 'user_media_types', array( 'fields' => 'ids' ) );
	$directory_id = mediatheque_get_user_media_type_id( 'mediatheque-directory' );

	// Single Directory display
	if ( in_array( $directory_id, $term_ids, true ) ) {
		if ( ! is_embed() ){
			$content .= "\n" . mediatheque_get_display_content( array(
				'directory' => $GLOBALS['post']->ID,
			) );
		} else {
			$content = mediatheque_prepend_embed_thumbnail( $content, $GLOBALS['post'], 'directory' );
		}

	// Single User Media display
	} else {
		if ( ! is_embed() ){
			$mediatheque->user_media_link = mediatheque_get_download_url( $GLOBALS['post'] );

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

			unset( $mediatheque->user_media_link );
		} else {
			$content = mediatheque_prepend_embed_thumbnail( $content, $GLOBALS['post'], 'user-media' );
		}
	}

	$GLOBALS['wp_query']->is_attachment = false;

	return $content;
}

function mediatheque_button( $args = array() ) {
	static $instance = 0;
	$instance++;

	$r = wp_parse_args( $args, array(
		'editor_id'           => 'content',
		'editor_btn_classes'  => array( 'mediabrary-insert' ),
		'editor_btn_text'     => __( 'Ajouter un media', 'mediatheque' ),
		'editor_btn_dashicon' => 'dashicons-format-image',
		'echo'                => true,
		'media_type'          => '',
	) );

	$post = get_post();
	if ( ! $post && ! empty( $GLOBALS['post_ID'] ) ) {
		$post = $GLOBALS['post_ID'];
	}

	wp_enqueue_media( array(
		'post' => $post
	) );

	if ( ! empty( $r['media_type'] ) ) {
		wp_add_inline_script( 'mediatheque-views', sprintf( '
				var mediaThequeCustoms = %s;
			',
			json_encode( array( 'mediaType' => $r['media_type'] ) )
		) );
	}

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
		mediatheque_button( array(
			'editor_id'           => $mediatheque->editor_id,
			'editor_btn_classes'  => array( 'mediatheque-insert' ),
			'echo'                => false,
		) ),
		$editor
	);
}

function mediatheque_wp_link_query_args( $args = array() ) {
	if ( is_array( $args['post_type'] ) && in_array( 'user_media', $args['post_type'], true ) ) {
		$args['post_type'] = array_diff( $args['post_type'], array( 'user_media' ) );
	}

	return $args;
}

function mediatheque_oembed_pre_dataparse( $result = null, $data = null, $url = '' ) {
	$mediatheque = mediatheque();

	if ( ! empty( $mediatheque->user_media_oembeds[ $url ] ) ) {
		return false;
	}

	return $result;
}

function mediatheque_oembed_dataparse( $result = null, $data = null, $url = '' ) {
	$mediatheque = mediatheque();

	if ( false !== $result || empty( $mediatheque->user_media_oembeds[ $url ] ) ) {
		return $result;
	}

	$return = $mediatheque->user_media_oembeds[ $url ];

	if ( is_multisite() && ms_is_switched() ) {
		restore_current_blog();
	}

	return $return;
}

function mediatheque_oembed_get_url_args( $url = '' ) {
	$args = array();

	if ( empty( $url ) ) {
		return $args;
	}

	$url       = str_replace( '&amp;', '&', $url );
	$url_parts = parse_url( $url );

	if ( false === array_search( mediatheque_get_root_slug(), explode( '/', wp_unslash( $url_parts['path'] ) ) ) ) {
		return $args;
	}

	if ( empty( $url_parts['query'] ) ) {
		return $args;
	}

	return wp_parse_args( $url_parts['query'], array(
		'attached' => false,
		'size'     => 'full',
		'align'    => 'none',
		'link'     => 'file'
	) );
}

function mediatheque_oembed_user_media_id( $id = 0, $url = '' ) {
	$_id = $id;

	/**
	 * On Multisite embedding an URL of the main site in a subsite
	 * fails to fetch the embed html.
	 *
	 * @see https://core.trac.wordpress.org/ticket/40673
	 */
	if ( ! $id && is_multisite() ) {
		$network_url = trailingslashit( network_site_url() ) . mediatheque_get_root_slug();

		$network_url = addcslashes( $network_url, '/' );
		preg_match( "/$network_url\/(.*?)\?/", $url, $matches );

		if ( empty( $matches[1] ) ) {
			return $id;
		}

		$slug = trim( $matches[1], '/' );
		$_id = mediatheque_get_post_by_slug( $slug );
	}

	if ( ! $_id ) {
		return $id;
	} elseif ( is_a( $_id, 'WP_Post' ) ) {
		$id = $_id->ID;
	}

	$url_id = str_replace( '&amp;', '&', $url );
	$args   = mediatheque_oembed_get_url_args( $url_id );

	if ( empty( $args['attached'] ) ) {
		return $id;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$user_media = get_post( $_id );
	$media_type = mediatheque_get_media_info( $user_media );

	// Take care of images
	if ( 'image' === $media_type ) {
		$image = mediatheque_image_get_intermediate_size( $user_media, $args['size'] );

		if ( ! empty( $image['url'] ) ) {
			$height = '';
			if ( ! empty( $image['height'] ) ) {
				$height = sprintf( ' height="%d"', esc_attr( $image['height'] ) );
			}

			$width = '';
			if ( ! empty( $image['width'] ) ) {
				$width = sprintf( ' width="%d"', esc_attr( $image['width'] ) );
			}

			$output = sprintf(
				'<img class="mediatheque-user-media size-%1$s align%2$s" src="%3$s" draggable="false"%4$s%5$s style="width: auto; height: auto">',
				esc_attr( $args['size'] ),
				esc_attr( $args['align'] ),
				esc_url_raw( $image['url'] ),
				$height,
				$width
			);

			if ( 'post' === $args['link'] ) {
				$link = get_post_permalink( $user_media );
			} elseif ( 'file' === $args['link'] ) {
				$link = mediatheque_get_download_url( $user_media );
			}

			if ( ! empty( $link ) ) {
				$output = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url_raw( $link ),
					$output
				);
			}

			mediatheque()->user_media_oembeds[ $url ] = $output;
		}

	// Take care of audios or videos
	} elseif ( 'video' === $media_type || 'audio' === $media_type ) {
		$default_av_args = array(
			'src'      => '',
			'loop'     => '',
			'autoplay' => '',
			'preload'  => 'metadata',
			'class'    => 'wp-video-shortcode',
		);

		$av_args = array_intersect_key( $args, $default_av_args );
		$av_args = wp_parse_args( $av_args, $default_av_args );

		$uploads        = mediatheque_get_upload_dir();
		$av_args['src'] = trailingslashit( $uploads['baseurl'] ) . get_post_meta( $user_media->ID, '_wp_attached_file', true );

		if ( ! $av_args['src'] ) {
			return $id;
		}

		// Video Shortcode
		if ( 'video' === $media_type ) {
			if ( 'none' !== $args['align'] ) {
				$av_args['class'] .= sprintf( ' align%s', $args['align'] );
			}

			$size = wp_get_attachment_metadata( $user_media->ID );
			if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) ) {
				$av_args['width']  = $size['width'];
				$av_args['height'] = $size['height'];
			}

			// Check the Themes content width
			if ( ! empty( $GLOBALS['content_width'] ) && $av_args['width'] > $GLOBALS['content_width'] ) {
				$av_args['height'] = round( ( $av_args['height'] * $GLOBALS['content_width'] ) / $av_args['width'] );
				$av_args['width']  = $GLOBALS['content_width'];
			}

			$output = wp_video_shortcode( $av_args );

		// Audio Shortcode
		} else {
			unset( $av_args['class'] );

			$output = wp_audio_shortcode( $av_args );
		}

		if ( empty( $output ) ) {
			return $id;
		}

		mediatheque()->user_media_oembeds[ $url ] = $output;

	// Take care of other files
	} else {
		$output = mediatheque_file_shortcode( $user_media, $args );

		if ( empty( $output ) ) {
			return $id;
		}

		mediatheque()->user_media_oembeds[ $url ] = $output;
	}

	// Track cached user media.
	if ( ! empty( $output ) ) {
		$key_suffix = md5( $url_id . serialize( wp_embed_defaults( $url ) ) );
		$cache_keys = array( '_oembed_' . $key_suffix, '_oembed_time_' . $key_suffix );

		$cached_keys = get_post_meta( $user_media->ID, '_user_media_cached_keys', true );

		// Defauts to an empty array.
		if ( ! $cached_keys ) {
			$cached_keys = array();
		}

		// Update cached keys, if needed.
		if ( ! array_intersect( $cache_keys, $cached_keys ) ) {
			update_post_meta( $user_media->ID, '_user_media_cached_keys', array_merge( $cached_keys, $cache_keys ) );
		}
	} else {
		if ( ! $is_main_site ) {
			restore_current_blog();
		}
	}

	return $id;
}

/**
 * Clear cached user media.
 *
 * @since  1.0.0
 *
 * @param  WP_Post $user_media The User Media Object.
 * @return bool                True when the cached user media has been cleared.
 *                             False otherwise.
 */
function mediatheque_clear_cached_media( $user_media = null ) {
	global $wpdb;

	if ( empty( $user_media->ID ) ) {
		return false;
	}

	$cached_keys = get_post_meta( $user_media->ID, '_user_media_cached_keys', true );

	if ( ! $cached_keys ) {
		return false;
	}

	$in = array_map( 'esc_sql', $cached_keys );
	$in = '"' . join( '","', $in ) . '"';

	// Remove the list of cache meta keys that are about to be deleted.
	delete_post_meta( $user_media->ID, '_user_media_cached_keys' );

	// Delete cached meta keys so that the cache will be reset at next media load.
	$return = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ({$in})" );

	if ( $return ) {
		wp_cache_flush();
	}

	return (bool) $return;
}

function mediatheque_get_attached_posts( $s = '' ) {
	if ( ! $s ) {
		return array();
	}

	$search_posts   = new WP_Query;
	return $search_posts->query( array(
		'post_type' => 'any',
		's'         => $s,
	) );
}

/**
 * Hide the vanished User Media or warn the Administrator of it.
 *
 * @since  1.0.0
 *
 * @param  string $link The oembed link output.
 * @param  string $url  The requested URL.
 * @return string       An empty string for regular users, a warning message for Admins
 */
function mediatheque_maybe_hide_link( $link = '', $url = '' ) {
	if ( empty( $link ) || empty( $url ) ) {
		return $link;
	}

	$mediatheque_url = mediatheque_oembed_get_url_args( $url );

	if ( empty( $mediatheque_url['attached'] ) ) {
		return $link;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		$vanished_media_log = get_option( '_mediatheque_vanished_media', array() );
		$query_vars = parse_url( $url, PHP_URL_QUERY );
		$s          = str_replace( '?' . $query_vars, '', $url );

		if ( ! in_array( $s, $vanished_media_log, true ) ) {
			update_option( '_mediatheque_vanished_media', array_merge( $vanished_media_log, array( $s ) ) );

			$search_posts   = new WP_Query;
			$search_results = mediatheque_get_attached_posts( $s );

			if ( ! empty( $search_results ) ) {
				$warning = _n(
					'Ci-dessous le titre du contenu dans lequel le media est présent :',
					'Ci-dessous la liste des titres de contenu dans lesquels le media est présent :',
					count( $search_results ),
					'mediatheque'
				);

				foreach ( $search_results as $p ) {
					$post_type_object = get_post_type_object( $p->post_type );

					$link = '';
					if ( $post_type_object->_edit_link ) {
						$link = ' ( ' . esc_url_raw( admin_url( sprintf( $post_type_object->_edit_link . '&action=edit', $p->ID ) ) ) . ' )';
					}

					$warning .= "\n " . sprintf( '- %1$s%2$s', esc_html( $p->post_title ), $link );
				}

				wp_mail(
					get_option( 'admin_email' ),
					"[ " . wp_specialchars_decode( get_option( 'blogname' ) ) . " ] " . __( 'Media disparu', 'mediatheque' ),
					$warning
				);
			}
		}

		return '';
	}

	return sprintf(
		'<div class="mediatheque-vanished-media" style="border-left: solid 4px #dc3232; padding-left: 1em"><p>%1$s &lt; <em>%2$s</em></p></div>',
		'¯\_(ツ)_/¯',
		__( 'Hum hum, il semble que ce media ait mystérieusement disparu.', 'mediatheque' )
	);
}

function mediatheque_load_textdomain() {
	$mediatheque = mediatheque();
	load_plugin_textdomain( $mediatheque->domain, false, trailingslashit( basename( $mediatheque->dir ) ) . 'languages' );
}
