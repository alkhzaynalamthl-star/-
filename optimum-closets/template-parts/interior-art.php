<?php
/**
 * داخل الخزانة المضاء: يظهر خلف الأبواب إلى أن تُرفع صورة حقيقية.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="interior-art" viewBox="0 0 400 560" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	<defs>
		<linearGradient id="ia-back" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#3a2f25"/>
			<stop offset="1" stop-color="#1c1712"/>
		</linearGradient>
		<radialGradient id="ia-glow" cx=".5" cy="0" r="1">
			<stop offset="0" stop-color="#ffe2a8" stop-opacity=".75"/>
			<stop offset=".5" stop-color="#ffcf7a" stop-opacity=".12"/>
			<stop offset="1" stop-color="#ffcf7a" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="ia-shelf" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#d9b58a"/>
			<stop offset="1" stop-color="#8a6443"/>
		</linearGradient>
	</defs>
	<rect width="400" height="560" fill="url(#ia-back)"/>
	<rect x="198" width="4" height="560" fill="#2a221b"/>
	<!-- right: hanging -->
	<rect x="214" y="52" width="176" height="6" rx="3" fill="#c9a45c"/>
	<g>
		<path d="M226 60h24l4 250h-32z" fill="#e9e2d6"/>
		<path d="M256 60h24l6 200h-36z" fill="#2b2723"/>
		<path d="M286 60h24l3 280h-30z" fill="#7d8b80"/>
		<path d="M316 60h24l5 220h-34z" fill="#c9a45c"/>
		<path d="M346 60h24l4 260h-32z" fill="#6b4f3a"/>
	</g>
	<rect x="206" y="372" width="194" height="8" fill="url(#ia-shelf)"/>
	<g fill="#8a6443">
		<rect x="214" y="392" width="178" height="74" rx="3"/>
		<rect x="214" y="474" width="178" height="74" rx="3"/>
	</g>
	<g fill="#e7c68c"><rect x="283" y="426" width="40" height="4" rx="2"/><rect x="283" y="508" width="40" height="4" rx="2"/></g>
	<!-- left: shelves -->
	<g fill="url(#ia-shelf)">
		<rect x="0" y="120" width="198" height="8"/>
		<rect x="0" y="230" width="198" height="8"/>
		<rect x="0" y="340" width="198" height="8"/>
		<rect x="0" y="450" width="198" height="8"/>
	</g>
	<g>
		<rect x="20" y="86" width="60" height="34" rx="3" fill="#d9cbb8"/>
		<rect x="92" y="96" width="80" height="24" rx="3" fill="#2b2723"/>
		<rect x="22" y="212" width="150" height="8" rx="4" fill="#7d8b80"/>
		<rect x="26" y="220" width="142" height="10" rx="4" fill="#e9e2d6"/>
		<rect x="22" y="200" width="150" height="12" rx="4" fill="#c9a45c"/>
		<rect x="30" y="300" width="44" height="40" rx="4" fill="#c9a45c"/>
		<rect x="86" y="310" width="36" height="30" rx="4" fill="#6b4f3a"/>
		<rect x="134" y="306" width="44" height="34" rx="4" fill="#e9e2d6"/>
		<path d="M30 450c0-18 8-24 22-24s22 6 22 24z" fill="#2b2723"/>
		<path d="M90 450c0-18 8-24 22-24s22 6 22 24z" fill="#8c3b2f"/>
		<path d="M150 450c0-14 6-20 18-20s18 6 18 20z" fill="#e9e2d6"/>
	</g>
	<!-- warm LED strips -->
	<g fill="#fff1cc">
		<rect x="0" y="128" width="198" height="2" opacity=".9"/>
		<rect x="0" y="238" width="198" height="2" opacity=".9"/>
		<rect x="0" y="348" width="198" height="2" opacity=".9"/>
		<rect x="206" y="44" width="194" height="2" opacity=".9"/>
	</g>
	<rect width="400" height="560" fill="url(#ia-glow)"/>
</svg>
