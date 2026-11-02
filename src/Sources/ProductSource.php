<?php
/**
 * WooCommerce products source.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Sources;

use Peyvanda\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Items from WooCommerce products. Free version shows image, name and price.
 */
final class ProductSource extends Source {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'product';
	}

	/**
	 * {@inheritDoc}
	 */
	public function label() {
		return __( 'Products', 'peyvanda' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category_taxonomy() {
		return 'product_cat';
	}

	/**
	 * {@inheritDoc}
	 */
	public function tag_taxonomy() {
		return 'product_tag';
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available() {
		return function_exists( 'wc_get_product' ) && post_type_exists( 'product' );
	}

	/**
	 * Hides catalog-hidden products and, optionally, out-of-stock ones.
	 *
	 * @return array<string,mixed>
	 */
	public function base_query_args() {
		$hidden = array( 'exclude-from-catalog' );
		if ( Settings::get( 'hide_out_of_stock' ) ) {
			$hidden[] = 'outofstock';
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Standard WooCommerce visibility filter.
			'tax_query' => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => $hidden,
					'operator' => 'NOT IN',
				),
			),
		);
	}

	/**
	 * Fallback: best sellers.
	 *
	 * @return array<string,mixed>
	 */
	public function fallback_query_args() {
		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Cached; runs rarely.
			'meta_key' => 'total_sales',
			'orderby'  => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
		);
	}

	/**
	 * Builds the item data, adding the price and short description.
	 *
	 * @param \WP_Post $post Item post.
	 * @return array<string,mixed>
	 */
	public function item( \WP_Post $post ) {
		$item    = parent::item( $post );
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post ) : null;
		if ( $product ) {
			$item['price']   = (string) $product->get_price_html();
			$item['excerpt'] = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 20, '…' );
		}
		return $item;
	}
}
