<?php
/**
 * Builds the HTML of a box.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Render;

use Peyvanda\Boxes\Box;
use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Designs\Design;
use Peyvanda\Designs\DesignRegistry;
use Peyvanda\Manual\ManualMetabox;
use Peyvanda\Matching\ItemSelector;
use Peyvanda\Matching\RelatedCache;
use Peyvanda\Settings;
use Peyvanda\Sources\Source;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves items for a box and renders its design template.
 */
final class Renderer {

	/**
	 * Boxes.
	 *
	 * @var BoxRepository
	 */
	private $boxes;

	/**
	 * Sources.
	 *
	 * @var SourceRegistry
	 */
	private $sources;

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
	 * @param SourceRegistry $sources Sources.
	 * @param DesignRegistry $designs Designs.
	 */
	public function __construct( BoxRepository $boxes, SourceRegistry $sources, DesignRegistry $designs ) {
		$this->boxes   = $boxes;
		$this->sources = $sources;
		$this->designs = $designs;
	}

	/**
	 * Box repository.
	 *
	 * @return BoxRepository
	 */
	public function boxes() {
		return $this->boxes;
	}

	/**
	 * Renders an active box by ID.
	 *
	 * @param int           $box_id Box ID.
	 * @param \WP_Post|null $post   Context post (defaults to the current post).
	 * @return string
	 */
	public function render_id( $box_id, $post = null ) {
		$box = $this->boxes->get_active( (int) $box_id );
		return $box ? $this->render( $box, $post ) : '';
	}

	/**
	 * Renders a box.
	 *
	 * @param Box           $box  Box.
	 * @param \WP_Post|null $post Context post (defaults to the current post).
	 * @return string
	 */
	public function render( Box $box, $post = null ) {
		$post   = $post instanceof \WP_Post ? $post : get_post();
		$source = $this->sources->get( (string) $box->get( 'source' ) );
		$design = $this->designs->get( (string) $box->get( 'design' ) );

		if ( ! $source || ! $design || ! is_readable( $design->template() ) ) {
			return '';
		}

		/**
		 * Whether a box should be rendered.
		 *
		 * @param bool          $render Render or not.
		 * @param Box           $box    Box.
		 * @param \WP_Post|null $post   Context post.
		 */
		if ( ! apply_filters( 'peyvanda_should_render', true, $box, $post ) ) {
			return '';
		}

		$items = $this->items( $box, $source, $design, $post );
		if ( ! $items ) {
			return '';
		}

		Assets::enqueue_design( $design );

		return $this->template( $box, $design, $items, $post );
	}

	/**
	 * Resolves the item list (manual → automatic → fallback), then lets add-ons change it.
	 *
	 * @param Box           $box    Box.
	 * @param Source        $source Source.
	 * @param Design        $design Design.
	 * @param \WP_Post|null $post   Context post.
	 * @return array<int,array<string,mixed>>
	 */
	public function items( Box $box, Source $source, Design $design, $post ) {
		$manual    = array();
		$automatic = array();
		$exclude   = array();

		if ( $post instanceof \WP_Post ) {
			$exclude[] = $post->ID;

			/**
			 * Filters manually picked IDs for a box on a post.
			 *
			 * @param int[]    $ids  IDs from `_peyvanda_manual_items`.
			 * @param Box      $box  Box.
			 * @param \WP_Post $post Post.
			 */
			$manual    = (array) apply_filters( 'peyvanda_manual_items', ManualMetabox::get_ids( $post->ID ), $box, $post );
			$automatic = RelatedCache::get( $post, $source, (string) $box->get( 'match_by' ) );
		}

		$fallback = static function () use ( $source ) {
			return 'none' === Settings::get( 'fallback' ) ? array() : RelatedCache::fallback( $source );
		};

		$ids = ItemSelector::select(
			$manual,
			$automatic,
			$fallback,
			$exclude,
			$box->count(),
			(bool) Settings::get( 'fill_manual' ),
			function ( $id ) use ( $source ) {
				return $this->is_valid( $id, $source );
			}
		);

		if ( $ids ) {
			_prime_post_caches( $ids, false, true );
		}

		$items = array();
		foreach ( $ids as $id ) {
			$item_post = get_post( $id );
			if ( $item_post ) {
				$item          = $source->item( $item_post );
				$item['image'] = $item['image_id'] ? $design->image_size() : '';
				$items[]       = $item;
			}
		}

		/**
		 * Filters the final list of items of a box.
		 *
		 * @param array         $items Items (`id`, `type`, `title`, `url`, `image_id`, `excerpt`, `price`).
		 * @param Box           $box   Box.
		 * @param \WP_Post|null $post  Context post.
		 */
		return (array) apply_filters( 'peyvanda_items', $items, $box, $post );
	}

	/**
	 * Whether an item may be shown: published, public, not password protected, right type, in stock.
	 *
	 * @param int    $id     Item ID.
	 * @param Source $source Source.
	 * @return bool
	 */
	private function is_valid( $id, Source $source ) {
		$item = get_post( $id );
		if ( ! $item || 'publish' !== $item->post_status || '' !== $item->post_password || $item->post_type !== $source->post_type() ) {
			return false;
		}

		if ( 'product' === $item->post_type && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $item );
			if ( ! $product || 'hidden' === $product->get_catalog_visibility() || 'search' === $product->get_catalog_visibility() ) {
				return false;
			}
			if ( Settings::get( 'hide_out_of_stock' ) && ! $product->is_in_stock() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Includes the design template.
	 *
	 * @param Box                            $box    Box.
	 * @param Design                         $design Design.
	 * @param array<int,array<string,mixed>> $items  Items.
	 * @param \WP_Post|null                  $post   Context post.
	 * @return string
	 */
	private function template( Box $box, Design $design, array $items, $post ) {
		$style   = (array) $box->get( 'style' );
		$values  = $design->values( isset( $style[ $design->slug ] ) && is_array( $style[ $design->slug ] ) ? $style[ $design->slug ] : array() );
		$heading = $box->heading();

		$root = sprintf(
			'class="%1$s" style="%2$s" aria-label="%3$s" data-pvd-box="%4$d"',
			esc_attr( 'pvd-box pvd-box--' . $design->slug ),
			esc_attr( $design->css_variables( $values ) ),
			esc_attr( '' !== $heading ? $heading : __( 'Related content', 'peyvanda' ) ),
			$box->id
		);

		$peyvanda_args = array(
			'box'             => $box,
			'design'          => $design,
			'items'           => $items,
			'post'            => $post,
			'heading'         => $heading,
			'settings'        => $values,
			'root_attributes' => $root,
			'item_html'       => static function ( $html, $item ) use ( $box ) {
				/**
				 * Filters the HTML of one item.
				 *
				 * @param string $html Item HTML.
				 * @param array  $item Item data.
				 * @param Box    $box  Box.
				 */
				return (string) apply_filters( 'peyvanda_item_html', $html, $item, $box );
			},
			'image'           => static function ( $item ) {
				if ( empty( $item['image_id'] ) ) {
					return '';
				}
				// Decorative: the linked title right next to it already names the item.
				return wp_get_attachment_image(
					(int) $item['image_id'],
					(string) $item['image'],
					false,
					array(
						'class'    => 'pvd-box__img',
						'alt'      => '',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
			},
		);

		ob_start();
		include $design->template();
		// Drop template indentation so later wpautop() runs cannot inject <p>/<br> into the box.
		return trim( (string) preg_replace( '/>\s*\n\s*</', '><', (string) ob_get_clean() ) );
	}
}
