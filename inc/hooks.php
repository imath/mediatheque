<?php
/**
 * MediaThèque hooks.
 *
 * @package mediatheque\inc
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Boot the Admin
if ( is_admin() ) {
	add_action( 'plugins_loaded', array( 'MediaTheque_Admin', 'start' ), 10 );
}

// Enqueue User Media
add_action( 'wp_enqueue_media',      'mediatheque_enqueue_user_media'        );
add_action( 'print_media_templates', 'mediatheque_print_containers',   10, 0 );

// Load translations
add_action( 'plugins_loaded', 'mediatheque_load_textdomain', 9 );

// Register objects
add_action( 'init', 'mediatheque_register_objects', 12 );

// Map capabilities
add_filter( 'map_meta_cap', 'mediatheque_map_meta_caps', 10, 4 );

// Add a new query parameter to Users rest request
add_filter( 'rest_user_collection_params', 'mediatheque_additionnal_user_rest_param', 10, 1 );
add_filter( 'rest_user_query',             'mediatheque_rest_user_query',             10, 2 );

// Set the single User Media Templates
add_action( 'parse_query',                    'mediatheque_parse_query'           );
add_filter( 'embed_template',                 'mediatheque_embed_template'        );
add_action( 'mediatheque_embed_content_meta', 'mediatheque_embed_download_button' );
add_action( 'mediatheque_embed_content_meta', 'print_embed_sharing_button'        );
add_action( 'enqueue_embed_scripts',          'mediatheque_embed_style'           );

add_filter( 'oembed_request_post_id', 'mediatheque_oembed_user_media_id',  9, 2 );
add_filter( 'oembed_dataparse',       'mediatheque_oembed_pre_dataparse',  9, 3 );
add_filter( 'oembed_dataparse',       'mediatheque_oembed_dataparse',     11, 3 );
add_filter( 'embed_maybe_make_link',  'mediatheque_maybe_hide_link',      10, 2 );

// Check if we need to add a specific The User Media UI
add_filter( 'wp_editor_settings', 'mediatheque_editor_settings',    10, 2 );
add_filter( 'the_editor',         'mediatheque_the_editor',         10, 1 );
add_filter( 'wp_link_query_args', 'mediatheque_wp_link_query_args', 10, 1 );

// Clear cached user media.
add_action( 'mediatheque_delete_media', 'mediatheque_clear_cached_media', 10, 1 );
add_action( 'mediatheque_move_media',   'mediatheque_clear_cached_media', 10, 1 );

// Remove all user's data on when the user is removed from the site.
if ( is_multisite() ) {
	add_action( 'wpmu_delete_user', 'mediatheque_delete_user_data', 10, 2 );
} else {
	add_action( 'delete_user', 'mediatheque_delete_user_data', 10, 2 );
}

add_action( 'enqueue_block_editor_assets', 'mediatheque_block_editor'     );
add_action( 'enqueue_block_assets',        'mediatheque_block_edit_style' );
