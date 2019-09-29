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
 * Checks if MediaThèque Views should be used according to the context.
 *
 * WordPress 4.8 added Media Widgets and the Customizer is enqueueing media for image
 * related features, so let's avoid adding scripts for nothing.
 *
 * @since 1.0.0
 *
 * @return boolean True when the context is supported. False otherwise.
 */
function mediatheque_can_enqueue_user_media() {
	return ! did_action( 'admin_print_scripts-widgets.php' ) && ! did_action( 'customize_controls_enqueue_scripts' );
}

/**
 * Loads the wpview & the paste TinyMCE plugins when MediaThèque is used on front-end.
 *
 * The paste plugin is required as the wpview one is using it.
 *
 * @since 1.3.0
 *
 * @param  array $tinymce_plugins The tiny or Teeny MCE plugins.
 * @return array                  The tiny or Teeny MCE plugins.
 */
function mediatheque_load_tinymce_plugins( $tinymce_plugins = array() ) {
	if ( ! in_array( 'wpview', $tinymce_plugins, true ) ) {
		$tinymce_plugins = array_merge( $tinymce_plugins, array( 'wpview', 'paste' ) );
	}

	return $tinymce_plugins;
}

/**
 * On front-end MCE Views are not loaded.
 *
 * These scripts make sure the media are rendered on front-end.
 *
 * @since  1.3.0
 */
function mediatheque_load_mce_views() {
	wp_enqueue_script( 'mce-view' );
	add_filter( 'tiny_mce_plugins', 'mediatheque_load_tinymce_plugins' );
	add_filter( 'teeny_mce_plugins', 'mediatheque_load_tinymce_plugins' );
}

/**
 * Enqueues User Media Scripts and styles for the WP Editor.
 *
 * @since 1.0.0
 */
function mediatheque_enqueue_user_media() {
	// Only enqueue our views when context is supported.
	if ( ! mediatheque_can_enqueue_user_media() ) {
		return;
	}

	wp_enqueue_script( 'mediatheque-editor' );
	mediatheque_localize_script();

	if ( ! mediatheque_is_post_type_admin_screen() ) {
		mediatheque_load_mce_views();

		wp_enqueue_style( 'mediatheque-front' );
	} else {
		wp_enqueue_style( 'mediatheque-editor' );
	}
}

/**
 * Prints Media containers
 *
 * @since  1.0.0
 *
 * @param  boolean $editor Whether the WP Media Editor is used.
 * @return string          User Media Templates output.
 */
function mediatheque_print_containers( $editor = true ) {
	// Only print our containers/templates when context is supported.
	if ( ! mediatheque_can_enqueue_user_media() ) {
		return;
	}

	$base_layout = '<div id="mediatheque-container">
		<div id="toolbar" class="wp-filter"></div>
		<div id="forms"></div>
		<div id="users"></div>
		<div id="trail"></div>
		<div id="media"></div>
	</div>';

	if ( true === $editor ) {
		printf( '<script type="text/html" id="tmpl-mediatheque-main">%s</script>', $base_layout ); // phpcs:ignore
		mediatheque_print_template_parts();
	}

	return $base_layout;
}

/**
 * Outputs the Mediatheque button & UI.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *   An array of arguments.
 *   @type string  $editor_id           Optional. The WordPress Editor css ID.
 *   @type array   $editor_btn_classes  Optional. The list of CSS classes for the button.
 *   @type string  $editor_btn_text     Optional. The caption for the button.
 *   @type string  $editor_btn_dashicon Optional. The dashicon to use for the button.
 *   @type boolean $echo                Optional. True to output, false to return.
 *   @type string  $media_type          Optional. The file types to filter the User Media query with.
 *                                      {@see mediatheque_get_i18n_media_type() for available keys}}
 * }
 */
