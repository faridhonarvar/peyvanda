<?php
/**
 * Loads and saves boxes.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Boxes;

defined( 'ABSPATH' ) || exit;

/**
 * Data access for `peyvanda_box` posts.
 */
final class BoxRepository {

	/**
	 * Per-request cache of published boxes.
	 *
	 * @var Box[]|null
	 */
	private $published = null;

	/**
	 * Loads a single box.
	 *
	 * @param int $id Box ID.
	 * @return Box|null
	 */
	public function get( $id ) {
		$post = get_post( (int) $id );
		if ( ! $post || BoxPostType::POST_TYPE !== $post->post_type ) {
			return null;
		}
		return self::from_post( $post );
	}

	/**
	 * Builds a box from its post.
	 *
	 * @param \WP_Post $post Box post.
	 * @return Box
	 */
	public static function from_post( \WP_Post $post ) {
		$settings = get_post_meta( $post->ID, Box::META, true );
		return new Box( $post->ID, $post->post_title, is_array( $settings ) ? $settings : array() );
	}

	/**
	 * Returns all published boxes (active or not), ordered by ID.
	 *
	 * @return Box[]
	 */
	public function published() {
		if ( null === $this->published ) {
			$posts = get_posts(
				array(
					'post_type'        => BoxPostType::POST_TYPE,
					'post_status'      => 'publish',
					'posts_per_page'   => 100,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				)
			);

			$this->published = array_map( array( __CLASS__, 'from_post' ), $posts );
		}
		return $this->published;
	}

	/**
	 * Returns a published, active box or null.
	 *
	 * @param int $id Box ID.
	 * @return Box|null
	 */
	public function get_active( $id ) {
		foreach ( $this->published() as $box ) {
			if ( $box->id === (int) $id ) {
				return $box->is_active() ? $box : null;
			}
		}
		return null;
	}

	/**
	 * Active boxes configured for automatic insertion on a post type.
	 *
	 * @param string $post_type Post type of the current post.
	 * @return Box[]
	 */
	public function auto_boxes_for( $post_type ) {
		$boxes = array();
		foreach ( $this->published() as $box ) {
			if ( $box->is_active() && $box->get( 'auto_insert' ) && in_array( $post_type, (array) $box->get( 'show_on' ), true ) ) {
				$boxes[] = $box;
			}
		}

		/**
		 * Maximum number of automatically inserted boxes per post. The free plugin inserts one.
		 *
		 * @param int    $max       Maximum boxes.
		 * @param string $post_type Current post type.
		 */
		$max = (int) apply_filters( 'peyvanda_max_auto_boxes', 1, $post_type );

		return array_slice( $boxes, 0, max( 0, $max ) );
	}

	/**
	 * Clears the per-request cache.
	 *
	 * @return void
	 */
	public function flush() {
		$this->published = null;
	}

	/**
	 * Creates a starter box on first activation when none exists.
	 *
	 * @return void
	 */
	public static function maybe_create_starter_box() {
		$existing = get_posts(
			array(
				'post_type'      => BoxPostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( $existing ) {
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => BoxPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => __( 'Read more (inside posts)', 'peyvanda' ),
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, Box::META, Box::defaults() );
		}
	}
}
