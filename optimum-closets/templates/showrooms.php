<?php
/**
 * Template Name: Showrooms
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="page-hero">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'Showrooms', 'optimum' ); ?></p>
		<h1 class="page-title"><?php esc_html_e( 'See the materials in person', 'optimum' ); ?></h1>
		<p class="page-desc"><?php esc_html_e( 'Touch the veneers, open the doors and try the drawers in our Jeddah and Makkah showrooms.', 'optimum' ); ?></p>
	</div>
</div>
<div class="container section">
	<div class="show-grid">
		<?php get_template_part( 'template-parts/showroom-cards' ); ?>
	</div>
	<?php
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) {
			echo '<div class="entry-content">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
	<div class="cta-band">
		<div>
			<h2><?php esc_html_e( 'Prefer we come to you?', 'optimum' ); ?></h2>
			<p><?php esc_html_e( 'Book a free measurement visit at home.', 'optimum' ); ?></p>
		</div>
		<a class="btn btn-primary" href="<?php echo esc_url( optimum_page_url( 'made-to-measure' ) ); ?>"><?php esc_html_e( 'Request a Made-to-Measure Design', 'optimum' ); ?></a>
	</div>
</div>
<?php
get_footer();