function mediatheque_button( $args = array() ) {
	static $instance = 0;
	$instance++;

	$r = wp_parse_args(
		$args,
		array(
			'editor_id'           => 'content',
			'editor_btn_classes'  => array( 'mediatheque-insert' ),
			'editor_btn_text'     => __( 'Ajouter un media', 'mediatheque' ),
			'editor_btn_dashicon' => 'mediatheque-icon',
			'echo'                => true,
			'media_type'          => '',
		)
	);

	$post = get_post();
	if ( ! $post && ! empty( $GLOBALS['post_ID'] ) ) {
		$post = $GLOBALS['post_ID'];
	}

	wp_enqueue_media( array( 'post' => $post ) );

	if ( ! empty( $r['media_type'] ) ) {
		wp_add_inline_script(
			'mediatheque-views',
			sprintf(
				'var mediaThequeCustoms = %s;',
				wp_json_encode( array( 'mediaType' => $r['media_type'] ) )
			)
		);
	}

	if ( ! is_admin() ) {
		wp_enqueue_style( 'mediatheque-front' );
		mediatheque_load_mce_views();
	}

	$img    = '';
	$output = '<a href="#"%s class="%s" data-editor="%s">%s</a>';

	if ( false !== $r['editor_btn_dashicon'] ) {
		if ( 'mediatheque-icon' === $r['editor_btn_dashicon'] ) {
			$img = sprintf( '<span class="dashicons" style="background: #fafafa url( %s )!important"></span> ', mediatheque_get_svg_icon( '#555d66', '#fafafa' ) );
		} else {
			$img = '<span class="dashicons ' . $r['editor_btn_dashicon'] . '"></span> ';
		}

		$output = '<button type="button"%s class="button %s" data-editor="%s">%s</button>';
	}

	$id_attribute = '';
	if ( 1 === $instance ) {
		$id_attribute = ' id="insert-mediabrary-item"';
	}

	if ( true === $r['echo'] ) {
		printf(
			$output, // phpcs:ignore
			$id_attribute, // phpcs:ignore
			join( ' ', array_map( 'sanitize_html_class', $r['editor_btn_classes'] ) ),
			esc_attr( $r['editor_id'] ),
			$img . $r['editor_btn_text'] // phpcs:ignore
		);
	}

	return sprintf(
		$output, // phpcs:ignore
		$id_attribute, // phpcs:ignore
		join( ' ', array_map( 'sanitize_html_class', $r['editor_btn_classes'] ) ),
		esc_attr( $r['editor_id'] ),
		$img . $r['editor_btn_text'] // phpcs:ignore
	);
}

/**
 * Adds a button to open a light WP Media Editor for users without the 'upload_files' capability.
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

	return sprintf(
		'<div id="wp-%1$s-media-buttons" class="wp-media-buttons mediatheque-buttons" data-editor="%1$s">%2$s</div>%3$s',
		esc_attr( $mediatheque->editor_id ),
		mediatheque_button(
			array(
				'editor_id'          => $mediatheque->editor_id,
				'editor_btn_classes' => array( 'mediatheque-insert' ),
				'echo'               => false,
			)
		),
		$editor
	);
}

/**
 * Returns the [mediatheque] shortcode output.
 *
 * @since 1.0.0
 * @since 1.2.0 Adds a new `user_id` Shortcode attributes to get
 *              a specific user's public media files.
 *
 * @param  array $attr Attributes of the [mediatheque] shortcode.
 * @return string      HTML Output.
 */
