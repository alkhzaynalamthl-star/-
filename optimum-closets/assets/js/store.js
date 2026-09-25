/**
 * Site-wide interactions, no dependencies: header, menus, search, filter
 * sheet, card swatches, material tabs, reveal-on-scroll, cart auto-update.
 */
( function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	root.classList.add( 'js' );
	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var rtl = root.dir === 'rtl';

	/* Header state */
	var header = doc.getElementById( 'site-header' );
	if ( header ) {
		var onScroll = function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 24 );
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();
	}

	/* Focus trap helper for dialogs */
	function trap( container, e ) {
		var f = container.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])' );
		if ( ! f.length ) { return; }
		var first = f[ 0 ], last = f[ f.length - 1 ];
		if ( e.shiftKey && doc.activeElement === first ) { e.preventDefault(); last.focus(); }
		else if ( ! e.shiftKey && doc.activeElement === last ) { e.preventDefault(); first.focus(); }
	}

	/* Mobile drawer */
	var drawer = doc.getElementById( 'drawer' );
	var menuBtn = doc.querySelector( '.menu-toggle' );
	function setDrawer( open ) {
		if ( ! drawer ) { return; }
		drawer.hidden = ! open;
		doc.body.classList.toggle( 'no-scroll', open );
		if ( menuBtn ) { menuBtn.setAttribute( 'aria-expanded', open ); }
		if ( open ) {
			var f = drawer.querySelector( '.drawer-panel button, .drawer-panel a' );
			if ( f ) { f.focus(); }
		} else if ( menuBtn ) {
			menuBtn.focus();
		}
	}
	if ( menuBtn ) { menuBtn.addEventListener( 'click', function () { setDrawer( true ); } ); }

	/* Mega menu ("Our Products") */
	function closeMegas( except ) {
		doc.querySelectorAll( '.mega-toggle[aria-expanded="true"]' ).forEach( function ( b ) {
			if ( b === except ) { return; }
			b.setAttribute( 'aria-expanded', 'false' );
			var p = doc.getElementById( b.getAttribute( 'aria-controls' ) );
			if ( p ) { p.hidden = true; }
		} );
	}

	/* Search */
	var searchBtn = doc.querySelector( '.search-toggle' );
	var searchBox = doc.getElementById( 'header-search' );

	/* Filters sheet (mobile) */
	var filters = doc.getElementById( 'shop-filters-panel' );
	var filtersBtn = doc.querySelector( '.filters-toggle' );
	var backdrop = doc.querySelector( '.sheet-backdrop' );
	function setFilters( open ) {
		if ( ! filters ) { return; }
		filters.classList.toggle( 'is-open', open );
		if ( backdrop ) { backdrop.hidden = ! open; }
		doc.body.classList.toggle( 'no-scroll', open );
		if ( filtersBtn ) { filtersBtn.setAttribute( 'aria-expanded', open ); }
		if ( open ) {
			var f = filters.querySelector( 'button, input' );
			if ( f ) { f.focus(); }
		} else if ( filtersBtn ) {
			filtersBtn.focus();
		}
	}

	doc.addEventListener( 'click', function ( e ) {
		var t = e.target;
		if ( t.closest( '[data-close-drawer]' ) ) { setDrawer( false ); return; }
		if ( drawer && ! drawer.hidden && t.closest( '.drawer a[href]' ) ) { setDrawer( false ); }

		var mt = t.closest( '.mega-toggle' );
		if ( mt ) {
			var panel = doc.getElementById( mt.getAttribute( 'aria-controls' ) );
			var open = mt.getAttribute( 'aria-expanded' ) !== 'true';
			closeMegas( mt );
			mt.setAttribute( 'aria-expanded', open );
			if ( panel ) { panel.hidden = ! open; }
			return;
		}
		if ( ! t.closest( '.mega' ) ) { closeMegas(); }

		if ( t.closest( '.search-toggle' ) && searchBox ) {
			var show = searchBox.hidden;
			searchBox.hidden = ! show;
			searchBtn.setAttribute( 'aria-expanded', show );
			if ( show ) { searchBox.querySelector( 'input[type="search"]' ).focus(); }
			return;
		}
		if ( t.closest( '.filters-toggle' ) ) { setFilters( true ); return; }
		if ( t.closest( '[data-close-filters]' ) ) { setFilters( false ); return; }

		/* Card swatches: swap to the matching image */
		var sw = t.closest( '.card .swatch' );
		if ( sw ) {
			e.preventDefault();
			var card = sw.closest( '.card' );
			card.querySelectorAll( '.swatch' ).forEach( function ( s ) { s.setAttribute( 'aria-checked', s === sw ); } );
			var img = card.querySelector( '.card-img:not(.card-img-open)' );
			var openImg = card.querySelector( '.card-img-open' );
			if ( img && sw.dataset.src ) {
				img.srcset = sw.dataset.srcset || '';
				img.src = sw.dataset.src;
			}
			if ( openImg && sw.dataset.openSrcset ) { openImg.srcset = sw.dataset.openSrcset; }
			card.querySelectorAll( 'a.card-media, .card-name a' ).forEach( function ( a ) { a.href = sw.dataset.href; } );
			return;
		}

		/* Tabs (materials) */
		var tab = t.closest( '[role="tab"]' );
		if ( tab && tab.closest( '[data-tabs]' ) ) { selectTab( tab ); }
	} );

	function selectTab( tab ) {
		var list = tab.closest( '[data-tabs]' );
		list.querySelectorAll( '[role="tab"]' ).forEach( function ( b ) {
			var on = b === tab;
			b.setAttribute( 'aria-selected', on );
			b.tabIndex = on ? 0 : -1;
			var p = doc.getElementById( b.getAttribute( 'aria-controls' ) );
			if ( p ) {
				p.classList.toggle( 'is-active', on );
				p.setAttribute( 'aria-hidden', on ? 'false' : 'true' );
			}
		} );
	}

	doc.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			if ( drawer && ! drawer.hidden ) { setDrawer( false ); }
			if ( filters && filters.classList.contains( 'is-open' ) ) { setFilters( false ); }
			var openMega = doc.querySelector( '.mega-toggle[aria-expanded="true"]' );
			if ( openMega ) { closeMegas(); openMega.focus(); }
			if ( searchBox && ! searchBox.hidden ) { searchBox.hidden = true; searchBtn.setAttribute( 'aria-expanded', 'false' ); searchBtn.focus(); }
		}
		if ( e.key === 'Tab' ) {
			if ( drawer && ! drawer.hidden ) { trap( drawer.querySelector( '.drawer-panel' ), e ); }
			if ( filters && filters.classList.contains( 'is-open' ) ) { trap( filters, e ); }
		}
		var tab = e.target.closest && e.target.closest( '[data-tabs] [role="tab"]' );
		if ( tab && [ 'ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].indexOf( e.key ) > -1 ) {
			var tabs = Array.prototype.slice.call( tab.closest( '[data-tabs]' ).querySelectorAll( '[role="tab"]' ) );
			var i = tabs.indexOf( tab );
			var fwd = e.key === 'ArrowDown' || ( e.key === 'ArrowLeft' && rtl ) || ( e.key === 'ArrowRight' && ! rtl );
			var n = e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : ( i + ( fwd ? 1 : -1 ) + tabs.length ) % tabs.length;
			e.preventDefault();
			tabs[ n ].focus();
			selectTab( tabs[ n ] );
		}
	} );

	/* Filters: apply checkbox changes straight away on large screens */
	var filterForm = doc.querySelector( 'form[data-autosubmit]' );
	if ( filterForm ) {
		filterForm.addEventListener( 'change', function ( e ) {
			if ( e.target.type === 'checkbox' && window.matchMedia( '(min-width: 1000px)' ).matches ) {
				filterForm.requestSubmit ? filterForm.requestSubmit() : filterForm.submit();
			}
		} );
		// Don't send empty fields: keeps URLs clean and shareable.
		filterForm.addEventListener( 'submit', function () {
			filterForm.querySelectorAll( 'input[type="number"]' ).forEach( function ( i ) { if ( ! i.value ) { i.disabled = true; } } );
		} );
	}

	/* Sorting: submit on change (WooCommerce's own script does this with jQuery) */
	var ordering = doc.querySelector( '.woocommerce-ordering select' );
	if ( ordering ) {
		ordering.addEventListener( 'change', function () { ordering.form.submit(); } );
	}

	/* Cart: update totals automatically when a quantity changes */
	var cartForm = doc.querySelector( 'form.woocommerce-cart-form' );
	if ( cartForm ) {
		var h = null;
		cartForm.addEventListener( 'change', function ( e ) {
			if ( ! e.target.classList.contains( 'qty' ) ) { return; }
			clearTimeout( h );
			h = setTimeout( function () {
				var btn = cartForm.querySelector( '[name="update_cart"]' );
				if ( btn ) { btn.disabled = false; btn.click(); }
			}, 450 );
		} );
	}

	/* Quantity steppers */
	doc.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '[data-step]' );
		if ( ! b ) { return; }
		var input = b.parentNode.querySelector( 'input.qty' );
		if ( ! input ) { return; }
		var min = parseFloat( input.min ) || 0;
		var max = parseFloat( input.max ) || Infinity;
		var v = ( parseFloat( input.value ) || 0 ) + parseFloat( b.getAttribute( 'data-step' ) );
		input.value = Math.max( min, Math.min( max, v ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );

	/* Request form: photo previews + client hints (server validates everything) */
	var photos = doc.querySelector( 'input[type="file"][data-previews]' );
	if ( photos ) {
		var out = doc.getElementById( photos.getAttribute( 'data-previews' ) );
		photos.addEventListener( 'change', function () {
			if ( ! out ) { return; }
			out.innerHTML = '';
			Array.prototype.slice.call( photos.files, 0, 6 ).forEach( function ( f ) {
				var li = doc.createElement( 'li' );
				if ( /^image\/(jpeg|png|webp)$/.test( f.type ) ) {
					var img = doc.createElement( 'img' );
					img.alt = f.name;
					img.src = URL.createObjectURL( f );
					li.appendChild( img );
				} else {
					li.textContent = f.name;
				}
				out.appendChild( li );
			} );
		} );
	}

	/* Reveal on scroll */
	var reveals = doc.querySelectorAll( '.reveal' );
	if ( reduced || ! ( 'IntersectionObserver' in window ) ) {
		reveals.forEach( function ( el ) { el.classList.add( 'is-in' ); } );
	} else {
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) {
				if ( en.isIntersecting ) {
					en.target.classList.add( 'is-in' );
					io.unobserve( en.target );
				}
			} );
		}, { rootMargin: '0px 0px -6% 0px', threshold: 0.08 } );
		reveals.forEach( function ( el ) { io.observe( el ); } );
	}
} )();
