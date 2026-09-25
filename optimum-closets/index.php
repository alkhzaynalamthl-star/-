<?php
/**
 * Blog, archives and search.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="page-hero">
	<div class="container">
		<h1 class="page-title">
			<?php
			if ( is_search() ) {
				/* translators: %s: search terms */
				printf( esc_html__( 'Search results for: %s', 'optimum' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
			} elseif ( is_archive() ) {
				echo wp_kses_post( get_the_archive_title() );
			} elseif ( is_home() && ! is_front_page() ) {
				single_post_title();
			} else {
				esc_html_e( 'Journal', 'optimum' );
			}
			?>
		</h1>
		<?php the_archive_description( '<div class="page-desc">', '</div>' ); ?>
	</div>
</div>

<div class="container section">
	<?php if ( have_posts() ) : ?>
		<div class="post-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content' );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'prev_text' => esc_html__( 'Previous', 'optimum' ),
				'next_text' => esc_html__( 'Next', 'optimum' ),
			)
		);
		?>
	<?php else : ?>
		<div class="empty-state">
			<h2><?php esc_html_e( 'Nothing found', 'optimum' ); ?></h2>
			<p><?php esc_html_e( 'Try different words.', 'optimum' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
