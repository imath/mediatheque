<?php
/**
 * WP User Media Admin Class.
 *
 * @package WP User Media\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The admin class
 *
 * @since  1.0.0
 */
class WP_User_Media_Admin {

	/**
	 * Title used in various places.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * The class constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->globals();
		$this->hooks();
	}

	/**
	 * Starts the Admin class
	 *
	 * @since 1.0.0
	 */
	public static function start() {
		if ( ! is_admin() ) {
			return;
		}

		$wp_user_media = wp_user_media();

		if ( empty( $wp_user_media->admin ) ) {
			$wp_user_media->admin = new self;
		}

		return $wp_user_media->admin;
	}

	/**
	 * Setups globals
	 *
	 * @since 1.0.0
	 */
	public function globals() {
		$this->title = __( 'User media', 'wp-user-media' );
	}

	/**
	 * Setups hooks
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_menu',            array( $this, 'menus'   )        );

		/** Media Editor **************************************************************/

		add_action( 'wp_enqueue_media',      array( $this, 'scripts' )        );
		add_filter( 'media_upload_tabs',     array( $this, 'tabs'    ), 10, 1 );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since  1.0.0
	 */
	public function scripts() {

		// Media Editor script
		wp_enqueue_script(
			'wp-user-media',
			sprintf( '%1$sscript%2$s.js', wp_user_media_js_url(), wp_user_media_min_suffix() ),
			array( 'media-editor' ),
			wp_user_media_version(),
			true
		);
	}

	public function tabs( $tabs = array() ) {
		return array_merge( $tabs, array(
			'user_media' => $this->title,
		) );
	}

	/**
	 * Add a sub menu to the Media Library
	 *
	 * @since 1.0.0
	 */
	public function menus() {
		add_media_page(
			$this->title,
			$this->title,
			'manage_options',
			'user-media',
			array( $this, 'media_grid' )
		);
	}

	/**
	 * Display the User Media Library
	 *
	 * @since 1.0.0
	 */
	public function media_grid() {
		printf( '
			<div class="wrap">
				<h1>%s</h1>
			</div>
		', esc_html( $this->title ) );
	}
}
