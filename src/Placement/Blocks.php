<?php
/**
 * Gutenberg blocks: `peyvanda/box` and `peyvanda/marker`.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Placement;

use Peyvanda\Render\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the blocks from their block.json files.
 */
final class Blocks {

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers both blocks.
	 *
	 * @return void
	 */
	public function register() {
		$box = register_block_type(
			PEYVANDA_DIR . 'blocks/box',
			array( 'render_callback' => array( $this, 'render_box' ) )
		);

		$marker = register_block_type(
			PEYVANDA_DIR . 'blocks/marker',
			array( 'render_callback' => array( $this, 'render_marker' ) )
		);

		foreach ( array( $box, $marker ) as $block ) {
			if ( $block instanceof \WP_Block_Type ) {
				foreach ( (array) $block->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'peyvanda', PEYVANDA_DIR . 'languages' );
				}
			}
		}
	}

	/**
	 * Renders the box block.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_box( $attributes ) {
		$id = isset( $attributes['boxId'] ) ? absint( $attributes['boxId'] ) : 0;
		return $id ? $this->renderer->render_id( $id ) : '';
	}

	/**
	 * Renders the marker: an invisible placeholder replaced by ContentInserter.
	 *
	 * @return string
	 */
	public function render_marker() {
		return ContentInserter::MARKER;
	}
}
