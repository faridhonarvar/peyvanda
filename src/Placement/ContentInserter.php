<?php
/**
 * Automatic insertion into `the_content`.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Placement;

use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Render\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Inserts automatic boxes after paragraph N, or where the author placed a marker block.
 */
final class ContentInserter {

	const MARKER = '<!--pvd-marker-->';

	/**
	 * Boxes.
	 *
	 * @var BoxRepository
	 */
	private $boxes;

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Re-entrancy guard.
	 *
	 * @var bool
	 */
	private $running = false;

	/**
	 * Constructor.
	 *
	 * @param BoxRepository $boxes    Boxes.
	 * @param Renderer      $renderer Renderer.
	 */
	public function __construct( BoxRepository $boxes, Renderer $renderer ) {
		$this->boxes    = $boxes;
		$this->renderer = $renderer;
	}

	/**
	 * Hooks. Priority 20 runs after blocks (9), wpautop (10) and shortcodes (11).
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'the_content', array( $this, 'filter' ), 20 );
	}

	/**
	 * Whether automatic insertion applies to the content being filtered.
	 *
	 * @return bool
	 */
	private function applies() {
		if ( $this->running || is_admin() || is_feed() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return false;
		}
		$post = get_post();
		return $post instanceof \WP_Post && (int) get_queried_object_id() === (int) $post->ID && ! post_password_required( $post );
	}

	/**
	 * Filters the content.
	 *
	 * @param string $content Content HTML.
	 * @return string
	 */
	public function filter( $content ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}

		if ( ! $this->applies() ) {
			return false === strpos( $content, self::MARKER ) ? $content : str_replace( self::MARKER, '', $content );
		}

		$this->running = true;
		$post          = get_post();

		foreach ( $this->boxes->auto_boxes_for( $post->post_type ) as $box ) {
			// Skip when the author already placed this box with a shortcode or block.
			if ( false !== strpos( $content, 'data-pvd-box="' . $box->id . '"' ) ) {
				continue;
			}

			$html = $this->renderer->render( $box, $post );
			if ( '' === $html ) {
				continue;
			}

			$marker = strpos( $content, self::MARKER );
			if ( false !== $marker ) {
				$content = substr_replace( $content, $html, $marker, strlen( self::MARKER ) );
				continue;
			}

			$content = ParagraphInserter::insert( $content, $html, (int) $box->get( 'paragraph', 3 ), 'hide' !== $box->get( 'short_content' ) );
		}

		$this->running = false;

		return str_replace( self::MARKER, '', $content );
	}
}
