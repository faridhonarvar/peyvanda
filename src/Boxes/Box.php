<?php
/**
 * Box model.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Boxes;

defined( 'ABSPATH' ) || exit;

/**
 * A saved box configuration (stored as a `peyvanda_box` post + `_peyvanda_settings` meta).
 */
final class Box {

	const META = '_peyvanda_settings';

	/**
	 * Box (post) ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Admin name of the box.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Settings merged with defaults.
	 *
	 * @var array<string,mixed>
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param int                 $id       Box ID.
	 * @param string              $name     Admin name.
	 * @param array<string,mixed> $settings Saved settings.
	 */
	public function __construct( $id, $name, array $settings ) {
		$this->id       = (int) $id;
		$this->name     = (string) $name;
		$this->settings = self::merge_defaults( $settings );
	}

	/**
	 * Default settings for a new box.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'active'        => true,
			'heading'       => __( 'Read more', 'peyvanda' ),
			'source'        => 'post',
			'count'         => 3,
			'match_by'      => 'category_tag',
			'auto_insert'   => true,
			'paragraph'     => 3,
			'short_content' => 'end',
			'show_on'       => array( 'post' ),
			'design'        => 'thumb-list',
			'style'         => array(),
		);
	}

	/**
	 * Merges saved settings with defaults (one level deep for `style`).
	 *
	 * @param array<string,mixed> $settings Saved settings.
	 * @return array<string,mixed>
	 */
	private static function merge_defaults( array $settings ) {
		$merged = array_merge( self::defaults(), $settings );
		if ( ! is_array( $merged['style'] ) ) {
			$merged['style'] = array();
		}
		if ( ! is_array( $merged['show_on'] ) ) {
			$merged['show_on'] = array();
		}
		return $merged;
	}

	/**
	 * Returns a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default_value Value used when the key is missing.
	 * @return mixed
	 */
	public function get( $key, $default_value = null ) {
		return array_key_exists( $key, $this->settings ) ? $this->settings[ $key ] : $default_value;
	}

	/**
	 * All settings.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return $this->settings;
	}

	/**
	 * Whether the box is enabled.
	 *
	 * @return bool
	 */
	public function is_active() {
		return (bool) $this->get( 'active' );
	}

	/**
	 * Number of items to show.
	 *
	 * @return int
	 */
	public function count() {
		return max( 1, min( 12, (int) $this->get( 'count', 3 ) ) );
	}

	/**
	 * Heading text shown above the items.
	 *
	 * @return string
	 */
	public function heading() {
		return (string) $this->get( 'heading', '' );
	}
}
