<?php
/**
 * Images: bundled renders, bilingual alt text, provenance labels.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manifest of bundled renders (assets/img/renders/images.json).
 *
 * @return array
 */
function optimum_render_manifest() {
	static $m = null;
	if ( null === $m ) {
		$file = OPTIMUM_DIR . '/assets/img/renders/images.json';
		$m    = file_exists( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $m;
}

/**
 * Hotspot coordinates projected from the 3D scenes.
 *
 * @param string $name Render name.
 * @return array key => [x%, y%]
 */
function optimum_render_hotspots( $name ) {
	static $h = null;
	if ( null === $h ) {
		$file = OPTIMUM_DIR . '/assets/img/renders/hotspots.json';
		$h    = file_exists( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return isset( $h[ $name ] ) ? $h[ $name ] : array();
}

/**
 * <picture> for a bundled render.
 *
 * @param string $name  Render name.
 * @param string $sizes sizes attribute.
 * @param array  $args  class, eager, media_mobile (render name for small screens).
 * @return string
 */
function optimum_render_picture( $name, $sizes = '100vw', $args = array() ) {
	$m = optimum_render_manifest();
	if ( empty( $m[ $name ] ) ) {
		return '';
	}
	$args = wp_parse_args( $args, array( 'class' => '', 'eager' => false, 'mobile' => '' ) );
	$base = OPTIMUM_URI . '/assets/img/renders/';
	$img  = $m[ $name ];
	$set  = function ( $n, $meta ) use ( $base ) {
		return implode(
			', ',
			array_map(
				function ( $w ) use ( $base, $n ) {
					return esc_url( $base . $n . '-' . $w . '.webp' ) . ' ' . (int) $w . 'w';
				},
				$meta['sizes']
			)
		);
	};
	$out = '<picture class="' . esc_attr( $args['class'] ) . '">';
	if ( $args['mobile'] && ! empty( $m[ $args['mobile'] ] ) ) {
		$out .= '<source media="(max-width: 699px)" type="image/webp" srcset="' . $set( $args['mobile'], $m[ $args['mobile'] ] ) . '" sizes="100vw">';
	}
	$out .= '<source type="image/webp" srcset="' . $set( $name, $img ) . '" sizes="' . esc_attr( $sizes ) . '">';
	$out .= sprintf(
		'<img src="%s" alt="%s" width="%d" height="%d"%s>',
		esc_url( $base . $name . '-' . $img['fallback'] . '.jpg' ),
		esc_attr( optimum_is_en() ? $img['alt']['en'] : $img['alt']['ar'] ),
		(int) $img['w'],
		(int) $img['h'],
		$args['eager'] ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"'
	);
	return $out . '</picture>';
}

/**
 * English alt text for attachments in English; Arabic alt is the standard alt field.
 *
 * @param array   $attr       Attributes.
 * @param WP_Post $attachment Attachment.
 * @return array
 */
function optimum_attachment_alt( $attr, $attachment ) {
	if ( optimum_is_en() ) {
		$en = get_post_meta( $attachment->ID, '_oc_alt_en', true );
		if ( $en ) {
			$attr['alt'] = $en;
		}
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'optimum_attachment_alt', 10, 2 );

/**
 * Is an attachment an illustrative render (not a photo of real work)?
 *
 * @param int $id Attachment ID.
 * @return bool
 */
function optimum_is_render( $id ) {
	return (bool) get_post_meta( $id, '_oc_render', true );
}

/**
 * Small provenance label.
 *
 * @return string
 */
function optimum_render_tag() {
	return '<span class="render-tag">' . esc_html__( 'Illustrative render', 'optimum' ) . '</span>';
}

/**
 * Attachment field for the English alt text and render flag.
 *
 * @param array   $fields Fields.
 * @param WP_Post $post   Attachment.
 * @return array
 */
function optimum_attachment_fields( $fields, $post ) {
	$fields['oc_alt_en'] = array(
		'label' => __( 'Alt text (English)', 'optimum' ),
		'input' => 'text',
		'value' => get_post_meta( $post->ID, '_oc_alt_en', true ),
		'helps' => __( 'The standard Alt Text field is used for Arabic.', 'optimum' ),
	);
	$checked            = optimum_is_render( $post->ID ) ? ' checked' : '';
	$fields['oc_render'] = array(
		'label' => __( 'Illustrative render', 'optimum' ),
		'input' => 'html',
		'html'  => '<label><input type="checkbox" name="attachments[' . (int) $post->ID . '][oc_render]" value="1"' . $checked . '> ' . esc_html__( 'Generated image — label it and never present it as completed work', 'optimum' ) . '</label>',
	);
	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'optimum_attachment_fields', 10, 2 );

add_filter(
	'attachment_fields_to_save',
	function ( $post, $attachment ) {
		if ( isset( $attachment['oc_alt_en'] ) ) {
			update_post_meta( $post['ID'], '_oc_alt_en', sanitize_text_field( $attachment['oc_alt_en'] ) );
		}
		update_post_meta( $post['ID'], '_oc_render', empty( $attachment['oc_render'] ) ? 0 : 1 );
		return $post;
	},
	10,
	2
);
