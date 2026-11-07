<?php
/**
 * General settings page (Peyvanda → Settings).
 *
 * @package Peyvanda
 */

namespace Peyvanda\Admin;

use Peyvanda\Boxes\BoxPostType;
use Peyvanda\Settings;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide options and a small dismissible notice card.
 */
final class SettingsPage {

	const SLUG           = 'peyvanda-settings';
	const GROUP          = 'peyvanda_settings_group';
	const DISMISS_META   = 'peyvanda_pro_card_dismissed';
	const DISMISS_ACTION = 'peyvanda_dismiss_pro_card';

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
	 * URL of the page.
	 *
	 * @return string
	 */
	public static function url() {
		return admin_url( 'edit.php?post_type=' . BoxPostType::POST_TYPE . '&page=' . self::SLUG );
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( $this, 'dismiss_card' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the submenu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . BoxPostType::POST_TYPE,
			__( 'Peyvanda settings', 'peyvanda' ),
			__( 'Settings', 'peyvanda' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Registers the option.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
			)
		);
	}

	/**
	 * Styles for the page.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( false !== strpos( (string) $hook, self::SLUG ) ) {
			wp_enqueue_style( 'peyvanda-settings', PEYVANDA_URL . 'assets/admin/settings.css', array(), PEYVANDA_VERSION );
		}
	}

	/**
	 * Renders the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = Settings::all();
		$name     = Settings::OPTION;
		$types    = (array) $settings['metabox_post_types'];
		?>
		<div class="wrap pvd-settings">
			<h1><?php esc_html_e( 'Peyvanda settings', 'peyvanda' ); ?></h1>

			<?php $this->pro_card(); ?>

			<form action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Manual picker on', 'peyvanda' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $this->sources->available() as $source ) : ?>
									<label class="pvd-settings__check">
										<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[metabox_post_types][]" value="<?php echo esc_attr( $source->post_type() ); ?>" <?php checked( in_array( $source->post_type(), $types, true ) ); ?> />
										<?php echo esc_html( $source->label() ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Shows the "Peyvanda: related items" box on these edit screens so authors can pick items by hand.', 'peyvanda' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Fill manual picks', 'peyvanda' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[fill_manual]" value="1" <?php checked( (bool) $settings['fill_manual'] ); ?> /> <?php esc_html_e( 'When fewer items are picked by hand than the box shows, fill the rest with automatic suggestions', 'peyvanda' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pvd-fallback"><?php esc_html_e( 'Fallback', 'peyvanda' ); ?></label></th>
						<td>
							<select id="pvd-fallback" name="<?php echo esc_attr( $name ); ?>[fallback]">
								<option value="recent" <?php selected( $settings['fallback'], 'recent' ); ?>><?php esc_html_e( 'Latest posts / best-selling products', 'peyvanda' ); ?></option>
								<option value="none" <?php selected( $settings['fallback'], 'none' ); ?>><?php esc_html_e( 'Nothing (show fewer items or hide the box)', 'peyvanda' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Used when not enough related items share tags or categories.', 'peyvanda' ); ?></p>
						</td>
					</tr>
					<?php if ( $this->sources->get( 'product' ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Out-of-stock products', 'peyvanda' ); ?></th>
							<td>
								<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_out_of_stock]" value="1" <?php checked( (bool) $settings['hide_out_of_stock'] ); ?> /> <?php esc_html_e( 'Never show out-of-stock products', 'peyvanda' ); ?></label>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Uninstall', 'peyvanda' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[delete_on_uninstall]" value="1" <?php checked( (bool) $settings['delete_on_uninstall'] ); ?> /> <?php esc_html_e( 'Delete all boxes, settings and Peyvanda post data when the plugin is deleted', 'peyvanda' ); ?></label>
						</td>
					</tr>
				</table>
				<?php if ( ! $this->sources->get( 'product' ) && $settings['hide_out_of_stock'] ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[hide_out_of_stock]" value="1" />
				<?php endif; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Small, dismissible notice card (no external links or calls).
	 *
	 * @return void
	 */
	private function pro_card() {
		/**
		 * Whether to show the small notice card on the settings page. Add-ons can turn it off.
		 *
		 * @param bool $show Show the card.
		 */
		$show = (bool) apply_filters( 'peyvanda_show_notice_card', ! did_action( 'peyvanda_pro_loaded' ) );
		if ( ! $show || get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ), self::DISMISS_ACTION );
		?>
		<div class="pvd-settings__card">
			<p>
				<strong><?php esc_html_e( 'Peyvanda Pro is on the way', 'peyvanda' ); ?></strong>
				<?php esc_html_e( 'More designs and advanced features are coming as a separate add-on.', 'peyvanda' ); ?>
			</p>
			<a class="pvd-settings__card-dismiss" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'peyvanda' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Dismisses the notice card for the current user.
	 *
	 * @return void
	 */
	public function dismiss_card() {
		check_admin_referer( self::DISMISS_ACTION );
		if ( current_user_can( 'manage_options' ) ) {
			update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
		}
		wp_safe_redirect( self::url() );
		exit;
	}
}
