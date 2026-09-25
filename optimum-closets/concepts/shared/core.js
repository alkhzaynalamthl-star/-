/*
 * Shared helpers for the concept previews: language switching (AR RTL / EN LTR),
 * number & currency formatting, responsive <picture> markup and reveal-on-scroll.
 */
( function () {
	'use strict';

	var D = window.OC_DATA;
	var IM = window.OC_IMAGES || {};
	var params = new URLSearchParams( location.search );
	var stored = null;
	try { stored = localStorage.getItem( 'oc-lang' ); } catch ( e ) {}
	var hash = ( location.hash || '' ).replace( '#', '' );
	var lang = params.get( 'lang' ) || ( hash === 'en' || hash === 'ar' ? hash : '' ) || stored || 'ar';
	if ( lang !== 'en' ) { lang = 'ar'; }

	var OC = window.OC = {
		get lang() { return lang; },
		reduced: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches,
		t: function ( key ) {
			var s = D.strings[ key ];
			return s ? s[ lang ] : key;
		},
		L: function ( obj ) { return obj ? ( obj[ lang ] !== undefined ? obj[ lang ] : obj.ar ) : ''; },
		num: function ( n ) {
			// Western digits in both languages (the norm in Saudi e-commerce), wrapped for correct bidi.
			return new Intl.NumberFormat( lang === 'ar' ? 'ar-SA-u-nu-latn' : 'en-SA' ).format( n );
		},
		money: function ( n ) {
			var v = OC.num( n );
			return lang === 'ar'
				? '<bdi class="money">' + v + '&nbsp;<span class="cur">ر.س</span></bdi>'
				: '<bdi class="money"><span class="cur">SAR</span>&nbsp;' + v + '</bdi>';
		},
		dims: function ( d ) {
			return '<bdi class="dims" dir="ltr">' + d.join( ' × ' ) + '</bdi>&nbsp;' + OC.t( 'cm' );
		},
		pic: function ( name, sizesAttr, cls, eager ) {
			var m = IM[ name ];
			var alt = m ? m.alt[ lang ] : '';
			if ( ! m ) {
				return '<picture class="img-missing ' + ( cls || '' ) + '" style="aspect-ratio:4 / 5"></picture>';
			}
			var srcset = m.sizes.map( function ( w ) { return base + name + '-' + w + '.webp ' + w + 'w'; } ).join( ', ' );
			var ratio = m.w + ' / ' + m.h;
			return '<picture class="' + ( cls || '' ) + '" style="aspect-ratio:' + ratio + '">' +
				'<source type="image/webp" srcset="' + srcset + '" sizes="' + ( sizesAttr || '100vw' ) + '">' +
				'<img src="' + base + name + '-' + m.fallback + '.jpg" alt="' + alt.replace( /"/g, '&quot;' ) + '" width="' + m.w + '" height="' + m.h + '"' +
				( eager ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"' ) + '>' +
				'</picture>';
		},
		setLang: function ( l ) {
			lang = l === 'en' ? 'en' : 'ar';
			try { localStorage.setItem( 'oc-lang', lang ); } catch ( e ) {}
			try {
				var u = new URL( location.href );
				u.hash = lang;
				u.searchParams.delete( 'lang' );
				history.replaceState( null, '', u );
			} catch ( e ) {}
			OC.apply();
		},
		apply: function () {
			var html = document.documentElement;
			html.lang = lang;
			html.dir = lang === 'ar' ? 'rtl' : 'ltr';
			document.querySelectorAll( '[data-t]' ).forEach( function ( el ) {
				el.textContent = OC.t( el.getAttribute( 'data-t' ) );
			} );
			document.querySelectorAll( '[data-t-attr]' ).forEach( function ( el ) {
				var p = el.getAttribute( 'data-t-attr' ).split( ':' );
				el.setAttribute( p[ 0 ], OC.t( p[ 1 ] ) );
			} );
			if ( typeof OC.render === 'function' ) {
				OC.render();
			}
			OC.observe();
		},
		observe: function () {
			var els = document.querySelectorAll( '.reveal:not(.is-in)' );
			if ( OC.reduced || ! ( 'IntersectionObserver' in window ) ) {
				els.forEach( function ( el ) { el.classList.add( 'is-in' ); } );
				return;
			}
			var io = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( e ) {
					if ( e.isIntersecting ) {
						e.target.classList.add( 'is-in' );
						io.unobserve( e.target );
					}
				} );
			}, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 } );
			els.forEach( function ( el ) { io.observe( el ); } );
		},
		esc: function ( s ) {
			return String( s ).replace( /[&<>"]/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ]; } );
		}
	};

	var base = ( document.documentElement.getAttribute( 'data-img-base' ) || '../assets/img/renders/' );

	document.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '[data-lang-toggle]' );
		if ( b ) {
			e.preventDefault();
			OC.setLang( lang === 'ar' ? 'en' : 'ar' );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function () { OC.apply(); } );
} )();
