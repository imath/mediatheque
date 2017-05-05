<?php
/**
 * MediaThèque Settings Class.
 *
 * @package mediatheque\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Settings class
 *
 * @since  1.0.0
 */
class MediaTheque_Settings {
	/**
	 * The settings sections
	 *
	 * @var array
	 */
	public $settings_sections = array();

	/**
	 * The settings fields
	 *
	 * @var array
	 */
	public $settings_fields = array();

	/**
	 * The class constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->settings_sections = apply_filters( 'mediatheque_get_settings_sections', array(
			'settings_page_user-media-options' => array(
				'title'     => __( 'Options disponibles', 'mediatheque' ),
				'callback'  => 'mediatheque_settings_section_callback',
			),
		) );

		$this->settings_fields = apply_filters( 'mediatheque_get_settings_fields', array(
			'settings_page_user-media-options' => array(
				'mediatheque_capability' => array(
					'title'             => __( 'Capacités requises.', 'mediatheque' ),
					'callback'          => 'mediatheque_settings_field_capability',
					'sanitize_callback' => 'mediatheque_sanitize_capability',
				),
				'mediatheque_mime_types' => array(
					'title'             => __( 'Types de fichier autorisés.', 'mediatheque' ),
					'callback'          => 'mediatheque_settings_field_mime_types',
					'sanitize_callback' => 'mediatheque_sanitize_mime_types',
				),
			),
		) );

		// Register the settings once the Administration is inited.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the MediaThèque settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		// Add settings sections.
		foreach ( (array) $this->settings_sections as $ks => $section ) {
			if ( empty( $section['title'] ) || empty( $section['callback'] ) ) {
				continue;
			}

			// Add the section.
			add_settings_section( $ks, $section['title'], $section['callback'], $ks );
		}

		// Add settings fields
		foreach ( (array) $this->settings_fields as $section_id => $fields ) {
			// Check the section exists.
			if ( ! isset( $this->settings_sections[ $section_id ] ) ) {
				continue;
			}

			foreach ( $fields as $option => $field ) {
				if ( empty( $field['title'] ) || empty( $field['callback'] ) || empty( $field['sanitize_callback'] ) ) {
					continue;
				}

				if ( empty( $field['args'] ) ) {
					$field['args'] = array();
				}

				// Add the field
				add_settings_field( $option, $field['title'], $field['callback'], $section_id, $section_id, $field['args'] );

				// Register the setting
				register_setting( $section_id, $option, $field['sanitize_callback'] );
			}
		}
	}
}
