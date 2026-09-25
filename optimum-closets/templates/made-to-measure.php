<?php
/**
 * Template Name: Made-to-measure request
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="split-hero">
	<div class="container split-in">
		<div class="split-copy">
			<p class="eyebrow"><?php esc_html_e( 'Made to measure', 'optimum' ); ?></p>
			<h1 class="page-title"><?php esc_html_e( 'Request a made-to-measure design', 'optimum' ); ?></h1>
			<p class="page-desc"><?php esc_html_e( 'Tell us the size of your wall, add a few photos and your contact details. Our designer will prepare a proposal and an initial quote, then visit to measure precisely — free of charge.', 'optimum' ); ?></p>
			<ul class="assurances">
				<li><?php echo optimum_icon( 'ruler', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Free consultation & measurement', 'optimum' ); ?></span></li>
				<li><?php echo optimum_icon( 'clock', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( optimum_mod_i18n( 'lead_time' ) ); ?></span></li>
			</ul>
		</div>
		<div class="split-media">
			<?php echo optimum_render_picture( 'bespoke-wall', '(min-width: 900px) 45vw, 100vw', array( 'eager' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo optimum_render_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</section>
<div class="container section form-wrap">
	<?php get_template_part( 'template-parts/request-form', null, array( 'kind' => 'design' ) ); ?>
	<?php
	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) {
			echo '<div class="entry-content">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>
<?php
get_footer();
