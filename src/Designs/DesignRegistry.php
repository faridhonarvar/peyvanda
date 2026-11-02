<?php
/**
 * Registry of designs.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Designs;

defined( 'ABSPATH' ) || exit;

/**
 * Holds built-in and add-on designs.
 */
final class DesignRegistry {

	const DEFAULT_SLUG = 'thumb-list';

	/**
	 * Designs keyed by slug.
	 *
	 * @var Design[]
	 */
	private $designs = array();

	/**
	 * Registers built-in designs and lets add-ons add more.
	 *
	 * @return void
	 */
	public function init() {
		$builtin = array(
			'thumb-list'      => __( 'List with thumbnails', 'peyvanda' ),
			'horizontal-card' => __( 'Horizontal cards', 'peyvanda' ),
			'minimal-list'    => __( 'Minimal text list', 'peyvanda' ),
		);

		foreach ( $builtin as $slug => $label ) {
			$this->add( new Design( $slug, $label, PEYVANDA_DIR . 'src/Designs/' . $slug, PEYVANDA_URL . 'src/Designs/' . $slug ) );
		}

		/**
		 * Register additional designs.
		 *
		 * @param DesignRegistry $registry Registry; call `$registry->add( new Design( … ) )`.
		 */
		do_action( 'peyvanda_register_designs', $this );
	}

	/**
	 * Adds a design.
	 *
	 * @param Design $design Design.
	 * @return void
	 */
	public function add( Design $design ) {
		$this->designs[ $design->slug ] = $design;
	}

	/**
	 * Design by slug, or the default design.
	 *
	 * @param string $slug Slug.
	 * @return Design|null
	 */
	public function get( $slug ) {
		if ( isset( $this->designs[ $slug ] ) ) {
			return $this->designs[ $slug ];
		}
		return isset( $this->designs[ self::DEFAULT_SLUG ] ) ? $this->designs[ self::DEFAULT_SLUG ] : null;
	}

	/**
	 * All designs.
	 *
	 * @return Design[]
	 */
	public function all() {
		return $this->designs;
	}
}
