/**
 * الخزائن الأمثل - تفاعلات الواجهة (بدون مكتبات).
 */
( function () {
	'use strict';

	var doc = document;
	var body = doc.body;
	doc.documentElement.classList.add( 'js' );

	/* رأس الصفحة: ظل عند التمرير */
	var header = doc.getElementById( 'site-header' );
	if ( header ) {
		var onScroll = function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();
	}

	/* قائمة الجوال */
	var drawer = doc.getElementById( 'mobile-drawer' );
	var menuToggle = doc.querySelector( '.menu-toggle' );
	function setDrawer( open ) {
		if ( ! drawer ) {
			return;
		}
		drawer.classList.toggle( 'is-open', open );
		drawer.setAttribute( 'aria-hidden', open ? 'false' : 'true' );
		body.classList.toggle( 'drawer-open', open );
		if ( menuToggle ) {
			menuToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}
		if ( open ) {
			var first = drawer.querySelector( 'a, button' );
			if ( first ) {
				first.focus();
			}
		} else if ( menuToggle ) {
			menuToggle.focus();
		}
	}
	if ( menuToggle ) {
		menuToggle.addEventListener( 'click', function () {
			setDrawer( true );
		} );
	}
	doc.querySelectorAll( '[data-close-drawer]' ).forEach( function ( el ) {
		el.addEventListener( 'click', function () {
			setDrawer( false );
		} );
	} );

	/* البحث */
	var searchToggle = doc.querySelector( '.search-toggle' );
	var searchBox = doc.getElementById( 'header-search' );
	if ( searchToggle && searchBox ) {
		searchToggle.addEventListener( 'click', function () {
			var open = searchBox.hasAttribute( 'hidden' );
			searchBox.toggleAttribute( 'hidden', ! open );
			searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				var input = searchBox.querySelector( 'input[type="search"]' );
				if ( input ) {
					input.focus();
				}
			}
		} );
	}

	/* فلاتر المتجر على الجوال */
	var filtersToggle = doc.querySelector( '.filters-toggle' );
	function setFilters( open ) {
		body.classList.toggle( 'filters-open', open );
	}
	if ( filtersToggle ) {
		filtersToggle.addEventListener( 'click', function () {
			setFilters( true );
		} );
		doc.querySelectorAll( '[data-close-filters]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				setFilters( false );
			} );
		} );
		body.addEventListener( 'click', function ( e ) {
			if ( body.classList.contains( 'filters-open' ) && ! e.target.closest( '.shop-sidebar, .filters-toggle' ) ) {
				setFilters( false );
			}
		} );
	}

	/* Escape يغلق القوائم */
	doc.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}
		if ( drawer && drawer.classList.contains( 'is-open' ) ) {
			setDrawer( false );
		}
		setFilters( false );
	} );

	/* شريط الشراء الثابت: يظهر بعد تجاوز زر الإضافة للسلة */
	var sticky = doc.getElementById( 'sticky-atc' );
	var cartForm = doc.querySelector( '.summary form.cart' );
	if ( sticky && cartForm && 'IntersectionObserver' in window ) {
		new IntersectionObserver( function ( entries ) {
			var passed = ! entries[ 0 ].isIntersecting && entries[ 0 ].boundingClientRect.top < 0;
			sticky.classList.toggle( 'is-visible', passed );
			sticky.setAttribute( 'aria-hidden', passed ? 'false' : 'true' );
			body.classList.toggle( 'has-sticky-atc', passed );
		} ).observe( cartForm );

		var stickyBtn = sticky.querySelector( '[data-scroll-to-cart]' );
		stickyBtn && stickyBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			cartForm.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			var btn = cartForm.querySelector( '.single_add_to_cart_button' );
			if ( btn && cartForm.classList.contains( 'variations_form' ) === false ) {
				setTimeout( function () {
					btn.focus();
				}, 400 );
			}
		} );
	}

	/* ظهور تدريجي للأقسام */
	var revealTargets = doc.querySelectorAll( '.section .section-head, .cat-card, .feature, .custom-media, .custom-content, .review-card, .faq-item, .woocommerce ul.products li.product' );
	if ( 'IntersectionObserver' in window && revealTargets.length ) {
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					io.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -40px 0px' } );

		revealTargets.forEach( function ( el, i ) {
			el.classList.add( 'reveal' );
			el.style.transitionDelay = ( i % 4 ) * 70 + 'ms';
			io.observe( el );
		} );
	}
}() );
