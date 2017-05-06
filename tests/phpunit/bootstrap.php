<?php

require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';

function _bootstrap_mediatheque() {
	// load WP Idea Stream
	require dirname( __FILE__ ) . '/../../mediatheque.php';

	add_action( 'init', '_install_mediatheque', 20 );
}
tests_add_filter( 'muplugins_loaded', '_bootstrap_mediatheque' );

function _install_mediatheque() {
	delete_network_option( 0, 'mediatheque_version' );
	delete_network_option( 0, 'mediatheque_capability' );
	delete_network_option( 0, 'mediatheque_mime_types' );

	$directory_id = mediatheque_get_user_media_type_id( 'mediatheque-directory' );
	if ( $directory_id ) {
		wp_delete_term( $directory_id, 'user_media_types' );
	}

	$file_id = mediatheque_get_user_media_type_id( 'mediatheque-file' );
	if ( $file_id ) {
		wp_delete_term( $directory_id, 'user_media_types' );
	}

	mediatheque_upgrade();
}

require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
require_once dirname( __FILE__ ) . '/factory.php';
