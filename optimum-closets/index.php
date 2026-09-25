<?php
/**
 * القالب العام: المدونة والأرشيف والبحث.
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
				/* translators: %s: كلمة البحث */
				printf( esc_html__( 'نتائج البحث عن: %s', 'optimum' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
			} elseif ( is_archive() ) {
				echo wp_kses_post( get_the_archive_title() );
			} elseif ( is_home() && ! is_front_page() ) {
				single_post_title();
			} else {
				esc_html_e( 'المدونة', 'optimum' );
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
				'prev_text' => esc_html__( 'السابق', 'optimum' ),
				'next_text' => esc_html__( 'التالي', 'optimum' ),
			)
		);
		?>
	<?php else : ?>
		<div class="empty-state">
			<h2><?php esc_html_e( 'لا توجد نتائج', 'optimum' ); ?></h2>
			<p><?php esc_html_e( 'جرّب البحث بكلمات أخرى.', 'optimum' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
