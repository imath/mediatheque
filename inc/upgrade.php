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

		// Add default options.
		$default_options = array(
			'mediatheque_capability' => 'exist',
			'mediatheque_mime_types' => mediatheque_get_default_mime_types(),
		);

		foreach ( $default_options as $option_name => $option_value ) {
			add_option( $option_name, $option_value );
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
			'title'    => __( 'Modifiez la structure de vos permaliens.', 'mediatheque' ),
			'content'  => __( 'MediaThèque nécessite que la structure de vos permaliens soit différente que celle définie par défaut.', 'mediatheque' ),
			'position' => 'bottom',
		),
		'menu-settings' => array(
			'title'    => __( 'Options des media utilisateurs', 'mediatheque' ),
			'content'  => __( 'Personnalisez les options des media utilisateurs depuis les réglages des media.', 'mediatheque' ),
			'position' => 'bottom',
		),
		'menu-media' => array(
			'title'    => __( 'Gestion des media utilisateurs', 'mediatheque' ),
			'content'  => __( 'Vous pouvez gérer les media utilisateurs depuis le sous-menu de la bibliothèque de media correspondant.', 'mediatheque' ),
			'position' => 'top',
		),
		'toplevel_page_user-media' => array(
			'title'    => __( 'Accédez à votre MediaThèque', 'mediatheque' ),
			'content'  => __( 'Vous pouvez ajouter, organiser et supprimer vos media utilisateurs depuis ce menu.', 'mediatheque' ),
			'position' => 'top',
		),
	);
}
