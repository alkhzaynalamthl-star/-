<?php
/**
 * Customizer: Appearance → Customize → Optimum Closets.
 *
 * Defaults marked "confirmed" come from the company's public contact page
 * (optimum-closets.com/en/contact-us) as indexed by search engines on
 * 2026-09-25. Everything else is empty until the company confirms it.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values.
 *
 * @return array
 */
function optimum_defaults() {
	return array(
		'concept'         => 'atelier',
		'preview_mode'    => true,
		// Confirmed.
		'phone'           => '+966541200434',
		'email'           => 'info@optimum-closets.com',
		'jeddah_ar'       => 'شارع الأمير سلطان، حي النعيم',
		'jeddah_en'       => 'Prince Sultan Street, Al-Naeem District',
		'makkah_ar'       => 'الطريق الدائري الثالث',
		'makkah_en'       => 'Third Ring Road',
		'lead_time_ar'    => 'من 2 إلى 4 أسابيع من اعتماد التصميم حتى التركيب',
		'lead_time_en'    => '2–4 weeks from final design approval to installation',
		// Not confirmed yet: left empty so nothing is invented.
		'whatsapp'        => '',
		'jeddah_map'      => 'https://www.google.com/maps/search/?api=1&query=Optimum+Closets+Prince+Sultan+Street+Al+Naeem+Jeddah',
		'makkah_map'      => 'https://www.google.com/maps/search/?api=1&query=Optimum+Closets+Third+Ring+Road+Makkah',
		'hours_ar'        => '',
		'hours_en'        => '',
		'instagram'       => 'https://www.instagram.com/optimum_closests/',
		'hero_image'      => '',
		'hero_image_m'    => '',
		'hero_title_ar'   => '',
		'hero_title_en'   => '',
		'hero_text_ar'    => '',
		'hero_text_en'    => '',
		'request_email'   => '',
		'request_webhook' => '',
	);
}

/**
 * Read a setting with its default.
 *
 * @param string $key Key.
 * @return mixed
 */
