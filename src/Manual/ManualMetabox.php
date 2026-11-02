<?php
/**
 * Manual item picker shown on the edit screen of enabled post types.
 *
 * @package Peyvanda
 */

namespace Peyvanda\Manual;

use Peyvanda\Settings;
use Peyvanda\Sources\SourceRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Free version: live search in the same post type, up to 3 items, stored as an ID array.
 */
final class ManualMetabox {

	const META  = '_peyvanda_manual_items';
	const NONCE = 'peyvanda_manual_nonce';

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
		add_action( 'init', array( $this, 'register_meta' ), 20 );
		add_action( 'add_meta_boxes', array( $this, 'add' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Limits of the picker (filterable by add-ons).
	 *
	 * @param string $post_type Post type being edited.
	 * @return array{max_items:int,same_type:bool}
	 */
	public static function limits( $post_type = '' ) {
		/**
		 * Filters the manual picker limits.
		 *
		 * @param array  $limits    `max_items` (int, 0 = unlimited) and `same_type` (bool).
		 * @param string $post_type Post type being edited.
		 */
		$limits = apply_filters(
			'peyvanda_manual_metabox_limits',
			array(
				'max_items' => 3,
				'same_type' => true,
			),
			$post_type
		);

		return array(
			'max_items' => isset( $limits['max_items'] ) ? max( 0, (int) $limits['max_items'] ) : 3,
			'same_type' => ! isset( $limits['same_type'] ) || (bool) $limits['same_type'],
		);
	}

	/**
	 * Post types where the picker is enabled.
	 *
	 * @return string[]
	 */
	public static function enabled_post_types() {
		$types = (array) Settings::get( 'metabox_post_types' );
		return array_values( array_filter( $types, 'post_type_exists' ) );
	}

	/**
	 * Registers the meta key (protected, not exposed in REST).
	 *
	 * @return void
	 */
	public function register_meta() {
		foreach ( self::enabled_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				self::META,
				array(
					'type'              => 'array',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => array( __CLASS__, 'sanitize_ids' ),
					'auth_callback'     => static function ( $allowed, $meta_key, $object_id ) {
						return current_user_can( 'edit_post', $object_id );
					},
				)
			);
		}
	}

	/**
	 * Adds the meta box.
	 *
	 * @param string   $post_type Post type.
	 * @param \WP_Post $post      Post.
	 * @return void
	 */
	public function add( $post_type, $post ) {
		if ( ! in_array( $post_type, self::enabled_post_types(), true ) || ! $post instanceof \WP_Post ) {
			return;
		}

		add_meta_box(
			'pvd-manual-items',
			__( 'Peyvanda: related items', 'peyvanda' ),
			array( $this, 'render' ),
			$post_type,
			'side',
			'default'
		);
	}

	/**
	 * Stored IDs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function get_ids( $post_id ) {
		$raw = get_post_meta( (int) $post_id, self::META, true );
		return self::sanitize_ids( $raw );
	}

	/**
	 * Normalizes a list of IDs. Entries that are arrays contribute their `id`.
	 *
	 * @param mixed $raw Raw value.
	 * @return int[]
	 */
	public static function sanitize_ids( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$ids = array();
		foreach ( $raw as $entry ) {
			if ( is_array( $entry ) ) {
				$entry = isset( $entry['id'] ) ? $entry['id'] : 0;
			}
			$id = absint( $entry );
			if ( $id ) {
				$ids[ $id ] = $id;
			}
		}
		return array_values( $ids );
	}

	/**
	 * Renders the meta box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		$limits = self::limits( $post->post_type );
		$ids    = self::get_ids( $post->ID );

		wp_nonce_field( 'peyvanda_manual_save_' . $post->ID, self::NONCE );
		?>
		<div class="pvd-manual" data-post-type="<?php echo esc_attr( $post->post_type ); ?>" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>" data-max="<?php echo esc_attr( (string) $limits['max_items'] ); ?>">
			<p class="description">
				<?php
				if ( $limits['max_items'] ) {
					/* translators: %d: maximum number of items. */
					echo esc_html( sprintf( _n( 'Pick up to %d item to show in Peyvanda boxes on this page. Leave empty for automatic suggestions.', 'Pick up to %d items to show in Peyvanda boxes on this page. Leave empty for automatic suggestions.', $limits['max_items'], 'peyvanda' ), $limits['max_items'] ) );
				} else {
					esc_html_e( 'Pick items to show in Peyvanda boxes on this page. Leave empty for automatic suggestions.', 'peyvanda' );
				}
				?>
			</p>
			<ul class="pvd-manual__selected">
				<?php foreach ( $ids as $id ) : ?>
					<?php
					$item = get_post( $id );
					if ( ! $item ) {
						continue;
					}
					?>
					<li class="pvd-manual__chip" data-id="<?php echo esc_attr( (string) $id ); ?>">
						<input type="hidden" name="peyvanda_manual_items[]" value="<?php echo esc_attr( (string) $id ); ?>" />
						<span class="pvd-manual__chip-title"><?php echo esc_html( get_the_title( $item ) ); ?></span>
						<button type="button" class="pvd-manual__remove button-link" aria-label="<?php echo esc_attr__( 'Remove', 'peyvanda' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="peyvanda_manual_present" value="1" />
			<label class="screen-reader-text" for="pvd-manual-search"><?php esc_html_e( 'Search items', 'peyvanda' ); ?></label>
			<input type="search" id="pvd-manual-search" class="pvd-manual__search widefat" autocomplete="off" placeholder="<?php echo esc_attr__( 'Search by title…', 'peyvanda' ); ?>" />
			<ul class="pvd-manual__results" role="listbox" aria-label="<?php echo esc_attr__( 'Search results', 'peyvanda' ); ?>"></ul>
			<p class="pvd-manual__status" aria-live="polite"></p>
		</div>
		<?php
	}

	/**
	 * Saves the picked IDs.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ], $_POST['peyvanda_manual_present'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), 'peyvanda_manual_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! in_array( $post->post_type, self::enabled_post_types(), true ) ) {
			return;
		}

		$raw    = isset( $_POST['peyvanda_manual_items'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['peyvanda_manual_items'] ) ) : array();
		$limits = self::limits( $post->post_type );
		$ids    = array();

		foreach ( self::sanitize_ids( $raw ) as $id ) {
			if ( $id === $post_id ) {
				continue;
			}
			$item = get_post( $id );
			if ( ! $item || ( $limits['same_type'] && $item->post_type !== $post->post_type ) ) {
				continue;
			}
			$ids[] = $id;
		}

		if ( $limits['max_items'] ) {
			$ids = array_slice( $ids, 0, $limits['max_items'] );
		}

		if ( $ids ) {
			update_post_meta( $post_id, self::META, $ids );
		} else {
			delete_post_meta( $post_id, self::META );
		}
	}

	/**
	 * Enqueues picker assets on enabled edit screens.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::enabled_post_types(), true ) ) {
			return;
		}

		wp_enqueue_style( 'peyvanda-manual', PEYVANDA_URL . 'assets/admin/manual.css', array(), PEYVANDA_VERSION );
		wp_enqueue_script( 'peyvanda-manual', PEYVANDA_URL . 'assets/admin/manual.js', array( 'wp-api-fetch' ), PEYVANDA_VERSION, true );
		wp_localize_script(
			'peyvanda-manual',
			'peyvandaManual',
			array(
				'path' => '/peyvanda/v1/search',
				'i18n' => array(
					'remove'    => __( 'Remove', 'peyvanda' ),
					'searching' => __( 'Searching…', 'peyvanda' ),
					'noResults' => __( 'Nothing found.', 'peyvanda' ),
					'limit'     => __( 'Maximum number of items reached.', 'peyvanda' ),
					'error'     => __( 'Search failed. Please try again.', 'peyvanda' ),
					'added'     => __( 'Item added.', 'peyvanda' ),
				),
			)
		);
	}
}
