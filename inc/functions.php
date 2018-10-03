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
 * @param  WP_Post|integer $user_media The User Media object or the ID of the User Media item.
 * @return string                      The download url for the User Media Item.
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

	/**
	 * Filter here to edit the download url for a User Media Item.
	 *
	 * @since 1.0.0
	 *
	 * @param  string          $download_url The download url for the User Media Item.
	 * @param  WP_Post|integer $user_media   The User Media object of the User Media item.
	 */
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
			$link = mediatheque_file_shortcode( null, array( 'use_file_name' => true, 'object_wrapper' => 'true' ) );
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

/**
 * Formats file size/disk usage for display.
 *
 * @since 1.0.0
 *
 * @param  integer $kb The size in kilobytes.
 * @return string      The formatted size.
 */
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

/**
 * Sorts a list of keyed arrays using their position value.
 *
 * @since 1.0.0
 *
 * @param  array $f The array to sort.
 * @return array    The sorted array.
 */
function mediatheque_sort_array_fields( $f = array() ) {
	$order = array();

	foreach ( $f as $kf => $af ) {
		$position = 2;
		if ( isset( $af['position'] ) ) {
			$position = (int) $af['position'];
		}

		$order[ $kf ] = $position;
	}

	asort( $order );

	$return = array();
	foreach ( array_keys( $order ) as $o ) {
		if ( ! isset( $f[ $o ] ) ) {
			continue;
		}

		$return[ $o ] = $f[ $o ];
	}

	return $return;
}

/**
 * Get allowed mime types for the <object> wrapper tag.
 *
 * @since  1.2.0
 *
 * @return array A list of allowed mime types. Defaults to PDF files.
 *               Use an empty array to completely disable this feature.
 */
function mediatheque_get_object_wrapper_mimes() {
	/**
	 * Filter here to add mimes to allow wrapping the file into an object tag.
	 *
	 * @since  1.2.0
	 *
	 * @param  array $value A list of allowed mime types. Defaults to PDF files.
	 *                      Use an empty array to completely disable this feature.
	 */
	return (array) apply_filters( 'mediatheque_object_wrapper_mimes', array(
		'application/pdf',
	) );
}

/**
 * Loacalizes scripts data.
 *
 * @since 1.0.0
 *
 * @param string $handle The Javascript script handle.
 */
function mediatheque_localize_script( $handle = 'mediatheque-views', $user_id = 0 ) {
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

	// Set editFields
	$edit_fields = array(
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
			'position' => 20,
			'type'     => 'list',
			'classes'  => array( 'posts' ),
		),
		'reset' => array(
			'caption'  => __( 'Annuler', 'mediatheque' ),
			'position' => 21,
			'type'     => 'reset',
			'classes'  => array( 'button-secondary', 'reset' ),
		),
		'submit' => array(
			'caption'  => __( 'Modifier', 'mediatheque' ),
			'position' => 22,
			'type'     => 'submit',
			'classes'  => array( 'button-primary', 'submit' ),
		),
	);

	/**
	 * Filter here to add Custom Fields to the edit view of User Media.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value The list of the custom fields keyed by their id.
	 */
	$custom_fields = (array) apply_filters( 'mediatheque_localize_custom_fields', array() );
	$edit_fields   = $edit_fields + $custom_fields;

	/**
	 * Filter here to edit the available major actions.
	 *
	 * @since  1.2.0
	 *
	 * @param  array $value The available major actions.
	 */
	$major_actions = (array) apply_filters( 'mediatheque_localize_major_actions', array(
		'upload'    => __( 'Nouveau(x) fichier(s)', 'mediatheque' ),
		'directory' => __( 'Nouveau répertoire', 'mediatheque' ),
	) );

	$preference_fields = array(
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
	);

	$object_wrapper_mimes = mediatheque_get_object_wrapper_mimes();

	if ( $preference_fields ) {
		$preference_fields['object_wrapper'] = array(
			'caption'  => __( 'Incorporer le media.', 'mediatheque' ),
			'position' => 4,
			'type'     => 'checkbox',
			'classes'  => array( 'setting', 'checkbox-setting' ),
			'validate' => $object_wrapper_mimes,
		);
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
				'users' => __( 'Choisissez un utilisateur', 'mediatheque' )
			),
			$mediatheque_statuses,
			$major_actions
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
				'right'  => __( 'Droite', 'mediatheque' ),
				'none'   => __( 'Aucun', 'mediatheque' ),
			),
			'directory'       => mediatheque_get_displayed_directory(),
			'user_id'         => $user_id,
			'isUserMediaOnly' => ! current_user_can( 'upload_files' ) || ! empty( $mediatheque->editor_id ),
		),
		'fields' => $preference_fields,
		'editFields' => mediatheque_sort_array_fields( $edit_fields ),

		/**
		 * Add the Rest Nonce in case Gutenberg is active.
		 *
		 * @see https://github.com/WordPress/gutenberg/pull/7329
		 */
		'restNonce' => ( wp_installing() && ! is_multisite() ) ? '' : wp_create_nonce( 'wp_rest' ),
	) );
}

