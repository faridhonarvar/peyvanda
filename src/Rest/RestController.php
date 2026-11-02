<?php
/**
 * REST endpoints for the manual picker and the block editor.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Rest;

use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Manual\ManualMetabox;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Routes under `peyvanda/v1`. All require an authenticated editor (REST nonce).
 */
final class RestController {

	const NAMESPACE_V1 = 'peyvanda/v1';

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
	 * Constructor.
	 *
	 * @param BoxRepository  $boxes   Boxes.
	 * @param SourceRegistry $sources Sources.
	 */
	public function __construct( BoxRepository $boxes, SourceRegistry $sources ) {
		$this->boxes   = $boxes;
		$this->sources = $sources;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => array( $this, 'can_search' ),
				'args'                => array(
					'search'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'post_type' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'exclude'   => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/boxes',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'boxes' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * The user must be able to edit posts of the searched type, and the type must be enabled.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function can_search( \WP_REST_Request $request ) {
		$post_type = sanitize_key( (string) $request->get_param( 'post_type' ) );
		$object    = get_post_type_object( $post_type );
		if ( ! $object || ! in_array( $post_type, ManualMetabox::enabled_post_types(), true ) ) {
			return false;
		}
		return current_user_can( $object->cap->edit_posts );
	}

	/**
	 * Searches published items by title.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function search( \WP_REST_Request $request ) {
		$exclude = array_map( 'absint', (array) $request->get_param( 'exclude' ) );
		$query   = new \WP_Query(
			array(
				'post_type'              => (string) $request->get_param( 'post_type' ),
				'post_status'            => 'publish',
				's'                      => (string) $request->get_param( 'search' ),
				'posts_per_page'         => 10,
				'post__not_in'           => array_filter( $exclude ),
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'    => (int) $post->ID,
				'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'date'  => get_the_date( '', $post ),
			);
		}

		return rest_ensure_response( $results );
	}

	/**
	 * Lists boxes for the block's box selector.
	 *
	 * @return \WP_REST_Response
	 */
	public function boxes() {
		$results = array();
		foreach ( $this->boxes->published() as $box ) {
			$results[] = array(
				'id'     => $box->id,
				'name'   => '' !== $box->name ? $box->name : sprintf( '#%d', $box->id ),
				'active' => $box->is_active(),
			);
		}
		return rest_ensure_response( $results );
	}
}
