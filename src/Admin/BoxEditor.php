<?php
/**
 * Box edit screen (tabs: source, placement, design).
 *
 * @package Peyvanda
 */

namespace Peyvanda\Admin;

use Peyvanda\Boxes\Box;
use Peyvanda\Boxes\BoxPostType;
use Peyvanda\Boxes\BoxRepository;
use Peyvanda\Designs\Design;
use Peyvanda\Designs\DesignRegistry;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Settings meta box for `peyvanda_box`.
 */
final class BoxEditor {

	const NONCE = 'peyvanda_box_nonce';

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
	 * Designs.
	 *
	 * @var DesignRegistry
	 */
	private $designs;

	/**
	 * Constructor.
	 *
	 * @param BoxRepository  $boxes   Boxes.
	 * @param SourceRegistry $sources Sources.
	 * @param DesignRegistry $designs Designs.
	 */
	public function __construct( BoxRepository $boxes, SourceRegistry $sources, DesignRegistry $designs ) {
		$this->boxes   = $boxes;
		$this->sources = $sources;
		$this->designs = $designs;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'add_meta_boxes_' . BoxPostType::POST_TYPE, array( $this, 'add' ) );
		add_action( 'save_post_' . BoxPostType::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Title placeholder.
	 *
	 * @param string   $text Placeholder.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function title_placeholder( $text, $post ) {
		return BoxPostType::POST_TYPE === $post->post_type ? __( 'Box name (only visible to you)', 'peyvanda' ) : $text;
	}

	/**
	 * Adds the meta boxes.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box( 'pvd-box-settings', __( 'Box settings', 'peyvanda' ), array( $this, 'render' ), BoxPostType::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'pvd-box-usage', __( 'How to show this box', 'peyvanda' ), array( $this, 'render_usage' ), BoxPostType::POST_TYPE, 'side', 'default' );
	}

	/**
	 * Tabs of the editor. Add-ons can add their own tabs.
	 *
	 * @return array<string,array{label:string,callback:callable}>
	 */
	private function tabs() {
		$tabs = array(
			'source'    => array(
				'label'    => __( 'Source & selection', 'peyvanda' ),
				'callback' => array( $this, 'tab_source' ),
			),
			'placement' => array(
				'label'    => __( 'Placement', 'peyvanda' ),
				'callback' => array( $this, 'tab_placement' ),
			),
			'design'    => array(
				'label'    => __( 'Design & appearance', 'peyvanda' ),
				'callback' => array( $this, 'tab_design' ),
			),
		);

		/**
		 * Filters the box editor tabs.
		 *
		 * @param array $tabs Tabs: id => [ 'label' => string, 'callback' => callable( Box $box ) ].
		 */
		$tabs = (array) apply_filters( 'peyvanda_box_settings_tabs', $tabs );

		return array_filter(
			$tabs,
			static function ( $tab ) {
				return is_array( $tab ) && isset( $tab['label'], $tab['callback'] ) && is_callable( $tab['callback'] );
			}
		);
	}

	/**
	 * Renders the tabbed settings.
	 *
	 * @param \WP_Post $post Box post.
	 * @return void
	 */
	public function render( $post ) {
		$box  = BoxRepository::from_post( $post );
		$tabs = $this->tabs();

		wp_nonce_field( 'peyvanda_box_save_' . $post->ID, self::NONCE );
		?>
		<div class="pvd-editor">
			<div class="pvd-editor__tabs" role="tablist">
				<?php $first = true; ?>
				<?php foreach ( $tabs as $id => $tab ) : ?>
					<button type="button" role="tab" class="pvd-editor__tab" id="pvd-tab-<?php echo esc_attr( $id ); ?>" aria-controls="pvd-panel-<?php echo esc_attr( $id ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>"><?php echo esc_html( $tab['label'] ); ?></button>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</div>
			<?php $first = true; ?>
			<?php foreach ( $tabs as $id => $tab ) : ?>
				<div class="pvd-editor__panel" role="tabpanel" id="pvd-panel-<?php echo esc_attr( $id ); ?>" aria-labelledby="pvd-tab-<?php echo esc_attr( $id ); ?>"<?php echo $first ? '' : ' hidden'; ?>>
					<?php call_user_func( $tab['callback'], $box ); ?>
				</div>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Tab: source & selection.
	 *
	 * @param Box $box Box.
	 * @return void
	 */
	public function tab_source( Box $box ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'peyvanda' ); ?></th>
				<td>
					<label><input type="checkbox" name="pvd[active]" value="1" <?php checked( $box->is_active() ); ?> /> <?php esc_html_e( 'Box is active', 'peyvanda' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pvd-heading"><?php esc_html_e( 'Heading', 'peyvanda' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="pvd-heading" name="pvd[heading]" value="<?php echo esc_attr( $box->heading() ); ?>" />
					<p class="description"><?php esc_html_e( 'Shown above the items, e.g. "Read more" or "Related products". Leave empty for no heading.', 'peyvanda' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pvd-source"><?php esc_html_e( 'Show items from', 'peyvanda' ); ?></label></th>
				<td>
					<select id="pvd-source" name="pvd[source]">
						<?php foreach ( $this->sources->available() as $source ) : ?>
							<option value="<?php echo esc_attr( $source->id() ); ?>" <?php selected( $box->get( 'source' ), $source->id() ); ?>><?php echo esc_html( $source->label() ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pvd-count"><?php esc_html_e( 'Number of items', 'peyvanda' ); ?></label></th>
				<td><input type="number" class="small-text" id="pvd-count" name="pvd[count]" min="1" max="12" value="<?php echo esc_attr( (string) $box->count() ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="pvd-match"><?php esc_html_e( 'Automatic matching', 'peyvanda' ); ?></label></th>
				<td>
					<select id="pvd-match" name="pvd[match_by]">
						<option value="category_tag" <?php selected( $box->get( 'match_by' ), 'category_tag' ); ?>><?php esc_html_e( 'Shared tags, then categories', 'peyvanda' ); ?></option>
						<option value="category" <?php selected( $box->get( 'match_by' ), 'category' ); ?>><?php esc_html_e( 'Shared categories only', 'peyvanda' ); ?></option>
						<option value="tag" <?php selected( $box->get( 'match_by' ), 'tag' ); ?>><?php esc_html_e( 'Shared tags only', 'peyvanda' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Items picked manually on a post always come first. The fallback (latest posts / best-selling products) is set in Peyvanda → Settings.', 'peyvanda' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Tab: placement.
	 *
	 * @param Box $box Box.
	 * @return void
	 */
	public function tab_placement( Box $box ) {
		$show_on = (array) $box->get( 'show_on' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Automatic insertion', 'peyvanda' ); ?></th>
				<td>
					<label><input type="checkbox" name="pvd[auto_insert]" value="1" <?php checked( (bool) $box->get( 'auto_insert' ) ); ?> /> <?php esc_html_e( 'Insert this box into the content automatically', 'peyvanda' ); ?></label>
					<p class="description"><?php esc_html_e( 'Only one automatic box is inserted per page. Authors can move it with the "Peyvanda marker" block.', 'peyvanda' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pvd-paragraph"><?php esc_html_e( 'After paragraph', 'peyvanda' ); ?></label></th>
				<td><input type="number" class="small-text" id="pvd-paragraph" name="pvd[paragraph]" min="1" max="50" value="<?php echo esc_attr( (string) (int) $box->get( 'paragraph' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="pvd-short"><?php esc_html_e( 'If the content is shorter', 'peyvanda' ); ?></label></th>
				<td>
					<select id="pvd-short" name="pvd[short_content]">
						<option value="end" <?php selected( $box->get( 'short_content' ), 'end' ); ?>><?php esc_html_e( 'Show at the end of the content', 'peyvanda' ); ?></option>
						<option value="hide" <?php selected( $box->get( 'short_content' ), 'hide' ); ?>><?php esc_html_e( 'Do not show', 'peyvanda' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show on', 'peyvanda' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( self::public_post_types() as $type => $label ) : ?>
							<label class="pvd-editor__check"><input type="checkbox" name="pvd[show_on][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $show_on, true ) ); ?> /> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Tab: design & appearance.
	 *
	 * @param Box $box Box.
	 * @return void
	 */
	public function tab_design( Box $box ) {
		$current = $this->designs->get( (string) $box->get( 'design' ) );
		$style   = (array) $box->get( 'style' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Design', 'peyvanda' ); ?></th>
				<td>
					<fieldset class="pvd-editor__designs">
						<?php foreach ( $this->designs->all() as $design ) : ?>
							<label class="pvd-editor__design">
								<input type="radio" name="pvd[design]" value="<?php echo esc_attr( $design->slug ); ?>" <?php checked( $current && $current->slug === $design->slug ); ?> />
								<span><?php echo esc_html( $design->label ); ?></span>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php foreach ( $this->designs->all() as $design ) : ?>
			<?php $values = $design->values( isset( $style[ $design->slug ] ) && is_array( $style[ $design->slug ] ) ? $style[ $design->slug ] : array() ); ?>
			<table class="form-table pvd-editor__design-fields" role="presentation" data-design="<?php echo esc_attr( $design->slug ); ?>"<?php echo ( $current && $current->slug === $design->slug ) ? '' : ' hidden'; ?>>
				<?php foreach ( $design->fields() as $key => $field ) : ?>
					<?php $this->field( $design, (string) $key, $field, $values[ $key ] ); ?>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Renders one design field row.
	 *
	 * @param Design              $design Design.
	 * @param string              $key    Field key.
	 * @param array<string,mixed> $field  Field schema.
	 * @param mixed               $value  Current value.
	 * @return void
	 */
	private function field( Design $design, $key, array $field, $value ) {
		$id   = 'pvd-style-' . $design->slug . '-' . $key;
		$name = 'pvd[style][' . $design->slug . '][' . $key . ']';
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( self::field_label( $key, $field ) ); ?></label></th>
			<td>
				<?php
				switch ( $type ) {
					case 'color':
						printf( '<input type="color" id="%1$s" name="%2$s" value="%3$s" />', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
						break;
					case 'number':
						printf(
							'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" /> %6$s',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( (string) $value ),
							esc_attr( isset( $field['min'] ) ? (string) $field['min'] : '0' ),
							esc_attr( isset( $field['max'] ) ? (string) $field['max'] : '100' ),
							esc_html( isset( $field['unit'] ) ? (string) $field['unit'] : '' )
						);
						break;
					case 'toggle':
						printf( '<input type="hidden" name="%1$s" value="0" /><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s />', esc_attr( $name ), esc_attr( $id ), checked( (bool) $value, true, false ) );
						break;
					case 'select':
						printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
						foreach ( (array) $field['options'] as $option ) {
							printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( (string) $option ), selected( (string) $value, (string) $option, false ), esc_html( (string) $option ) );
						}
						echo '</select>';
						break;
					default:
						printf( '<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" />', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Translated label of a known design field.
	 *
	 * @param string              $key   Field key.
	 * @param array<string,mixed> $field Field schema.
	 * @return string
	 */
	private static function field_label( $key, array $field ) {
		$labels = array(
			'accent'       => __( 'Accent color', 'peyvanda' ),
			'gap'          => __( 'Spacing', 'peyvanda' ),
			'radius'       => __( 'Corner radius', 'peyvanda' ),
			'columns'      => __( 'Columns', 'peyvanda' ),
			'show_image'   => __( 'Show image', 'peyvanda' ),
			'show_excerpt' => __( 'Show excerpt', 'peyvanda' ),
			'show_price'   => __( 'Show price (products)', 'peyvanda' ),
		);
		if ( isset( $labels[ $key ] ) ) {
			return $labels[ $key ];
		}
		return isset( $field['label'] ) ? (string) $field['label'] : $key;
	}

	/**
	 * Usage help in the sidebar.
	 *
	 * @param \WP_Post $post Box post.
	 * @return void
	 */
	public function render_usage( $post ) {
		?>
		<p><?php esc_html_e( 'Shortcode:', 'peyvanda' ); ?></p>
		<p><input type="text" class="widefat code" readonly value="<?php echo esc_attr( sprintf( '[peyvanda id="%d"]', $post->ID ) ); ?>" onfocus="this.select();" /></p>
		<p class="description"><?php esc_html_e( 'Or add the "Peyvanda box" block in the block editor and choose this box.', 'peyvanda' ); ?></p>
		<?php
	}

	/**
	 * Public post types (label keyed by name).
	 *
	 * @return array<string,string>
	 */
	public static function public_post_types() {
		$types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' !== $type->name ) {
				$types[ $type->name ] = $type->labels->singular_name;
			}
		}
		return $types;
	}

	/**
	 * Saves settings.
	 *
	 * @param int      $post_id Box ID.
	 * @param \WP_Post $post    Box post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), 'peyvanda_box_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Sanitized as soon as it is received; sanitize_settings() then validates each value strictly.
		$input = isset( $_POST['pvd'] ) && is_array( $_POST['pvd'] ) ? map_deep( wp_unslash( $_POST['pvd'] ), 'sanitize_text_field' ) : array();

		update_post_meta( $post_id, Box::META, $this->sanitize_settings( $input ) );
		$this->boxes->flush();

		/**
		 * Fires after a box's settings are saved. Add-ons save their own tab fields here.
		 *
		 * @param int      $post_id Box ID.
		 * @param array    $input   Submitted `pvd` fields, already passed through sanitize_text_field(); validate them for your own use.
		 * @param \WP_Post $post    Box post.
		 */
		do_action( 'peyvanda_save_box_settings', $post_id, $input, $post );
	}

	/**
	 * Validates box settings (input is already passed through sanitize_text_field()).
	 *
	 * @param array<string,mixed> $raw Submitted fields.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( array $raw ) {
		$defaults = Box::defaults();
		$source   = isset( $raw['source'] ) ? sanitize_key( (string) $raw['source'] ) : $defaults['source'];
		$design   = isset( $raw['design'] ) ? sanitize_key( (string) $raw['design'] ) : $defaults['design'];
		$match_by = isset( $raw['match_by'] ) ? sanitize_key( (string) $raw['match_by'] ) : $defaults['match_by'];
		$public   = array_keys( self::public_post_types() );
		$show_on  = isset( $raw['show_on'] ) ? array_map( 'sanitize_key', (array) $raw['show_on'] ) : array();

		$settings = array(
			'active'        => ! empty( $raw['active'] ),
			'heading'       => isset( $raw['heading'] ) ? sanitize_text_field( (string) $raw['heading'] ) : '',
			'source'        => $this->sources->get( $source ) ? $source : $defaults['source'],
			'count'         => isset( $raw['count'] ) ? max( 1, min( 12, absint( $raw['count'] ) ) ) : $defaults['count'],
			'match_by'      => in_array( $match_by, array( 'category_tag', 'category', 'tag' ), true ) ? $match_by : $defaults['match_by'],
			'auto_insert'   => ! empty( $raw['auto_insert'] ),
			'paragraph'     => isset( $raw['paragraph'] ) ? max( 1, min( 50, absint( $raw['paragraph'] ) ) ) : $defaults['paragraph'],
			'short_content' => ( isset( $raw['short_content'] ) && 'hide' === $raw['short_content'] ) ? 'hide' : 'end',
			'show_on'       => array_values( array_intersect( $show_on, $public ) ),
			'design'        => isset( $this->designs->all()[ $design ] ) ? $design : $defaults['design'],
			'style'         => array(),
		);

		$style = isset( $raw['style'] ) && is_array( $raw['style'] ) ? $raw['style'] : array();
		foreach ( $this->designs->all() as $slug => $object ) {
			$values = isset( $style[ $slug ] ) && is_array( $style[ $slug ] ) ? $style[ $slug ] : array();
			// Unchecked toggles post "0" via hidden inputs, so missing keys mean defaults.
			$settings['style'][ $slug ] = $object->values( $values );
		}

		/**
		 * Filters sanitized box settings before they are saved.
		 *
		 * @param array $settings Sanitized settings.
		 * @param array $raw      Submitted fields, already passed through sanitize_text_field().
		 */
		return (array) apply_filters( 'peyvanda_sanitize_box_settings', $settings, $raw );
	}

	/**
	 * Admin assets for the box editor.
	 *
	 * @param string $hook Admin page.
	 * @return void
	 */
	public function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || BoxPostType::POST_TYPE !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'peyvanda-box-editor', PEYVANDA_URL . 'assets/admin/box-editor.css', array(), PEYVANDA_VERSION );
		wp_enqueue_script( 'peyvanda-box-editor', PEYVANDA_URL . 'assets/admin/box-editor.js', array(), PEYVANDA_VERSION, true );
	}
}
