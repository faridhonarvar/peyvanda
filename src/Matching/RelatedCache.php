<?php
/**
 * Pre-computes related items when a post is saved.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Matching;

use Peyvanda\Boxes\BoxPostType;
use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Sources\Source;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Stores related IDs in `_peyvanda_related_cache` so page views never run matching queries.
 */
final class RelatedCache {

	const META         = '_peyvanda_related_cache';
	const MAX_IDS      = 12;
	const MAX_AGE      = WEEK_IN_SECONDS;
	const FALLBACK_KEY = 'peyvanda_fallback_';
	const FALLBACK_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Sources.
	 *
	 * @var SourceRegistry
	 */
	private $sources;

	/**
	 * Constructor.
	 *
	 * @param SourceRegistry $sources Sources.
	 */
	public function __construct( SourceRegistry $sources ) {
		$this->sources = $sources;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'save_post', array( $this, 'on_save_post' ), 20, 2 );
	}

	/**
	 * Recomputes the cache of a saved post and clears the fallback list of its type.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public function on_save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || BoxPostType::POST_TYPE === $post->post_type ) {
			return;
		}

		$source = $this->sources->for_post_type( $post->post_type );
		if ( $source ) {
			delete_transient( self::FALLBACK_KEY . $source->id() );
		}

		delete_post_meta( $post_id, self::META );
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$repository = new BoxRepository();
		foreach ( $repository->published() as $box ) {
			if ( ! $box->is_active() || ! in_array( $post->post_type, (array) $box->get( 'show_on' ), true ) ) {
				continue;
			}
			$target = $this->sources->get( (string) $box->get( 'source' ) );
			if ( $target ) {
				self::get( $post, $target, (string) $box->get( 'match_by' ) );
			}
		}
	}

	/**
	 * Returns cached related IDs, computing and storing them if missing or stale.
	 *
	 * @param \WP_Post $post     Current post.
	 * @param Source   $target   Source to pick items from.
	 * @param string   $match_by `category_tag`, `category` or `tag`.
	 * @return int[]
	 */
	public static function get( \WP_Post $post, Source $target, $match_by ) {
		$key   = $target->id() . '|' . $match_by;
		$cache = get_post_meta( $post->ID, self::META, true );
		$cache = is_array( $cache ) ? $cache : array();

		if ( isset( $cache[ $key ]['ids'], $cache[ $key ]['time'] ) && ( time() - (int) $cache[ $key ]['time'] ) < self::MAX_AGE ) {
			return array_map( 'intval', (array) $cache[ $key ]['ids'] );
		}

		$ids           = self::compute( $post, $target, $match_by );
		$cache[ $key ] = array(
			'ids'  => $ids,
			'time' => time(),
		);
		update_post_meta( $post->ID, self::META, $cache );

		return $ids;
	}

	/**
	 * Finds items sharing tags and/or categories with the post.
	 *
	 * @param \WP_Post $post     Current post.
	 * @param Source   $target   Source to pick items from.
	 * @param string   $match_by Matching mode.
	 * @return int[]
	 */
	public static function compute( \WP_Post $post, Source $target, $match_by ) {
		$groups = array();
		if ( 'category' !== $match_by ) {
			$groups[] = array( 'tag', $target->tag_taxonomy() );
		}
		if ( 'tag' !== $match_by ) {
			$groups[] = array( 'category', $target->category_taxonomy() );
		}

		$ids = array();
		foreach ( $groups as $group ) {
			list( $kind, $target_taxonomy ) = $group;

			$term_ids = self::matching_terms( $post, $kind, $target_taxonomy );
			if ( ! $term_ids ) {
				continue;
			}

			$found = self::query(
				$target,
				array(
					'post__not_in' => array_merge( array( $post->ID ), $ids ),
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Runs on save, not on page view.
					'tax_query'    => array(
						array(
							'taxonomy' => $target_taxonomy,
							'field'    => 'term_id',
							'terms'    => $term_ids,
						),
					),
				),
				self::MAX_IDS - count( $ids )
			);

			$ids = array_merge( $ids, $found );
			if ( count( $ids ) >= self::MAX_IDS ) {
				break;
			}
		}

		return array_slice( array_map( 'intval', $ids ), 0, self::MAX_IDS );
	}

	/**
	 * Term IDs in the target taxonomy that match the post's own tags or categories.
	 * When the post and the target use different taxonomies (e.g. a post showing products),
	 * terms are matched by slug.
	 *
	 * @param \WP_Post $post            Current post.
	 * @param string   $kind            `tag` or `category`.
	 * @param string   $target_taxonomy Taxonomy of the target source.
	 * @return int[]
	 */
	private static function matching_terms( \WP_Post $post, $kind, $target_taxonomy ) {
		if ( ! taxonomy_exists( $target_taxonomy ) ) {
			return array();
		}

		$own_taxonomy = self::own_taxonomy( $post->post_type, $kind );
		if ( ! $own_taxonomy ) {
			return array();
		}

		$terms = get_the_terms( $post, $own_taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}

		$terms = array_filter(
			$terms,
			static function ( $term ) {
				return 'uncategorized' !== $term->slug;
			}
		);

		if ( $own_taxonomy === $target_taxonomy ) {
			return array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) );
		}

		$slugs = wp_list_pluck( $terms, 'slug' );
		if ( ! $slugs ) {
			return array();
		}

		$mapped = get_terms(
			array(
				'taxonomy'   => $target_taxonomy,
				'slug'       => array_values( $slugs ),
				'fields'     => 'ids',
				'hide_empty' => true,
			)
		);

		return is_wp_error( $mapped ) ? array() : array_map( 'intval', $mapped );
	}

	/**
	 * The tag or category taxonomy of a post type.
	 *
	 * @param string $post_type Post type.
	 * @param string $kind      `tag` or `category`.
	 * @return string
	 */
	private static function own_taxonomy( $post_type, $kind ) {
		$map = array(
			'product' => array(
				'tag'      => 'product_tag',
				'category' => 'product_cat',
			),
		);
		if ( isset( $map[ $post_type ][ $kind ] ) ) {
			return $map[ $post_type ][ $kind ];
		}

		$taxonomy = 'tag' === $kind ? 'post_tag' : 'category';
		return is_object_in_taxonomy( $post_type, $taxonomy ) ? $taxonomy : '';
	}

	/**
	 * Fallback IDs for a source (cached in a transient, cleared when an item is saved).
	 *
	 * @param Source $source Source.
	 * @return int[]
	 */
	public static function fallback( Source $source ) {
		$key = self::FALLBACK_KEY . $source->id();
		$ids = get_transient( $key );
		if ( is_array( $ids ) ) {
			return array_map( 'intval', $ids );
		}

		$ids = self::query( $source, $source->fallback_query_args(), self::MAX_IDS + 1 );
		set_transient( $key, $ids, self::FALLBACK_TTL );

		return $ids;
	}

	/**
	 * Runs a light ID-only query against a source.
	 *
	 * @param Source              $source Source.
	 * @param array<string,mixed> $args   Extra query args.
	 * @param int                 $limit  Max IDs.
	 * @return int[]
	 */
	private static function query( Source $source, array $args, $limit ) {
		if ( $limit <= 0 ) {
			return array();
		}

		$base = $source->base_query_args();
		if ( isset( $base['tax_query'], $args['tax_query'] ) ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $base['tax_query'], $args['tax_query'] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			unset( $base['tax_query'] );
		}

		$query = new \WP_Query(
			array_merge(
				array(
					'post_type'              => $source->post_type(),
					'post_status'            => 'publish',
					'has_password'           => false,
					'posts_per_page'         => (int) $limit,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'orderby'                => 'date',
					'order'                  => 'DESC',
				),
				$base,
				$args
			)
		);

		return array_map( 'intval', $query->posts );
	}
}
