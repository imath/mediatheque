<?php
/**
 * MediaThèque Media.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Disables some extensions from the User Media files.
 *
 * @since 1.0.0
 *
 * @return array The potential User Media mime types.
 */
function mediatheque_get_mime_types() {
	return array_diff_key( wp_get_mime_types(), array(
		'swf'      => false,
		'exe'      => false,
		'htm|html' => false,
		'js'       => false,
		'css'      => false,
	) );
}

/**
 * Returns the default available mime types for User Media.
 *
 * Used when activating the plugin for the first time.
 *
 * @since 1.0.0
 *
 * @return array The default available mime types for User Media (for regular users).
 */
function mediatheque_get_default_mime_types() {
	$mime_types = array_intersect_key( mediatheque_get_mime_types(), array(
		'jpg|jpeg|jpe' => true,
		'gif'          => true,
		'png'          => true,
		'mp4|m4v'      => true,
		'mp3|m4a|m4b'  => true,
		'pdf'          => true,
		'rtf'          => true,
	) );

	return array_values( $mime_types );
}

/**
 * Returns the allowed mime types for User Media.
 *
 * @since 1.0.0
 *
 * @return array The allowed available mime types for User Media (for regular users).
 */
function mediatheque_get_allowed_mime_types() {
	$mime_types = mediatheque_get_allowed_file_types();
	$mime_types = array_intersect( mediatheque_get_mime_types(), $mime_types );

	/**
	 * Filter here to edit the he allowed mime types for User Media.
	 *
	 * @since 1.0.0
	 *
	 * @param array $mime_types The allowed available mime types for User Media (for regular users).
	 */
	return (array) apply_filters( 'mediatheque_get_allowed_mime_types', $mime_types );
}

/**
 * Translates the media types.
 *
 * @since 1.0.0
 *
 * @param string $media_type A specific Media Type.
 * @return string|array      A translated Media Type or a translated list of Media Types.
 */
function mediatheque_get_i18n_media_type( $media_type = '' ) {
	$i18n_media_types = array(
		'image'       => __( 'Image', 'mediatheque' ),
		'audio'       => __( 'Son', 'mediatheque' ),
		'video'       => __( 'Vidéo', 'mediatheque' ),
		'document'    => __( 'Document', 'mediatheque' ),
		'spreadsheet' => __( 'Tableur', 'mediatheque' ),
		'interactive' => __( 'Présentation', 'mediatheque' ),
		'text'        => __( 'Texte', 'mediatheque' ),
		'archive'     => __( 'Archive', 'mediatheque' ),
		'code'        => __( 'Code', 'mediatheque' ),
	);

	if ( is_array( $media_type ) ) {
		return array_intersect_key( $i18n_media_types, $media_type );
	}

	if ( isset( $i18n_media_types[ $media_type ] ) ) {
		return $i18n_media_types[ $media_type ];
	}

	return $media_type;
}

/**
 * Gets file infos about the User Media.
 *
 * @since 1.0.0
 *
 * @param  WP_Post      $user_media The User Media object.
 * @param  string       $arg        'all' to get all infos or the key of the needed info.
 * @return string|array             The requested info or the all infos list.
 */
function mediatheque_get_media_info( $user_media = null, $arg = 'media_type' ) {
	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$user_media = get_post( $user_media );

	if ( empty( $user_media->ID ) ) {
		return false;
	}

	$file     = get_attached_file( $user_media->ID );
	$filedata = wp_check_filetype( $file, mediatheque_get_mime_types() );

	if ( empty( $filedata['ext'] ) ) {
		return false;
	}

	$filedata['media_type'] = wp_ext2type( $filedata['ext'] );
	$filedata['size']       = 0;

	if ( file_exists( $file ) ) {
		$filedata['size'] = filesize( $file );
	}

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	if ( 'all' !== $arg ) {
		return $filedata[ $arg ];
	}

	return $filedata;
}

