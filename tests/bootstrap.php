<?php
/**
 * Unit test bootstrap: loads only WordPress's HTML API (no database needed).
 *
 * Set WP_CORE_DIR to a WordPress checkout, e.g. WP_CORE_DIR=/path/to/wordpress composer test.
 *
 * @package Peyvanda
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'ABSPATH', rtrim( (string) getenv( 'WP_CORE_DIR' ), '/' ) . '/' );

if ( ! is_dir( ABSPATH . 'wp-includes/html-api' ) ) {
	fwrite( STDERR, "Set WP_CORE_DIR to a WordPress checkout (6.4+).\n" );
	exit( 1 );
}

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}
if ( ! function_exists( '_doing_it_wrong' ) ) {
	function _doing_it_wrong() {}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

$peyvanda_html_api = ABSPATH . 'wp-includes/html-api/';
foreach ( array( 'class-wp-token-map.php' ) as $peyvanda_file ) {
	if ( is_readable( ABSPATH . 'wp-includes/' . $peyvanda_file ) ) {
		require_once ABSPATH . 'wp-includes/' . $peyvanda_file;
	}
}
foreach ( array( 'html5-named-character-references.php', 'class-wp-html-attribute-token.php', 'class-wp-html-span.php', 'class-wp-html-text-replacement.php', 'class-wp-html-decoder.php', 'class-wp-html-tag-processor.php' ) as $peyvanda_file ) {
	if ( is_readable( $peyvanda_html_api . $peyvanda_file ) ) {
		require_once $peyvanda_html_api . $peyvanda_file;
	}
}
