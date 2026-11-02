<?php
/**
 * Main plugin bootstrap.
 *
 * @package Peyvanda
 */

namespace Peyvanda;

use Peyvanda\Admin\AdminMenu;
use Peyvanda\Admin\BoxEditor;
use Peyvanda\Admin\SettingsPage;
use Peyvanda\Boxes\BoxPostType;
use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Designs\DesignRegistry;
use Peyvanda\Manual\ManualMetabox;
use Peyvanda\Matching\RelatedCache;
use Peyvanda\Placement\Blocks;
use Peyvanda\Placement\ContentInserter;
use Peyvanda\Placement\Shortcode;
use Peyvanda\Render\Assets;
use Peyvanda\Render\Renderer;
use Peyvanda\Rest\RestController;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every component together. Each component registers its own hooks.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Registered sources.
	 *
	 * @var SourceRegistry
	 */
	public $sources;

	/**
	 * Registered designs.
	 *
	 * @var DesignRegistry
	 */
	public $designs;

	/**
	 * Box repository.
	 *
	 * @var BoxRepository
	 */
	public $boxes;

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	public $renderer;

	/**
	 * Returns the plugin instance, booting it on first call.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Registers all components.
	 *
	 * @return void
	 */
	private function boot() {
		$this->sources  = new SourceRegistry();
		$this->designs  = new DesignRegistry();
		$this->boxes    = new BoxRepository();
		$this->renderer = new Renderer( $this->boxes, $this->sources, $this->designs );

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( $this->sources, 'init' ), 5 );
		add_action( 'init', array( $this->designs, 'init' ), 5 );

		( new BoxPostType() )->register_hooks();
		( new RelatedCache( $this->sources ) )->register_hooks();
		( new Assets( $this->boxes, $this->designs ) )->register_hooks();
		( new ContentInserter( $this->boxes, $this->renderer ) )->register_hooks();
		( new Shortcode( $this->renderer ) )->register_hooks();
		( new Blocks( $this->renderer ) )->register_hooks();
		( new ManualMetabox( $this->sources ) )->register_hooks();
		( new RestController( $this->boxes, $this->sources ) )->register_hooks();

		if ( is_admin() ) {
			( new AdminMenu() )->register_hooks();
			( new BoxEditor( $this->boxes, $this->sources, $this->designs ) )->register_hooks();
			( new SettingsPage( $this->sources ) )->register_hooks();
		}

		/**
		 * Fires once Peyvanda is fully loaded. Add-ons hook here.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'peyvanda_loaded', $this );
	}

	/**
	 * Loads the bundled translation (fa_IR ships with the plugin) unless a language pack is installed.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$locale = determine_locale();

		// Language packs from translate.wordpress.org (wp-content/languages/plugins) take priority.
		if ( file_exists( WP_LANG_DIR . '/plugins/peyvanda-' . $locale . '.mo' ) ) {
			return;
		}

		$mofile = PEYVANDA_DIR . 'languages/peyvanda-' . $locale . '.mo';
		if ( is_readable( $mofile ) ) {
			load_textdomain( 'peyvanda', $mofile, $locale );
		}
	}

	/**
	 * Activation: register the post type and create a starter box.
	 *
	 * @return void
	 */
	public static function activate() {
		( new BoxPostType() )->register();
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}
		BoxRepository::maybe_create_starter_box();
	}
}
