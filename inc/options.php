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
 * Gets the MediaThèque Version saved in DB.
 *
 * @since 1.0.0
 *
 * @return string The MediaThèque Raw DB Version.
 */
function mediatheque_db_version() {
	return get_network_option( 0, 'mediatheque_version', 0 );
}

/**
 * Gets the required user capability to use the MediaThèque.
 *
 * @since 1.0.0
 *
 * @param  string $default Default capability.
 * @return string          The required user capability to use the MediaThèque.
 */
function mediatheque_get_required_cap( $default = 'exist' ) {
	/**
	 * Filter here to edit the required cap.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The required cap.
	 */
	return apply_filters( 'mediatheque_get_required_cap', get_network_option( 0, 'mediatheque_capability', $default ) );
}

/**
 * Gets the allowed mime types of the MediaThèque.
 *
 * @since 1.0.0
 *
 * @param  array $default Default mime types.
 * @return array          The allowed mime types of the MediaThèque.
 */
function mediatheque_get_allowed_file_types( $default = array() ) {
	/**
	 * Filter here to edit the allowed mime types.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value The allowed mime types.
	 */
	return apply_filters( 'mediatheque_get_allowed_file_types', get_network_option( 0, 'mediatheque_mime_types', $default ) );
}

/**
 * Is the Personal avatar feature enabled?
 *
 * @since 1.0.0
 *
 * @param  boolean $default Defaults to enabled.
 * @return boolean          True if the Personal avatar feature is enabled.
 *                          False otherwise.
 */
function mediatheque_use_personal_avatar( $default = true ) {
	/**
	 * Filter here to allow/disallow the Personal avatar feature.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $value True if the Personal avatar feature is enabled.
	 *                       False otherwise.
	 */
	return (bool) apply_filters( 'mediatheque_use_personal_avatar', get_network_option( 0, 'mediatheque_personal_avatar', $default ) );
}

/**
 * Is the MediaThèque button disabled on front-end?
 *
 * @since 1.3.0
 *
 * @param  boolean $default Defaults to enabled.
 * @return boolean          True if the MediaThèque button is disabled.
 *                          False otherwise.
 */
function mediatheque_disable_front_end_button( $default = false ) {
	/**
	 * Filter here to allow/disallow the MediaThèque button on front-end.
	 *
	 * @since 1.3.0
	 *
	 * @param boolean $value True if the MediaThèque button is disabled.
	 *                       False otherwise.
	 */
	return (bool) apply_filters( 'mediatheque_disable_front_end_button', get_network_option( 0, 'mediatheque_disable_on_front_end', $default ) );
}

/**
 * Default values for the options (used at Install step).
 *
 * @since 1.0.0
 *
 * @return array The default values for the options.
 */
function mediatheque_get_default_options() {
	/**
	 * Filter here to edit default values for the options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value The default values for the options.
	 */
	return apply_filters(
		'mediatheque_get_default_options',
		array(
			'mediatheque_capability'           => 'exist',
			'mediatheque_mime_types'           => mediatheque_get_default_mime_types(),
			'mediatheque_personal_avatar'      => 1,
			'mediatheque_disable_on_front_end' => 0,
		)
	);
}
