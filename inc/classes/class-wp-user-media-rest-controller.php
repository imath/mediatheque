<?php
/**
 * WP User Media Rest Controller Class.
 *
 * @package WP User Media\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Rest Controller class
 *
 * @since  1.0.0
 */
class WP_User_Media_REST_Controller extends WP_REST_Attachments_Controller {
	public $user_media_status     = 'publish';
	public $user_media_type_ids   = array();
	public $user_media_parent     = 0;
	public $user_media_parent_dir = '';

	/**
	 * Temporarly Adds specific User Media metas to the registered post metas.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_post_type_only_metas() {
		$this->user_meta_fields = get_registered_meta_keys( $this->post_type );

		foreach( $this->user_meta_fields as $k_field => $user_meta_field ) {
			register_meta( 'post', $k_field, $user_meta_field );
		}
	}

	/**
	 * Removes specific User Media metas from the registered post metas.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function unregister_post_type_only_metas() {
		if ( ! isset( $this->user_meta_fields ) ) {
			$this->user_meta_fields = get_registered_meta_keys( $this->post_type );
		}

		foreach( array_keys( $this->user_meta_fields ) as $user_meta_field ) {
			unregister_meta_key( 'post', $user_meta_field );
		}
	}

	public function get_user_media_type_id( $type = '' ) {
		if ( ! $type ) {
			return false;
		}

		if ( ! isset( $this->user_media_type_ids[ $type ] ) ) {
			$user_media_type = get_term_by( 'slug', $type, 'user_media_types' );

			if ( empty( $user_media_type->term_id ) ) {
				return false;
			}

			$user_media_type_ids[ $type ] = $user_media_type->term_id;
		}

		return (int) $user_media_type_ids[ $type ];
	}

	/**
	 * Checks if a given request has access to create a User Media.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|true Boolean true if the User Media may be created, or a WP_Error if not.
	 */
	public function create_item_permissions_check( $request ) {
		$ret = WP_REST_Posts_Controller::create_item_permissions_check( $request );

		if ( ! $ret || is_wp_error( $ret ) ) {
			return $ret;
		}

		return true;
	}

	/**
	 * Retrieves the query params for collections of User Media.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Query parameters for the User Media collection as an array.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['parent'] = array(
			'description'       => __( 'Limit result set to those of particular parent IDs.', 'wp-user-media' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
		);

		return $params;
	}

	/**
	 * Set the Post Status for the User Media GET requests.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param  array           $prepared_args The User Media query arguments.
	 * @param  WP_REST_Request $request       Full details about the request.
	 * @return array                          The User Media query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$parent_args = parent::prepare_items_query( $prepared_args, $request );
		$post_status = $request->get_param( 'post_status' );

		if ( ! $post_status ) {
			$post_status = array( 'publish' );
		} else {
			$post_status = explode( ',', $post_status );
		}

		$prepared_args = array_merge(
			$parent_args,
			array( 'post_status' => $post_status )
		);

		// In Admin, uploads are editable: control their access.
		$context = $request->get_param( 'user_media_context' );
		if ( 'admin' === $context ) {
			$post_author = $request->get_param( 'user_id' );

			// Admins can edit any media.
			if ( $post_author && current_user_can( 'manage_options' ) ) {
				$prepared_args['author'] = (int) $post_author;

			// How could this be possible into the Administration :)
			} elseif ( ! is_user_logged_in() ) {
				$prepared_args['author'] = -1;

			// Regular users can only edit their media.
			} else {
				$prepared_args['author'] = get_current_user_id();
			}
		}

		return $prepared_args;
	}

	/**
	 * Retrieves a collection of User Media.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$this->register_post_type_only_metas();

		$response = parent::get_items( $request );

		$this->unregister_post_type_only_metas();

		return $response;
	}

	/**
	 * Temporarly set the WordPress Uploads dir to be the User Media one.
	 *
	 * @since  1.0.0
	 *
	 * @return array The Uploads dir data.
	 */
	public function upload_dir_filter() {
		$dir = wp_user_media_get_upload_dir();

		if ( $this->user_media_parent_dir ) {
			$dir['subdir'] = '/' . $this->user_media_parent_dir;
		} else {
			// Should check the request for a user ID and fall back to current user ID.
			$dir['subdir'] .= sprintf( '/%1$s/%2$s', $this->user_media_status, get_current_user_id() );
		}

		$dir['path']    = sprintf( '%s%s', $dir['basedir'], $dir['subdir'] );
		$dir['url']     = sprintf( '%s%s', $dir['baseurl'], $dir['subdir'] );

		return $dir;
	}