/**
 * Gets a specific (or nearest) intermediate size for a User Media.
 *
 * @since 1.0.0
 *
 * @param  int     $user_media_id The User Media ID.
 * @param  array   $size          The width and height in pixels.
 * @return array                  The User Media intermediate size data.
 */
function mediatheque_image_get_intermediate_size( $user_media_id = 0, $size = array() ) {
	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$user_media = get_post( $user_media_id );
	$size_data  = array();

	if ( empty( $user_media->post_type ) || 'user_media' !== $user_media->post_type ) {
		return $size_data;
	}

	if ( empty( $size ) ) {
		$size = 'full';
	}

	// Get the full image
	if ( 'full' === $size ) {
		$meta_data = wp_get_attachment_metadata( $user_media->ID );

		if ( empty( $meta_data['file'] ) ) {
			return $size_data;
		}

		$size_data['path'] = $meta_data['file'];

		if ( isset( $meta_data['width'] ) && isset( $meta_data['height'] ) ) {
			$size_data['width']  = $meta_data['width'];
			$size_data['height'] = $meta_data['height'];
		}

	// Get the the intermediate size
	} else {
		$size_data = image_get_intermediate_size( $user_media->ID, $size );
	}

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	if ( empty( $size_data['path'] ) ) {
		return $size_data;
	}

	$uploads = mediatheque_get_upload_dir();
	$size_data['url']  = trailingslashit( $uploads['baseurl'] ) . $size_data['path'];
	$size_data['path'] = trailingslashit( $uploads['basedir'] ) . $size_data['path'];

	return $size_data;
}

/**
 * Gets the User Media Types term ID given its slug.
 *
 * @since 1.0.0
 *
 * @param  string        $slug The term slug.
 * @return integer|false       The term ID or false if not found.
 */
function mediatheque_get_user_media_type_id( $slug = '' ) {
	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$user_media_type = get_term_by( 'slug', $slug, 'user_media_types' );

	if ( empty( $user_media_type->term_id ) ) {
		return false;
	}

	$term_id = (int) $user_media_type->term_id;

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	return $term_id;
}

/**
 * Gets a User Media object given its slug.
 *
 * @since 1.0.0
 *
 * @param  string        $slug The User Media slug.
 * @return WP_Post|false       The User Media object or false if not found.
 */
