<?php
/**
 * Registers the internal `peyvanda_box` post type.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Boxes;

defined( 'ABSPATH' ) || exit;

/**
 * Non-public post type that stores box configurations.
 */
final class BoxPostType {

	const POST_TYPE = 'peyvanda_box';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers the post type.
	 *
	 * @return void
	 */
	public function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Boxes', 'peyvanda' ),
					'singular_name'      => __( 'Box', 'peyvanda' ),
					'menu_name'          => __( 'Peyvanda', 'peyvanda' ),
					'all_items'          => __( 'Boxes', 'peyvanda' ),
					'add_new'            => __( 'New box', 'peyvanda' ),
					'add_new_item'       => __( 'Add new box', 'peyvanda' ),
					'edit_item'          => __( 'Edit box', 'peyvanda' ),
					'new_item'           => __( 'New box', 'peyvanda' ),
					'search_items'       => __( 'Search boxes', 'peyvanda' ),
					'not_found'          => __( 'No boxes found.', 'peyvanda' ),
					'not_found_in_trash' => __( 'No boxes found in Trash.', 'peyvanda' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'menu_position'       => 58,
				'menu_icon'           => 'dashicons-screenoptions',
				'capability_type'     => 'post',
				'capabilities'        => array(
					'edit_post'          => 'manage_options',
					'read_post'          => 'manage_options',
					'delete_post'        => 'manage_options',
					'edit_posts'         => 'manage_options',
					'edit_others_posts'  => 'manage_options',
					'delete_posts'       => 'manage_options',
					'publish_posts'      => 'manage_options',
					'read_private_posts' => 'manage_options',
					'create_posts'       => 'manage_options',
				),
				'map_meta_cap'        => false,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
			)
		);
	}
}
