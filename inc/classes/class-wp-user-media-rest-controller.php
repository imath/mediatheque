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
	/**
	 * Temporarly Adds specific idea metas to the registered post metas.
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
	 * Removes specific idea metas from the registered post metas.
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

	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = array_merge(
			parent::prepare_items_query( $prepared_args, $request ),
			array( 'post_status' => 'publish' )
		);

		return $query_args;
	}

	/**
	 * Retrieves a collection of User Meta.
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

	protected function upload_from_file( $files, $headers, $action = '' ) {
		if ( empty( $files ) ) {
			return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.' ), array( 'status' => 400 ) );
		}

		// Verify hash, if given.
		if ( ! empty( $headers['content_md5'] ) ) {
			$content_md5 = array_shift( $headers['content_md5'] );
			$expected    = trim( $content_md5 );
			$actual      = md5_file( $files['file']['tmp_name'] );

			if ( $expected !== $actual ) {
				return new WP_Error( 'rest_upload_hash_mismatch', __( 'Content hash did not match expected.' ), array( 'status' => 412 ) );
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

		$file = wp_handle_upload( $files['wp_user_media_upload'], $overrides );

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'rest_upload_unknown_error', $file['error'], array( 'status' => 500 ) );
		}

		return $file;
	}

	protected function prepare_item_for_database( $request ) {
		$prepared_attachment = parent::prepare_item_for_database( $request );

		if ( ! isset( $request['post_status'] ) ) {
			$prepared_attachment->post_status = 'publish';
		}

		return $prepared_attachment;
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
		// Get the file via $_FILES or raw data.
		$files   = $request->get_file_params();
		$headers = $request->get_headers();
		$action  = $request->get_param( 'action' );
		$size    = 0;

		/**
		 * A folder can be created.
		 * @todo We should first check the term.
		 */

		if ( ! empty( $files ) ) {
			$size = $files['wp_user_media_upload']['size'];
			$file = $this->upload_from_file( $files, $headers, $action );
		} else {
			return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.' ), array( 'status' => 400 ) );
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

		$id = wp_insert_post( wp_slash( (array) $user_media ), true );

		if ( is_wp_error( $id ) ) {
			if ( 'db_update_error' === $id->get_error_code() ) {
				$id->add_data( array( 'status' => 500 ) );
			} else {
				$id->add_data( array( 'status' => 400 ) );
			}
			return $id;

		// Create the Attached file & update the user's disk usage.
		} else {
			update_attached_file( $id, $file );

			if ( $size ) {
				wp_user_meta_disk_usage_update( get_current_user_id(), $size );
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
		 * @param bool            $creating   True when creating an attachment, false when updating.
		 */
		do_action( 'rest_insert_user_media', $user_media, $request, true );

		// Include admin functions to get access to wp_generate_attachment_metadata().
		require_once ABSPATH . 'wp-admin/includes/admin.php';

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

		if ( isset( $request['alt_text'] ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
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