/**
 * Registers JavaScripts and Styles.
 *
 * @since 1.0.0
 * @since 1.1.0 Adds a way to enqueue JavaScripts in header.
 */
function mediatheque_register_scripts() {
	$min = mediatheque_min_suffix();
	$v   = mediatheque_version();

	/** JavaScripts **********************************************************/
	$url = mediatheque_js_url();

	$scripts = apply_filters( 'mediatheque_register_javascripts', array(
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
		'mediatheque-block' => array(
			'location' => sprintf( '%1$sblock%2$s.js', $url, $min ),
			'deps'     => array( 'wp-blocks', 'wp-element' ),
			'footer'   => false,
		),
	), $url, $min, $v );

	foreach ( $scripts as $js_handle => $script ) {
		$in_footer = true;

		if ( isset( $script['footer'] ) ) {
			$in_footer = $script['footer'];
		}

		wp_register_script( $js_handle, $script['location'], $script['deps'], $v, $in_footer );
	}

	/** Styles ***************************************************************/
	$url = mediatheque_assets_url();

	$styles = apply_filters( 'mediatheque_register_styles', array(
		'mediatheque-ui' => array(
			'location' => sprintf( '%1$sui%2$s.css', $url, $min ),
			'deps'     => array( 'dashicons' ),
		),
		'mediatheque-uploader' => array(
			'location' => sprintf( '%1$suploader%2$s.css', $url, $min ),
			'deps'     => array( 'mediatheque-ui' ),
		),
		'mediatheque-editor' => array(
			'location' => sprintf( '%1$seditor%2$s.css', $url, $min ),
			'deps'     => array( 'mediatheque-uploader' ),
		),
		'mediatheque-front' => array(
			'location' => sprintf( '%1$sfront%2$s.css', $url, $min ),
			'deps'     => array( 'mediatheque-editor' ),
		),
		'mediatheque-block' => array(
			'location' => sprintf( '%1$sblock%2$s.css', $url, $min ),
			'deps'     => array( 'wp-blocks' ),
		),
	), $url, $min, $v );

	foreach ( $styles as $css_handle => $style ) {
		wp_register_style( $css_handle, $style['location'], $style['deps'], $v );
	}
}

/**
 * Register various objects for MediaThèque's needs.
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
	return apply_filters( 'mediatheque_templates_dir', mediatheque()->templates );
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
 * Enqueues the Gutenberg block script.
 *
 * @since 1.1.0
 */
function mediatheque_block_editor() {
	if ( ! current_user_can( mediatheque_get_required_cap() ) ) {
		return;
	}

	wp_enqueue_script( 'mediatheque-block' );
	wp_localize_script( 'mediatheque-block', 'mediaThequeBlock', array(
		'insertBtn'      => _x( 'Insérer un Media d\'utilisateur.', 'Gutenberg block', 'mediatheque' ),
		'alignmentLabel' => _x( 'Alignement du Media d\'utilisateur', 'Gutenberg block', 'mediatheque' ),
		'editTitle'      => _x( 'Modifier', 'Gutenberg block', 'mediatheque' ),
		'genericError'   => _x( 'Une erreur est survenue, merci de réessayer.', 'Gutenberg block', 'mediatheque' ),
		'description'    => _x( 'Un Media de votre MediaThèque personnelle.', 'Gutenberg block', 'mediatheque' )
	) );
}

/**
 * Enqueues the Gutenberg block style.
 *
 * @since 1.1.0
 */
function mediatheque_block_edit_style() {
	if ( ! current_user_can( mediatheque_get_required_cap() ) ) {
		return;
	}

	wp_enqueue_style( 'mediatheque-block' );
}

/**
 * Finds a template part by looking in the active parent theme or child theme first.
 *
 * @since 1.0.0
 *
 * @param  string $template The needed template name.
 * @param  string $type     The extension for the template.
 * @return string           The located template.
 */
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
 * Print JavaScript templates for the MediaThèque.
 *
 * @since 1.0.0
 */
