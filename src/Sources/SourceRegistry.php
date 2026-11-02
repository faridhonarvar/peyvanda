<?php
/**
 * Registry of item sources.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Sources;

defined( 'ABSPATH' ) || exit;

/**
 * Holds available sources.
 */
final class SourceRegistry {

	/**
	 * Registered sources keyed by ID.
	 *
	 * @var Source[]
	 */
	private $sources = array();

	/**
	 * Registers the built-in sources and lets add-ons add more.
	 *
	 * @return void
	 */
	public function init() {
		$this->add( new PostSource() );
		$this->add( new ProductSource() );

		/**
		 * Register additional sources.
		 *
		 * @param SourceRegistry $registry Registry; call `$registry->add( $source )`.
		 */
		do_action( 'peyvanda_register_sources', $this );
	}

	/**
	 * Adds a source.
	 *
	 * @param Source $source Source instance.
	 * @return void
	 */
	public function add( Source $source ) {
		$this->sources[ $source->id() ] = $source;
	}

	/**
	 * Source by ID, only if available.
	 *
	 * @param string $id Source ID.
	 * @return Source|null
	 */
	public function get( $id ) {
		if ( isset( $this->sources[ $id ] ) && $this->sources[ $id ]->is_available() ) {
			return $this->sources[ $id ];
		}
		return null;
	}

	/**
	 * Source handling a post type.
	 *
	 * @param string $post_type Post type.
	 * @return Source|null
	 */
	public function for_post_type( $post_type ) {
		foreach ( $this->available() as $source ) {
			if ( $source->post_type() === $post_type ) {
				return $source;
			}
		}
		return null;
	}

	/**
	 * All available sources.
	 *
	 * @return Source[]
	 */
	public function available() {
		return array_filter(
			$this->sources,
			static function ( Source $source ) {
				return $source->is_available();
			}
		);
	}
}