function mediatheque_get_post_by_slug( $slug = '' ) {
	if ( ! $slug ) {
		return false;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$mediatheque_statuses = wp_list_pluck( mediatheque_get_post_statuses( 'all' ), 'name' );

	$posts = get_posts( array(
		'name'        => $slug,
		'post_type'   => 'user_media',
		'post_status' => $mediatheque_statuses,
	) );

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	if ( ! is_array( $posts ) || 1 !== count( $posts ) ) {
		return false;
	}

	/**
	 * Filter here to edit the User Media Object.
	 *
	 * @param WP_Post $value                The User Media object.
	 * @param string  $slug                 The User Media slug.
	 * @param array   $mediatheque_statuses The available User Media statuses.
	 */
	return apply_filters( 'mediatheque_get_post_by_slug', reset( $posts ), $slug, $mediatheque_statuses );
}

/**
 * Returns the HTML Output of a WP Editor inserted file.
 *
 * @since 1.0.0
 *
 * @param  WP_Post $user_media The User Media object.
 * @param  array   $args       The display preferences for the inserted file.
 * @return string              HTML Output for a file.
 */
function mediatheque_file_shortcode( $user_media = null, $args = array() ) {
	if ( empty( $user_media->post_type ) || 'user_media' !== $user_media->post_type ) {
		$user_media = get_post();
	}

	$filedata = mediatheque_get_media_info( $user_media, 'all' );

	if ( empty( $filedata ) ) {
		return '';
	}

	$mediatheque = mediatheque();
	if ( isset( $mediatheque->user_media_link ) ) {
		$download_link = $mediatheque->user_media_link;
	} else {
		$download_link = mediatheque_get_download_url( $user_media );
	}

	$file_args = wp_parse_args( $args, array(
		'icon'           => false,
		'ext'            => false,
		'media_type'     => false,
		'file_size'      => false,
		'use_file_name'  => false,
		'object_wrapper' => false,
	) );

	$icon = '';
	if ( ( false === (bool) $file_args['icon'] || 'false' === $file_args['icon'] ) && ! empty( $filedata['media_type'] ) ) {
		$icon = sprintf( '<a href="%1$s" class="mediatheque-file-link"><img src="%2$s" class="alignleft"></a>',
			esc_url_raw( $download_link ),
			esc_url_raw( wp_mime_type_icon( $filedata['media_type'] ) )
		);
	}

	$title = $user_media->post_title;

	if ( true === $file_args['use_file_name'] ) {
		$title = basename( $user_media->guid );
	}

	$title = sprintf( '<a href="%1$s"><strong>%2$s</strong></a>',
		esc_url_raw( $download_link ),
		esc_html( $title )
	);

	$file_type = '';
	if ( ( false === (bool) $file_args['media_type'] || 'false' === $file_args['media_type'] )  && ! empty( $filedata['media_type'] ) ) {
		$file_type = mediatheque_get_i18n_media_type( $filedata['media_type'] );
	}

	$file_ext = '';
	if ( ( false === (bool) $file_args['ext'] || 'false' === $file_args['ext'] ) && ! empty( $filedata['ext'] ) ) {
		$file_ext = ' (' . $filedata['ext'] . ')';
	}

	$file_size = '';
	if ( ( false === (bool) $file_args['file_size'] || 'false' === $file_args['file_size'] ) && ! empty( $filedata['size'] ) ) {
		$file_size = absint( $filedata['size'] ) / 1000; // Size in kylobytes
		$file_size = '<dd><small>' . mediatheque_format_file_size( $file_size ) . '</small></dd>';
	}

	$template = sprintf(
		'<div class="mediatheque-file">%1$s<dl><dt>%2$s<br/><small>%3$s%4$s</small></dt>%5$s</dl></div>',
		$icon,
		$title,
		$file_type,
		$file_ext,
		$file_size
	);

	if ( in_array( $user_media->post_mime_type, mediatheque_get_object_wrapper_mimes(), true ) && 'true' === $file_args['object_wrapper'] && 'private' !== get_post_status( $user_media ) ) {
		$template = sprintf(
			'<object data="%1$s" type="%2$s" width="%3$s" height="400px" typemustmatch>%4$s</object>',
			esc_url( $user_media->guid ),
			esc_attr( $user_media->post_mime_type ),
			'100%',
			$template
		);
	}

	/**
	 * Use this filter to edit the template Used to display the file.
	 *
	 * @since 1.2.0
	 *
	 * @param string  $template   The HTML output.
	 * @param WP_Post $user_media The user media Object.
	 * @param array   $filedata   Information about the file (extension, type, size).
	 * @param array   $file_args  The options requested for the output.
	 */
	return apply_filters( 'mediatheque_file_shortcode_output', $template, $user_media, $filedata, $file_args );
}

/**
 * Downloads a given User Media object.
 *
 * @since 1.0.0
 *
 * @param WP_Post $user_media The User Media object.
 */
function mediatheque_download( $user_media = null ) {
	if ( empty( $user_media->mediatheque_file ) || ! file_exists( $user_media->mediatheque_file ) ) {
		return false;
	}

	$file_info = mediatheque_get_media_info( $user_media, 'all' );
	$filename = basename( $user_media->mediatheque_file );

	/**
	 * Hook here to run custom actions before download.
	 */
	do_action( 'mediatheque_download', $user_media, $file_info );

	status_header( 200 );
	header( 'Cache-Control: cache, must-revalidate' );
	header( 'Pragma: public' );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Length: ' . $file_info['size'] );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Content-Type: ' . $file_info['type'] );

	while ( ob_get_level() > 0 ) {
		ob_end_flush();
	}

	readfile( $user_media->mediatheque_file );
	die();
}