function mediatheque_get_display_content( $attr ) {
	$content = '';

	if ( did_action( 'mediatheque_display_content' ) ) {
		return $content;
	}

	$is_main_site = mediatheque_is_main_site();

	if ( ! $is_main_site ) {
		switch_to_blog( get_current_network_id() );
	}

	$atts = shortcode_atts(
		array(
			'directory' => 0,
			'width'     => '100%',
			'height'    => '450px',
			'user_id'   => 0,
		),
		$attr,
		'mediatheque'
	);

	// Globalize template tags.
	mediatheque_set_template_tags( $atts );

	$template = mediatheque_locate_template_part( 'display', 'php' );

	if ( $template ) {
		wp_enqueue_script( 'mediatheque-display' );
		mediatheque_localize_script( 'mediatheque-views', $atts['user_id'] );

		wp_enqueue_style( 'mediatheque-ui' );

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

/**
 * Builds the User Media's Thumbnail Output for the embed template.
 *
 * @since 1.0.0
 *
 * @param  string  $excerpt    The embed User Media's excerpt.
 * @param  WP_Post $user_media The User Media Object.
 * @param  string  $type       Whether it's a 'directory' or a 'user-media'.
 * @return string              HTML Output.
 */
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

		$excerpt = sprintf(
			'<dl>
				<dt><strong>%1$s</strong><dt>
				<dd><small>%2$s (%3$s)</small></dd>
			</dl>',
			esc_html( $media_title ),
			esc_html( $media_type ),
			esc_html( $media_size )
		);
	}

	$thumbnail = sprintf(
		'<div class="wp-embed-featured-image square">
			<a href="%1$s" target="_top">
				%2$s
			</a>
		</div>',
		get_post_permalink( $user_media ),
		$media_icon
	);

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

	// Single Directory display.
	if ( in_array( $directory_id, $term_ids, true ) ) {
		if ( ! is_embed() ) {
			$content .= "\n" . mediatheque_get_display_content(
				array(
					'directory' => $GLOBALS['post']->ID,
				)
			);
		} else {
			$content = mediatheque_prepend_embed_thumbnail( $content, $GLOBALS['post'], 'directory' );
		}

		// Single User Media display.
	} else {
		if ( ! is_embed() ) {
			/**
			 * Some themes are first outputing the Attachment image before the content.
			 *
			 * Eg: Twenty Nineteen, TwentySixteen.
			 *
			 * If the image has been output, no need to prepend the content with it.
			 */
			if ( did_action( 'mediatheque_image_downsized' ) ) {
				return $content;
			}

			$mediatheque->user_media_link = mediatheque_get_download_url( $GLOBALS['post'] );

			// Overrides.
			$reset_post                 = clone $GLOBALS['post'];
			$GLOBALS['post']->post_type = 'attachment'; // phpcs:ignore

			wp_cache_set( $reset_post->ID, $GLOBALS['post'], 'posts' );
			add_filter( 'wp_get_attachment_link', 'mediatheque_attachment_link', 10, 1 );

			$content = prepend_attachment( $content );

			// Resets.
			$GLOBALS['post'] = $reset_post; // phpcs:ignore
			wp_cache_set( $reset_post->ID, $reset_post, 'posts' );
			remove_filter( 'the_content', 'mediatheque_prepend_user_media', 11 );
			remove_filter( 'wp_get_attachment_link', 'mediatheque_attachment_link', 10, 1 );

			unset( $mediatheque->user_media_link );
		} else {
			$content = mediatheque_prepend_embed_thumbnail( $content, $GLOBALS['post'], 'user-media' );
		}
	}

	$GLOBALS['wp_query']->is_attachment = false; // phpcs:ignore

	return $content;
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
		$query_vars         = wp_parse_url( $url, PHP_URL_QUERY );
		$s                  = str_replace( '?' . $query_vars, '', $url );

		if ( ! in_array( $s, $vanished_media_log, true ) ) {
			update_option( '_mediatheque_vanished_media', array_merge( $vanished_media_log, array( $s ) ) );

			$search_posts   = new WP_Query();
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
					'[' . wp_specialchars_decode( get_option( 'blogname' ) ) . ']' . __( 'Media disparu', 'mediatheque' ),
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

/**
 * Sets and globalizes the displayed directory.
 *
 * @since 1.0.0
 *
 * @param integer $id The directory ID.
 */
function mediatheque_set_displayed_directory( $id = 0 ) {
	mediatheque()->template_tags->directory = $id;
}

/**
 * Sets and globalizes a list of template tags.
 *
 * @since 1.0.0
 *
 * @param array $tags The list of template tags.
 */
function mediatheque_set_template_tags( $tags = array() ) {
	if ( empty( $tags ) || ! is_array( $tags ) ) {
		return;
	}

	$mediatheque = mediatheque();

	foreach ( $tags as $kt => $vt ) {
		$mediatheque->template_tags->{$kt} = $vt;
	}
}

/**
 * Gets the output for a template tag.
 *
 * @since 1.0.0
 *
 * @param  string $tag The template tag's key.
 * @return mixed       The output for the template tag.
 */
function mediatheque_get_tag( $tag = '' ) {
	$content_tag = 0;

	if ( ! $tag ) {
		return $content_tag;
	}

	$mediatheque = mediatheque();

	if ( isset( $mediatheque->template_tags->{$tag} ) ) {
		$content_tag = $mediatheque->template_tags->{$tag};
	}

	/**
	 * Filter here to edit the $tag output.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $content_tag The output for the template tag.
	 */
	return apply_filters( 'mediatheque_get_displayed_' . $tag, $content_tag );
}

/**
 * Gets the displayed directory.
 *
 * @since 1.0.0
 *
 * @return integer The displayed directory.
 */
function mediatheque_get_displayed_directory() {
	return (int) mediatheque_get_tag( 'directory' );
}

/**
 * Print the User Media excerpt for the embed template.
 *
 * @since  1.0.0
 */
function mediatheque_embed_excerpt() {
	$excerpt = apply_filters( 'the_excerpt_embed', get_the_excerpt() );
	$excerpt = mediatheque_prepend_user_media( $excerpt );

	/**
	 * Filter here to edit the embed excerpt.
	 *
	 * @since  1.0.0
	 *
	 * @param string $excerpt The embed excerpt.
	 */
	echo apply_filters( 'mediatheque_embed_excerpt', $excerpt ); // phpcs:ignore
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
