<?php
/**
 * Design: minimal text list.
 *
 * Available: $peyvanda_args (see Renderer::template()).
 *
 * @package Peyvanda
 */

defined( 'ABSPATH' ) || exit;

$peyvanda_settings = $peyvanda_args['settings'];
?>
<aside <?php echo $peyvanda_args['root_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer::template(). ?>>
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
					<span class="pvd-box__name"><?php echo esc_html( $peyvanda_item['title'] ); ?></span>
					<?php if ( ! empty( $peyvanda_settings['show_price'] ) && '' !== $peyvanda_item['price'] ) : ?>
						<span class="pvd-box__price"><?php echo wp_kses_post( $peyvanda_item['price'] ); ?></span>
					<?php endif; ?>
				</a>
			</li>
			<?php
			echo $peyvanda_args['item_html']( (string) ob_get_clean(), $peyvanda_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above; filterable via peyvanda_item_html.
		endforeach;
		?>
	</ul>
</aside>
