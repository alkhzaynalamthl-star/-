<?php
/**
 * Footer.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;
$optimum_ph = optimum_phone();
?>
</main>

<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-brand">
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="brand-ar" lang="ar">الخزائن الأمثل</span>
				<span class="brand-en" lang="en">Optimum Closets</span>
			</a>
			<p><?php esc_html_e( 'Wardrobes and walk-in closets, designed, made and installed in Jeddah and Makkah.', 'optimum' ); ?></p>
			<?php optimum_language_switcher( 'footer-lang' ); ?>
		</div>

		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Our Products', 'optimum' ); ?></h2>
			<ul class="footer-links">
				<?php foreach ( optimum_product_categories() as $optimum_cat ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $optimum_cat ) ); ?>"><?php echo esc_html( $optimum_cat->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Showrooms', 'optimum' ); ?></h2>
			<ul class="footer-links">
				<?php foreach ( optimum_showrooms() as $optimum_s ) : ?>
					<li><strong><?php echo esc_html( $optimum_s['city'] ); ?></strong><br><?php echo esc_html( $optimum_s['address'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div>
			<h2 class="footer-title"><?php esc_html_e( 'Contact', 'optimum' ); ?></h2>
			<ul class="footer-links">
				<li><a href="<?php echo esc_url( $optimum_ph['href'] ); ?>"><bdi dir="ltr"><?php echo esc_html( $optimum_ph['label'] ); ?></bdi></a></li>
				<li><a href="mailto:<?php echo esc_attr( optimum_mod( 'email' ) ); ?>"><?php echo esc_html( optimum_mod( 'email' ) ); ?></a></li>
				<?php if ( optimum_mod( 'instagram' ) ) : ?>
					<li><a href="<?php echo esc_url( optimum_mod( 'instagram' ) ); ?>" rel="noopener" target="_blank">Instagram</a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( optimum_page_url( 'made-to-measure' ) ); ?>"><?php esc_html_e( 'Request a made-to-measure design', 'optimum' ); ?></a></li>
			</ul>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'footer-links footer-menu',
					'depth'          => 1,
					'fallback_cb'    => '__return_false',
				)
			);
			?>
		</div>
	</div>
	<div class="container footer-base">
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
		<?php if ( optimum_preview_mode() ) : ?>
			<span><?php esc_html_e( 'Images marked “Illustrative render” are generated visualisations, not photos of completed projects.', 'optimum' ); ?></span>
		<?php endif; ?>
	</div>
</footer>

<?php
$optimum_wa = optimum_whatsapp_url( __( 'Hello, I would like to ask about your wardrobes', 'optimum' ) );
if ( $optimum_wa ) :
	?>
	<a class="wa-float" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
		<?php echo optimum_icon( 'whatsapp', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Chat on WhatsApp', 'optimum' ); ?></span>
	</a>
<?php endif; ?>

<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<?php wp_footer(); ?>
</body>
</html>
