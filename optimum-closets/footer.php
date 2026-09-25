<?php
/**
 * التذييل.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

$optimum_phone   = optimum_mod( 'phone' );
$optimum_email   = optimum_mod( 'email' );
$optimum_address = optimum_mod( 'address' );
$optimum_hours   = optimum_mod( 'hours' );
$optimum_wa      = optimum_whatsapp_url( __( 'مرحباً، أرغب بالاستفسار عن الخزائن', 'optimum' ) );
$optimum_pay     = array_filter( array_map( 'trim', explode( ',', (string) optimum_mod( 'payment_methods' ) ) ) );
?>
</main>

<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-col footer-about">
			<a class="brand-text brand-light" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="brand-name"><?php bloginfo( 'name' ); ?></span></a>
			<p><?php echo esc_html( optimum_mod( 'footer_about' ) ); ?></p>
			<?php optimum_social_links(); ?>
		</div>

		<div class="footer-col">
			<h3 class="footer-title"><?php esc_html_e( 'تسوّق', 'optimum' ); ?></h3>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => 'optimum_fallback_menu',
				)
			);
			?>
		</div>

		<div class="footer-col">
			<h3 class="footer-title"><?php esc_html_e( 'خدمة العملاء', 'optimum' ); ?></h3>
			<?php
			if ( has_nav_menu( 'footer2' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer2',
						'container'      => false,
						'depth'          => 1,
					)
				);
			} elseif ( class_exists( 'WooCommerce' ) ) {
				echo '<ul class="menu">';
				printf( '<li><a href="%s">%s</a></li>', esc_url( wc_get_page_permalink( 'myaccount' ) ), esc_html__( 'حسابي', 'optimum' ) );
				printf( '<li><a href="%s">%s</a></li>', esc_url( wc_get_cart_url() ), esc_html__( 'سلة المشتريات', 'optimum' ) );
				$optimum_privacy = get_privacy_policy_url();
				if ( $optimum_privacy ) {
					printf( '<li><a href="%s">%s</a></li>', esc_url( $optimum_privacy ), esc_html__( 'سياسة الخصوصية', 'optimum' ) );
				}
				echo '</ul>';
			}
			?>
		</div>

		<div class="footer-col">
			<h3 class="footer-title"><?php esc_html_e( 'تواصل معنا', 'optimum' ); ?></h3>
			<ul class="contact-list">
				<?php if ( $optimum_wa ) : ?>
					<li><?php echo optimum_icon( 'whatsapp', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'واتساب', 'optimum' ); ?></a></li>
				<?php endif; ?>
				<?php if ( $optimum_phone ) : ?>
					<li><?php echo optimum_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="tel:<?php echo esc_attr( $optimum_phone ); ?>" dir="ltr"><?php echo esc_html( $optimum_phone ); ?></a></li>
				<?php endif; ?>
				<?php if ( $optimum_email ) : ?>
					<li><?php echo optimum_icon( 'mail', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="mailto:<?php echo esc_attr( antispambot( $optimum_email ) ); ?>"><?php echo esc_html( antispambot( $optimum_email ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( $optimum_address ) : ?>
					<li><?php echo optimum_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $optimum_address ); ?></span></li>
				<?php endif; ?>
				<?php if ( $optimum_hours ) : ?>
					<li><?php echo optimum_icon( 'clock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $optimum_hours ); ?></span></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="footer-bottom">
		<div class="container footer-bottom-inner">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'جميع الحقوق محفوظة.', 'optimum' ); ?></p>
			<?php if ( $optimum_pay ) : ?>
				<ul class="payment-badges" aria-label="<?php esc_attr_e( 'وسائل الدفع', 'optimum' ); ?>">
					<?php foreach ( $optimum_pay as $optimum_method ) : ?>
						<li><?php echo esc_html( $optimum_method ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php if ( $optimum_wa ) : ?>
	<a class="wa-float" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'تواصل معنا عبر واتساب', 'optimum' ); ?>">
		<?php echo optimum_icon( 'whatsapp', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
