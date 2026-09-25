<?php
/**
 * مصمم الخزانة التفاعلي.
 *
 * يُستخدم في الصفحة الرئيسية، وفي قالب صفحة «مصمم الخزانة»، وبالكود المختصر [optimum_configurator].
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

$optimum_finishes = optimum_config_finishes();
$optimum_cfg      = array(
	'whatsapp' => preg_replace( '/\D/', '', (string) optimum_mod( 'whatsapp' ) ),
	'priceM2'  => (float) optimum_mod( 'config_price_m2' ),
	'currency' => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : 'ر.س',
	'finishes' => $optimum_finishes,
	'site'     => home_url( '/' ),
);
?>
<div class="configurator" data-optimum-config="<?php echo esc_attr( wp_json_encode( $optimum_cfg ) ); ?>">
	<div class="cfg-stage">
		<div class="cfg-view" role="group" aria-label="<?php esc_attr_e( 'طريقة العرض', 'optimum' ); ?>">
			<button type="button" class="cfg-view-btn" data-view="closed" aria-pressed="false"><?php esc_html_e( 'مغلقة', 'optimum' ); ?></button>
			<button type="button" class="cfg-view-btn is-active" data-view="open" aria-pressed="true"><?php esc_html_e( 'مفتوحة', 'optimum' ); ?></button>
		</div>
		<div class="cfg-canvas" aria-live="polite"></div>
		<p class="cfg-hint"><?php echo optimum_icon( 'plus', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'اضغط على أي قسم داخل الخزانة لتغيير توزيعه', 'optimum' ); ?></p>
	</div>

	<form class="cfg-panel" onsubmit="return false">
		<fieldset class="cfg-step">
			<legend><span class="cfg-num">01</span><?php esc_html_e( 'المقاسات', 'optimum' ); ?></legend>
			<label class="cfg-range">
				<span><?php esc_html_e( 'العرض', 'optimum' ); ?> <output data-out="width">240</output> <?php esc_html_e( 'سم', 'optimum' ); ?></span>
				<input type="range" name="width" min="100" max="450" step="10" value="240">
			</label>
			<label class="cfg-range">
				<span><?php esc_html_e( 'الارتفاع', 'optimum' ); ?> <output data-out="height">260</output> <?php esc_html_e( 'سم', 'optimum' ); ?></span>
				<input type="range" name="height" min="200" max="300" step="5" value="260">
			</label>
		</fieldset>

		<fieldset class="cfg-step">
			<legend><span class="cfg-num">02</span><?php esc_html_e( 'نوع الأبواب', 'optimum' ); ?></legend>
			<div class="cfg-chips">
				<label><input type="radio" name="doors" value="hinged" checked><span><?php esc_html_e( 'مفصلية', 'optimum' ); ?></span></label>
				<label><input type="radio" name="doors" value="sliding"><span><?php esc_html_e( 'سحّابة', 'optimum' ); ?></span></label>
				<label><input type="radio" name="doors" value="none"><span><?php esc_html_e( 'مفتوحة بدون أبواب', 'optimum' ); ?></span></label>
			</div>
		</fieldset>

		<fieldset class="cfg-step">
			<legend><span class="cfg-num">03</span><?php esc_html_e( 'اللون والتشطيب', 'optimum' ); ?> <em class="cfg-picked" data-out="finish"></em></legend>
			<div class="cfg-swatches">
				<?php foreach ( $optimum_finishes as $optimum_key => $optimum_finish ) : ?>
					<label title="<?php echo esc_attr( $optimum_finish['name'] ); ?>">
						<input type="radio" name="finish" value="<?php echo esc_attr( $optimum_key ); ?>" <?php checked( 'oak', $optimum_key ); ?>>
						<span style="--sw:<?php echo esc_attr( $optimum_finish['color'] ); ?>" class="<?php echo $optimum_finish['grain'] ? 'is-grain' : ''; ?>"></span>
						<span class="screen-reader-text"><?php echo esc_html( $optimum_finish['name'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<fieldset class="cfg-step">
			<legend><span class="cfg-num">04</span><?php esc_html_e( 'المقابض', 'optimum' ); ?></legend>
			<div class="cfg-chips">
				<label><input type="radio" name="handle" value="brass" checked><span><i class="dot" style="--d:#c9a45c"></i><?php esc_html_e( 'ذهبية', 'optimum' ); ?></span></label>
				<label><input type="radio" name="handle" value="black"><span><i class="dot" style="--d:#1a1816"></i><?php esc_html_e( 'سوداء', 'optimum' ); ?></span></label>
				<label><input type="radio" name="handle" value="hidden"><span><?php esc_html_e( 'مخفية (ضغط)', 'optimum' ); ?></span></label>
			</div>
		</fieldset>

		<fieldset class="cfg-step">
			<legend><span class="cfg-num">05</span><?php esc_html_e( 'إضافات', 'optimum' ); ?></legend>
			<div class="cfg-chips">
				<label><input type="checkbox" name="extras" value="led" checked><span><?php esc_html_e( 'إضاءة LED داخلية', 'optimum' ); ?></span></label>
				<label><input type="checkbox" name="extras" value="mirror"><span><?php esc_html_e( 'مرآة على باب', 'optimum' ); ?></span></label>
				<label><input type="checkbox" name="extras" value="jewelry"><span><?php esc_html_e( 'درج مجوهرات مبطّن', 'optimum' ); ?></span></label>
			</div>
		</fieldset>

		<div class="cfg-summary">
			<div class="cfg-estimate" data-estimate hidden>
				<span><?php esc_html_e( 'السعر التقديري يبدأ من', 'optimum' ); ?></span>
				<strong data-out="price"></strong>
				<small><?php esc_html_e( 'السعر النهائي يُحدد بعد المعاينة والقياس', 'optimum' ); ?></small>
			</div>
			<?php if ( $optimum_cfg['whatsapp'] ) : ?>
				<a class="btn btn-whatsapp btn-lg btn-block" data-send href="#" target="_blank" rel="noopener">
					<?php echo optimum_icon( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'أرسل تصميمي واحصل على عرض سعر', 'optimum' ); ?>
				</a>
			<?php elseif ( current_user_can( 'edit_theme_options' ) ) : ?>
				<p class="cfg-admin-note"><?php esc_html_e( 'للمدير: أضف رقم واتساب من المظهر ← تخصيص ← إعدادات الخزائن الأمثل ← التواصل، ليظهر زر إرسال التصميم.', 'optimum' ); ?></p>
			<?php endif; ?>
			<button type="button" class="cfg-reset" data-reset><?php esc_html_e( 'البدء من جديد', 'optimum' ); ?></button>
		</div>
	</form>
</div>
