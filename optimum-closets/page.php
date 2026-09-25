<?php
/**
 * الصفحات الثابتة.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="page-hero">
		<div class="container">
			<h1 class="page-title"><?php the_title(); ?></h1>
		</div>
	</div>
	<div class="container container-narrow section">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content' ); ?>>
			<?php
			the_content();
			wp_link_pages();
			?>
		</article>
		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</div>
	<?php
endwhile;

get_footer();
