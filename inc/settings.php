<?php
/**
 * MediaThèque settings.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Settings section callback.
 *
 * @since 1.0.0.
 */
function mediatheque_settings_section_callback() {}

/**
 * Capability Setting field callback.
 *
 * @since 1.0.0.
 */
function mediatheque_settings_field_capability() {
	$role_names = wp_roles()->role_names;
	$setting    = mediatheque_get_required_cap();

	$caps = array(
		'exist' => __( 'Utilisateur connecté', 'mediatheque' ),
	);

	if ( isset( $role_names['subscriber'] ) ) {
		$caps['read'] = translate_user_role( $role_names['subscriber'] );
	}

	if ( isset( $role_names['contributor'] ) ) {
		$caps['edit_posts'] = translate_user_role( $role_names['contributor'] );
	}
	?>
	<select name="mediatheque_capability" id="mediatheque_capability"<?php echo is_network_admin() ? ' disabled' : '' ;?>>

		<?php foreach ( (array) apply_filters( 'mediatheque_caps', $caps ) as $cap => $role ) : ?>
			<option value="<?php echo esc_attr( $cap ); ?>" <?php selected( $setting, $cap ); ?>>
				<?php echo esc_html( $role ); ?>
			</option>
		<?php endforeach; ?>

	</select>
	<p class="description"><?php esc_html_e( 'Sélectionner les capacités du rôle qu\'il faut à minima détenir pour pouvoir utiliser la MediaThèque.', 'mediatheque' ); ?></p>
	<?php
}

/**
 * Sanitizes the Capability Setting field before saving it to DB.
 *
 * @since 1.0.0.
 *
 * @param string $capability The new capability.
 * @param string             The sanitized capability.
 */
function mediatheque_sanitize_capability( $capability ) {
	if ( empty( $capability ) ) {
		$capability = 'exist';
	}

	return sanitize_text_field( $capability );
}

/**
 * Allowed Mime Types Setting field callback.
 *
 * @since 1.0.0.
 */
function mediatheque_settings_field_mime_types() {
	$types            = wp_get_ext_types();
	$translated_types = mediatheque_get_i18n_media_type( $types );
	$setting          = mediatheque_get_allowed_mime_types();
	$mimes            = mediatheque_get_mime_types();
	$printed_mime     = array();

	foreach ( $translated_types as $k_type => $translated_type ) {
		if ( 'code' === $k_type ) {
			continue;
		}
		?>
		<fieldset style="border: solid 1px #ccc; margin-bottom: 1em">
			<legend style="padding: 0 1em">
				<label for="mediatheque-selectall-<?php echo esc_attr( $k_type ); ?>">
					<input id="mediatheque-selectall-<?php echo esc_attr( $k_type ); ?>" type="checkbox" class="mediatheque-selectall" data-mime-type="<?php echo esc_attr( $k_type ); ?>"> <?php echo esc_html( $translated_type ); ?>
				</label>
			</legend>

			<ul style="margin: 1em 2em 1em;">

			<?php foreach ( $types[ $k_type ] as $wp_type ) {
				$ext_mime = wp_check_filetype( '.' . $wp_type, $mimes );

				if ( $ext_mime['type'] && ! in_array( $ext_mime['type'], $printed_mime, true ) ) {
					array_push( $printed_mime, $ext_mime['type'] );
					$sub_type_id = str_replace( $k_type . '/', '', $ext_mime['type'] );
					?>
					<li>
						<label for="mediatheque_mime_type-<?php echo esc_attr( $sub_type_id ); ?>">
							<input id="mediatheque_mime_type-<?php echo esc_attr( $sub_type_id ); ?>" type="checkbox" name="mediatheque_mime_types[]" data-mime-type="<?php echo esc_attr( $k_type );?>" value="<?php echo esc_attr( $ext_mime['type'] );?>" <?php checked( true, in_array( $ext_mime['type'], $setting, true ) );?>> <?php echo esc_html( $ext_mime['type'] ) ;?>
						</label>
					</li>
					<?php
				}
			} ?>

		</fieldset>
		<?php
	}
}

/**
 * Sanitizes the Allowed Mime Types Setting field before saving it to DB.
 *
 * @since 1.0.0.
 *
 * @param array $mime_types The new Allowed Mime Types.
 * @param array             The sanitized Allowed Mime Types.
 */
function mediatheque_sanitize_mime_types( $mime_types ) {
	if ( ! is_array( $mime_types ) ) {
		return array();
	}

	return array_map( 'sanitize_text_field', $mime_types );
}

/**
 * Personal Avatar Feature Setting field callback.
 *
 * @since 1.0.0.
 */
function mediatheque_settings_field_avatars() {
	$use_avatars = mediatheque_use_personal_avatar();

	?>
	<input name="mediatheque_personal_avatar" id="mediatheque-personal-avatar" type="checkbox" value="1" <?php checked( mediatheque_use_personal_avatar() ); ?> />
	<label for="mediatheque-personal-avatar"><?php esc_html_e( 'Autoriser les utilisateurs à choisir un de leurs media comme image de profil.', 'mediatheque' ); ?></label>
	<?php
}
