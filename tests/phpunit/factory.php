<?php
/**
 * MediaThèque User Media Factory.
 */
class MediaTheque_UnitTest_Factory extends WP_UnitTest_Factory {

	function __construct() {
		parent::__construct();

		$this->user_media_file   = new MediaTheque_UnitTest_Factory_For_File( $this );
		$this->user_media_folder = new MediaTheque_UnitTest_Factory_For_Folder( $this );
	}
}

class MediaTheque_UnitTest_Factory_For_File extends WP_UnitTest_Factory_For_Post {
	protected $user_media_parent_dir = 0;
	protected $user_media_status     = 'publish';
	protected $author                = 0;

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'post_title'    => new WP_UnitTest_Generator_Sequence( 'User Media %s' ),
			'post_content'  => new WP_UnitTest_Generator_Sequence( 'User Media description %s' ),
			'post_status'   => 'publish',
			'post_type'     => 'user_media',
		);
	}

	public function upload_dir_filter() {
		$dir     = mediatheque_get_upload_dir();
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return array_merge( $dir, array( 'error' => new WP_Error( 'user_id_error', 'Empty user ID' ) ) );
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

	public function upload_mimes( $mimes ) {
		if ( ! is_super_admin( $this->author ) ) {
			$mimes = mediatheque_get_allowed_mime_types();
		}

		return $mimes;
	}

	function create_object( $args ) {
		$is_main_site = mediatheque_is_main_site();

		if ( ! $is_main_site ) {
			switch_to_blog( get_current_network_id() );
		}

		$user_id = get_current_user_id();

		if ( ! isset( $args['post_author'] ) ) {
			if ( is_user_logged_in() ) {
				$args['post_author'] = get_current_user_id();

			// Create a user
			} else {
				$args['post_author'] = $this->factory->user->create( array( 'role' => 'subscriber' ) );
			}
		}

		if ( isset( $args['post_status'] ) ) {
			$this->user_media_status = $args['post_status'];
		}

		if ( isset( $args['post_parent'] ) ) {
			$parent_status = get_post_status( $args['post_parent'] );

			if ( $parent_status ) {
				$this->user_media_parent_dir = get_post_meta( $args['post_parent'], '_mediatheque_relative_path', true );
				$args['post_status']         = $parent_status;
				$this->user_media_status     = $parent_status;
			}
		}

		$this->author = (int) $args['post_author'];
		wp_set_current_user( $this->author );

		$size = 0;

		if ( ! isset( $args['file'] ) ) {
			$args['file'] = DIR_TESTDATA . '/images/test-image.jpg';
		}

		$size = filesize( $args['file'] );

		add_filter( 'upload_mimes', array( $this, 'upload_mimes' ), 20, 1 );
		add_filter( 'upload_dir', array( $this, 'upload_dir_filter' ), 1, 0 );

		$contents = file_get_contents( $args['file'] );
		$upload = wp_upload_bits( basename( $args['file'] ), null, $contents );

		remove_filter( 'upload_dir', array( $this, 'upload_dir_filter' ), 1, 0 );
		remove_filter( 'upload_mimes', array( $this, 'upload_mimes' ), 20, 1 );

		if ( is_wp_error( $upload ) ) {
			return false;
		}

		$file                   = $upload['file'];
		$args['post_mime_type'] = $upload['type'];
		$args['guid']           = $upload['url'];
		$args['tax_input']      = array(
			'user_media_types' => array( mediatheque_get_user_media_type_id( 'mediatheque-file' ) ),
		);

		unset( $args['file'] );

		$id = wp_insert_post( wp_slash( (array) $args ), true );

		if ( ! is_wp_error( $id ) ) {
			update_attached_file( $id, $file );

			// Include admin functions to get access to wp_generate_attachment_metadata().
			require_once ABSPATH . 'wp-admin/includes/admin.php';
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

			if ( $size ) {
				mediatheque_disk_usage_update( $this->author, $size );
			}
		}

		if ( ! $is_main_site ) {
			restore_current_blog();
		}

		wp_set_current_user( $user_id );

		return $id;
	}
}

class MediaTheque_UnitTest_Factory_For_Folder extends WP_UnitTest_Factory_For_Post {
	protected $author = 0;

	function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'post_title'    => new WP_UnitTest_Generator_Sequence( 'User Media Folder %s' ),
			'post_content'  => new WP_UnitTest_Generator_Sequence( 'User Media Folder description %s' ),
			'post_status'   => 'publish',
			'post_type'     => 'user_media',
		);
	}

	function create_object( $args ) {
		$is_main_site = mediatheque_is_main_site();

		if ( ! $is_main_site ) {
			switch_to_blog( get_current_network_id() );
		}

		$user_id = get_current_user_id();

		if ( ! isset( $args['post_author'] ) ) {
			if ( is_user_logged_in() ) {
				$args['post_author'] = get_current_user_id();

			// Create a user
			} else {
				$args['post_author'] = $this->factory->user->create( array( 'role' => 'subscriber' ) );
			}
		}

		$this->author = (int) $args['post_author'];
		wp_set_current_user( $this->author );

		$args['tax_input']      = array(
			'user_media_types' => array( mediatheque_get_user_media_type_id( 'mediatheque-directory' ) ),
		);

		$id = wp_insert_post( wp_slash( (array) $args ), true );

		if ( ! is_wp_error( $id ) ) {
			$dir    = mediatheque_get_upload_dir();
			$folder = get_post( $id );

			if ( $folder->post_parent ) {
				$dir['subdir'] = '/' . get_post_meta( $folder->post_parent, '_mediatheque_relative_path', true );
			} else {
				$dir['subdir'] .= sprintf( '/%1$s/%2$s', get_post_status( $folder ), $this->author );
			}

			$dir['path'] = sprintf( '%s%s', $dir['basedir'], $dir['subdir'] );

			if ( ! is_dir( $dir['path'] ) ) {
				wp_mkdir_p( $dir['path'] );
			}

			$dirname = wp_unique_filename( $dir['path'], $folder->post_name );

			if ( wp_mkdir_p( $dir['path'] . '/' . $dirname ) ) {
				update_post_meta( $id, '_mediatheque_relative_path', _wp_relative_upload_path( $dir['path'] . '/' . $dirname ) );
			} else {
				return false;
			}
		}

		if ( ! $is_main_site ) {
			restore_current_blog();
		}

		wp_set_current_user( $user_id );

		return $id;
	}
}
