<?php
/**
 * الصفحة الرئيسية.
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

<section class="hero<?php echo $optimum_hero_img ? ' has-image' : ''; ?>">
	<?php if ( $optimum_hero_img ) : ?>
		<img class="hero-bg" src="<?php echo esc_url( $optimum_hero_img ); ?>" alt="" fetchpriority="high">
	<?php endif; ?>
	<div class="container hero-inner">
		<div class="hero-content">
			<span class="eyebrow"><?php echo esc_html( optimum_mod( 'hero_eyebrow' ) ); ?></span>
			<h1 class="hero-title"><?php echo esc_html( optimum_mod( 'hero_title' ) ); ?></h1>
			<p class="hero-text"><?php echo esc_html( optimum_mod( 'hero_text' ) ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-primary btn-lg" href="<?php echo esc_url( $optimum_shop ); ?>"><?php echo esc_html( optimum_mod( 'hero_btn_text' ) ); ?></a>
				<?php if ( $optimum_wa ) : ?>
					<a class="btn btn-ghost btn-lg" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
						<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo esc_html( optimum_mod( 'hero_btn2_text' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! $optimum_hero_img ) : ?>
			<div class="hero-art" aria-hidden="true"><?php get_template_part( 'template-parts/wardrobe-art' ); ?></div>
		<?php endif; ?>
	</div>
</section>

<section class="features">
	<div class="container features-grid">
		<?php
		$optimum_feature_icons = array( 1 => 'ruler', 2 => 'truck', 3 => 'gem', 4 => 'lock' );
		foreach ( $optimum_feature_icons as $optimum_i => $optimum_icon ) :
			$optimum_title = optimum_mod( "feature_{$optimum_i}_title" );
			if ( ! $optimum_title ) {
				continue;
			}
			?>
			<div class="feature">
				<span class="feature-icon"><?php echo optimum_icon( $optimum_icon, 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<h3><?php echo esc_html( $optimum_title ); ?></h3>
					<p><?php echo esc_html( optimum_mod( "feature_{$optimum_i}_text" ) ); ?></p>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<?php
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
		<section class="section">
			<div class="container">
				<?php optimum_section_head( __( 'تصفّح حسب القسم', 'optimum' ), __( 'اختر مساحتك', 'optimum' ), optimum_shop_url() ); ?>
				<div class="cat-grid cat-count-<?php echo esc_attr( count( $optimum_cats ) ); ?>">
					<?php
					foreach ( $optimum_cats as $optimum_cat ) :
						$optimum_thumb = get_term_meta( $optimum_cat->term_id, 'thumbnail_id', true );
						?>
						<a class="cat-card" href="<?php echo esc_url( get_term_link( $optimum_cat ) ); ?>">
							<?php
							if ( $optimum_thumb ) {
								echo wp_get_attachment_image( $optimum_thumb, 'optimum-category', false, array( 'class' => 'cat-img', 'loading' => 'lazy' ) );
							} else {
								echo '<span class="cat-img cat-img-empty"></span>';
							}
							?>
							<span class="cat-body">
								<span class="cat-name"><?php echo esc_html( $optimum_cat->name ); ?></span>
								<span class="cat-count">
									<?php
									/* translators: %s: عدد المنتجات */
									echo esc_html( sprintf( _n( '%s منتج', '%s منتجات', $optimum_cat->count, 'optimum' ), number_format_i18n( $optimum_cat->count ) ) );
									?>
								</span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section-soft">
		<div class="container">
			<?php optimum_section_head( __( 'الأكثر طلباً', 'optimum' ), __( 'اختيارات عملائنا', 'optimum' ), optimum_shop_url() ); ?>
			<?php echo do_shortcode( '[products limit="8" columns="4" best_selling="true"]' ); ?>
		</div>
	</section>
<?php endif; ?>

<section class="section custom-cta">
	<div class="container custom-grid">
		<div class="custom-media">
			<?php if ( optimum_mod( 'custom_image' ) ) : ?>
				<img src="<?php echo esc_url( optimum_mod( 'custom_image' ) ); ?>" alt="<?php echo esc_attr( optimum_mod( 'custom_title' ) ); ?>" loading="lazy">
			<?php else : ?>
				<div class="custom-media-art" aria-hidden="true"><?php get_template_part( 'template-parts/wardrobe-art' ); ?></div>
			<?php endif; ?>
		</div>
		<div class="custom-content">
			<span class="eyebrow"><?php esc_html_e( 'خدمة التفصيل', 'optimum' ); ?></span>
			<h2 class="section-title"><?php echo esc_html( optimum_mod( 'custom_title' ) ); ?></h2>
			<p class="lead"><?php echo esc_html( optimum_mod( 'custom_text' ) ); ?></p>
			<ol class="steps">
				<?php for ( $optimum_i = 1; $optimum_i <= 3; $optimum_i++ ) : ?>
					<li class="step">
						<span class="step-num"><?php echo esc_html( number_format_i18n( $optimum_i ) ); ?></span>
						<div>
							<h3><?php echo esc_html( optimum_mod( "step_{$optimum_i}_title" ) ); ?></h3>
							<p><?php echo esc_html( optimum_mod( "step_{$optimum_i}_text" ) ); ?></p>
						</div>
					</li>
				<?php endfor; ?>
			</ol>
			<?php if ( $optimum_wa ) : ?>
				<a class="btn btn-whatsapp btn-lg" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
					<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'أرسل مقاساتك الآن', 'optimum' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php if ( $optimum_has_wc ) : ?>
	<section class="section">
		<div class="container">
			<?php optimum_section_head( __( 'جديدنا', 'optimum' ), __( 'وصل حديثاً', 'optimum' ), optimum_shop_url() ); ?>
			<?php echo do_shortcode( '[products limit="4" columns="4" orderby="date" order="DESC"]' ); ?>
		</div>
	</section>

	<?php
	// آراء حقيقية من تقييمات المنتجات (4 نجوم فأكثر) - لا تظهر إن لم توجد تقييمات.
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
				<?php optimum_section_head( __( 'آراء العملاء', 'optimum' ), __( 'ماذا قال عملاؤنا', 'optimum' ) ); ?>
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
	<?php endif; ?>
<?php endif; ?>

<?php
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
	<section class="section">
		<div class="container container-narrow">
			<?php optimum_section_head( __( 'لديك سؤال؟', 'optimum' ), __( 'الأسئلة الشائعة', 'optimum' ) ); ?>
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

<?php if ( $optimum_wa ) : ?>
	<section class="cta-band">
		<div class="container cta-band-inner">
			<div>
				<h2><?php esc_html_e( 'محتار في اختيار الخزانة المناسبة؟', 'optimum' ); ?></h2>
				<p><?php esc_html_e( 'تحدث مع مستشارنا الآن، وسنساعدك في اختيار التصميم والمقاس المناسب لمساحتك.', 'optimum' ); ?></p>
			</div>
			<a class="btn btn-light btn-lg" href="<?php echo esc_url( $optimum_wa ); ?>" target="_blank" rel="noopener">
				<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'تحدث مع مستشارنا', 'optimum' ); ?>
			</a>
		</div>
	</section>
<?php endif; ?>

<?php
get_footer();
