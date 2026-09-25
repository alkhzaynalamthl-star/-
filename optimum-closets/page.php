<?php
/**
 * Pages (including WooCommerce cart, checkout and account pages).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();

$optimum_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );

while ( have_posts() ) :
	the_post();
	?>
	<div class="page-hero<?php echo $optimum_wc_page ? ' page-hero-compact' : ''; ?>">
		<div class="container">
			<h1 class="page-title"><?php the_title(); ?></h1>
		</div>
	</div>
	<div class="container section <?php echo $optimum_wc_page ? 'wc-page' : 'container-narrow'; ?>">
		<article id="post-<?php the_ID(); ?>" <?php post_class( $optimum_wc_page ? '' : 'entry-content' ); ?>>
			<?php
			the_content();
			wp_link_pages();
			?>
		</article>
		<?php
		if ( ! $optimum_wc_page && ( comments_open() || get_comments_number() ) ) {
			comments_template();
		}
		?>
	</div>
	<?php
endwhile;

get_footer();
