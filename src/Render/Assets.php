<?php
/**
 * Front-end styles. No front-end JavaScript is ever loaded.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Render;

use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Designs\Design;
use Peyvanda\Designs\DesignRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Loads design CSS only on pages that show a box.
 */
final class Assets {

	const BASE_HANDLE = 'peyvanda-base';

	/**
	 * Boxes.
	 *
	 * @var BoxRepository
	 */
	private $boxes;

	/**
	 * Designs.
	 *
	 * @var DesignRegistry
	 */
	private $designs;

	/**
	 * Constructor.
	 *
	 * @param BoxRepository  $boxes   Boxes.
	 * @param DesignRegistry $designs Designs.
	 */
	public function __construct( BoxRepository $boxes, DesignRegistry $designs ) {
		$this->boxes   = $boxes;
		$this->designs = $designs;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_for_current_page' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_in_editor' ) );
	}

	/**
	 * Enqueues a design's stylesheet (and the shared base). Safe to call late: WordPress
	 * then prints it in the footer.
	 *
	 * @param Design $design Design.
	 * @return void
	 */
	public static function enqueue_design( Design $design ) {
		if ( ! wp_style_is( self::BASE_HANDLE, 'registered' ) ) {
			wp_register_style( self::BASE_HANDLE, PEYVANDA_URL . 'assets/css/base.css', array(), PEYVANDA_VERSION );
		}
		if ( ! wp_style_is( $design->handle(), 'registered' ) ) {
			wp_register_style( $design->handle(), $design->url . 'style.css', array( self::BASE_HANDLE ), PEYVANDA_VERSION );
		}
		wp_enqueue_style( $design->handle() );
	}

	/**
	 * Predicts which boxes will appear on the current singular page so their CSS lands in <head>.
	 *
	 * @return void
	 */
	public function enqueue_for_current_page() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$boxes = $this->boxes->auto_boxes_for( $post->post_type );
		foreach ( self::box_ids_in_content( $post ) as $id ) {
			$box = $this->boxes->get_active( $id );
			if ( $box ) {
				$boxes[] = $box;
			}
		}

		foreach ( $boxes as $box ) {
			$design = $this->designs->get( (string) $box->get( 'design' ) );
			if ( $design ) {
				self::enqueue_design( $design );
			}
		}
	}

	/**
	 * Box IDs referenced by shortcodes or blocks in a post's content.
	 *
	 * @param \WP_Post $post Post.
	 * @return int[]
	 */
	public static function box_ids_in_content( \WP_Post $post ) {
		$ids     = array();
		$content = (string) $post->post_content;

		if ( false !== strpos( $content, '[peyvanda' ) && preg_match_all( '/' . get_shortcode_regex( array( 'peyvanda' ) ) . '/', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$atts = shortcode_parse_atts( $match[3] );
				if ( is_array( $atts ) && ! empty( $atts['id'] ) ) {
					$ids[] = absint( $atts['id'] );
				}
			}
		}

		if ( false !== strpos( $content, '<!-- wp:peyvanda/box' ) ) {
			$ids = array_merge( $ids, self::box_ids_in_blocks( parse_blocks( $content ) ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Recursively collects `boxId` attributes of `peyvanda/box` blocks.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 * @return int[]
	 */
	private static function box_ids_in_blocks( array $blocks ) {
		$ids = array();
		foreach ( $blocks as $block ) {
			if ( 'peyvanda/box' === $block['blockName'] && ! empty( $block['attrs']['boxId'] ) ) {
				$ids[] = absint( $block['attrs']['boxId'] );
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$ids = array_merge( $ids, self::box_ids_in_blocks( $block['innerBlocks'] ) );
			}
		}
		return $ids;
	}

	/**
	 * Loads all design styles inside the block editor for the server-side preview.
	 *
	 * @return void
	 */
	public function enqueue_in_editor() {
		if ( ! is_admin() ) {
			return;
		}
		foreach ( $this->designs->all() as $design ) {
			self::enqueue_design( $design );
		}
	}
}