function optimum_mod( $key ) {
	$d = optimum_defaults();
	return get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

/**
 * Read a bilingual setting (key_ar / key_en) for the current language.
 *
 * @param string $key      Base key.
 * @param string $fallback Fallback when both are empty.
 * @return string
 */
function optimum_mod_i18n( $key, $fallback = '' ) {
	$v = optimum_pick( optimum_mod( $key . '_ar' ), optimum_mod( $key . '_en' ) );
	return '' !== (string) $v ? $v : $fallback;
}

/**
 * Register the panel.
 *
 * @param WP_Customize_Manager $c Manager.
 */
function optimum_customize_register( $c ) {
	$d = optimum_defaults();
	$c->add_panel( 'optimum', array( 'title' => __( 'Optimum Closets', 'optimum' ), 'priority' => 30 ) );

	$sections = array(
		'optimum_design'    => __( 'Design & preview mode', 'optimum' ),
		'optimum_contact'   => __( 'Contact & showrooms', 'optimum' ),
		'optimum_home'      => __( 'Homepage hero', 'optimum' ),
		'optimum_integrate' => __( 'Requests & integrations', 'optimum' ),
	);
	foreach ( $sections as $id => $title ) {
		$c->add_section( $id, array( 'title' => $title, 'panel' => 'optimum' ) );
	}

	$choices = array();
	foreach ( optimum_concepts() as $k => $v ) {
		$choices[ $k ] = $v['label'];
	}
	$c->add_setting( 'concept', array( 'default' => 'atelier', 'sanitize_callback' => 'sanitize_key' ) );
	$c->add_control( 'concept', array( 'section' => 'optimum_design', 'type' => 'radio', 'label' => __( 'Store design direction', 'optimum' ), 'choices' => $choices ) );

	$c->add_setting( 'preview_mode', array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	$c->add_control(
		'preview_mode',
		array(
			'section'     => 'optimum_design',
			'type'        => 'checkbox',
			'label'       => __( 'Preview mode (demo data, no live payments)', 'optimum' ),
			'description' => __( 'Shows a preview banner and enables the “Preview — no payment” checkout method. Switch off only after a real payment gateway is configured.', 'optimum' ),
		)
	);

	$fields = array(
		'phone'           => array( 'optimum_contact', 'text', __( 'Phone (international format)', 'optimum' ), 'optimum_sanitize_phone' ),
		'whatsapp'        => array( 'optimum_contact', 'text', __( 'WhatsApp number (international, digits only). Leave empty to hide WhatsApp buttons.', 'optimum' ), 'optimum_sanitize_phone' ),
		'email'           => array( 'optimum_contact', 'email', __( 'E-mail', 'optimum' ), 'sanitize_email' ),
		'jeddah_ar'       => array( 'optimum_contact', 'text', __( 'Jeddah showroom address (Arabic)', 'optimum' ), 'sanitize_text_field' ),
		'jeddah_en'       => array( 'optimum_contact', 'text', __( 'Jeddah showroom address (English)', 'optimum' ), 'sanitize_text_field' ),
		'jeddah_map'      => array( 'optimum_contact', 'url', __( 'Jeddah map link', 'optimum' ), 'esc_url_raw' ),
		'makkah_ar'       => array( 'optimum_contact', 'text', __( 'Makkah showroom address (Arabic)', 'optimum' ), 'sanitize_text_field' ),
		'makkah_en'       => array( 'optimum_contact', 'text', __( 'Makkah showroom address (English)', 'optimum' ), 'sanitize_text_field' ),
		'makkah_map'      => array( 'optimum_contact', 'url', __( 'Makkah map link', 'optimum' ), 'esc_url_raw' ),
		'hours_ar'        => array( 'optimum_contact', 'text', __( 'Opening hours (Arabic) — empty until confirmed', 'optimum' ), 'sanitize_text_field' ),
		'hours_en'        => array( 'optimum_contact', 'text', __( 'Opening hours (English) — empty until confirmed', 'optimum' ), 'sanitize_text_field' ),
		'lead_time_ar'    => array( 'optimum_contact', 'text', __( 'Lead time (Arabic)', 'optimum' ), 'sanitize_text_field' ),
		'lead_time_en'    => array( 'optimum_contact', 'text', __( 'Lead time (English)', 'optimum' ), 'sanitize_text_field' ),
		'instagram'       => array( 'optimum_contact', 'url', __( 'Instagram link', 'optimum' ), 'esc_url_raw' ),
		'hero_title_ar'   => array( 'optimum_home', 'text', __( 'Headline (Arabic) — empty uses the theme text', 'optimum' ), 'sanitize_text_field' ),
		'hero_title_en'   => array( 'optimum_home', 'text', __( 'Headline (English)', 'optimum' ), 'sanitize_text_field' ),
		'hero_text_ar'    => array( 'optimum_home', 'textarea', __( 'Intro (Arabic)', 'optimum' ), 'sanitize_textarea_field' ),
		'hero_text_en'    => array( 'optimum_home', 'textarea', __( 'Intro (English)', 'optimum' ), 'sanitize_textarea_field' ),
		'request_email'   => array( 'optimum_integrate', 'email', __( 'Send new design requests to (default: site admin e-mail)', 'optimum' ), 'sanitize_email' ),
		'request_webhook' => array( 'optimum_integrate', 'url', __( 'Request handling service webhook URL (optional). Signed with OPTIMUM_WEBHOOK_SECRET from wp-config.php.', 'optimum' ), 'esc_url_raw' ),
	);
	foreach ( $fields as $key => $f ) {
		$c->add_setting( $key, array( 'default' => isset( $d[ $key ] ) ? $d[ $key ] : '', 'sanitize_callback' => $f[3] ) );
		$c->add_control( $key, array( 'section' => $f[0], 'type' => $f[1], 'label' => $f[2] ) );
	}

	foreach ( array( 'hero_image' => __( 'Hero image — wide (2400×1200)', 'optimum' ), 'hero_image_m' => __( 'Hero image — mobile (1080×1440)', 'optimum' ) ) as $key => $label ) {
		$c->add_setting( $key, array( 'default' => '', 'sanitize_callback' => 'absint' ) );
		$c->add_control( new WP_Customize_Media_Control( $c, $key, array( 'section' => 'optimum_home', 'label' => $label, 'mime_type' => 'image' ) ) );
	}
}
add_action( 'customize_register', 'optimum_customize_register' );

/**
 * Keep "+" and digits.
 *
 * @param string $value Value.
 * @return string
 */
function optimum_sanitize_phone( $value ) {
	return preg_replace( '/[^\d+]/', '', (string) $value );
}
