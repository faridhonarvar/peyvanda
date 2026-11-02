<?php
/**
 * Plugin row links and the boxes list table.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Admin;

use Peyvanda\Boxes\BoxPostType;
use Peyvanda\Boxes\BoxRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Admin list improvements for `peyvanda_box`.
 */
final class AdminMenu {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( PEYVANDA_FILE ), array( $this, 'action_links' ) );
		add_filter( 'manage_' . BoxPostType::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . BoxPostType::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
	}

	/**
	 * Adds "Boxes" and "Settings" links on the Plugins screen.
	 *
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$extra = array(
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . BoxPostType::POST_TYPE ) ) . '">' . esc_html__( 'Boxes', 'peyvanda' ) . '</a>',
			'<a href="' . esc_url( SettingsPage::url() ) . '">' . esc_html__( 'Settings', 'peyvanda' ) . '</a>',
		);
		return array_merge( $extra, $links );
	}

	/**
	 * List table columns.
	 *
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;
		unset( $columns['date'] );

		$columns['peyvanda_status']    = __( 'Status', 'peyvanda' );
		$columns['peyvanda_placement'] = __( 'Placement', 'peyvanda' );
		$columns['peyvanda_shortcode'] = __( 'Shortcode', 'peyvanda' );

		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	/**
	 * Column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Box ID.
	 * @return void
	 */
	public function column( $column, $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$box = BoxRepository::from_post( $post );

		switch ( $column ) {
			case 'peyvanda_status':
				echo $box->is_active() ? esc_html__( 'Active', 'peyvanda' ) : esc_html__( 'Inactive', 'peyvanda' );
				break;
			case 'peyvanda_placement':
				if ( $box->get( 'auto_insert' ) ) {
					/* translators: %d: paragraph number. */
					echo esc_html( sprintf( __( 'Automatic, after paragraph %d', 'peyvanda' ), (int) $box->get( 'paragraph' ) ) );
				} else {
					esc_html_e( 'Shortcode / block only', 'peyvanda' );
				}
				break;
			case 'peyvanda_shortcode':
				echo '<code>' . esc_html( sprintf( '[peyvanda id="%d"]', $box->id ) ) . '</code>';
				break;
		}
	}
}
