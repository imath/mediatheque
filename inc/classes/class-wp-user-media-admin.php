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

		/** Media Editor ******************************************************/

		add_action( 'wp_enqueue_media',      array( $this, 'scripts'   ) );
		add_action( 'print_media_templates', array( $this, 'templates' ) );

		/** Settings *********************************************************/

		add_action( 'admin_enqueue_scripts',                       array( $this, 'inline_scripts'          ) );
		add_action( 'admin_head',                                  array( $this, 'admin_head'              ) );
		add_action( 'admin_head-settings_page_user-media-options', array( $this, 'settings_menu_highlight' ) );
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
	 * Add a navigation to the Media options
	 *
	 * @since 1.0.0
	 */
	public function inline_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		$inline_scripts = array();
		if ( 'options-media' === $screen->id || 'settings_page_user-media-options' === $screen->id ) {
			$links = array(
				sprintf( '<a href="%1$s"%2$s>%3$s</a>',
					esc_url( admin_url( 'options-media.php' ) ),
					'options-media' === $screen->id ? ' class="current"' : '',
					esc_html__( 'Shared Media', 'wp-user-media' )
				),
				sprintf( '<a href="%1$s"%2$s>%3$s</a>',
					esc_url( add_query_arg( 'page', 'user-media-options', admin_url( 'options-general.php' ) ) ),
					'settings_page_user-media-options' === $screen->id ? ' class="current"' : '',
					esc_html( $this->title )
				),
			);

			$inline_scripts['media-tabs']= sprintf( '
				$( \'.wrap h1\' ).first().after( $( \'<div></div>\' )
					.addClass( \'wp-filter\')
					.html(
						$( \'<ul></ul>\' )
						.addClass( \'filter-links\')
						.html(
							%s
						)
					)
				);', '\'<li>' . join( '</li><li>', $links ) . '</li>\'' );
		}

		$pointer = '';
		$pointer_placeholders = '
			$( document ).ready( function( $ ) {
				$( \'#%1$s\' ).pointer( {
					content: \'<h3>%2$s</h3><p>%3$s</p>\',
					position: {
						my: \'left top\',
						at: \'center bottom\',
						offset: \'-25 0\'
					},
					close: function() {
						setUserSetting( \'%4$s\', 1 );
					}
				} ).pointer( \'open\' );
			} );
		';

		$pointers = wp_user_media_get_pointers();

		if ( $pointers ) {
			$can_manage_options  = current_user_can( 'manage_options' );
			$permalink_structure = get_option( 'permalink_structure' );

			foreach ( $pointers as $key => $p ) {
				$selector_id = $key;
				$setting     = sanitize_key( $key );

				if ( 'toplevel_page_user-media' !== $key && ! $can_manage_options ) {
					continue;

				// Permalink is specific
				} elseif ( 'user-media-permalinks' === $key && $can_manage_options ) {
					if ( ! get_option( 'permalink_structure' ) ) {
						$selector_id = 'menu-settings';
					} else {
						continue;
					}
				} elseif ( ! $permalink_structure ) {
					continue;
				}

				if ( ! get_user_setting( $setting ) ) {
					$pointer = sprintf(
						$pointer_placeholders,
						$selector_id,
						$p['title'],
						$p['content'],
						$setting
					);
					break;
				}
			}
		}

		if ( $pointer ) {
			array_push( $inline_scripts, $pointer );
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script( 'utils' );
		}

		if ( $inline_scripts ) {
			$inline_scripts = sprintf( '( function($) {%1$s%2$s%1$s} )( jQuery );', "\n", join( "\n", $inline_scripts ) );

			wp_add_inline_script( 'common', $inline_scripts );
		}
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

		// User Media options
		add_options_page(
			$this->title,
			$this->title,
			'manage_options',
			'user-media-options',
			array( $this, 'do_settings' )
		);
	}

	/**
	 * Remove the subnav as User Media options is a subtab of shared media.
	 *
	 * @since 1.0.0
	 */
	public function admin_head() {
		remove_submenu_page( 'options-general.php', 'user-media-options' );
	}

	/**
	 * Make sure the highlighted submenu is the Media Options for User Media Options.
	 *
	 * @since 1.0.0
	 */
	public function settings_menu_highlight() {
		$GLOBALS['submenu_file'] = 'options-media.php';
	}

	/**
	 * Media options' form
	 *
	 * @since 1.0.0
	 */
	function do_settings() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Media Settings', 'wp-user-media' ); ?></h1>

			<?php if ( ! empty( $GLOBALS['is_nginx'] ) ) {

				printf(
					'<textarea class="code" readonly="readonly" cols="100" rows="5">%s</textarea>',
					sprintf( '
location ~* /(?:uploads|files)/wp-user-media/private/.* {
	if ($http_cookie !~* "wordpress_logged_in") {
		return 301 %s;
	}
}', wp_login_url() )
				);

			} ?>
		</div>
		<?php
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

		wp_localize_script( 'wp-user-media-admin', 'wpUserMediaSettings', array(
			'params' => array(
				'container' => 'wp-user-media-ui',
				'browser'   => 'wp-user-media-browse',
				'dropzone'  => 'drag-drop-area',
			),
			'toolbarItems' => array_merge( array(
					'users'    => __( 'Browse Users', 'wp-user-media' )
				),
				wp_list_pluck( wp_user_media_get_post_statuses( 'all' ), 'label', 'name' ),
				array(
					'upload'    => __( 'Upload file(s)', 'wp-user-media' ),
					'directory' => __( 'Add a directory', 'wp-user-media' ),
				)
			),
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
				<h1 id="wp-user-media-title">%s</h1>
				<div id="wp-user-media-container">
					<div id="toolbar" class="wp-filter"></div>
					<div id="forms"></div>
					<div id="users"></div>
					<div id="media"></div>
				</div>
			</div>
		', esc_html( $this->title ) );

		wp_user_media_get_template_part( 'toolbar-item', 'wp-user-media-toolbar-item' );
		wp_user_media_get_template_part( 'user', 'wp-user-media-user' );
		wp_user_media_get_template_part( 'user-media', 'wp-user-media-media' );
		wp_user_media_get_template_part( 'uploader', 'wp-user-media-uploader' );
		wp_user_media_get_template_part( 'progress', 'wp-user-media-progress' );
		wp_user_media_get_template_part( 'dirmaker', 'wp-user-media-dirmaker' );
	}
}
