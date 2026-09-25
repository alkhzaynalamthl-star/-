( function () {
	'use strict';
	var D = OC_DATA;
	var $ = function ( s ) { return document.querySelector( s ); };
	var filter = 'all';
	var sort = 'pop';
	var query = '';
	var picked = {};
	var cart = 0;

	function list() {
		var q = query.trim().toLowerCase();
		var items = D.products.filter( function ( p ) {
			if ( filter !== 'all' && p.cat !== filter ) { return false; }
			if ( ! q ) { return true; }
			var cat = D.categories.filter( function ( c ) { return c.id === p.cat; } )[ 0 ];
			return [ p.name.ar, p.name.en, cat.ar, cat.en, p.desc.ar, p.desc.en ].join( ' ' ).toLowerCase().indexOf( q ) > -1;
		} );
		if ( sort !== 'pop' ) {
			items = items.slice().sort( function ( a, b ) { return sort === 'low' ? a.price - b.price : b.price - a.price; } );
		}
		return items;
	}

	function renderGrid() {
		var items = list();
		$( '#count' ).innerHTML = '<bdi>' + OC.num( items.length ) + '</bdi> ' + OC.t( 'results' );
		$( '#products' ).innerHTML = items.map( function ( p ) {
			var f = picked[ p.id ] || p.finishes[ 0 ];
			var price = ( p.from ? '<small>' + OC.t( 'from' ) + '</small> ' : '' ) + OC.money( p.price ) + ( p.unit ? ' <small>' + OC.t( 'perM' ) + '</small>' : '' );
			return '<article class="pc">' +
				'<a class="pc-media" href="#p-' + p.id + '">' + OC.pic( p.img( f, 'closed' ), '(min-width: 1000px) 22vw, 46vw', 'pc-img' ) + '</a>' +
				'<div class="pc-body">' +
				'<h3 class="pc-name"><a href="#p-' + p.id + '">' + OC.L( p.name ) + '</a></h3>' +
				'<p class="pc-dims">' + OC.dims( p.dims ) + '</p>' +
				( p.finishes.length > 1 ? '<div class="dots" role="radiogroup" aria-label="' + OC.esc( OC.t( 'options' ) ) + '">' + p.finishes.map( function ( k ) {
					return '<button type="button" role="radio" class="dot" aria-checked="' + ( k === f ) + '" data-p="' + p.id + '" data-f="' + k + '" aria-label="' + OC.esc( OC.L( D.finishes[ k ] ) ) + '" title="' + OC.esc( OC.L( D.finishes[ k ] ) ) + '">' + OC.pic( D.finishes[ k ].swatch, '22px', 'dot-img' ) + '</button>';
				} ).join( '' ) + '<span class="dot-name">' + OC.L( D.finishes[ f ] ) + '</span></div>' : '<div class="dots"><span class="dot-name">' + OC.L( D.finishes[ f ] ) + '</span></div>' ) +
				'<div class="pc-foot"><p class="pc-price">' + price + '</p>' +
				'<button type="button" class="add" data-add="' + p.id + '" aria-label="' + OC.esc( OC.t( 'add' ) + ': ' + OC.L( p.name ) ) + '"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button></div>' +
				'</div></article>';
		} ).join( '' );
	}

	OC.render = function () {
		var L = OC.L;
		$( '#chips' ).innerHTML = '<button type="button" class="chip" aria-pressed="' + ( filter === 'all' ) + '" data-cat="all">' + OC.t( 'all' ) + '</button>' + D.categories.map( function ( c ) {
			return '<button type="button" class="chip" aria-pressed="' + ( filter === c.id ) + '" data-cat="' + c.id + '">' + L( c.short ) + '</button>';
		} ).join( '' ) + '<a class="chip chip-link" href="#bespoke">' + OC.t( 'bespoke' ) + '</a>';
		$( '#hero-media' ).innerHTML = OC.pic( 'bespoke-wall', '(min-width: 900px) 50vw, 100vw', 'hero-img', true ) + '<span class="render-tag">' + OC.t( 'rendered' ) + '</span>';
		$( '#tiles' ).innerHTML = D.categories.map( function ( c ) {
			return '<button type="button" class="tile" data-cat="' + c.id + '">' + OC.pic( c.img, '(min-width: 900px) 18vw, 40vw', 'tile-img' ) + '<span>' + L( c ) + '</span></button>';
		} ).join( '' );
		renderGrid();
		$( '#local-media' ).innerHTML = OC.pic( 'hinged-oak-open', '(min-width: 900px) 40vw, 100vw', 'local-img' ) + '<span class="render-tag">' + OC.t( 'rendered' ) + '</span>';
		$( '#steps' ).innerHTML = D.steps.map( function ( s, i ) {
			var t = s[ OC.lang ];
			return '<li><span class="n">' + ( i + 1 ) + '</span><strong>' + t[ 0 ] + '</strong><p>' + t[ 1 ] + '</p></li>';
		} ).join( '' );
		$( '#show' ).innerHTML = D.showrooms.map( function ( s ) {
			return '<article class="sc"><h3>' + L( s.city ) + '</h3><p>' + L( s.address ) + '</p><div class="sc-a">' +
				'<a class="btn btn-soft btn-sm" href="' + s.map + '" target="_blank" rel="noopener">' + OC.t( 'directions' ) + '</a>' +
				'<a class="btn btn-main btn-sm" href="tel:' + D.contact.phone + '">' + OC.t( 'call' ) + '</a></div></article>';
		} ).join( '' );
		$( '#ftr-contact' ).innerHTML = '<a href="tel:' + D.contact.phone + '"><bdi dir="ltr">' + D.contact.phoneDisplay + '</bdi></a> · ' + D.contact.email;
		$( '#sort' ).value = sort;
	};

	function setFilter( id ) {
		filter = id;
		document.querySelectorAll( '.chip[data-cat]' ).forEach( function ( c ) { c.setAttribute( 'aria-pressed', c.getAttribute( 'data-cat' ) === id ); } );
		renderGrid();
	}

	document.addEventListener( 'click', function ( e ) {
		var c = e.target.closest( '[data-cat]' );
		if ( c ) {
			setFilter( c.getAttribute( 'data-cat' ) );
			if ( c.classList.contains( 'tile' ) ) { $( '#grid' ).scrollIntoView( { behavior: OC.reduced ? 'auto' : 'smooth' } ); }
			return;
		}
		var d = e.target.closest( '.dot[data-f]' );
		if ( d ) {
			picked[ d.getAttribute( 'data-p' ) ] = d.getAttribute( 'data-f' );
			renderGrid();
			var again = document.querySelector( '.dot[data-p="' + d.getAttribute( 'data-p' ) + '"][aria-checked="true"]' );
			if ( again ) { again.focus(); }
			return;
		}
		var a = e.target.closest( '[data-add]' );
		if ( a ) {
			cart++;
			$( '#cart-n' ).textContent = OC.num( cart );
			var t = $( '#toast' );
			t.textContent = OC.t( 'added' );
			t.classList.add( 'is-on' );
			clearTimeout( t._h );
			t._h = setTimeout( function () { t.classList.remove( 'is-on' ); }, 2200 );
		}
	} );
	document.addEventListener( 'input', function ( e ) {
		if ( e.target.id === 'q' ) { query = e.target.value; renderGrid(); }
	} );
	document.addEventListener( 'change', function ( e ) {
		if ( e.target.id === 'sort' ) { sort = e.target.value; renderGrid(); }
	} );
} )();