/**
 * Deletes a directory of User Media.
 *
 * @since 1.0.0
 *
 * @param  integer|WP_Post     $dir  Required. The User Media Directory ID or Object.
 * @return array|false|WP_Post       False on failure.
 */
function mediatheque_delete_dir( $dir = null ) {
	if ( empty( $dir ) ) {
		return false;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$dir = get_post( $dir );

	if ( empty( $dir->post_type ) || 'user_media' !== $dir->post_type ) {
		return false;
	}

	$dir_id     = (int) $dir->ID;
	$uploadpath = mediatheque_get_upload_dir();
	$dirpath    = get_post_meta( $dir_id, '_mediatheque_relative_path', true );
	$dirpath    = trailingslashit( $uploadpath['basedir'] ) . $dirpath;

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

	// Remove the directory.
	if ( is_dir( $dirpath ) ) {
		@ rmdir( $dirpath );
	}

	$dir = wp_delete_post( $dir_id, true );

	/**
	 * Fires after a directory of User Media is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $dir_id The User Media Directory ID.
	 */
	do_action( 'mediatheque_deleted_dir', $dir_id );

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	return $dir;
}

/**
 * Deletes a User Media.
 *
 * @since 1.0.0
 *
 * @param  integer|WP_Post     $media Required. The User Media ID or Object.
 * @return array|false|WP_Post        False on failure.
 */
function mediatheque_delete_media( $media = null ) {
	if ( empty( $media ) ) {
		return false;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
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

	$user_media_id = (int) $user_media->ID;
	$meta          = wp_get_attachment_metadata( $user_media_id );
	$file          = get_attached_file( $user_media_id );

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

	$user_media = wp_delete_post( $user_media_id, true );

	/**
	 * Fires after a User Media is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_media_id The User Media ID.
	 */
	do_action( 'mediatheque_deleted_media', $user_media_id );

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	return $user_media;
}

/**
 * Moves a User Media.
 *
 * @since 1.0.0
 *
 * @param  int|WP_Post  $media  Required. The User Media ID or Object.
 * @param  integer      $parent Required. The User Media's new parent ID.
 * @return false|string         False on failure, the new file path on success.
 */
function mediatheque_move_media( $media = null, $parent = null ) {
	if ( empty( $media ) || is_null( $parent ) ) {
		return false;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
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

	$user_media_id = (int) $user_media->ID;
	$meta          = wp_get_attachment_metadata( $user_media_id );
	$file          = get_attached_file( $user_media_id );

	$newdir     = $uploadpath['basedir'] . $path;
	$filename   = wp_unique_filename( $newdir, basename( $file ) );
	$new_file   = trailingslashit( $newdir ) . $filename;

	/**
	 * Fires before a media is moved to another place.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Post $media    The Use Media Object.
	 * @param  string  $newdir   The new destination dir.
	 * @param  string  $filename The file name.
	 */
	do_action( 'mediatheque_move_media', $user_media, $newdir, $filename );

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

	/**
	 * Fires after a media has moved to another place.
	 *
	 * @since  1.0.0
	 *
	 * @param  int     $user_media_id The Use Media ID.
	 * @param  string  $newdir        The new destination dir.
	 * @param  string  $filename      The file name.
	 */
	do_action( 'mediatheque_moved_media', $user_media_id, $newdir, $filename );

	if ( ! $is_main_site ) {
		restore_current_blog();
	}

	return $new_file;
}

/**
 * Replaces the WordPress default not allowed file error message.
 *
 * @since 1.0.0
 *
 * @param array  $file      Reference to a single element of $_FILES.
 * @param string $message   The error message.
 * @return array            The error message as expected by _wp_handle_upload().
 */
function mediatheque_upload_error_handler( $file = array(), $message = '' ) {
	/* translators: do not translate this string, it is used in a if statement */
	if ( __( 'Sorry, this file type is not permitted for security reasons.', 'default' ) === $message ) {
		$message = __( 'Désolé, vous n\'êtes pas autorisé à télécharger ce type de fichier', 'mediatheque' );
	}

	return array( 'error' => $message );
}
