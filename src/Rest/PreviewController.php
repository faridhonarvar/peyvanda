<?php
/**
 * Live preview of a box in the box editor.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Rest;

use Peyvanda\Admin\BoxEditor;
use Peyvanda\Boxes\Box;
use Peyvanda\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * POST peyvanda/v1/preview: renders unsaved box settings against a real post of the site.
 */
final class PreviewController {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the route.
	 *
	 * @return void
	 */
	public function routes() {
		register_rest_route(
			RestController::NAMESPACE_V1,
			'/preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'box_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'fields' => array(
						'type'    => 'object',
						'default' => array(),
					),
					'all'    => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * Renders the preview.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function preview( \WP_REST_Request $request ) {
		$core   = Plugin::instance();
		$fields = map_deep( (array) $request->get_param( 'fields' ), 'sanitize_text_field' );
		$editor = new BoxEditor( $core->boxes, $core->sources, $core->designs );

		$settings = $editor->sanitize_settings( $fields );
		$box_id   = (int) $request->get_param( 'box_id' );
		$sample   = $this->sample_post( (array) $settings['show_on'] );
		$styles   = array( PEYVANDA_URL . 'assets/css/base.css' );
		$out      = array(
			'sample' => $sample ? html_entity_decode( get_the_title( $sample ), ENT_QUOTES, get_bloginfo( 'charset' ) ) : '',
		);

		if ( $request->get_param( 'all' ) ) {
			$out['designs'] = array();
			foreach ( $core->designs->all() as $slug => $design ) {
				$settings['design']      = $slug;
				$out['designs'][ $slug ] = $core->renderer->render( new Box( $box_id, '', $settings ), $sample );
				$styles[]                = $design->url . 'style.css';
			}
		} else {
			$design      = $core->designs->get( (string) $settings['design'] );
			$out['html'] = $core->renderer->render( new Box( $box_id, '', $settings ), $sample );
			if ( $design ) {
				$styles[] = $design->url . 'style.css';
			}
		}

		$out['styles'] = array_map(
			static function ( $url ) {
				return add_query_arg( 'ver', PEYVANDA_VERSION, $url );
			},
			array_values( array_unique( $styles ) )
		);

		// The box markup is already escaped by the design templates.
		return rest_ensure_response( $out );
	}

	/**
	 * A recent published item to preview against (one of the box's "Show on" types).
	 *
	 * @param string[] $types Post types.
	 * @return \WP_Post|null
	 */
	private function sample_post( array $types ) {
		$types = array_values( array_filter( $types, 'post_type_exists' ) );
		$posts = get_posts(
			array(
				'post_type'        => $types ? $types : array( 'post' ),
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'has_password'     => false,
				'suppress_filters' => false,
			)
		);
		return $posts ? $posts[0] : null;
	}
}
