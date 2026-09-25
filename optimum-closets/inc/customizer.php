<?php
/**
 * إعدادات القالب في: المظهر ← تخصيص ← إعدادات الخزائن الأمثل.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * القيم الافتراضية لكل الإعدادات.
 *
 * @return array
 */
function optimum_defaults() {
	return array(
		// الألوان.
		'color_primary'      => '#9a6a3f',
		'color_accent'       => '#c8a15a',
		'color_dark'         => '#14110e',

		// التواصل.
		'whatsapp'           => '',
		'phone'              => '',
		'email'              => '',
		'address'            => '',
		'hours'              => '',

		// الشريط العلوي.
		'announcement'       => 'توصيل وتركيب داخل المدينة • اطلب خزانتك بمقاساتك عبر واتساب',

		// الواجهة الرئيسية.
		'hero_intro'         => 'الفخامة تبدأ من الداخل',
		'hero_eyebrow'       => 'تصميم • تفصيل • تركيب',
		'hero_title'         => 'خزائن تُصمَّم لمساحتك، وتدوم معك',
		'hero_text'          => 'غرف ملابس وخزائن جدارية ومطابخ بخامات مختارة وتشطيب متقن، نفصّلها على مقاسك ونركّبها في منزلك.',
		'hero_btn_text'      => 'تسوّق الخزائن',
		'hero_btn_url'       => '',
		'hero_btn2_text'     => 'اطلب تصميمك الخاص',
		'hero_image'         => '',

		// مصمم الخزانة.
		'config_title'       => 'صمّم خزانتك في دقيقة',
		'config_text'        => 'اختر المقاس ونوع الأبواب واللون وتوزيع الداخل، وشاهد خزانتك تتشكل أمامك. أرسل التصميم لنا وسنعود إليك بعرض سعر دقيق.',
		'config_price_m2'    => 0,

		// المزايا.
		'feature_1_title'    => 'تفصيل حسب المقاس',
		'feature_1_text'     => 'نصمم الخزانة على أبعاد غرفتك بالضبط',
		'feature_2_title'    => 'توصيل وتركيب',
		'feature_2_text'     => 'فريقنا يوصل ويركّب في منزلك',
		'feature_3_title'    => 'خامات مختارة',
		'feature_3_text'     => 'أخشاب وإكسسوارات عالية الجودة',
		'feature_4_title'    => 'دفع آمن',
		'feature_4_text'     => 'وسائل دفع موثوقة ومحمية',

		// قسم التفصيل.
		'custom_title'       => 'خزانتك بمقاساتك، كما تتخيلها',
		'custom_text'        => 'أرسل لنا مقاسات المساحة وصورة لها، ويجهّز لك مصممونا تصوراً وعرض سعر واضحاً قبل البدء.',
		'custom_image'       => '',

		// خطوات الطلب.
		'step_1_title'       => 'أرسل المقاسات',
		'step_1_text'        => 'صوّر المساحة وأرسل الأبعاد عبر واتساب.',
		'step_2_title'       => 'استلم التصميم والسعر',
		'step_2_text'        => 'نرسل لك تصوراً للخزانة وعرض سعر مفصلاً.',
		'step_3_title'       => 'نصنّع ونركّب',
		'step_3_text'        => 'نصنع خزانتك ونركبها في الموعد المتفق عليه.',

		// الأسئلة الشائعة (لا يظهر السؤال إلا إذا كتبت إجابته).
		'faq_1_q'            => 'كم تستغرق مدة التصنيع والتركيب؟',
		'faq_1_a'            => '',
		'faq_2_q'            => 'هل التركيب مشمول في السعر؟',
		'faq_2_a'            => '',
		'faq_3_q'            => 'ما مدة الضمان على الخزائن؟',
		'faq_3_a'            => '',
		'faq_4_q'            => 'ما هي طرق الدفع المتاحة؟',
		'faq_4_a'            => '',
		'faq_5_q'            => 'هل يمكن تعديل التصميم أو الألوان؟',
		'faq_5_a'            => '',

		// المتجر.
		'free_shipping'      => 0,
		'trust_1'            => 'توصيل وتركيب',
		'trust_2'            => 'ضمان على المنتج',
		'trust_3'            => 'دفع آمن',
		'trust_4'            => 'دعم عبر واتساب',

		// التذييل.
		'footer_about'       => 'الخزائن الأمثل: تصميم وتفصيل وتركيب غرف الملابس والخزائن الجدارية والمطابخ بجودة عالية ولمسة عصرية.',
		'payment_methods'    => '',
		'instagram'          => '',
		'snapchat'           => '',
		'tiktok'             => '',
		'x'                  => '',
	);
}

