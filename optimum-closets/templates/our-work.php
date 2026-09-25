<?php
/**
 * Template Name: Our work
 *
 * Real project photos go in the page content (e.g. a Gallery block). Until
 * then the page says so honestly; inspiration renders are shown separately
 * and clearly labelled — never as completed projects.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'Our Work', 'optimum' ); ?></p>
		<h1 class="page-title"><?php esc_html_e( 'Wardrobes we have designed and installed', 'optimum' ); ?></h1>
	</div>
</div>
<div class="container section">
	<?php
	$optimum_has = false;
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) {
			$optimum_has = true;
			echo '<div class="entry-content work-gallery">';
			the_content();
			echo '</div>';
		}
	endwhile;
	if ( ! $optimum_has ) :
		?>
		<div class="empty-state work-empty">
			<?php echo optimum_icon( 'camera', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h2><?php esc_html_e( 'Project photos are coming soon', 'optimum' ); ?></h2>
			<p><?php esc_html_e( 'We are preparing photos of completed projects. Meanwhile, visit a showroom to see our work in person.', 'optimum' ); ?></p>
			<p class="empty-actions">
				<?php if ( optimum_mod( 'instagram' ) ) : ?>
					<a class="btn btn-outline" href="<?php echo esc_url( optimum_mod( 'instagram' ) ); ?>" target="_blank" rel="noopener">Instagram</a>
				<?php endif; ?>
				<a class="btn btn-primary" href="<?php echo esc_url( optimum_page_url( 'showrooms' ) ); ?>"><?php esc_html_e( 'Visit a showroom', 'optimum' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<section class="inspiration" aria-labelledby="insp-title">
		<header class="sec-head">
			<h2 class="sec-title" id="insp-title"><?php esc_html_e( 'Inspiration', 'optimum' ); ?></h2>
			<span class="tag"><?php esc_html_e( 'Illustrative renders — not completed projects', 'optimum' ); ?></span>
		</header>
		<div class="insp-grid">
			<?php foreach ( array( 'walkin-oak-wide', 'walkin-walnut-angle', 'bespoke-wall', 'walkin-cashmere-angle', 'glass-black-open' ) as $optimum_i => $optimum_r ) : ?>
				<figure class="insp insp-<?php echo (int) $optimum_i; ?> reveal">
					<?php echo optimum_render_picture( $optimum_r, 0 === $optimum_i ? '(min-width: 900px) 66vw, 100vw' : '(min-width: 900px) 33vw, 100vw' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo optimum_render_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</figure>
			<?php endforeach; ?>
		</div>
	</section>
</div>
<?php
get_footer();