function mediatheque_print_template_parts() {
	foreach ( mediatheque_get_template_parts() as $id => $tmpl ) {
		mediatheque_get_template_part( $id, $tmpl );
	}
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
	if ( current_user_can( 'upload_files' ) && ( ! isset( $settings['media_buttons'] ) || ! empty( $settings['media_buttons'] ) ) ) {
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
 * Makes sure to avoid requesting for User Media when in the WP Editor context.
 *
 * @since 1.0.0
 *
 * @param  array $args The query arguments.
 * @return array       The query arguments.
 */
function mediatheque_wp_link_query_args( $args = array() ) {
	if ( is_array( $args['post_type'] ) && in_array( 'user_media', $args['post_type'], true ) ) {
		$args['post_type'] = array_diff( $args['post_type'], array( 'user_media' ) );
	}

	return $args;
}

/**
 * Shortcircuits wp_filter_oembed_result() in the case of an inserted User Media.
 *
 * @since 1.0.0
 *
 * @param string       $result The oEmbed HTML output result.
 * @param object       $data   Data object from WP_oEmbed::data2html().
 * @param string       $url    The embedded URL.
 * @return false|string        The oEmbed HTML output result.
 */
function mediatheque_oembed_pre_dataparse( $result = null, $data = null, $url = '' ) {
	$mediatheque = mediatheque();

	if ( ! empty( $mediatheque->user_media_oembeds[ $url ] ) ) {
		return false;
	}

	return $result;
}

/**
 * Sets the oEmbed HTML output result for inserted User Media.
 *
 * @since 1.0.0
 *
 * @param string       $result The oEmbed HTML output result.
 * @param object       $data   Data object from WP_oEmbed::data2html().
 * @param string       $url    The embedded URL.
 * @return false|string        The oEmbed HTML output result.
 */
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

/**
 * Parses the oEmbed URL to get its query params.
 *
 * @since 1.0.0
 *
 * @param string $url The oEmbed URL.
 * @return array      The query params.
 */
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

/**
 * Builds a specific User Media oEmbed HTML Output.
 *
 * @since 1.0.0
 *
 * @param  integer $id  The Found post ID.
 * @param  string  $url The oEmbed URL.
 * @return integer $id  The Found post ID.
 */
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

/**
 * Performs a User Media search in all post types.
 *
 * @since 1.0.0
 *
 * @param  string $s The User Media permalink.
 * @return array     The found posts, pages etc..
 */
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
 * Loads translation.
 *
 * @since 1.0.0
 */
function mediatheque_load_textdomain() {
	$mediatheque = mediatheque();
	load_plugin_textdomain( $mediatheque->domain, false, trailingslashit( basename( $mediatheque->dir ) ) . 'languages' );
}

/**
 * Get the plugin's SVG icon.
 *
 * @since 1.1.0
 *
 * @return string Base 64 encoded string.
 */
function mediatheque_get_svg_icon( $color = '#23282d', $bgcolor = '#23282d' ) {
	$fill_head = '';
	if ( $color !== $bgcolor ) {
		$fill_head = ' fill="' . $bgcolor . '" ';
	}

	return 'data:image/svg+xml;base64,' . base64_encode( '
		<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="20px" height="20px" viewBox="0 0 20 20">
			<path fill="' . $color . '" d="M 13 11 L 13 4 C 13 3.45 12.55 3 12 3 L 10.33 3 L 9 1 L 5 1 L 3.67 3 L 2 3 C 1.45 3 1 3.45 1 4 L 1 11 C 1 11.55 1.45 12 2 12 L 12 12 C 12.55 12 13 11.55 13 11 Z"/>
			<path fill="' . $color . '" d="M 14 6 L 19 6 L 19 16.5 C 19 17.88 17.88 19 16.5 19 C 15.12 19 14 17.88 14 16.5 C 14 15.12 15.12 14 16.5 14 C 16.67 14 16.84 14.02 17 14.05 L 17 9 L 14 9 L 14 6 Z"/>
			<path ' . $fill_head . 'd="M 7 4.5 C 8.38 4.5 9.5 5.62 9.5 7 C 9.5 8.38 8.38 9.5 7 9.5 C 5.62 9.5 4.5 8.38 4.5 7 C 4.5 5.62 5.62 4.5 7 4.5 Z"/>
			<path fill="' . $color . '" stroke="' . $bgcolor . '" d="M 7.006 11.465 L 9.121 10.05 C 10.979 10.05 12.636 11.861 12.636 13.573 L 12.636 15.508 C 12.636 15.508 9.797 16.386 7.006 16.386 C 4.168 16.386 1.376 15.508 1.376 15.508 L 1.376 13.573 C 1.376 11.823 2.885 10.089 4.852 10.089 Z"/>
		</svg>
	' );
}
