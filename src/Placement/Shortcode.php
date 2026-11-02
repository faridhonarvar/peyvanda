<?php
/**
 * `[peyvanda id="12"]` shortcode.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Placement;

use Peyvanda\Render\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a box anywhere shortcodes run.
 */
final class Shortcode {

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
	 * Registers the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'peyvanda', array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'peyvanda' );
		return $this->renderer->render_id( absint( $atts['id'] ) );
	}
}
