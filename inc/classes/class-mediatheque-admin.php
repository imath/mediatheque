<?php
/**
 * MediaThèque Admin Class.
 *
 * @package mediatheque\inc\classes
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
class MediaTheque_Admin {

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
	 * The MediaThèque settings
	 *
	 * @var MediaTheque_Settings
	 */
	public $settings = null;

	/**
	 * The MediaThèque vanished user media logs
	 *
	 * @var array
	 */
	public $vanished_logs = array();

	/**
	 * The settings page for the current WordPress config.
	 *
	 * @var string
	 */
	public $settings_page = null;

	/**
	 * The admin capability for the current WordPress config.
	 *
	 * @var string
	 */
	public $capability = null;

	/**
	 * The menu hook to use for the current WordPress config.
	 *
	 * @var string
	 */
	public $menu_hook = null;

	/**
	 * The class constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		if ( ! $this->settings ) {
			$this->settings = new MediaTheque_Settings();
		}

		$this->settings_page = 'options-general.php';
		$this->capability    = 'manage_options';
		$this->menu_hook     = 'admin_menu';

		if ( is_multisite() ) {
			$this->settings_page = 'settings.php';
			$this->capability    = 'manage_network_options';
			$this->menu_hook     = 'network_admin_menu';
		}

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

		$mediatheque = mediatheque();

		if ( empty( $mediatheque->admin ) ) {
			$mediatheque->admin = new self;
		}

		return $mediatheque->admin;
	}

	/**
	 * Setups hooks
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'admin_menu',         array( $this, 'menus'   )     );
		add_action( 'network_admin_menu', array( $this, 'menus'   )     );
		add_action( 'user_admin_menu',    array( $this, 'menus'   )     );
		add_action( 'init',               array( $this, 'globals' ), 14 );

		/** Media Editor ******************************************************/

		add_action( 'wp_enqueue_media',      array( $this, 'scripts'  )        );
		add_action( 'print_media_templates', array( $this, 'template' ), 10, 0 );

		/** Settings *********************************************************/

		add_action( 'admin_enqueue_scripts', array( $this, 'inline_scripts' ) );

		if ( ! is_multisite() ) {
			add_action( 'admin_head',                                  array( $this, 'admin_head'              ) );
			add_action( 'admin_head-settings_page_user-media-options', array( $this, 'settings_menu_highlight' ) );
		}
	}

	/**
	 * Setups globals
	 *
	 * @since 1.0.0
	 */
	public function globals() {
		$this->post_type_object = get_post_type_object( 'user_media' );
		$this->title            = $this->post_type_object->labels->menu_name;

		if ( is_super_admin() ) {
			$this->title = __( 'MediaThèque Utilisateurs', 'mediatheque' );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since  1.0.0
	 */
	public function scripts() {
		wp_enqueue_script( 'mediatheque-editor' );
		mediatheque_localize_script();

		wp_enqueue_style( 'mediatheque-style' );
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

		if ( ! is_multisite() ) {
			if ( 'options-media' === $screen->id || 'settings_page_user-media-options' === $screen->id ) {
				$links = array(
					sprintf( '<a href="%1$s"%2$s>%3$s</a>',
						esc_url( admin_url( 'options-media.php' ) ),
						'options-media' === $screen->id ? ' class="current"' : '',
						esc_html__( 'Bibliothèque partagée', 'mediatheque' )
					),
					sprintf( '<a href="%1$s"%2$s>%3$s</a>',
						esc_url( add_query_arg( 'page', 'user-media-options', admin_url( 'options-general.php' ) ) ),
						'settings_page_user-media-options' === $screen->id ? ' class="current"' : '',
						esc_html( $this->title )
					),
				);

				$inline_scripts['media-tabs'] = sprintf( '
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
		}

		if ( 0 === strpos( $screen->id, 'settings_page_user-media-options' ) ) {
			$inline_scripts['select-unselect-all'] = '
				$( \'.mediatheque-selectall\' ).on( \'click\', function( e ) {
					$.each( $( \'[data-mime-type="\' + $( e.currentTarget ).data( \'mime-type\') + \'"]\' ), function( i, cb ) {
						if ( 0 === i ) {
							return;
						}

						$( cb ).prop( \'checked\', $( e.currentTarget ).prop( \'checked\' ) );
					} );
				} );
			';
		}

		$pointer = '';
		$pointer_placeholders = '
			$( document ).ready( function( $ ) {
				$( \'#%1$s\' ).pointer( {
					content: \'<h3>%2$s</h3><p>%3$s</p>\',
					position: {
						edge: \'%5$s\',
						align: \'center\',
						offset: \'-25 0\'
					},
					close: function() {
						setUserSetting( \'%4$s\', 1 );
					}
				} ).pointer( \'open\' );
			} );
		';

		$pointers = mediatheque_get_pointers();

		if ( $pointers ) {
			$can_manage_options  = current_user_can( $this->capability );

			if ( ! is_multisite() ) {
				$permalink_structure = get_option( 'permalink_structure' );
			}

			foreach ( $pointers as $key => $p ) {
				$selector_id = $key;
				$setting     = sanitize_key( $key );

				if ( 'toplevel_page_user-media' !== $key && ! $can_manage_options ) {
					continue;

				// Permalink is specific
				} elseif ( ! isset( $permalink_structure ) && 'user-media-permalinks' === $key ) {
					continue;
				} elseif ( 'user-media-permalinks' === $key && $can_manage_options ) {
					if ( ! $permalink_structure ) {
						$selector_id = 'menu-settings';
					} else {
						continue;
					}
				} elseif ( 'menu-settings' === $key && ! mediatheque_is_main_site() ) {
					continue;
				}

				if ( ! get_user_setting( $setting ) ) {
					$pointer = sprintf(
						$pointer_placeholders,
						$selector_id,
						$p['title'],
						$p['content'],
						$setting,
						$p['position']
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

	public function get_template_parts() {
		foreach ( mediatheque_get_template_parts() as $id => $tmpl ) {
			mediatheque_get_template_part( $id, $tmpl );
		}
	}

	/**
	 * Print Media Editor's templates
	 *
	 * @since  1.0.0
	 */
	public function template( $editor = true ) {
		$base_layout = '<div id="mediatheque-container">
			<div id="toolbar" class="wp-filter"></div>
			<div id="forms"></div>
			<div id="users"></div>
			<div id="trail"></div>
			<div id="media"></div>
		</div>';

		if ( true === $editor ) {
			printf( '<script type="text/html" id="tmpl-mediatheque-main">%s</script>', $base_layout );
			$this->get_template_parts();
		}

		return $base_layout;
	}

	/**
	 * Add a sub menu to the Media Library
	 *
	 * @since 1.0.0
	 */
	public function menus() {
		$menu_title = $this->title;

		// Regular user
		if ( ( is_user_logged_in() && ! current_user_can( 'upload_files' ) ) || is_network_admin() ) {
			add_menu_page(
				$this->title,
				$this->title,
				'create_user_uploads',
				'user-media',
				array( $this, 'media_grid' ),
				'dashicons-admin-media',
				20 // Before comments
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
		$screen_id = add_submenu_page(
			$this->settings_page,
			$this->title,
			$this->title,
			$this->capability,
			'user-media-options',
			array( $this, 'do_settings' )
		);

		if ( ! is_network_admin() && ! is_user_admin() ) {
			$this->vanished_logs = get_option( '_mediatheque_vanished_media', array() );
			$count_vanished      = count( $this->vanished_logs );

			if ( $count_vanished > 0 ) {
				add_management_page(
					$this->title,
					$this->title,
					'manage_options',
					'user-media-tools',
					array( $this, 'do_tools' )
				);

				add_action( 'tool_box', array( $this, 'tools_card' ), 100 );
			}

		// Save MediaThèque settings on specific page load
		} else {
			add_action( "load-{$screen_id}", array( $this, 'load_mediatheque_settings' ) );
		}
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
	 * Include options head file to enjoy WordPress settings feedback
	 * in multisite configs.
	 *
	 * @since 1.0.0
	 */
	public function restore_settings_feedback() {
		require( ABSPATH . 'wp-admin/options-head.php' );
	}

	/**
	 * Handle settings changes for multisite configs.
	 *
	 * @since  1.0.0
	 */
	public function load_mediatheque_settings() {
		add_action( 'all_admin_notices', array( $this, 'restore_settings_feedback' ) );

		if ( ! empty( $_POST['mediatheque_settings'] ) && ! empty( $_POST['option_page'] ) ) {
			$option_page = $_POST['option_page'];

			check_admin_referer( $option_page . '-options' );

			$options = apply_filters( 'whitelist_options', array() );

			if ( isset( $options[ $option_page ] ) ) {

				foreach ( $options[$option_page] as $option ) {

					$option = trim( $option );
					$value = null;

					if ( isset( $_POST[ $option ] ) ) {
						$value = $_POST[ $option ];

						if ( ! is_array( $value ) ) {
							$value = trim( $value );
						}

						$value = wp_unslash( $value );
					}

					update_network_option( 0, $option, $value );
				}
			}

			wp_redirect( add_query_arg( 'updated', 'true',  wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Media options' form
	 *
	 * @since 1.0.0
	 */
	function do_settings() {
		$form_url = self_admin_url( 'options.php' );
		if ( is_multisite() ) {
			$form_url = add_query_arg( 'page', 'user-media-options', self_admin_url( 'settings.php' ) );
		}

		$setting_section = str_replace( '-network', '', get_current_screen()->id );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Réglages de la MediaThèque', 'mediatheque' ); ?></h1>

			<form action="<?php echo esc_url( $form_url ); ?>" method="post">

				<?php settings_fields( $setting_section ); ?>

				<?php do_settings_sections( $setting_section ); ?>

				<p class="submit">
					<input type="submit" name="mediatheque_settings" class="button-primary" value="<?php esc_attr_e( 'Enregistrer les modifications', 'mediatheque' ); ?>" />
				</p>
			</form>

			<?php if ( ! empty( $GLOBALS['is_nginx'] ) ) : ?>
				<h2><?php esc_html_e( 'Configuration complémentaire Nginx', 'mediatheque' ) ;?></h2>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Configuration de votre serveur', 'mediatheque' ) ;?></th>
							<td>
								<?php printf(
									'<textarea class="code" readonly="readonly" cols="100" rows="5">%s</textarea>',
sprintf( '
location ~* /(?:uploads|files)/mediatheque/private/.* {
	if ($http_cookie !~* "wordpress_logged_in") {
		return 301 %s;
	}
}', wp_login_url() ) ); ?>
								<p class="description"><?php esc_html_e( 'Vous utilisez Nginx. Si vous souhaitez protéger les media privés partagés par vos utilisateurs, ajoutez le code ci-dessus au fichier de configuration de votre serveur.', 'mediatheque' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display the User Media Library
	 *
	 * @since 1.0.0
	 */
	public function media_grid() {
		wp_enqueue_script( 'mediatheque-manage' );
		mediatheque_localize_script();

		wp_enqueue_style( 'mediatheque-style' );

		wp_plupload_default_settings();

		printf( '
			<div class="wrap">
				<h1 id="mediatheque-title">%1$s</h1>
				<div id="mediatheque-backdrop"></div>
				%2$s
			</div>
		', esc_html( $this->title ), $this->template( false ) );

		$this->get_template_parts();
	}

	public function tools_card() {
		$page = add_query_arg( 'page', 'user-media-tools', self_admin_url( 'tools.php' ) );
		?>
		<div class="card">
			<h2 class="title"><?php esc_html_e( 'Outils de la MediaThèque', 'mediatheque' ) ?></h2>
			<p><?php printf( __( 'Vous pouvez gérer les media d\'utilisateur qui sont attachés à vos contenus et qui ont disparu depuis cet <a href="%s">outil</a>.', 'mediatheque' ), esc_url( $page ) ); ?></p>
		</div>
		<?php
	}

	public function do_tools() {
		$page = add_query_arg( array(
			'page'  => 'user-media-tools',
			'reset' => 1,
		) , self_admin_url( 'tools.php' ) );

		if ( ! empty( $_GET['reset'] ) ) {
			delete_option( '_mediatheque_vanished_media' );
			$this->vanished_logs = array();
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Outils de la MediaThèque', 'mediatheque' ); ?></h1>

			<hr class="wp-header-end">

			<p class="description"><?php esc_html_e( 'Ci-dessous la liste des media d\'utilisateur qui ont tenté de s\'afficher pour vos visiteurs. Utilisez leur adresse pour identifier les contenus les intégrant toujours. Utilisez le bouton &quot;Vider&quot; pour supprimer ces adresses une fois vos contenus modifiés.', 'mediatheque' ); ?></p>

			<table class="widefat" id="mediatheque-vanished-logs">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ancienne adresse du media', 'mediatheque' ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'Ancienne adresse du media', 'mediatheque' ); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<?php if ( ! $this->vanished_logs ) : ?>

						<tr>
							<td colspan="2"><?php esc_html_e( 'Aucun media disparu n\'a tenté de s\'afficher à vos visiteurs', 'mediatheque' ); ?></td>
						</tr>

					<?php else : foreach ( $this->vanished_logs as $url ) : ?>

						<tr>
							<td><code><?php echo esc_html( $url ) ;?></code></td>
						</tr>

					<?php endforeach; endif; ?>

				</tbody>
			</table>

			<div class="submit">
				<a href="<?php echo esc_url( $page ); ?>" class="button button-primary large"><?php esc_html_e( 'Vider', 'mediatheque' ); ?></a>
			</div>
		</div>
		<?php
	}
}
