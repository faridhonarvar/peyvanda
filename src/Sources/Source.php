<?php
/**
 * Base class for item sources.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Sources;

defined( 'ABSPATH' ) || exit;

/**
 * A source supplies items of one post type. Add-ons can register more via `peyvanda_register_sources`.
 */
abstract class Source {

	/**
	 * Unique source ID (usually the post type).
	 *
	 * @return string
	 */
	abstract public function id();

	/**
	 * Human readable label.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Post type queried by this source.
	 *
	 * @return string
	 */
	public function post_type() {
		return $this->id();
	}

	/**
	 * Category-like taxonomy used for matching.
	 *
	 * @return string
	 */
	abstract public function category_taxonomy();

	/**
	 * Tag-like taxonomy used for matching.
	 *
	 * @return string
	 */
	abstract public function tag_taxonomy();

	/**
	 * Whether this source can be used on this site.
	 *
	 * @return bool
	 */
	public function is_available() {
		return post_type_exists( $this->post_type() );
	}

	/**
	 * Extra WP_Query arguments applied to every query for this source.
	 *
	 * @return array<string,mixed>
	 */
	public function base_query_args() {
		return array();
	}

	/**
	 * WP_Query arguments for the fallback (used when matching finds too few items).
	 *
	 * @return array<string,mixed>
	 */
	public function fallback_query_args() {
		return array(
			'orderby' => 'date',
			'order'   => 'DESC',
		);
	}

	/**
	 * Builds the item data passed to design templates.
	 *
	 * @param \WP_Post $post Item post.
	 * @return array<string,mixed>
	 */
	public function item( \WP_Post $post ) {
		return array(
			'id'       => $post->ID,
			'type'     => $post->post_type,
			'title'    => get_the_title( $post ),
			'url'      => get_permalink( $post ),
			'image_id' => (int) get_post_thumbnail_id( $post ),
			'excerpt'  => self::plain_excerpt( $post ),
			'price'    => '',
		);
	}

	/**
	 * Builds a short plain-text excerpt without running `the_content` filters (avoids recursion).
	 *
	 * @param \WP_Post $post Post.
	 * @param int      $words Number of words.
	 * @return string
	 */
	public static function plain_excerpt( \WP_Post $post, $words = 20 ) {
		$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		$text = excerpt_remove_blocks( $text );
		$text = wp_strip_all_tags( strip_shortcodes( $text ) );
		return wp_trim_words( $text, $words, '…' );
	}
}
