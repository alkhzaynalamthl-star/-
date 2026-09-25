<?php
/**
 * الصفحة الرئيسية السينمائية.
 *
 * كل النصوص والصور قابلة للتعديل من: المظهر ← تخصيص ← إعدادات الخزائن الأمثل.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

get_header();

$optimum_has_wc   = class_exists( 'WooCommerce' );
$optimum_shop     = optimum_mod( 'hero_btn_url' ) ? optimum_mod( 'hero_btn_url' ) : optimum_shop_url();
$optimum_hero_img = optimum_mod( 'hero_image' );
$optimum_wa       = optimum_whatsapp_url( __( 'مرحباً، أرغب بتصميم خزانة حسب المقاس', 'optimum' ) );
?>

<?php /* ---------- 1. الخزانة التي تنفتح مع التمرير ---------- */ ?>
<section class="door-hero" data-door-hero>
	<div class="door-hero-sticky">
		<div class="door-hero-intro">
			<span class="eyebrow"><?php bloginfo( 'name' ); ?></span>
			<p class="door-intro-title"><?php echo esc_html( optimum_mod( 'hero_intro' ) ); ?></p>
			<span class="scroll-cue"><?php esc_html_e( 'مرّر لتفتح الأبواب', 'optimum' ); ?><i aria-hidden="true"></i></span>
		</div>

		<div class="door-hero-grid container">
			<div class="door-hero-content">
				<span class="eyebrow"><?php echo esc_html( optimum_mod( 'hero_eyebrow' ) ); ?></span>
				<h1 class="display-title"><?php echo esc_html( optimum_mod( 'hero_title' ) ); ?></h1>
				<p class="lead"><?php echo esc_html( optimum_mod( 'hero_text' ) ); ?></p>
				<div class="hero-actions">
					<a class="btn btn-gold btn-lg" href="#design"><?php esc_html_e( 'صمّم خزانتك', 'optimum' ); ?></a>
					<a class="btn btn-ghost btn-lg" href="<?php echo esc_url( $optimum_shop ); ?>"><?php echo esc_html( optimum_mod( 'hero_btn_text' ) ); ?></a>
				</div>
			</div>

			<div class="door-frame-wrap">
				<div class="door-frame">
					<div class="door-interior">
						<?php if ( $optimum_hero_img ) : ?>
							<img src="<?php echo esc_url( $optimum_hero_img ); ?>" alt="" fetchpriority="high">
						<?php else : ?>
							<?php get_template_part( 'template-parts/interior-art' ); ?>
						<?php endif; ?>
					</div>
					<div class="door door-l"><span class="door-handle"></span></div>
					<div class="door door-r"><span class="door-handle"></span></div>
				</div>
				<div class="door-spill" aria-hidden="true"></div>
			</div>
		</div>
	</div>
</section>

<?php /* ---------- 2. شريط متحرك ---------- */ ?>
<div class="marquee" aria-hidden="true">
	<div class="marquee-track">
		<?php
		$optimum_words = array();
		for ( $optimum_i = 1; $optimum_i <= 4; $optimum_i++ ) {
			$optimum_words[] = optimum_mod( "feature_{$optimum_i}_title" );
		}
		$optimum_words = array_filter( $optimum_words );
		for ( $optimum_r = 0; $optimum_r < 4; $optimum_r++ ) :
			foreach ( $optimum_words as $optimum_word ) :
				?>
				<span><?php echo esc_html( $optimum_word ); ?></span><b>✦</b>
				<?php
			endforeach;
		endfor;
		?>
	</div>
</div>

