<?php
/**
 * Plugin Name:       Peyvanda
 * Plugin URI:        https://github.com/faridhonarvar/peyvanda
 * Description:       Show beautiful, lightweight related posts and products boxes inside your content — after paragraph N, with a shortcode or a block.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Farid Honarvar
 * Author URI:        https://faridhonarvar.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       peyvanda
 * Domain Path:       /languages
 *
 * @package Peyvanda
 */

defined( 'ABSPATH' ) || exit;

define( 'PEYVANDA_VERSION', '0.1.0' );
define( 'PEYVANDA_FILE', __FILE__ );
define( 'PEYVANDA_DIR', plugin_dir_path( __FILE__ ) );
define( 'PEYVANDA_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( PEYVANDA_DIR . 'vendor/autoload.php' ) ) {
	require_once PEYVANDA_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'Peyvanda\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$relative = substr( $class_name, strlen( $prefix ) );
			$file     = PEYVANDA_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

register_activation_hook( __FILE__, array( 'Peyvanda\\Plugin', 'activate' ) );

add_action( 'plugins_loaded', array( 'Peyvanda\\Plugin', 'instance' ) );
