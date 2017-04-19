<?php
/**
 * MediaThèque Rest Controller Class.
 *
 * @package mediatheque\inc\classes
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
class MediaTheque_REST_Controller extends WP_REST_Attachments_Controller {
	public $user_media_status        = 'publish';
	public $user_media_type_ids      = array();
	public $user_media_parent        = 0;
	public $user_media_parent_dir    = '';
	public $user_media_guid          = '';
	public $user_media_deleted_space = 0;
	public $user_id                  = 0;

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
			'description'       => __( 'Limite les résultats en fonction du répertoire parent.', 'mediatheque' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
		);

		$params['orderby'] = array(
			'description'        => __( 'Ordonne la liste en fonction des attributs des Media utilisateurs.', 'mediatheque' ),
			'type'               => 'string',
			'default'            => 'modified',
			'enum'               => array(
				'modified',
				'date',
				'relevance',
				'id',
				'include',
				'title',
				'slug',
			),
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

		// Makes sure it is possible to define display preferences for anyone.
		} elseif ( 'display-preferences' !== $context ) {
			$prepared_args['author'] = get_current_user_id();
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
		$dir     = mediatheque_get_upload_dir();
		$user_id = $this->user_id;

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $this->user_media_parent_dir ) {
			$dir['subdir'] = '/' . $this->user_media_parent_dir;
		} else {
			// Should check the request for a user ID and fall back to current user ID.
			$dir['subdir'] .= sprintf( '/%1$s/%2$s', $this->user_media_status, $user_id );
		}

		$dir['path'] = sprintf( '%s%s', $dir['basedir'], $dir['subdir'] );
		$dir['url']  = sprintf( '%s%s', $dir['baseurl'], $dir['subdir'] );

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
			return new WP_Error( 'rest_upload_no_data', __( 'Aucune donnée fournie.', 'mediatheque' ), array( 'status' => 400 ) );
		}

		// Verify hash, if given.
		if ( ! empty( $headers['content_md5'] ) ) {
			$content_md5 = array_shift( $headers['content_md5'] );
			$expected    = trim( $content_md5 );
			$actual      = md5_file( $files['file']['tmp_name'] );

			if ( $expected !== $actual ) {
				return new WP_Error( 'rest_upload_hash_mismatch', __( 'Le hash md5 pour le fichier ne correspond pas.', 'mediatheque' ), array( 'status' => 412 ) );
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

		$file = wp_handle_upload( $files['mediatheque_upload'], $overrides );

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

		if ( $this->user_media_guid ) {
			$prepared_user_media->guid = $this->user_media_guid;
		}

		if ( $this->user_id ) {
			$prepared_user_media->post_author = $this->user_id;
		}

		return $prepared_user_media;
	}

	/**
	 * Temporarly remove the Private prefix from User Media titles.
	 *
	 * @since
	 *
	 * @param string  $prefixed_title The User Media prefixed title.
	 * @param WP_Post $user_media     The User Media object.
	 * @param string                  The User Media title.
	 */
	public function no_title_prefix( $prefixed_title = '', $user_media = null ) {
		if ( empty( $user_media->post_title ) ) {
			return $prefixed_title;
		}

		return $user_media->post_title;
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
		// Remove the 'Private Prefix'
		add_filter( 'private_title_format', array( $this, 'no_title_prefix' ), 10, 2 );

		$response = parent::prepare_item_for_response( $post, $request );

		// Restore the 'Private Prefix'
		remove_filter( 'private_title_format', array( $this, 'no_title_prefix' ), 10, 2 );

		// Get the Response Data.
		$data = $response->get_data();

		if ( in_array( $this->get_user_media_type_id( 'mediatheque-directory' ), $data['user_media_types'], true ) ) {
			$data['media_type'] = 'dir';
		} elseif ( 'image' !== $data['media_type'] ) {
			$type = methiatheque_get_media_info( $post );
			$data['media_icon'] = wp_mime_type_icon( $type );

			/**
			 * The WP_REST_Attachments_Controller::prepare_item_for_response is only taking care of images
			 * all other types are using a 'file' type.
			 */
			if ( 'video' === $type || 'audio' === $type ) {
				$data['media_type'] = $type;
			}
		}

		$data['parent'] = (int) $post->post_parent;
		$response = rest_ensure_response( $data );

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
				$this->user_media_parent_dir = get_post_meta( $this->user_media_parent, '_mediatheque_relative_path', true );
			}
		}

		$user_id = $request->get_param( 'user_id' );
		if ( $user_id ) {
			$this->user_id = (int) $user_id;
		} else {
			$this->user_id = (int) get_current_user_id();
		}

		// Add a file
		if ( 'upload_user_media' === $action ) {
			// Get the file via $_FILES or raw data.
			$files   = $request->get_file_params();

			if ( ! empty( $files ) ) {
				$size = $files['mediatheque_upload']['size'];
				$file = $this->upload_from_file( $files, $headers, $action );
			} else {
				return new WP_Error( 'rest_upload_no_data', __( 'Aucune donnée fournie.', 'mediatheque' ), array( 'status' => 400 ) );
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

			$user_media_type_id = $this->get_user_media_type_id( 'mediatheque-file' );

		// Add a folder
		} else {
			$user_media         = $this->prepare_item_for_database( $request );
			$user_media_type_id = $this->get_user_media_type_id( 'mediatheque-directory' );
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
					mediatheque_disk_usage_update( get_current_user_id(), $size );
				}
			}
		}

		$user_media = get_post( $id );

		/**
		 * Fires after a single User Media is created via the REST API.
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
			$upload_dir = $this->upload_dir_filter();
			$dir        = $upload_dir['path'];

			// If the user's root dir is not set yet, create it.
			if ( ! is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					return new WP_Error( 'rest_mkdir_failed', __( 'L\'écriture du répertoire de l\'utilisateur a échoué.', 'mediatheque' ), array( 'status' => 400 ) );
				}
			}

			$dirname = wp_unique_filename( $dir, $user_media->post_name );

			if ( ! wp_mkdir_p( $dir . '/' . $dirname ) ) {
				return new WP_Error( 'rest_mkdir_failed', __( 'L\'écriture du répertoire a échoué.', 'mediatheque' ), array( 'status' => 400 ) );
			}

			update_post_meta( $id, '_mediatheque_relative_path', _wp_relative_upload_path( $dir . '/' . $dirname ) );
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

	/**
	 * Count the deleted space in bytes when a media or a directory of media
	 * is deleted.
	 *
	 * It avoids updating multiple times the same user meta.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $media The User Media Object.
	 * @param string  $file  The absolute path to the file.
	 */
	public function count_deleted_space( $media = null, $file = '' ) {
		$deleted_space = 0;

		if ( empty( $file ) || ! file_exists( $file ) ) {
			return $deleted_space;
		} else {
			$deleted_space = filesize( $file );
		}

		$this->user_media_deleted_space += $deleted_space;
	}

	/**
	 * Deletes a User Media or a Directory of User Media.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$user_media = $this->get_post( $request['id'] );
		if ( is_wp_error( $user_media ) ) {
			return $user_media;
		}

		if ( ! $this->check_delete_permission( $user_media ) ) {
			return new WP_Error( 'rest_user_cannot_delete_post', __( 'Désolé vous n\'êtes pas habilité(e) à supprimer ce media utilisateur.', 'mediatheque' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$request->set_param( 'context', 'edit' );

		$previous = $this->prepare_item_for_response( $user_media, $request );

		// Reset the deleted space.
		$this->user_media_deleted_space = 0;

		add_action( 'mediatheque_delete_media', array( $this, 'count_deleted_space' ), 10, 2 );

		$result = mediatheque_delete_media( $user_media );

		remove_action( 'mediatheque_delete_media', array( $this, 'count_deleted_space' ), 10, 2 );

		if ( $this->user_media_deleted_space ) {
			mediatheque_disk_usage_update( $user_media->post_author, $this->user_media_deleted_space, true );
		}

		$response = new WP_REST_Response();
		$response->set_data( array( 'deleted' => true, 'previous' => $previous->get_data() ) );

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Ce media utilisateur ne peut être supprimé.', 'mediatheque' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires immediately after a User Media is deleted via the REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param object           $user_media The deleted User Media.
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    The request sent to the API.
		 */
		do_action( 'rest_delete_user_media', $user_media, $response, $request );

		return $response;
	}

	public function force_guid( $guid = '' ) {
		if ( ! empty( $this->user_media_guid ) ) {
			$guid = $this->user_media_guid;
		}

		return $guid;
	}

	/**
	 * Updates a single User Media.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( empty( $id ) || 'user_media' !== get_post_type( $id ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Type invalide.', 'mediatheque' ), array( 'status' => 400 ) );
		}

		$this->user_media_status = $request->get_param( 'status' );
		$parent                  = $request->get_param( 'post_parent' );
		$uploadpath              = mediatheque_get_upload_dir();

		if ( '' !== $parent ) {
			$this->user_media_parent = (int) $parent;
		}

		if ( $this->user_media_parent !== (int) $request->get_param( 'parent' ) ) {
			// Include admin functions to get access to wp_generate_attachment_metadata().
			require_once ABSPATH . 'wp-admin/includes/admin.php';

			$file = mediatheque_move( $id, $this->user_media_parent );

			if ( ! $file ) {
				return new WP_Error( 'rest_mv_failed', __( 'Le déplacement du media utilisateur a échoué.', 'mediatheque' ), array( 'status' => 400 ) );
			}

			update_attached_file( $id, $file );
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
			$this->user_media_guid = trailingslashit( $uploadpath['baseurl'] ) . get_post_meta( $id, '_wp_attached_file', true );
		}

		/**
		 * Force the Guid to be the new one as WordPress is not
		 * taking account the guid parameter of the request in
		 * case of an update.
		 */
		add_filter( 'post_guid', array( $this, 'force_guid' ) );

		$response = WP_REST_Posts_Controller::update_item( $request );

		/**
		 * Stop using the force Luke!
		 */
		remove_filter( 'post_guid', array( $this, 'force_guid' ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );
		$data = $response->get_data();

		if ( isset( $request['alt_text'] ) ) {
			update_post_meta( $data['id'], '_wp_attachment_image_alt', $request['alt_text'] );
		}

		$user_media = get_post( $id );

		// Maybe Update parent directory modified date
		if ( ! empty( $this->user_media_parent ) ) {
			wp_update_post( array(
				'ID'            => $this->user_media_parent,
				'post_modified' => current_time( 'mysql' ),
			) );
		}

		/**
		 * Fires after a single User Media is updated via the REST API.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post         $user_media Inserted or updated User media
		 *                                    object.
		 * @param WP_REST_Request $request    The request sent to the API.
		 * @param bool            $creating   True when creating a User Media/Dir, false when updating.
		 * @param string          $action     The action being performed.
		 */
		do_action( 'rest_update_user_media', $data, $request, false, '' );

		$fields_update = $this->update_additional_fields_for_object( $user_media, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $user_media, $request );
		$response = rest_ensure_response( $response );

		return $response;
	}
}
