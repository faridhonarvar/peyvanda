<?php
/**
 * General (site-wide) settings.
 *
 * @package Peyvanda
 */

namespace Peyvanda;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and sanitizes the `peyvanda_settings` option.
 */
final class Settings {

	const OPTION = 'peyvanda_settings';

	/**
	 * Default values.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'metabox_post_types'  => array( 'post', 'product' ),
			'fallback'            => 'recent',
			'fill_manual'         => true,
			'hide_out_of_stock'   => true,
			'delete_on_uninstall' => false,
		);
	}

	/**
	 * Returns all settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Returns a single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Sanitizes raw settings input.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$types = isset( $input['metabox_post_types'] ) && is_array( $input['metabox_post_types'] ) ? $input['metabox_post_types'] : array();

		$out['metabox_post_types']  = array_values( array_filter( array_map( 'sanitize_key', $types ), 'post_type_exists' ) );
		$out['fallback']            = ( isset( $input['fallback'] ) && 'none' === $input['fallback'] ) ? 'none' : 'recent';
		$out['fill_manual']         = ! empty( $input['fill_manual'] );
		$out['hide_out_of_stock']   = ! empty( $input['hide_out_of_stock'] );
		$out['delete_on_uninstall'] = ! empty( $input['delete_on_uninstall'] );

		return $out;
	}
}
