<?php
/**
 * WP User Media Upgrades.
 *
 * @package WP User Media\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get the WP User Media Version saved in DB.
 *
 * @since 1.0.0
 *
 * @return string The WP User Media Raw DB Version.
 */
function wp_user_media_db_version() {
	return get_option( 'wp_user_media_version', 0 );
}

/**
 * Does the plugin needs to be upgraded ?
 *
 * @since 1.0.0
 *
 * @return bool True if it's an upgrade. False otherwise.
 */
function wp_user_media_is_upgrade() {
	return version_compare( wp_user_media_db_version(), wp_user_media_version(), '<' );
}

/**
 * Is this the first install of the plugin ?
 *
 * @since 1.0.0
 *
 * @return bool True if it's the first install. False otherwise.
 */
function wp_user_media_is_install() {
	return 0 === wp_user_media_db_version();
}

/**
 * Run the upgrade routines.
 *
 * @since 1.0.0
 */
function wp_user_media_upgrade() {
	if ( ! wp_user_media_is_upgrade() && ! wp_user_media_is_install() ) {
		return;
	}

	$db_version = wp_user_media_version();

	if ( wp_user_media_is_install() ) {

		// Create the two available terms
		foreach ( array(
			'wp-user-media-file',
			'wp-user-media-directory',
		) as $term ) {
			wp_insert_term( $term, 'user_media_types' );
		}

		/**
		 * Trigger the 'wp_user_media_install' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wp_user_media_install' );

	} elseif ( wp_user_media_is_upgrade() ) {
		/**
		 * Trigger the 'wp_user_media_upgrade' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wp_user_media_upgrade', $db_version );
	}

	// Force rewrite rules to be refreshed
	if ( get_option( 'permalink_structure' ) ) {
		delete_option( 'rewrite_rules' );
	}

	// Update the db version.
	update_option( 'wp_user_media_version', $db_version );
}
add_action( 'admin_init', 'wp_user_media_upgrade', 999 );

/**
 * Used to guide the user when new features are added.
 *
 * @since 1.0.0.
 */
function wp_user_media_get_pointers() {
	return array(
		'user-media-permalinks' => array(
			'title'   => __( 'Edit your permalink settings.', 'wp-user-media' ),
			'content' => __( 'WP User Media requires the permalinks to be set to something different than Default', 'wp-user-media' ),
		),
		'menu-settings' => array(
			'title'   => __( 'User Media Options', 'wp-user-media' ),
			'content' => __( 'Customize the User Media Options from the Media settings.', 'wp-user-media' ),
		),
		'menu-media' => array(
			'title'   => __( 'User Media Administration', 'wp-user-media' ),
			'content' => __( 'You can manage the User Media from the corresponding Media sub menu.', 'wp-user-media' ),
		),
		'toplevel_page_user-media' => array(
			'title'   => __( 'Access to your Media', 'wp-user-media' ),
			'content' => __( 'You can add or edit Media at anytime from this menu.', 'wp-user-media' ),
		),
	);
}
