<?php
/**
 * Template Name: Contact
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
$optimum_ph = optimum_phone();
$optimum_wa = optimum_whatsapp_url();
?>
<div class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'Contact', 'optimum' ); ?></p>
		<h1 class="page-title"><?php esc_html_e( 'We are here to help', 'optimum' ); ?></h1>
	</div>
</div>
<div class="container section contact-layout">
	<aside class="contact-cards">
		<a class="contact-card" href="<?php echo esc_url( $optimum_ph['href'] ); ?>">
			<?php echo optimum_icon( 'phone', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><strong><?php esc_html_e( 'Call us', 'optimum' ); ?></strong><bdi dir="ltr"><?php echo esc_html( $optimum_ph['label'] ); ?></bdi></span>
		</a>
		<?php if ( $optimum_wa ) : ?>
			<a class="contact-card" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
				<?php echo optimum_icon( 'whatsapp', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><strong><?php esc_html_e( 'WhatsApp', 'optimum' ); ?></strong><?php esc_html_e( 'Send us a message', 'optimum' ); ?></span>
			</a>
		<?php endif; ?>
		<a class="contact-card" href="mailto:<?php echo esc_attr( optimum_mod( 'email' ) ); ?>">
			<?php echo optimum_icon( 'mail', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><strong><?php esc_html_e( 'E-mail', 'optimum' ); ?></strong><?php echo esc_html( optimum_mod( 'email' ) ); ?></span>
		</a>
		<?php foreach ( optimum_showrooms() as $optimum_s ) : ?>
			<a class="contact-card" href="<?php echo esc_url( $optimum_s['map'] ); ?>" target="_blank" rel="noopener">
				<?php echo optimum_icon( 'pin', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><strong><?php echo esc_html( $optimum_s['city'] ); ?></strong><?php echo esc_html( $optimum_s['address'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</aside>
	<div class="contact-form">
		<h2><?php esc_html_e( 'Send us a message', 'optimum' ); ?></h2>
		<?php get_template_part( 'template-parts/request-form', null, array( 'kind' => 'contact' ) ); ?>
	</div>
</div>
<?php
get_footer();
