/**
 * Product page: gallery + zoom, "see inside" with hotspots, finish-matched
 * images, and live prices from the server (the browser never computes the
 * price it shows; it asks /wp-json/optimum/v1/price).
 */
( function () {
	'use strict';

	var dataEl = document.getElementById( 'configurator-data' );
	if ( ! dataEl ) {
		return;
	}
	var D = JSON.parse( dataEl.textContent || '{}' );
	var form = document.querySelector( '[data-configurator]' );
	var stage = document.querySelector( '[data-stage]' );
	var frame = document.querySelector( '[data-frame]' );
	var hotspotsEl = document.querySelector( '[data-hotspots]' );
	var thumbsEl = document.querySelector( '[data-thumbs]' );
	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var i18n = D.i18n || ( window.OptimumStore && OptimumStore.i18n ) || {};
	var view = 'closed';
	var index = 0;
	var reqId = 0;

	/* ---------------- Images ---------------- */

	function chosen( group ) {
		var el = form ? form.querySelector( '[name="oc[' + group + ']"]:checked' ) : null;
		return el ? el.value : '';
	}

	// The image set for the chosen finish; the interior view follows the chosen layout.
	function currentSet() {
		if ( ! D.images ) {
			return null;
		}
		var base = D.images[ chosen( D.imageGroup ) ] || D.images[ Object.keys( D.images )[ 0 ] ] || null;
		if ( ! base ) {
			return null;
		}
		var layout = chosen( 'layout' );
		var open = base.openBy && layout && base.openBy[ layout ] ? base.openBy[ layout ] : base.open;
		return { closed: base.closed, open: open, gallery: base.gallery };
	}

	function imageList() {
		if ( ! D.images ) {
			return D.gallery || [];
		}
		var s = currentSet();
		if ( ! s ) {
			return [];
		}
		return [ s.closed, s.open ].concat( s.gallery || [] ).filter( Boolean );
	}

	function showImage( img, instant ) {
		if ( ! img || ! frame ) {
			return;
		}
		var old = frame.querySelector( '[data-stage-img]' );
		var next = new Image();
		next.className = 'stage-img';
		next.setAttribute( 'data-stage-img', '1' );
		next.alt = img.alt || '';
		next.width = img.w;
		next.height = img.h;
		if ( img.srcset ) {
			next.srcset = img.srcset;
			next.sizes = '(min-width: 1000px) 55vw, 100vw';
		}
		next.src = img.src;
		var done = function () {
			if ( old && old.parentNode ) {
				if ( instant || reduced ) {
					old.remove();
				} else {
					next.classList.add( 'is-entering' );
					requestAnimationFrame( function () {
						next.classList.remove( 'is-entering' );
					} );
					setTimeout( function () { old.remove(); }, 450 );
				}
			}
		};
		frame.insertBefore( next, hotspotsEl );
		if ( next.complete ) { done(); } else { next.onload = done; next.onerror = done; }
		var tag = stage.querySelector( '[data-render-tag]' );
		if ( tag ) { tag.hidden = ! img.render; }
		frame.style.aspectRatio = img.w + ' / ' + img.h;
	}

	function renderThumbs() {
		if ( ! thumbsEl ) {
			return;
		}
		var list = imageList();
		thumbsEl.innerHTML = list.map( function ( img, i ) {
			return '<li><button type="button" class="thumb" data-thumb="' + i + '" aria-pressed="' + ( i === index ) + '">' +
				'<img src="' + img.src + '" alt="" loading="lazy" width="80" height="100"></button></li>';
		} ).join( '' );
	}

	function setIndex( i ) {
		var list = imageList();
		index = Math.max( 0, Math.min( i, list.length - 1 ) );
		var s = currentSet();
		view = s && s.open && list[ index ] && list[ index ].id === s.open.id ? 'open' : 'closed';
		showImage( list[ index ] );
		syncViewToggle();
		renderHotspots();
		if ( thumbsEl ) {
			thumbsEl.querySelectorAll( '[data-thumb]' ).forEach( function ( b ) {
				b.setAttribute( 'aria-pressed', +b.getAttribute( 'data-thumb' ) === index );
			} );
		}
	}

	function setView( v ) {
		var list = imageList();
		var s = currentSet();
		if ( ! s ) {
			return;
		}
		var target = v === 'open' ? s.open : s.closed;
		var i = -1;
		list.forEach( function ( im, n ) { if ( target && im.id === target.id && i < 0 ) { i = n; } } );
		if ( i > -1 ) {
			setIndex( i );
		}
	}

	function syncViewToggle() {
		document.querySelectorAll( '[data-view]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-checked', b.getAttribute( 'data-view' ) === view );
		} );
		stage && stage.classList.toggle( 'is-open', view === 'open' );
	}

	/* ---------------- Hotspots (see inside) ---------------- */

	function renderHotspots() {
		if ( ! hotspotsEl ) {
			return;
		}
		var img = imageList()[ index ];
		var spots = view === 'open' && D.hotspots && img ? ( D.hotspots[ img.id ] || [] ) : [];
		hotspotsEl.setAttribute( 'aria-hidden', spots.length ? 'false' : 'true' );
		hotspotsEl.innerHTML = spots.map( function ( s, i ) {
			return '<button type="button" class="hotspot' + ( s.x > 52 ? ' is-flip' : '' ) + '" style="--x:' + s.x + '%;--y:' + s.y + '%;--d:' + ( i * 90 ) + 'ms" aria-expanded="false" aria-describedby="hs-' + i + '">' +
				'<span class="hotspot-dot" aria-hidden="true">+</span>' +
				'<span class="hotspot-label" id="hs-' + i + '" role="tooltip">' + escapeHtml( s.label ) + '</span></button>';
		} ).join( '' );
		var note = document.querySelector( '[data-hotspot-note]' );
		if ( note ) {
			note.hidden = ! spots.length;
		}
	}

	function escapeHtml( s ) {
		return String( s ).replace( /[&<>"]/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ];
		} );
	}

	/* ---------------- Zoom ---------------- */

	function openZoom() {
		var list = imageList();
		var img = list[ index ];
		if ( ! img ) {
			return;
		}
		var dlg = document.createElement( 'div' );
		dlg.className = 'zoom';
		dlg.setAttribute( 'role', 'dialog' );
		dlg.setAttribute( 'aria-modal', 'true' );
		dlg.setAttribute( 'aria-label', i18n.zoom || 'Zoom' );
		dlg.innerHTML = '<button type="button" class="icon-btn zoom-close" aria-label="' + escapeHtml( i18n.close || 'Close' ) + '">×</button>' +
			'<div class="zoom-canvas"><img src="' + img.full + '" alt="' + escapeHtml( img.alt || '' ) + '"></div>';
		document.body.appendChild( dlg );
		document.body.classList.add( 'no-scroll' );
		var canvas = dlg.querySelector( '.zoom-canvas' );
		var pic = dlg.querySelector( 'img' );
		var closeBtn = dlg.querySelector( '.zoom-close' );
		var zoomed = false;
		var last = null;
		var pos = { x: 0, y: 0 };
		var prevFocus = document.activeElement;
		closeBtn.focus();

		function apply() {
			pic.style.transform = zoomed ? 'translate(' + pos.x + 'px,' + pos.y + 'px) scale(2.4)' : '';
			canvas.classList.toggle( 'is-zoomed', zoomed );
		}
		function close() {
			dlg.remove();
			document.body.classList.remove( 'no-scroll' );
			document.removeEventListener( 'keydown', onKey );
			if ( prevFocus ) { prevFocus.focus(); }
		}
		function onKey( e ) {
			if ( e.key === 'Escape' ) { close(); }
			if ( e.key === 'Tab' ) { e.preventDefault(); closeBtn.focus(); }
			if ( e.key === '+' || e.key === '=' ) { zoomed = true; apply(); }
			if ( e.key === '-' ) { zoomed = false; pos = { x: 0, y: 0 }; apply(); }
		}
		document.addEventListener( 'keydown', onKey );
		closeBtn.addEventListener( 'click', close );
		canvas.addEventListener( 'click', function ( e ) {
			if ( last && last.moved ) { return; }
			if ( ! zoomed ) {
				var r = pic.getBoundingClientRect();
				pos.x = ( r.left + r.width / 2 - e.clientX ) * 1.4;
				pos.y = ( r.top + r.height / 2 - e.clientY ) * 1.4;
			} else {
				pos = { x: 0, y: 0 };
			}
			zoomed = ! zoomed;
			apply();
		} );
		canvas.addEventListener( 'pointerdown', function ( e ) {
			last = { x: e.clientX, y: e.clientY, moved: false };
		} );
		canvas.addEventListener( 'pointermove', function ( e ) {
			if ( ! last || ! zoomed || ! e.buttons ) { return; }
			var dx = e.clientX - last.x, dy = e.clientY - last.y;
			if ( Math.abs( dx ) + Math.abs( dy ) > 3 ) { last.moved = true; }
			pos.x += dx; pos.y += dy;
			last.x = e.clientX; last.y = e.clientY;
			apply();
		} );
		canvas.addEventListener( 'pointerup', function () { setTimeout( function () { last = null; }, 0 ); } );
	}

	/* ---------------- Price (server) ---------------- */

	function selection() {
		var sel = { extras: [] };
		if ( ! form ) {
			return sel;
		}
		new FormData( form ).forEach( function ( v, k ) {
			var m = k.match( /^oc\[([a-z_]+)\](\[\])?$/ );
			if ( ! m ) { return; }
			if ( m[ 1 ] === 'extras' ) { sel.extras.push( v ); } else { sel[ m[ 1 ] ] = v; }
		} );
		return sel;
	}

	var priceEl = document.querySelector( '[data-price]' );
	var stateEl = document.querySelector( '[data-price-state]' );
	var linesEl = document.querySelector( '[data-lines]' );
	var errEl = document.querySelector( '[data-form-error]' );
	var addBtn = document.querySelector( '[data-add]' );
	var stickyPrice = document.querySelector( '[data-price-sticky]' );
	var timer = null;

	var shownNote = document.querySelector( '[data-shown-note]' );
	function updateShownNote() {
		if ( ! shownNote || ! D.shown || ! D.shown.width ) { return; }
		var w = form ? ( form.querySelector( '[name="oc[width]"]:checked' ) || form.querySelector( 'input[name="oc[width]"][type="number"]' ) || {} ).value : '';
		var differs = w && +w !== +D.shown.width;
		shownNote.hidden = ! differs;
		if ( differs ) { shownNote.textContent = ( i18n.shownWidth || '' ).replace( '%s', D.shown.width ); }
	}

	function updatePrice() {
		updateShownNote();
		clearTimeout( timer );
		timer = setTimeout( fetchPrice, 120 );
		if ( stateEl ) { stateEl.textContent = i18n.updating || ''; }
		priceEl && priceEl.classList.add( 'is-updating' );
	}

	function fetchPrice() {
		var id = ++reqId;
		fetch( D.endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			credentials: 'same-origin',
			body: JSON.stringify( { product_id: D.productId, selection: selection(), lang: D.lang } )
		} ).then( function ( r ) {
			return r.json().then( function ( j ) { return { ok: r.ok, body: j }; } );
		} ).then( function ( res ) {
			if ( id !== reqId ) { return; }
			priceEl.classList.remove( 'is-updating' );
			if ( stateEl ) { stateEl.textContent = ''; }
			if ( ! res.ok ) {
				showError( res.body && res.body.message ? res.body.message : i18n.error );
				return;
			}
			showError( '' );
			priceEl.innerHTML = res.body.total_html;
			if ( stickyPrice ) { stickyPrice.innerHTML = res.body.total_html; }
			if ( linesEl ) {
				linesEl.innerHTML = res.body.lines.map( function ( l ) {
					return '<div><dt>' + escapeHtml( l.label ) + '</dt><dd>' + l.amount + '</dd></div>';
				} ).join( '' );
			}
		} ).catch( function () {
			if ( id !== reqId ) { return; }
			priceEl.classList.remove( 'is-updating' );
			if ( stateEl ) { stateEl.textContent = ''; }
			showError( i18n.error );
		} );
	}

	function showError( msg ) {
		if ( ! errEl ) { return; }
		errEl.textContent = msg || '';
		errEl.hidden = ! msg;
		if ( addBtn ) { addBtn.disabled = !! msg; }
	}

	/* ---------------- Add to cart (AJAX with full-page fallback) ---------------- */

	function toast( html ) {
		var t = document.getElementById( 'toast' );
		if ( ! t ) { return; }
		t.innerHTML = html;
		t.hidden = false;
		requestAnimationFrame( function () { t.classList.add( 'is-on' ); } );
		clearTimeout( t._h );
		t._h = setTimeout( function () {
			t.classList.remove( 'is-on' );
			setTimeout( function () { t.hidden = true; }, 400 );
		}, 5000 );
	}

	if ( form ) {
		form.addEventListener( 'change', function ( e ) {
			var name = e.target.name || '';
			var m = name.match( /^oc\[([a-z_]+)\]/ );
			if ( m && e.target.type === 'radio' ) {
				var cur = form.querySelector( '[data-current="' + m[ 1 ] + '"]' );
				if ( cur && e.target.getAttribute( 'data-label' ) ) { cur.textContent = e.target.getAttribute( 'data-label' ); }
				if ( m[ 1 ] === D.imageGroup || m[ 1 ] === 'layout' ) {
					var keepView = m[ 1 ] === 'layout' ? 'open' : view;
					renderThumbs();
					index = -1;
					setView( keepView );
				}
			}
			updatePrice();
		} );
		form.addEventListener( 'input', function ( e ) {
			if ( e.target.type === 'number' ) { updatePrice(); }
		} );
		form.addEventListener( 'submit', function ( e ) {
			if ( ! window.fetch || ! D.ajaxAdd || form.dataset.fallback ) { return; }
			e.preventDefault();
			var fd = new FormData( form );
			fd.append( 'product_id', D.productId );
			addBtn.disabled = true;
			addBtn.classList.add( 'is-loading' );
			fetch( D.ajaxAdd, { method: 'POST', body: fd, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					addBtn.disabled = false;
					addBtn.classList.remove( 'is-loading' );
					if ( ! res || res.error ) {
						// Let WooCommerce show its own notice on a full page load.
						form.dataset.fallback = '1';
						var b = document.createElement( 'input' );
						b.type = 'hidden'; b.name = 'add-to-cart'; b.value = D.productId;
						form.appendChild( b );
						form.submit();
						return;
					}
					if ( res.fragments ) {
						Object.keys( res.fragments ).forEach( function ( sel ) {
							document.querySelectorAll( sel ).forEach( function ( el ) {
								var tmp = document.createElement( 'div' );
								tmp.innerHTML = res.fragments[ sel ];
								if ( tmp.firstElementChild ) { el.replaceWith( tmp.firstElementChild ); }
							} );
						} );
					}
					toast( '<span>' + escapeHtml( i18n.added ) + '</span> <a href="' + D.cartUrl + '">' + escapeHtml( i18n.viewCart ) + '</a>' );
					document.body.dispatchEvent( new CustomEvent( 'optimum:added' ) );
				} )
				.catch( function () {
					form.dataset.fallback = '1';
					form.submit();
				} );
		} );
	}

	/* ---------------- Events ---------------- */

	document.addEventListener( 'click', function ( e ) {
		var v = e.target.closest( '[data-view]' );
		if ( v ) { setView( v.getAttribute( 'data-view' ) ); return; }
		var t = e.target.closest( '[data-thumb]' );
		if ( t ) { setIndex( +t.getAttribute( 'data-thumb' ) ); return; }
		if ( e.target.closest( '[data-zoom]' ) || ( e.target.closest( '[data-stage-img]' ) && ! e.target.closest( '.hotspot' ) ) ) { openZoom(); return; }
		var h = e.target.closest( '.hotspot' );
		document.querySelectorAll( '.hotspot[aria-expanded="true"]' ).forEach( function ( o ) {
			if ( o !== h ) { o.setAttribute( 'aria-expanded', 'false' ); }
		} );
		if ( h ) { h.setAttribute( 'aria-expanded', h.getAttribute( 'aria-expanded' ) === 'true' ? 'false' : 'true' ); }
		if ( e.target.closest( '[data-sticky-add]' ) && addBtn ) { addBtn.click(); }
	} );

	// Arrow keys inside the closed/open toggle.
	document.addEventListener( 'keydown', function ( e ) {
		var b = e.target.closest( '[data-view]' );
		if ( b && ( e.key === 'ArrowLeft' || e.key === 'ArrowRight' ) ) {
			e.preventDefault();
			setView( view === 'open' ? 'closed' : 'open' );
			document.querySelector( '[data-view="' + view + '"]' ).focus();
		}
	} );

	// Swipe between images on touch screens.
	if ( stage ) {
		var sx = null;
		stage.addEventListener( 'touchstart', function ( e ) { sx = e.touches[ 0 ].clientX; }, { passive: true } );
		stage.addEventListener( 'touchend', function ( e ) {
			if ( sx === null ) { return; }
			var dx = e.changedTouches[ 0 ].clientX - sx;
			sx = null;
			if ( Math.abs( dx ) < 50 ) { return; }
			var rtl = document.documentElement.dir === 'rtl';
			setIndex( index + ( ( dx < 0 ) !== rtl ? 1 : -1 ) );
		}, { passive: true } );
	}

	// Sticky buy bar on small screens when the main button is off-screen.
	var sticky = document.querySelector( '[data-sticky-buy]' );
	if ( sticky && addBtn && 'IntersectionObserver' in window ) {
		sticky.hidden = false;
		new IntersectionObserver( function ( entries ) {
			sticky.classList.toggle( 'is-on', ! entries[ 0 ].isIntersecting && entries[ 0 ].boundingClientRect.top < 0 );
		} ).observe( addBtn );
	}

	renderThumbs();
	syncViewToggle();
	updateShownNote();
} )();