<?php /* ---------- 3. البيان والمزايا ---------- */ ?>
<section class="manifesto">
	<div class="container manifesto-grid">
		<p class="manifesto-text">
			<?php esc_html_e( 'لا نبيع خزائن جاهزة فقط.', 'optimum' ); ?>
			<em><?php esc_html_e( 'نصمم مساحة تعرف مكان كل شيء،', 'optimum' ); ?></em>
			<?php esc_html_e( 'وتبدو كل صباح كما تخيلتها.', 'optimum' ); ?>
		</p>
		<ul class="manifesto-list">
			<?php
			$optimum_feature_icons = array( 1 => 'ruler', 2 => 'truck', 3 => 'gem', 4 => 'lock' );
			foreach ( $optimum_feature_icons as $optimum_i => $optimum_icon ) :
				$optimum_title = optimum_mod( "feature_{$optimum_i}_title" );
				if ( ! $optimum_title ) {
					continue;
				}
				?>
				<li>
					<?php echo optimum_icon( $optimum_icon, 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div>
						<h3><?php echo esc_html( $optimum_title ); ?></h3>
						<p><?php echo esc_html( optimum_mod( "feature_{$optimum_i}_text" ) ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php
/* ---------- 4. المجموعات: قائمة بخط كبير وصورة تتبع المؤشر ---------- */
if ( $optimum_has_wc ) :
	$optimum_cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 6,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'orderby'    => 'menu_order',
		)
	);

	if ( ! is_wp_error( $optimum_cats ) && $optimum_cats ) :
		?>
		<section class="collections">
			<div class="container">
				<div class="collections-head">
					<span class="eyebrow"><?php esc_html_e( 'المجموعات', 'optimum' ); ?></span>
					<a class="link-more" href="<?php echo esc_url( optimum_shop_url() ); ?>"><?php esc_html_e( 'كل المنتجات', 'optimum' ); ?> <?php echo optimum_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
				<ol class="collection-list" data-collection-list>
					<?php
					foreach ( $optimum_cats as $optimum_index => $optimum_cat ) :
						$optimum_thumb = get_term_meta( $optimum_cat->term_id, 'thumbnail_id', true );
						$optimum_src   = $optimum_thumb ? wp_get_attachment_image_url( $optimum_thumb, 'optimum-category' ) : '';
						?>
						<li>
							<a class="collection-row" href="<?php echo esc_url( get_term_link( $optimum_cat ) ); ?>" <?php echo $optimum_src ? 'data-img="' . esc_url( $optimum_src ) . '"' : ''; ?>>
								<span class="collection-num"><?php echo esc_html( sprintf( '%02d', $optimum_index + 1 ) ); ?></span>
								<?php if ( $optimum_src ) : ?>
									<img class="collection-thumb" src="<?php echo esc_url( $optimum_src ); ?>" alt="" loading="lazy">
								<?php endif; ?>
								<span class="collection-name"><?php echo esc_html( $optimum_cat->name ); ?></span>
								<span class="collection-count">
									<?php
									/* translators: %s: عدد المنتجات */
									echo esc_html( sprintf( _n( '%s منتج', '%s منتجات', $optimum_cat->count, 'optimum' ), number_format_i18n( $optimum_cat->count ) ) );
									?>
								</span>
								<span class="collection-arrow"><?php echo optimum_icon( 'arrow', 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
			<div class="collection-float" aria-hidden="true"><img alt=""></div>
		</section>
		<?php
	endif;
endif;
?>

<?php /* ---------- 5. مصمم الخزانة ---------- */ ?>
<section class="config-section" id="design">
	<div class="container">
		<div class="config-head">
			<span class="eyebrow"><?php esc_html_e( 'جديد: مصمم الخزانة', 'optimum' ); ?></span>
			<h2 class="display-title"><?php echo esc_html( optimum_mod( 'config_title' ) ); ?></h2>
			<p class="lead"><?php echo esc_html( optimum_mod( 'config_text' ) ); ?></p>
		</div>
		<?php get_template_part( 'template-parts/configurator' ); ?>
	</div>
</section>

<?php if ( $optimum_has_wc ) : ?>
	<?php /* ---------- 6. الأكثر طلباً ---------- */ ?>
	<section class="section section-ivory">
		<div class="container">
			<?php optimum_section_head( __( 'الأكثر طلباً', 'optimum' ), __( 'اختيارات عملائنا', 'optimum' ), optimum_shop_url() ); ?>
			<?php echo do_shortcode( '[products limit="8" columns="4" best_selling="true"]' ); ?>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 7. كيف نعمل ---------- */ ?>
<section class="process">
	<div class="container">
		<div class="process-head">
			<span class="eyebrow"><?php esc_html_e( 'كيف نعمل', 'optimum' ); ?></span>
			<h2 class="display-title"><?php echo esc_html( optimum_mod( 'custom_title' ) ); ?></h2>
			<p class="lead"><?php echo esc_html( optimum_mod( 'custom_text' ) ); ?></p>
		</div>
		<ol class="process-steps">
			<?php for ( $optimum_i = 1; $optimum_i <= 3; $optimum_i++ ) : ?>
				<li>
					<span class="process-num"><?php echo esc_html( sprintf( '%02d', $optimum_i ) ); ?></span>
					<h3><?php echo esc_html( optimum_mod( "step_{$optimum_i}_title" ) ); ?></h3>
					<p><?php echo esc_html( optimum_mod( "step_{$optimum_i}_text" ) ); ?></p>
				</li>
			<?php endfor; ?>
		</ol>
		<?php if ( optimum_mod( 'custom_image' ) ) : ?>
			<figure class="process-media"><img src="<?php echo esc_url( optimum_mod( 'custom_image' ) ); ?>" alt="<?php echo esc_attr( optimum_mod( 'custom_title' ) ); ?>" loading="lazy"></figure>
		<?php endif; ?>
	</div>
</section>

<?php
/* ---------- 8. آراء حقيقية من تقييمات المنتجات ---------- */
if ( $optimum_has_wc ) :
	$optimum_reviews = get_comments(
		array(
			'post_type'  => 'product',
			'status'     => 'approve',
			'type'       => 'review',
			'number'     => 3,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'rating',
					'value'   => 4,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	if ( $optimum_reviews ) :
		?>
		<section class="section section-dark">
			<div class="container">
				<?php optimum_section_head( __( 'آراء العملاء', 'optimum' ), __( 'بكلمات عملائنا', 'optimum' ) ); ?>
				<div class="reviews-grid">
					<?php
					foreach ( $optimum_reviews as $optimum_review ) :
						$optimum_rating = (int) get_comment_meta( $optimum_review->comment_ID, 'rating', true );
						?>
						<figure class="review-card">
							<div class="stars" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: التقييم */ __( 'التقييم %d من 5', 'optimum' ), $optimum_rating ) ); ?>">
								<?php
								for ( $optimum_s = 1; $optimum_s <= 5; $optimum_s++ ) {
									echo '<span class="' . ( $optimum_s <= $optimum_rating ? 'on' : '' ) . '">' . optimum_icon( 'star', 18 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>
							<blockquote><?php echo esc_html( wp_trim_words( $optimum_review->comment_content, 40 ) ); ?></blockquote>
							<figcaption>
								<strong><?php echo esc_html( $optimum_review->comment_author ); ?></strong>
								<a href="<?php echo esc_url( get_permalink( $optimum_review->comment_post_ID ) ); ?>"><?php echo esc_html( get_the_title( $optimum_review->comment_post_ID ) ); ?></a>
							</figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	endif;
endif;
?>

<?php
/* ---------- 9. الأسئلة الشائعة ---------- */
$optimum_faqs = array();
for ( $optimum_i = 1; $optimum_i <= 5; $optimum_i++ ) {
	$optimum_q = optimum_mod( "faq_{$optimum_i}_q" );
	$optimum_a = optimum_mod( "faq_{$optimum_i}_a" );
	if ( $optimum_q && $optimum_a ) {
		$optimum_faqs[] = array( $optimum_q, $optimum_a );
	}
}
if ( $optimum_faqs ) :
	?>
	<section class="section section-ivory">
		<div class="container faq-grid">
			<div>
				<span class="eyebrow"><?php esc_html_e( 'لديك سؤال؟', 'optimum' ); ?></span>
				<h2 class="display-title"><?php esc_html_e( 'الأسئلة الشائعة', 'optimum' ); ?></h2>
			</div>
			<div class="faq">
				<?php foreach ( $optimum_faqs as $optimum_faq ) : ?>
					<details class="faq-item">
						<summary><?php echo esc_html( $optimum_faq[0] ); ?><?php echo optimum_icon( 'plus', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></summary>
						<div class="faq-answer"><?php echo wp_kses_post( wpautop( $optimum_faq[1] ) ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php /* ---------- 10. الختام ---------- */ ?>
<section class="finale">
	<div class="container">
		<p class="finale-title"><?php esc_html_e( 'خزانتك القادمة', 'optimum' ); ?> <em><?php esc_html_e( 'تبدأ برسالة.', 'optimum' ); ?></em></p>
		<div class="hero-actions">
			<?php if ( $optimum_wa ) : ?>
				<a class="btn btn-gold btn-lg" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
					<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'تحدث مع مصممنا', 'optimum' ); ?>
				</a>
			<?php endif; ?>
			<a class="btn btn-ghost btn-lg" href="#design"><?php esc_html_e( 'أو صمّمها بنفسك', 'optimum' ); ?></a>
		</div>
	</div>
</section>

<?php
get_footer();