	/**
	 * Handles an upload via multipart/form-data ($_FILES).
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param  array          $files   Data from the `$_FILES` superglobal.
	 * @param  array          $headers HTTP headers from the request.
	 * @param  string         $action  The form action input value.
	 * @return array|WP_Error          Data from wp_handle_upload().
	 */
	protected function upload_from_file( $files, $headers, $action = '' ) {
		if ( empty( $files ) ) {
			return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.', 'wp-user-media' ), array( 'status' => 400 ) );
		}

		// Verify hash, if given.
		if ( ! empty( $headers['content_md5'] ) ) {
			$content_md5 = array_shift( $headers['content_md5'] );
			$expected    = trim( $content_md5 );
			$actual      = md5_file( $files['file']['tmp_name'] );

			if ( $expected !== $actual ) {
				return new WP_Error( 'rest_upload_hash_mismatch', __( 'Content hash did not match expected.', 'wp-user-media' ), array( 'status' => 412 ) );
			}
		}

		// Pass off to WP to handle the actual upload.
		$overrides = array(
			'action' => 'upload_user_media',
		);

		if ( $action ) {
			$overrides['action'] = $action;
		}

		/** Include admin functions to get access to wp_handle_upload() */
		require_once ABSPATH . 'wp-admin/includes/admin.php';

		add_filter( 'upload_dir', array( $this, 'upload_dir_filter' ), 1, 0 );

		$file = wp_handle_upload( $files['wp_user_media_upload'], $overrides );

		remove_filter( 'upload_dir', array( $this, 'upload_dir_filter' ), 1, 0 );

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'rest_upload_unknown_error', $file['error'], array( 'status' => 500 ) );
		}

		if ( 'private' === $this->user_media_status && ! empty( $GLOBALS['is_apache'] ) ) {
			$upload_dir = $this->upload_dir_filter();
			$up_parent_dir = dirname( $upload_dir['path'] );

			if ( ! file_exists( $up_parent_dir .'/.htaccess' ) ) {
				// Include admin functions to get access to insert_with_markers().
				require_once ABSPATH . 'wp-admin/includes/misc.php';

				$slashed_home = trailingslashit( get_option( 'home' ) );
				$base         = parse_url( $slashed_home, PHP_URL_PATH );

				// Defining the rule, we need to make it unreachable and use php to reach it
				$rules = array(
					'<IfModule mod_rewrite.c>',
					'RewriteEngine On',
					sprintf( 'RewriteBase %s', $base ),
					'RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in.*$ [NC]',
					'RewriteRule  .* wp-login.php [NC,L]',
					'</IfModule>',
				);

				// creating the .htaccess file
				insert_with_markers( $up_parent_dir .'/.htaccess', 'WP User Status', $rules );
			}
		}

		return $file;
	}

	/**
	 * Prepares a single User Media for create or update.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @param  WP_REST_Request   $request Full details about the request.
	 * @return WP_Error|stdClass          User Media object.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_user_media = parent::prepare_item_for_database( $request );
		$prepared_user_media->post_status = $this->user_media_status;
		$prepared_user_media->post_parent = $this->user_media_parent;

		return $prepared_user_media;
	}

	/**
	 * Prepares a single User Media output for response.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param  WP_Post         $post    User Media object.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$response = parent::prepare_item_for_response( $post, $request );
		$data     = $response->get_data();

		if ( in_array( $this->get_user_media_type_id( 'wp-user-media-directory' ), $data['user_media_types'], true ) ) {
			$data['media_type'] = 'dir';
			$response = rest_ensure_response( $data );
		} elseif ( 'image' !== $data['media_type'] ) {
			$filepath = get_attached_file( $data['id'] );
			$filetype = wp_check_filetype( $filepath );
			$type     = wp_ext2type( $filetype['ext'] );
			$data['media_icon'] = wp_mime_type_icon( $type );
			$response = rest_ensure_response( $data );
		}

		/**
		 * Filters a User Media returned from the REST API.
		 *
		 * Allows modification of the attachment right before it is returned.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $post     The original User Media post.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_user_media', $response, $post, $request );
	}

	/**
	 * Creates a User Media.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$headers = $request->get_headers();
		$action  = $request->get_param( 'action' );
		$size    = 0;

		$requested_status = $request->get_param( 'post_status' );
		if ( $requested_status && get_post_status_object( $requested_status ) ) {
			$this->user_media_status = $requested_status;
		}

		$parent_dir = $request->get_param( 'post_parent' );
		if ( $parent_dir ) {
			$post_parent = get_post( $parent_dir );

			if ( ! empty( $post_parent->ID ) ) {
				$this->user_media_parent     = $post_parent->ID;
				$this->user_media_parent_dir = get_post_meta( $this->user_media_parent, '_wp_user_media_relative_path', true );
			}
		}

		// Add a file
		if ( 'upload_user_media' === $action ) {
			// Get the file via $_FILES or raw data.
			$files   = $request->get_file_params();

			if ( ! empty( $files ) ) {
				$size = $files['wp_user_media_upload']['size'];
				$file = $this->upload_from_file( $files, $headers, $action );
			} else {
				return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.', 'wp-user-media' ), array( 'status' => 400 ) );
			}

			if ( is_wp_error( $file ) ) {
				return $file;
			}

			$name       = basename( $file['file'] );
			$name_parts = pathinfo( $name );
			$name       = trim( substr( $name, 0, -(1 + strlen( $name_parts['extension'] ) ) ) );

			$url     = $file['url'];
			$type    = $file['type'];
			$file    = $file['file'];

			// use image exif/iptc data for title and caption defaults if possible
			$image_meta = @wp_read_image_metadata( $file );

			if ( ! empty( $image_meta ) ) {
				if ( empty( $request['title'] ) && trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
					$request['title'] = $image_meta['title'];
				}

				if ( empty( $request['caption'] ) && trim( $image_meta['caption'] ) ) {
					$request['caption'] = $image_meta['caption'];
				}
			}

			$user_media = $this->prepare_item_for_database( $request );
			$user_media->file = $file;
			$user_media->post_mime_type = $type;
			$user_media->guid = $url;

			if ( empty( $user_media->post_title ) ) {
				$user_media->post_title = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
			}

			$user_media_type_id = $this->get_user_media_type_id( 'wp-user-media-file' );

		// Add a folder
		} else {
			$user_media         = $this->prepare_item_for_database( $request );
			$user_media_type_id = $this->get_user_media_type_id( 'wp-user-media-directory' );
		}

		// Dir or file types are terms
		if ( ! empty( $user_media_type_id ) ) {
			$user_media->tax_input = array(
				'user_media_types' => array( $user_media_type_id ),
			);
		}

		$id = wp_insert_post( wp_slash( (array) $user_media ), true );

		if ( is_wp_error( $id ) ) {
			if ( 'db_update_error' === $id->get_error_code() ) {
				$id->add_data( array( 'status' => 500 ) );
			} else {
				$id->add_data( array( 'status' => 400 ) );
			}
			return $id;
		} else {
			// Create the Attached file & update the user's disk usage.
			if ( 'upload_user_media' === $action ) {
				// @todo Multisite probably requires to do a switch to blog there
				update_attached_file( $id, $file );

				if ( $size ) {
					wp_user_meta_disk_usage_update( get_current_user_id(), $size );
				}
			}
		}

		$user_media = get_post( $id );

		/**
		 * Fires after a single User Media is created or updated via the REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post         $user_media Inserted or updated User media
		 *                                    object.
		 * @param WP_REST_Request $request    The request sent to the API.
		 * @param bool            $creating   True when creating a User Media/Dir, false when updating.
		 * @param string          $action     The action being performed.
		 */
		do_action( 'rest_insert_user_media', $user_media, $request, true, $action );

		if ( 'upload_user_media' === $action ) {
			// Include admin functions to get access to wp_generate_attachment_metadata().
			require_once ABSPATH . 'wp-admin/includes/admin.php';

			// @todo Multisite probably requires to do a switch to blog there
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

			if ( isset( $request['alt_text'] ) ) {
				update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
			}

		} elseif ( 'mkdir_user_media' === $action ) {
			$parent     = wp_get_post_parent_id( $user_media );
			$upload_dir = $this->upload_dir_filter();

			// If the user's root dir is not set yet, create it.
			if ( ! is_dir( $upload_dir['path'] ) ) {
				if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
					return new WP_Error( 'rest_mkdir_failed', __( 'Writing the user\'s root directory failed.', 'wp-user-media' ), array( 'status' => 400 ) );
				}
			}

			if ( ! $parent ) {
				$relative_path = $upload_dir['subdir'];
			} else {
				$relative_path = get_post_meta( $parent, '_wp_user_media_relative_path', true );
			}

			$dir = $upload_dir['basedir'] . $relative_path;

			if ( ! is_dir( $dir ) ) {
				return new WP_Error( 'rest_mkdir_no_parent_dir', __( 'No data supplied.', 'wp-user-media' ), array( 'status' => 400 ) );
			}

			$dirname = wp_unique_filename( $dir, $user_media->post_name );

			if ( ! wp_mkdir_p( $dir . '/' . $dirname ) ) {
				return new WP_Error( 'rest_mkdir_failed', __( 'Writing the directory failed.', 'wp-user-media' ), array( 'status' => 400 ) );
			}

			update_post_meta( $id, '_wp_user_media_relative_path', _wp_relative_upload_path( $dir . '/' . $dirname ) );
		}

		$fields_update = $this->update_additional_fields_for_object( $user_media, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( $user_media, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

		return $response;
	}
}
