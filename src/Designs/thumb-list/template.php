<?php
/**
 * Design: list with thumbnails.
 *
 * Available: $peyvanda_args (see Renderer::template()).
 *
 * @package Peyvanda
 */

defined( 'ABSPATH' ) || exit;

$peyvanda_settings = $peyvanda_args['settings'];
?>
<aside class="<?php echo esc_attr( $peyvanda_args['root_class'] ); ?>" style="<?php echo esc_attr( $peyvanda_args['root_style'] ); ?>" aria-label="<?php echo esc_attr( $peyvanda_args['root_label'] ); ?>" data-pvd-box="<?php echo esc_attr( (string) $peyvanda_args['box']->id ); ?>">
	<?php if ( '' !== $peyvanda_args['heading'] ) : ?>
		<p class="pvd-box__title"><?php echo esc_html( $peyvanda_args['heading'] ); ?></p>
	<?php endif; ?>
	<ul class="pvd-box__list">
		<?php
		foreach ( $peyvanda_args['items'] as $peyvanda_item ) :
			ob_start();
			?>
			<li class="pvd-box__item">
				<a class="pvd-box__link" href="<?php echo esc_url( $peyvanda_item['url'] ); ?>">
					<?php
					if ( ! empty( $peyvanda_settings['show_image'] ) ) {
						echo wp_kses( $peyvanda_args['image']( $peyvanda_item ), $peyvanda_args['allowed_html'] );
					}
					?>
					<span class="pvd-box__body">
						<span class="pvd-box__name"><?php echo esc_html( $peyvanda_item['title'] ); ?></span>
						<?php if ( ! empty( $peyvanda_settings['show_price'] ) && '' !== $peyvanda_item['price'] ) : ?>
							<span class="pvd-box__price"><?php echo wp_kses( $peyvanda_item['price'], $peyvanda_args['allowed_html'] ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
			<?php
			// Item HTML is filterable (peyvanda_item_html), so it is escaped again after filtering.
			echo wp_kses( $peyvanda_args['item_html']( (string) ob_get_clean(), $peyvanda_item ), $peyvanda_args['allowed_html'] );
		endforeach;
		?>
	</ul>
</aside>
