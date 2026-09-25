<?php
/**
 * المقال المفرد.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="page-hero">
		<div class="container container-narrow">
			<time class="eyebrow" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			<h1 class="page-title"><?php the_title(); ?></h1>
		</div>
	</div>
	<div class="container container-narrow section">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="single-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content' ); ?>>
			<?php
			the_content();
			wp_link_pages();
			?>
		</article>
		<?php
		the_post_navigation(
			array(
				'prev_text' => '<span>' . esc_html__( 'السابق', 'optimum' ) . '</span> %title',
				'next_text' => '<span>' . esc_html__( 'التالي', 'optimum' ) . '</span> %title',
			)
		);
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</div>
	<?php
endwhile;

get_footer();
