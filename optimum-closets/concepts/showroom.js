( function () {
	'use strict';
	var D = OC_DATA;
	var $ = function ( s ) { return document.querySelector( s ); };
	var TYPES = [
		{ id: 'glass', label: { ar: 'زجاجية', en: 'Glass' }, img: function ( s ) { return 'glass-bronze-' + s; } },
		{ id: 'hinged', label: { ar: 'خشبية', en: 'Wooden' }, img: function ( s ) { return 'hinged-walnut-' + s; } },
		{ id: 'sliding', label: { ar: 'منزلقة', en: 'Sliding' }, img: function ( s ) { return 'sliding-oak-' + s; } }
	];
	var type = 0;
	var manual = null; // 'open' | 'closed' when the user used the toggle
	var progress = 0;
	var finish = 'oak';
	var cardOpen = {};

	function stageHTML() {
		var t = TYPES[ type ];
		return '<div class="layer layer-closed">' + OC.pic( t.img( 'closed' ), '100vw', 'stage-img', true ) + '</div>' +
			'<div class="layer layer-open">' + OC.pic( t.img( 'open' ), '100vw', 'stage-img' ) + '</div>' +
			'<span class="render-tag">' + OC.t( 'rendered' ) + '</span>';
	}

	function hotspotsHTML() {
		var t = TYPES[ type ];
		var spots = ( window.OC_HOTSPOTS || {} )[ t.img( 'open' ) ] || {};
		return Object.keys( spots ).map( function ( k, i ) {
			var p = spots[ k ];
			return '<button type="button" class="hs" data-x="' + p[ 0 ] + '" data-y="' + p[ 1 ] + '" style="--d:' + ( i * 120 ) + 'ms" aria-describedby="hs-' + k + '">' +
				'<span class="hs-dot" aria-hidden="true"></span><span class="hs-label" id="hs-' + k + '" role="tooltip">' + OC.t( 'hs_' + k ) + '</span></button>';
		} ).join( '' );
	}

	// Map hotspot percentages (relative to the full render) onto the object-fit: cover crop.
	function placeHotspots() {
		var host = $( '#hotspots' );
		var img = document.querySelector( '.layer-open img' );
		var m = ( window.OC_IMAGES || {} )[ TYPES[ type ].img( 'open' ) ];
		if ( ! host || ! img || ! m ) { return; }
		var W = host.clientWidth, H = host.clientHeight;
		var s = Math.max( W / m.w, H / m.h );
		var dw = m.w * s, dh = m.h * s;
		var pos = getComputedStyle( img ).objectPosition.split( ' ' ).map( parseFloat );
		var ox = ( W - dw ) * ( pos[ 0 ] / 100 ), oy = ( H - dh ) * ( ( pos[ 1 ] || 50 ) / 100 );
		host.querySelectorAll( '.hs' ).forEach( function ( el ) {
			var x = ox + dw * parseFloat( el.getAttribute( 'data-x' ) ) / 100;
			var y = oy + dh * parseFloat( el.getAttribute( 'data-y' ) ) / 100;
			el.style.left = x + 'px';
			el.style.top = y + 'px';
			el.hidden = x < 24 || y < 24 || x > W - 24 || y > H - 24;
		} );
	}
	window.addEventListener( 'resize', placeHotspots );

	function applyState() {
		var open = manual ? manual === 'open' : progress > 0.45;
		var mix = manual ? ( open ? 1 : 0 ) : Math.min( 1, Math.max( 0, ( progress - 0.2 ) / 0.4 ) );
		var st = $( '.stage' );
		st.style.setProperty( '--open', mix.toFixed( 3 ) );
		st.classList.toggle( 'is-open', open );
		document.querySelectorAll( '.seg-state button' ).forEach( function ( b ) {
			var on = b.getAttribute( 'data-state' ) === ( open ? 'open' : 'closed' );
			b.setAttribute( 'aria-checked', on );
		} );
	}

	function renderStage() {
		$( '#stage-media' ).innerHTML = stageHTML();
		$( '#hotspots' ).innerHTML = hotspotsHTML();
		$( '#type-seg' ).innerHTML = TYPES.map( function ( t, i ) {
			return '<button type="button" role="radio" aria-checked="' + ( i === type ) + '" data-type="' + i + '">' + OC.L( t.label ) + '</button>';
		} ).join( '' );
		applyState();
		placeHotspots();
	}

	function renderStudio() {
		$( '#fin-list' ).innerHTML = [ 'oak', 'walnut', 'cashmere' ].map( function ( k ) {
			var f = D.finishes[ k ];
			return '<button type="button" role="radio" class="fin" aria-checked="' + ( k === finish ) + '" data-fin="' + k + '">' + OC.pic( f.swatch, '56px', 'fin-img' ) + '<span>' + OC.L( f ) + '</span></button>';
		} ).join( '' );
	}

	function swapStudio( animate ) {
		var host = $( '#studio-media' );
		var next = document.createElement( 'div' );
		next.className = 'studio-layer' + ( animate && ! OC.reduced ? ' is-entering' : '' );
		next.innerHTML = OC.pic( 'hinged-' + finish + '-closed', '(min-width: 900px) 55vw, 100vw', 'studio-img' );
		host.appendChild( next );
		var old = host.querySelectorAll( '.studio-layer' );
		if ( animate && ! OC.reduced ) {
			requestAnimationFrame( function () { requestAnimationFrame( function () { next.classList.remove( 'is-entering' ); } ); } );
			setTimeout( function () { for ( var i = 0; i < old.length - 1; i++ ) { old[ i ].remove(); } }, 950 );
		} else {
			for ( var i = 0; i < old.length - 1; i++ ) { old[ i ].remove(); }
		}
		var label = host.querySelector( '.studio-label' ) || document.createElement( 'p' );
		label.className = 'studio-label';
		label.innerHTML = OC.L( D.finishes[ finish ] ) + ' · <span>' + OC.t( 'rendered' ) + '</span>';
		host.appendChild( label );
	}

	OC.render = function () {
		var L = OC.L;
		renderStage();
		$( '#rail' ).innerHTML = D.categories.map( function ( c, i ) {
			return '<a class="type-card" href="#cat-' + c.id + '">' + OC.pic( c.img, '(min-width: 900px) 30vw, 75vw', 'type-img' ) +
				'<span class="type-cap"><span class="type-n">0' + ( i + 1 ) + ' / 05</span><strong>' + L( c ) + '</strong></span></a>';
		} ).join( '' );
		renderStudio();
		$( '#studio-media' ).innerHTML = '';
		swapStudio( false );
		$( '#cards' ).innerHTML = D.products.slice( 0, 3 ).map( function ( p ) {
			var f = p.finishes[ 0 ];
			var st = cardOpen[ p.id ] ? 'open' : 'closed';
			return '<article class="card" data-id="' + p.id + '">' +
				'<div class="card-media">' + OC.pic( p.img( f, st ), '(min-width: 900px) 30vw, 90vw', 'card-img' ) +
				'<div class="mini-seg" role="radiogroup" aria-label="' + OC.esc( OC.t( 'inside' ) ) + '">' +
				'<button type="button" role="radio" data-card-state="closed" aria-checked="' + ( st === 'closed' ) + '">' + OC.t( 'closed' ) + '</button>' +
				'<button type="button" role="radio" data-card-state="open" aria-checked="' + ( st === 'open' ) + '">' + OC.t( 'open' ) + '</button></div></div>' +
				'<div class="card-body"><h3>' + L( p.name ) + '</h3><p class="muted">' + L( p.desc ) + '</p>' +
				'<div class="card-row"><span class="price">' + OC.money( p.price ) + '</span><span class="dims">' + OC.dims( p.dims ) + '</span></div>' +
				'<a class="btn btn-brass btn-sm" href="#p-' + p.id + '">' + OC.t( 'choose' ) + '</a></div></article>';
		} ).join( '' );
		$( '#timeline' ).innerHTML = '<span class="tl-fill" aria-hidden="true"></span>' + D.steps.map( function ( s, i ) {
			var t = s[ OC.lang ];
			return '<li class="tl-step"><span class="tl-dot">' + ( i + 1 ) + '</span><div><h3>' + t[ 0 ] + '</h3><p class="muted">' + t[ 1 ] + '</p></div></li>';
		} ).join( '' );
		$( '#bespoke-media' ).innerHTML = OC.pic( 'bespoke-wall', '(min-width: 900px) 40vw, 100vw', 'b-img' );
		$( '#show' ).innerHTML = D.showrooms.map( function ( s ) {
			return '<article class="show"><p class="kicker">' + L( s.city ) + '</p><h3>' + L( s.address ) + '</h3>' +
				'<div class="show-actions"><a class="btn btn-glass btn-sm" href="' + s.map + '" target="_blank" rel="noopener">' + OC.t( 'directions' ) + '</a>' +
				'<a class="btn btn-brass btn-sm" href="tel:' + D.contact.phone + '">' + OC.t( 'visit' ) + '</a></div></article>';
		} ).join( '' );
		$( '#ftr-contact' ).innerHTML = '<a href="tel:' + D.contact.phone + '"><bdi dir="ltr">' + D.contact.phoneDisplay + '</bdi></a> · <a href="mailto:' + D.contact.email + '">' + D.contact.email + '</a>';
		onScroll();
	};

	// Scroll-linked door opening (disabled for reduced motion; the toggle always works)
	var scene = $( '.scene' );
	function onScroll() {
		var r = scene.getBoundingClientRect();
		var total = scene.offsetHeight - window.innerHeight;
		progress = total > 0 ? Math.min( 1, Math.max( 0, -r.top / total ) ) : 0;
		if ( ! OC.reduced ) {
			applyState();
		}
		var tl = $( '#timeline' );
		if ( tl ) {
			var tr = tl.getBoundingClientRect();
			var p = Math.min( 1, Math.max( 0, ( window.innerHeight * 0.7 - tr.top ) / tr.height ) );
			tl.style.setProperty( '--tl', p.toFixed( 3 ) );
		}
	}
	window.addEventListener( 'scroll', onScroll, { passive: true } );
	window.addEventListener( 'resize', onScroll );
	if ( OC.reduced ) { document.documentElement.classList.add( 'reduced' ); }

	document.addEventListener( 'click', function ( e ) {
		var t = e.target.closest( '[data-type]' );
		if ( t ) { type = +t.getAttribute( 'data-type' ); renderStage(); $( '[data-type="' + type + '"]' ).focus(); return; }
		var s = e.target.closest( '[data-state]' );
		if ( s ) { manual = s.getAttribute( 'data-state' ); applyState(); return; }
		var f = e.target.closest( '[data-fin]' );
		if ( f ) {
			finish = f.getAttribute( 'data-fin' );
			document.querySelectorAll( '.fin' ).forEach( function ( b ) { b.setAttribute( 'aria-checked', b.getAttribute( 'data-fin' ) === finish ); } );
			swapStudio( true );
			return;
		}
		var c = e.target.closest( '[data-card-state]' );
		if ( c ) {
			var id = c.closest( '.card' ).getAttribute( 'data-id' );
			cardOpen[ id ] = c.getAttribute( 'data-card-state' ) === 'open';
			var p = D.products.filter( function ( x ) { return x.id === id; } )[ 0 ];
			var card = c.closest( '.card' );
			card.querySelector( '.card-media picture' ).outerHTML = OC.pic( p.img( p.finishes[ 0 ], cardOpen[ id ] ? 'open' : 'closed' ), '(min-width: 900px) 30vw, 90vw', 'card-img' );
			card.querySelectorAll( '[data-card-state]' ).forEach( function ( b ) { b.setAttribute( 'aria-checked', b === c ); } );
			return;
		}
		var r = e.target.closest( '[data-rail]' );
		if ( r ) {
			var rail = $( '#rail' );
			var dir = +r.getAttribute( 'data-rail' ) * ( document.dir === 'rtl' ? -1 : 1 );
			rail.scrollBy( { left: dir * rail.clientWidth * 0.8, behavior: OC.reduced ? 'auto' : 'smooth' } );
		}
	} );

	// Arrow keys move between radio options in any segmented control
	document.addEventListener( 'keydown', function ( e ) {
		if ( [ 'ArrowLeft', 'ArrowRight' ].indexOf( e.key ) < 0 ) { return; }
		var b = e.target.closest( '[role="radio"]' );
		if ( ! b ) { return; }
		var group = Array.prototype.slice.call( b.parentNode.querySelectorAll( '[role="radio"]' ) );
		var fwd = ( e.key === 'ArrowLeft' ) === ( document.dir === 'rtl' );
		var n = group[ ( group.indexOf( b ) + ( fwd ? 1 : -1 ) + group.length ) % group.length ];
		n.focus();
		n.click();
		e.preventDefault();
	} );
} )();
