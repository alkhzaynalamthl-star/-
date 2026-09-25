<?php
/**
 * رسم توضيحي لخزانة ملابس، يظهر بدلاً من الصورة إلى أن تُرفع صورة حقيقية.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="wardrobe-art" viewBox="0 0 420 460" xmlns="http://www.w3.org/2000/svg" role="presentation">
	<defs>
		<linearGradient id="wa-wood" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0" stop-color="#c79a6b"/>
			<stop offset="1" stop-color="#8a5a33"/>
		</linearGradient>
		<linearGradient id="wa-inner" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#f4ece2"/>
			<stop offset="1" stop-color="#e6d8c6"/>
		</linearGradient>
	</defs>
	<ellipse cx="210" cy="440" rx="190" ry="12" fill="#000" opacity=".18"/>
	<rect x="40" y="20" width="340" height="415" rx="6" fill="url(#wa-wood)"/>
	<rect x="54" y="34" width="312" height="387" rx="3" fill="url(#wa-inner)"/>
	<!-- rail + clothes -->
	<rect x="66" y="62" width="160" height="5" rx="2.5" fill="#b08968"/>
	<g fill="none" stroke="#8a6a50" stroke-width="2">
		<path d="M86 67v10M112 67v10M138 67v10M164 67v10M190 67v10"/>
	</g>
	<path d="M76 78h22l4 150H72z" fill="#2d2a26"/>
	<path d="M102 78h22l6 120h-34z" fill="#c8a15a"/>
	<path d="M128 78h22l3 170h-28z" fill="#7a8b7f"/>
	<path d="M154 78h22l5 130h-32z" fill="#e9e1d6" stroke="#cdbfae"/>
	<path d="M180 78h22l4 160h-30z" fill="#6b4f3a"/>
	<!-- shelves -->
	<rect x="240" y="34" width="4" height="387" fill="#caa987"/>
	<g fill="#caa987">
		<rect x="244" y="110" width="122" height="5"/>
		<rect x="244" y="185" width="122" height="5"/>
		<rect x="244" y="260" width="122" height="5"/>
	</g>
	<g>
		<rect x="258" y="86" width="40" height="24" rx="2" fill="#d9cbb8"/>
		<rect x="304" y="92" width="48" height="18" rx="2" fill="#2d2a26"/>
		<rect x="258" y="160" width="94" height="10" rx="2" fill="#7a8b7f"/>
		<rect x="262" y="171" width="86" height="14" rx="2" fill="#e9e1d6"/>
		<rect x="264" y="232" width="30" height="28" rx="3" fill="#c8a15a"/>
		<rect x="304" y="238" width="42" height="22" rx="3" fill="#6b4f3a"/>
	</g>
	<!-- drawers -->
	<g>
		<rect x="66" y="300" width="162" height="54" rx="3" fill="url(#wa-wood)"/>
		<rect x="66" y="360" width="162" height="54" rx="3" fill="url(#wa-wood)"/>
		<rect x="244" y="300" width="122" height="114" rx="3" fill="url(#wa-wood)"/>
		<rect x="130" y="325" width="36" height="4" rx="2" fill="#f1e2c6"/>
		<rect x="130" y="385" width="36" height="4" rx="2" fill="#f1e2c6"/>
		<rect x="287" y="355" width="36" height="4" rx="2" fill="#f1e2c6"/>
	</g>
	<!-- warm light -->
	<rect x="54" y="34" width="312" height="6" fill="#fff6dc" opacity=".9"/>
</svg>
