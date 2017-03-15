<?php
/*
Plugin Name: WP User Media
Plugin URI: https://imathi.eu/tag/wp-user-media/
Description: A media library, for WordPress users.
Version: 1.0.0
Requires at least: 4.7
Tested up to: 4.7
License: GNU/GPL 2
Author: imath
Author URI: https://imathi.eu/
Text Domain: wp-user-media
Domain Path: /languages/
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_User_Media' ) ) :
/**
 * Main plugin's class
 *
 * @package WP User Media
 *
 * @since 1.0.0
 */
final class WP_User_Media {

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
		$this->hooks();
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
		$this->domain = 'wp-user-media';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Path and URL
		$this->dir        = plugin_dir_path( $this->file );
		$this->url        = plugin_dir_url ( $this->file );
		$this->js_url     = trailingslashit( $this->url . 'js' );
		$this->assets_url = trailingslashit( $this->url . 'assets' );
		$this->inc_dir    = trailingslashit( $this->dir . 'inc' );
		$this->templates  = trailingslashit( $this->dir . 'templates' );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require( $this->inc_dir . 'functions.php' );
		require( $this->inc_dir . 'upgrade.php' );
	}

	/**
	 * Setups hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		// Boot the Admin
		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( 'WP_User_Media_Admin', 'start' ), 10 );
		}

		// Load translations
		add_action( 'init', array( $this, 'load_textdomain' ), 9 );

		// Register objects
		add_action( 'init', 'wp_user_media_register_objects', 12 );

		// Map capabilities
		add_filter( 'map_meta_cap', 'wp_user_media_map_meta_caps', 10, 4 );

		// Add a new query parameter to Users rest request
		add_filter( 'rest_user_collection_params', 'wp_user_media_additionnal_user_rest_param', 10, 1 );
		add_filter( 'rest_user_query',             'wp_user_media_rest_user_query',             10, 2 );

		// Set the single User Media Templates
		add_action( 'parse_query',                      'wp_user_media_parse_query'           );
		add_filter( 'embed_template',                   'wp_user_media_embed_template'        );
		add_action( 'wp_user_media_embed_content_meta', 'wp_user_media_embed_download_button' );
		add_action( 'wp_user_media_embed_content_meta', 'print_embed_sharing_button'          );
		add_action( 'enqueue_embed_scripts',            'wp_user_media_embed_style'           );
	}

	/**
	 * Loads the translation files
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( $this->domain, false, trailingslashit( basename( $this->dir ) ) . 'languages' );
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
function wp_user_media() {
	return WP_User_Media::start();
}
add_action( 'plugins_loaded', 'wp_user_media', 5 );
