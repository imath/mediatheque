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
	 * The title used in various screens
	 *
	 * @var string
	 */
	public $title = null;

	/**
	 * The Post Type object
	 *
	 * @var WP_Post_Type
	 */
	public $post_type_object = null;

	/**
	 * The class constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
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
	 * Setups hooks
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_menu',            array( $this, 'menus'     )     );
		add_action( 'init',                  array( $this, 'globals'   ), 14 );

		/** Media Editor **************************************************************/

		add_action( 'wp_enqueue_media',      array( $this, 'scripts'   )     );
		add_action( 'print_media_templates', array( $this, 'templates' )     );
	}

	/**
	 * Setups globals
	 *
	 * @since 1.0.0
	 */
	public function globals() {
		$this->post_type_object = get_post_type_object( 'user_media' );
		$this->title            = $this->post_type_object->labels->menu_name;
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
			array( 'media-editor', 'wp-backbone', 'underscore' ),
			wp_user_media_version(),
			true
		);
	}

	/**
	 * Print Media Editor's templates
	 *
	 * @since  1.0.0
	 */
	public function templates() {
		?>
		<script type="text/html" id="tmpl-user-media-main">
			<h2><?php esc_html_e( 'Hello World!', 'wp-user-media' ); ?></h2>
		</script>
		<?php
	}

	/**
	 * Add a sub menu to the Media Library
	 *
	 * @since 1.0.0
	 */
	public function menus() {
		// Regular user
		if ( is_user_logged_in() && ! current_user_can( 'upload_files' ) ) {
			add_menu_page(
				$this->title,
				$this->title,
				'exist',
				'user-media',
				array( $this, 'media_grid' ),
				'dashicons-admin-media'
			);

		// Contributors and Up.
		} else {
			add_media_page(
				$this->title,
				$this->title,
				'upload_files',
				'user-media',
				array( $this, 'media_grid' )
			);
		}
	}

	/**
	 * Display the User Media Library
	 *
	 * @since 1.0.0
	 */
	public function media_grid() {
		wp_enqueue_script(
			'wp-user-media-admin',
			sprintf( '%1$sadmin%2$s.js', wp_user_media_js_url(), wp_user_media_min_suffix() ),
			array( 'wp-api', 'wp-backbone', 'wp-plupload' ),
			wp_user_media_version(),
			true
		);

		wp_localize_script( 'wp-user-media-admin', 'wpUserMediaParams', array(
			'container' => 'wp-user-media-ui',
			'browser'   => 'wp-user-media-browse',
			'dropzone'  => 'drag-drop-area',
		) );

		wp_enqueue_style(
			'wp-user-media-uploader',
			sprintf( '%1$suploader%2$s.css', wp_user_media_assets_url(), wp_user_media_min_suffix() ),
			array(),
			wp_user_media_version()
		);

		wp_plupload_default_settings();

		printf( '
			<div class="wrap">
				<h1>%s</h1>
				<div id="wp-user-media-uploader"></div>
				<div id="wp-user-media-container"></div>
			</div>
		', esc_html( $this->title ) );

		wp_user_media_get_template_part( 'user', 'wp-user-media-user' );
		wp_user_media_get_template_part( 'user-media', 'wp-user-media-media' );
		wp_user_media_get_template_part( 'uploader', 'wp-user-media-uploader' );
		wp_user_media_get_template_part( 'progress', 'wp-user-media-progress' );
	}
}
