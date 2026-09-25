<?php
/**
 * Template Name: مصمم الخزانة
 *
 * صفحة كاملة لمصمم الخزانة التفاعلي.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="config-section config-page">
	<div class="container">
		<div class="config-head">
			<span class="eyebrow"><?php esc_html_e( 'مصمم الخزانة', 'optimum' ); ?></span>
			<h1 class="display-title"><?php echo esc_html( optimum_mod( 'config_title' ) ); ?></h1>
			<p class="lead"><?php echo esc_html( optimum_mod( 'config_text' ) ); ?></p>
		</div>
		<?php get_template_part( 'template-parts/configurator' ); ?>
		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) :
				?>
				<div class="entry-content config-content"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>
	</div>
</section>
<?php
get_footer();
