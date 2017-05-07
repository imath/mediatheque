<?php
/**
 * MediaThèque options.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the MediaThèque Version saved in DB.
 *
 * @since 1.0.0
 *
 * @return string The MediaThèque Raw DB Version.
 */
function mediatheque_db_version() {
	return get_network_option( 0, 'mediatheque_version', 0 );
}

function mediatheque_get_required_cap( $default = 'exist' ) {
	return apply_filters( 'mediatheque_get_required_cap', get_network_option( 0, 'mediatheque_capability', $default ) );
}

function mediatheque_get_allowed_file_types( $default = array() ) {
	return apply_filters( 'mediatheque_get_allowed_file_types', get_network_option( 0, 'mediatheque_mime_types', $default ) );
}

function mediatheque_use_personal_avatar( $default = true ) {
	return (bool) apply_filters( 'mediatheque_use_personal_avatar', get_network_option( 0, 'mediatheque_personal_avatar', $default ) );
}

function mediatheque_get_default_options() {
	return apply_filters( 'mediatheque_get_default_options', array(
		'mediatheque_capability'      => 'exist',
		'mediatheque_mime_types'      => mediatheque_get_default_mime_types(),
		'mediatheque_personal_avatar' => 1,
	) );
}
