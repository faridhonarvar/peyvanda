<?php
/**
 * Blog posts source.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Sources;

defined( 'ABSPATH' ) || exit;

/**
 * Items from the `post` post type.
 */
final class PostSource extends Source {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'post';
	}

	/**
	 * {@inheritDoc}
	 */
	public function label() {
		return __( 'Posts', 'peyvanda' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function category_taxonomy() {
		return 'category';
	}

	/**
	 * {@inheritDoc}
	 */
	public function tag_taxonomy() {
		return 'post_tag';
	}
}
