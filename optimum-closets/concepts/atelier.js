( function () {
	'use strict';
	var D = OC_DATA;
	var $ = function ( s ) { return document.querySelector( s ); };
	var matIndex = 0;
	var cardState = {};

	function catLink( c ) { return '#cat-' + c.id; }

	OC.render = function () {
		var L = OC.L;
		// Mega menu + drawer + footer categories
		$( '#mega-list' ).innerHTML = D.categories.map( function ( c ) {
			return '<a class="mega-card" href="' + catLink( c ) + '">' + OC.pic( c.img, '180px', 'mega-img' ) + '<span>' + L( c ) + '</span></a>';
		} ).join( '' );
		$( '#drawer-cats' ).innerHTML = D.categories.map( function ( c ) {
			return '<li><a href="' + catLink( c ) + '">' + L( c ) + '</a></li>';
		} ).join( '' );
		$( '#ftr-cats' ).innerHTML = $( '#drawer-cats' ).innerHTML;

		// Categories: editorial asymmetric grid
		$( '#cats' ).innerHTML = D.categories.map( function ( c, i ) {
			return '<a class="cat reveal cat-' + i + '" href="' + catLink( c ) + '">' +
				OC.pic( c.img, i === 3 ? '(min-width: 900px) 50vw, 80vw' : '(min-width: 900px) 25vw, 70vw', 'cat-img' ) +
				'<span class="cat-cap"><span class="cat-n">0' + ( i + 1 ) + '</span><span class="cat-name">' + L( c ) + '</span>' +
				'<svg class="arr" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></a>';
		} ).join( '' );

		// Product cards
		$( '#cards' ).innerHTML = D.products.slice( 0, 4 ).map( function ( p ) {
			var f = cardState[ p.id ] || p.finishes[ 0 ];
			var cat = D.categories.filter( function ( c ) { return c.id === p.cat; } )[ 0 ];
			var hasOpen = p.cat !== 'walk-in';
			var price = ( p.from ? '<span class="from">' + OC.t( 'from' ) + '</span> ' : '' ) + OC.money( p.price );
			return '<article class="card reveal" data-id="' + p.id + '">' +
				'<a class="card-media" href="#p-' + p.id + '">' +
				OC.pic( p.img( f, 'closed' ), '(min-width: 1100px) 25vw, (min-width: 700px) 45vw, 85vw', 'card-img' ) +
				( hasOpen ? OC.pic( p.img( f, 'open' ), '(min-width: 1100px) 25vw, (min-width: 700px) 45vw, 85vw', 'card-img card-img-open' ) : '' ) +
				'<span class="render-tag">' + OC.t( 'rendered' ) + '</span></a>' +
				'<div class="card-body">' +
				'<p class="card-cat">' + L( cat.short ) + '</p>' +
				'<h3 class="card-name"><a href="#p-' + p.id + '">' + L( p.name ) + '</a></h3>' +
				'<p class="card-dims">' + OC.dims( p.dims ) + '</p>' +
				'<div class="card-foot"><p class="card-price">' + price + '</p>' +
				'<div class="swatches" role="radiogroup" aria-label="' + OC.esc( L( { ar: 'التشطيب', en: 'Finish' } ) ) + '">' +
				p.finishes.map( function ( k ) {
					var fin = D.finishes[ k ];
					return '<button type="button" role="radio" class="sw' + ( k === f ? ' is-on' : '' ) + '" aria-checked="' + ( k === f ) + '" data-f="' + k + '" title="' + OC.esc( L( fin ) ) + '" aria-label="' + OC.esc( L( fin ) ) + '">' + OC.pic( fin.swatch, '28px', 'sw-img' ) + '</button>';
				} ).join( '' ) + '</div></div></div></article>';
		} ).join( '' );

		// Materials
		$( '#mat-list' ).innerHTML = D.materials.map( function ( m, i ) {
			var t = m[ OC.lang ];
			return '<li><button type="button" role="tab" class="mat' + ( i === matIndex ? ' is-on' : '' ) + '" aria-selected="' + ( i === matIndex ) + '" data-i="' + i + '">' +
				'<span class="mat-n">0' + ( i + 1 ) + '</span><span><strong>' + t[ 0 ] + '</strong><span>' + t[ 1 ] + '</span></span></button></li>';
		} ).join( '' );
		$( '#mat-media' ).innerHTML = D.materials.map( function ( m, i ) {
			return '<div class="mat-frame' + ( i === matIndex ? ' is-on' : '' ) + '">' + OC.pic( m.img, '(min-width: 900px) 50vw, 100vw', 'mat-img' ) + '</div>';
		} ).join( '' ) + '<span class="render-tag">' + OC.t( 'rendered' ) + '</span>';

		// Steps
		$( '#steps' ).innerHTML = D.steps.map( function ( s, i ) {
			var t = s[ OC.lang ];
			return '<li class="step reveal"><span class="step-n">0' + ( i + 1 ) + '</span><h3>' + t[ 0 ] + '</h3><p>' + t[ 1 ] + '</p></li>';
		} ).join( '' );

		$( '#bespoke-media' ).innerHTML = OC.pic( 'bespoke-wall-wide', '100vw', 'bespoke-img' );

		// Showrooms
		$( '#show' ).innerHTML = D.showrooms.map( function ( s ) {
			return '<article class="show reveal"><h3>' + L( s.city ) + '</h3><p>' + L( s.address ) + '</p>' +
				'<p class="show-links"><a href="' + s.map + '" target="_blank" rel="noopener">' + OC.t( 'directions' ) + ' ↗</a>' +
				'<a href="tel:' + D.contact.phone + '"><bdi dir="ltr">' + D.contact.phoneDisplay + '</bdi></a></p></article>';
		} ).join( '' );
		$( '#ftr-contact' ).innerHTML = '<p class="eyebrow">' + OC.t( 'contact' ) + '</p><p><a href="tel:' + D.contact.phone + '"><bdi dir="ltr">' + D.contact.phoneDisplay + '</bdi></a><br><a href="mailto:' + D.contact.email + '">' + D.contact.email + '</a></p>' +
			D.showrooms.map( function ( s ) { return '<p>' + L( s.city ) + ' — ' + L( s.address ) + '</p>'; } ).join( '' );

		var heroAlt = ( OC_IMAGES[ 'hero-glass-wide' ] || { alt: {} } ).alt[ OC.lang ] || '';
		$( '#hero-img' ).alt = heroAlt;
	};

	document.addEventListener( 'click', function ( e ) {
		var sw = e.target.closest( '.sw' );
		if ( sw ) {
			var id = sw.closest( '.card' ).getAttribute( 'data-id' );
			cardState[ id ] = sw.getAttribute( 'data-f' );
			OC.render();
			document.querySelectorAll( '.reveal' ).forEach( function ( el ) { el.classList.add( 'is-in' ); } );
			var again = document.querySelector( '.card[data-id="' + id + '"] .sw.is-on' );
			if ( again ) { again.focus(); }
			return;
		}
		var m = e.target.closest( '.mat' );
		if ( m ) {
			matIndex = +m.getAttribute( 'data-i' );
			document.querySelectorAll( '.mat' ).forEach( function ( b, i ) { b.classList.toggle( 'is-on', i === matIndex ); b.setAttribute( 'aria-selected', i === matIndex ); } );
			document.querySelectorAll( '.mat-frame' ).forEach( function ( f, i ) { f.classList.toggle( 'is-on', i === matIndex ); } );
			return;
		}
		var mb = e.target.closest( '.nav-link[aria-controls="mega"]' );
		var mega = $( '#mega' );
		if ( mb ) {
			var open = mega.hasAttribute( 'hidden' );
			mega.toggleAttribute( 'hidden', ! open );
			mb.setAttribute( 'aria-expanded', open );
			return;
		}
		if ( ! e.target.closest( '#mega' ) && mega && ! mega.hasAttribute( 'hidden' ) ) {
			mega.setAttribute( 'hidden', '' );
			$( '.nav-link[aria-controls="mega"]' ).setAttribute( 'aria-expanded', 'false' );
		}
		var menu = e.target.closest( '.menu-btn' );
		var drawer = $( '#drawer' );
		if ( menu ) { drawer.removeAttribute( 'hidden' ); menu.setAttribute( 'aria-expanded', 'true' ); drawer.querySelector( 'button' ).focus(); return; }
		if ( e.target.closest( '.drawer-x' ) || e.target === drawer || e.target.closest( '.drawer a' ) ) {
			drawer.setAttribute( 'hidden', '' );
			$( '.menu-btn' ).setAttribute( 'aria-expanded', 'false' );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			var mega = $( '#mega' );
			if ( mega && ! mega.hasAttribute( 'hidden' ) ) { mega.setAttribute( 'hidden', '' ); $( '.nav-link[aria-controls="mega"]' ).focus(); }
			var d = $( '#drawer' );
			if ( d && ! d.hasAttribute( 'hidden' ) ) { d.setAttribute( 'hidden', '' ); $( '.menu-btn' ).focus(); }
		}
	} );

	var hdr = $( '#hdr' );
	var onScroll = function () { hdr.classList.toggle( 'is-solid', window.scrollY > 40 ); };
	window.addEventListener( 'scroll', onScroll, { passive: true } );
	onScroll();
} )();
