<?php
/**
 * Plugin Name: MediaThèque
 * Plugin URI: https://imathi.eu/tag/mediatheque/
 * Description: Une gestion alternative des media dans WordPress, pour tous.
 * Version: 1.0.0
 * Requires at least: 4.7
 * Tested up to: 4.7
 * License: GNU/GPL 2
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: mediatheque
 * Domain Path: /languages/
 * Network: True
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MediaTheque' ) ) :
/**
 * Main plugin's class
 *
 * @package mediatheque
 *
 * @since 1.0.0
 */
final class MediaTheque {

	/**
	 * Plugin's main instance
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->globals();
		$this->inc();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Setups plugin's globals
	 *
	 * @since 1.0.0
	 */
	private function globals() {
		// Version
		$this->version = '1.0.0';

		// Domain
		$this->domain = 'mediatheque';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Path and URL
		$this->dir                = plugin_dir_path( $this->file );
		$this->url                = plugin_dir_url ( $this->file );
		$this->js_url             = trailingslashit( $this->url . 'js' );
		$this->assets_url         = trailingslashit( $this->url . 'assets' );
		$this->inc_dir            = trailingslashit( $this->dir . 'inc' );
		$this->templates          = trailingslashit( $this->dir . 'templates' );
		$this->personal_avatars   = array();
		$this->user_media_oembeds = array();
		$this->template_tags      = new stdClass;
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require( $this->inc_dir . 'options.php' );
		require( $this->inc_dir . 'users.php' );
		require( $this->inc_dir . 'functions.php' );
		require( $this->inc_dir . 'media.php' );
		require( $this->inc_dir . 'templates.php' );
		require( $this->inc_dir . 'upgrade.php' );

		if ( mediatheque_use_personal_avatar() ) {
			require( $this->inc_dir . 'avatars.php' );
		}

		if ( is_admin() ) {
			require( $this->inc_dir . 'settings.php' );
		}

		// Last but not least!
		require( $this->inc_dir . 'hooks.php' );
	}

	/**
	 * Class Autoload function
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class The class name.
	 */
	public function autoload( $class ) {
		$name = str_replace( '_', '-', strtolower( $class ) );

		if ( false === strpos( $name, $this->domain ) ) {
			return;
		}

		$folder = null;
		$parts = explode( '-', $name );

		if ( isset( $parts[2] ) ) {
			$folder = $parts[2];
		}

		$path = $this->inc_dir . "classes/class-{$name}.php";

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		require $path;
	}
}

endif;

/**
 * Boot the plugin.
 *
 * @since 1.0.0
 */
function mediatheque() {
	return MediaTheque::start();
}
add_action( 'plugins_loaded', 'mediatheque', 5 );