/**
 * قراءة إعداد مع قيمته الافتراضية.
 *
 * @param string $key المفتاح.
 * @return mixed
 */
function optimum_mod( $key ) {
	$defaults = optimum_defaults();
	return get_theme_mod( $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/**
 * تنظيف رقم الواتساب ليصبح أرقاماً فقط (بالصيغة الدولية).
 *
 * @param string $value الرقم.
 * @return string
 */
function optimum_sanitize_phone( $value ) {
	$value = strtr( (string) $value, array_combine( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), range( 0, 9 ) ) );
	return preg_replace( '/[^0-9+]/', '', $value );
}

/**
 * تسجيل الأقسام والإعدادات.
 *
 * @param WP_Customize_Manager $wp_customize مدير التخصيص.
 */
function optimum_customize_register( $wp_customize ) {
	$defaults = optimum_defaults();

	$wp_customize->add_panel(
		'optimum',
		array(
			'title'    => __( 'إعدادات الخزائن الأمثل', 'optimum' ),
			'priority' => 20,
		)
	);

	$sections = array(
		'optimum_colors'   => __( 'الألوان', 'optimum' ),
		'optimum_contact'  => __( 'التواصل وواتساب', 'optimum' ),
		'optimum_hero'     => __( 'الصفحة الرئيسية: الواجهة', 'optimum' ),
		'optimum_features' => __( 'الصفحة الرئيسية: المزايا والخطوات', 'optimum' ),
		'optimum_custom'   => __( 'الصفحة الرئيسية: قسم التفصيل', 'optimum' ),
		'optimum_config'   => __( 'مصمم الخزانة التفاعلي', 'optimum' ),
		'optimum_faq'      => __( 'الأسئلة الشائعة', 'optimum' ),
		'optimum_shop'     => __( 'المتجر والشحن', 'optimum' ),
		'optimum_footer'   => __( 'التذييل والتواصل الاجتماعي', 'optimum' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, array( 'title' => $title, 'panel' => 'optimum' ) );
	}

	// [مفتاح => [القسم, النوع, العنوان, الوصف]].
	$fields = array(
		'whatsapp'        => array( 'optimum_contact', 'phone', 'رقم واتساب', 'بالصيغة الدولية بدون أصفار أو +، مثال: 9665XXXXXXXX' ),
		'phone'           => array( 'optimum_contact', 'phone', 'رقم الهاتف', '' ),
		'email'           => array( 'optimum_contact', 'email', 'البريد الإلكتروني', '' ),
		'address'         => array( 'optimum_contact', 'text', 'العنوان / المدينة', '' ),
		'hours'           => array( 'optimum_contact', 'text', 'أوقات العمل', 'مثال: السبت - الخميس، 9 ص - 10 م' ),
		'announcement'    => array( 'optimum_contact', 'text', 'نص الشريط العلوي', 'اتركه فارغاً لإخفاء الشريط' ),

		'hero_intro'      => array( 'optimum_hero', 'text', 'العبارة الأولى (قبل فتح الأبواب)', 'تظهر على الخزانة المغلقة، ثم تنفتح الأبواب مع التمرير' ),
		'hero_eyebrow'    => array( 'optimum_hero', 'text', 'النص الصغير فوق العنوان', '' ),
		'hero_title'      => array( 'optimum_hero', 'text', 'العنوان الرئيسي', '' ),
		'hero_text'       => array( 'optimum_hero', 'textarea', 'الوصف', '' ),
		'hero_btn_text'   => array( 'optimum_hero', 'text', 'نص الزر الأول', '' ),
		'hero_btn_url'    => array( 'optimum_hero', 'url', 'رابط الزر الأول', 'اتركه فارغاً ليفتح صفحة المتجر' ),
		'hero_btn2_text'  => array( 'optimum_hero', 'text', 'نص زر واتساب', '' ),
		'hero_image'      => array( 'optimum_hero', 'image', 'الصورة خلف الأبواب', 'صورة من أعمالكم تظهر عندما تنفتح أبواب الخزانة (يفضل عمودية أو مربعة، 1200×1400 تقريباً)' ),

		'config_title'    => array( 'optimum_config', 'text', 'العنوان', '' ),
		'config_text'     => array( 'optimum_config', 'textarea', 'الوصف', '' ),
		'config_price_m2' => array( 'optimum_config', 'number', 'سعر المتر المربع للواجهة (ريال)', 'لعرض سعر تقديري للعميل أثناء التصميم. ضع 0 لإخفاء السعر.' ),

		'custom_title'    => array( 'optimum_custom', 'text', 'العنوان', '' ),
		'custom_text'     => array( 'optimum_custom', 'textarea', 'الوصف', '' ),
		'custom_image'    => array( 'optimum_custom', 'image', 'الصورة', '' ),

		'free_shipping'   => array( 'optimum_shop', 'number', 'حد التوصيل المجاني (ريال)', 'يظهر للعميل شريط «باقي X ريال للتوصيل المجاني». ضع 0 لإخفائه. يجب أن يطابق إعداد الشحن في ووكومرس.' ),
		'trust_1'         => array( 'optimum_shop', 'text', 'شارة الثقة 1', 'تظهر تحت زر الإضافة للسلة' ),
		'trust_2'         => array( 'optimum_shop', 'text', 'شارة الثقة 2', '' ),
		'trust_3'         => array( 'optimum_shop', 'text', 'شارة الثقة 3', '' ),
		'trust_4'         => array( 'optimum_shop', 'text', 'شارة الثقة 4', '' ),

		'footer_about'    => array( 'optimum_footer', 'textarea', 'نبذة عن المتجر', '' ),
		'payment_methods' => array( 'optimum_footer', 'text', 'وسائل الدفع', 'مفصولة بفواصل، مثال: مدى, فيزا, Apple Pay, تابي' ),
		'instagram'       => array( 'optimum_footer', 'url', 'رابط انستقرام', '' ),
		'snapchat'        => array( 'optimum_footer', 'url', 'رابط سناب شات', '' ),
		'tiktok'          => array( 'optimum_footer', 'url', 'رابط تيك توك', '' ),
		'x'               => array( 'optimum_footer', 'url', 'رابط X (تويتر)', '' ),
	);

	for ( $i = 1; $i <= 4; $i++ ) {
		$fields[ "feature_{$i}_title" ] = array( 'optimum_features', 'text', "الميزة {$i}: العنوان", '' );
		$fields[ "feature_{$i}_text" ]  = array( 'optimum_features', 'text', "الميزة {$i}: الوصف", '' );
	}
	for ( $i = 1; $i <= 3; $i++ ) {
		$fields[ "step_{$i}_title" ] = array( 'optimum_features', 'text', "الخطوة {$i}: العنوان", '' );
		$fields[ "step_{$i}_text" ]  = array( 'optimum_features', 'text', "الخطوة {$i}: الوصف", '' );
	}
	for ( $i = 1; $i <= 5; $i++ ) {
		$fields[ "faq_{$i}_q" ] = array( 'optimum_faq', 'text', "السؤال {$i}", '' );
		$fields[ "faq_{$i}_a" ] = array( 'optimum_faq', 'textarea', "الإجابة {$i}", 'لا يظهر السؤال في الموقع حتى تكتب إجابته' );
	}

	$sanitizers = array(
		'text'     => 'sanitize_text_field',
		'textarea' => 'sanitize_textarea_field',
		'url'      => 'esc_url_raw',
		'email'    => 'sanitize_email',
		'phone'    => 'optimum_sanitize_phone',
		'number'   => 'absint',
		'image'    => 'esc_url_raw',
	);

	foreach ( $fields as $key => $field ) {
		list( $section, $type, $label, $description ) = $field;

		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitizers[ $type ],
			)
		);

		if ( 'image' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					$key,
					array(
						'label'       => $label,
						'description' => $description,
						'section'     => $section,
					)
				)
			);
			continue;
		}

		$wp_customize->add_control(
			$key,
			array(
				'label'       => $label,
				'description' => $description,
				'section'     => $section,
				'type'        => 'phone' === $type ? 'tel' : $type,
			)
		);
	}

	$colors = array(
		'color_primary' => __( 'اللون الأساسي (الأزرار والروابط)', 'optimum' ),
		'color_accent'  => __( 'اللون الذهبي (اللمسات)', 'optimum' ),
		'color_dark'    => __( 'اللون الداكن (الخلفيات الداكنة والنصوص)', 'optimum' ),
	);
	foreach ( $colors as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				$key,
				array(
					'label'   => $label,
					'section' => 'optimum_colors',
				)
			)
		);
	}
}
add_action( 'customize_register', 'optimum_customize_register' );

/**
 * متغيرات CSS من الألوان المختارة.
 *
 * @return string
 */
function optimum_customizer_css() {
	return sprintf(
		':root{--c-primary:%1$s;--c-accent:%2$s;--c-dark:%3$s;}',
		esc_attr( optimum_mod( 'color_primary' ) ),
		esc_attr( optimum_mod( 'color_accent' ) ),
		esc_attr( optimum_mod( 'color_dark' ) )
	);
}
