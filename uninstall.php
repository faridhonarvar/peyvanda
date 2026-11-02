<?php
/**
 * Removes Peyvanda data on uninstall, only if the admin opted in.
 *
 * @package Peyvanda
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$peyvanda_settings = get_option( 'peyvanda_settings', array() );

if ( ! is_array( $peyvanda_settings ) || empty( $peyvanda_settings['delete_on_uninstall'] ) ) {
	return;
}

$peyvanda_boxes = get_posts(
	array(
		'post_type'      => 'peyvanda_box',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
foreach ( $peyvanda_boxes as $peyvanda_box_id ) {
	wp_delete_post( $peyvanda_box_id, true );
}

delete_post_meta_by_key( '_peyvanda_manual_items' );
delete_post_meta_by_key( '_peyvanda_related_cache' );
delete_metadata( 'user', 0, 'peyvanda_pro_card_dismissed', '', true );
delete_option( 'peyvanda_settings' );

foreach ( array( 'post', 'product' ) as $peyvanda_source ) {
	delete_transient( 'peyvanda_fallback_' . $peyvanda_source );
}
