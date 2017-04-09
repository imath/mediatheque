<?php
/**
 * MediaThèque Upgrades.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get the MediaThèque Version saved in DB.
 *
 * @since 1.0.0
 *
 * @return string The MediaThèque Raw DB Version.
 */
function mediatheque_db_version() {
	return get_option( 'mediatheque_version', 0 );
}

/**
 * Does the plugin needs to be upgraded ?
 *
 * @since 1.0.0
 *
 * @return bool True if it's an upgrade. False otherwise.
 */
function mediatheque_is_upgrade() {
	return version_compare( mediatheque_db_version(), mediatheque_version(), '<' );
}

/**
 * Is this the first install of the plugin ?
 *
 * @since 1.0.0
 *
 * @return bool True if it's the first install. False otherwise.
 */
function mediatheque_is_install() {
	return 0 === mediatheque_db_version();
}

/**
 * Run the upgrade routines.
 *
 * @since 1.0.0
 */
function mediatheque_upgrade() {
	if ( ! mediatheque_is_upgrade() && ! mediatheque_is_install() ) {
		return;
	}

	$db_version = mediatheque_version();

	if ( mediatheque_is_install() ) {

		// Create the two available terms
		foreach ( array(
			'mediatheque-file',
			'mediatheque-directory',
		) as $term ) {
			wp_insert_term( $term, 'user_media_types' );
		}

		/**
		 * Trigger the 'mediatheque_install' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'mediatheque_install' );

	} elseif ( mediatheque_is_upgrade() ) {
		/**
		 * Trigger the 'mediatheque_upgrade' action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'mediatheque_upgrade', $db_version );
	}

	// Force rewrite rules to be refreshed
	if ( get_option( 'permalink_structure' ) ) {
		delete_option( 'rewrite_rules' );
	}

	// Update the db version.
	update_option( 'mediatheque_version', $db_version );
}
add_action( 'admin_init', 'mediatheque_upgrade', 999 );

/**
 * Used to guide the user when new features are added.
 *
 * @since 1.0.0.
 */
function mediatheque_get_pointers() {
	return array(
		'user-media-permalinks' => array(
			'title'   => __( 'Edit your permalink settings.', 'mediatheque' ),
			'content' => __( 'MediaThèque requires the permalinks to be set to something different than Default', 'mediatheque' ),
		),
		'menu-settings' => array(
			'title'   => __( 'User Media Options', 'mediatheque' ),
			'content' => __( 'Customize the User Media Options from the Media settings.', 'mediatheque' ),
		),
		'menu-media' => array(
			'title'   => __( 'User Media Administration', 'mediatheque' ),
			'content' => __( 'You can manage the User Media from the corresponding Media sub menu.', 'mediatheque' ),
		),
		'toplevel_page_user-media' => array(
			'title'   => __( 'Access to your Media', 'mediatheque' ),
			'content' => __( 'You can add or edit Media at anytime from this menu.', 'mediatheque' ),
		),
	);
}
