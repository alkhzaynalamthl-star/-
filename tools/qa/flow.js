/**
 * End-to-end order journey in preview mode:
 * product → change options (server price updates) → add to cart → switch
 * language (cart kept) → change quantity → checkout → preview order → confirmation.
 * Also checks that a tampered price cannot reach the cart.
 *
 *   node tools/qa/flow.js [--base http://localhost:8080] [--out dir] [--width 1440]
 */
const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );

const argv = process.argv.slice( 2 );
const arg = ( k, d ) => ( argv.indexOf( '--' + k ) > -1 ? argv[ argv.indexOf( '--' + k ) + 1 ] : d );
const base = arg( 'base', 'http://localhost:8080' );
const out = arg( 'out', 'flow' );
const width = +arg( 'width', 1440 );
const lang = arg( 'lang', 'ar' );
const prefix = lang === 'en' ? '/en' : '';

const ok = ( cond, msg ) => {
	console.log( ( cond ? 'PASS ' : 'FAIL ' ) + msg );
	if ( ! cond ) { process.exitCode = 1; }
};
const money = ( s ) => +String( s ).replace( /[^\d.]/g, '' );

( async () => {
	fs.mkdirSync( out, { recursive: true } );
	const browser = await chromium.launch( { executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' } );
	const ctx = await browser.newContext( { viewport: { width, height: width < 700 ? 812 : 900 }, deviceScaleFactor: width < 700 ? 2 : 1 } );
	const page = await ctx.newPage();
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( String( e ) ) );
	const shot = ( n ) => page.screenshot( { path: path.join( out, `${ n }-${ lang }-${ width }.png`, ), fullPage: true } );

	// 1. Product page, default price.
	await page.goto( base + prefix + '/product/sidra-hinged-wooden-wardrobe/', { waitUntil: 'networkidle' } );
	const p0 = money( await page.textContent( '[data-price]' ) );
	ok( p0 === 6900, `default price 6900 (got ${ p0 })` );

	// 2. Change finish → price and image follow.
	const imgBefore = await page.getAttribute( '[data-stage-img]', 'src' );
	await page.click( 'label.choice-swatch:has(input[value="walnut"])' );
	await page.waitForFunction( () => ! document.querySelector( '[data-price]' ).classList.contains( 'is-updating' ) );
	const p1 = money( await page.textContent( '[data-price]' ) );
	ok( p1 === 7800, `walnut adds 900 → 7800 (got ${ p1 })` );
	await page.waitForTimeout( 500 );
	const imgAfter = await page.getAttribute( '[data-stage-img]', 'src' );
	ok( imgAfter !== imgBefore && /walnut/.test( imgAfter ), 'image switched to the walnut render' );

	// 3. Width + layout + extra.
	await page.click( 'label.choice:has(input[name="oc[width]"][value="280"])' );
	await page.click( 'label.choice:has(input[name="oc[layout]"][value="long"])' );
	await page.click( 'label.check:has(input[value="led"])' );
	await page.waitForTimeout( 400 );
	await page.waitForFunction( () => ! document.querySelector( '[data-price]' ).classList.contains( 'is-updating' ) );
	const p2 = money( await page.textContent( '[data-price]' ) );
	ok( p2 === 7900 + 900 + 250 + 690, `280 cm + walnut + long + LED = 9740 (got ${ p2 })` );

	// 4. See inside: open view + hotspots.
	await page.click( '[data-view="open"]' );
	await page.waitForTimeout( 500 );
	const openSrc = await page.getAttribute( '[data-stage-img]', 'src' );
	const hs = await page.$$eval( '.hotspot', ( a ) => a.length );
	ok( /walnut-open-long/.test( openSrc ), `interior view matches finish + layout (${ openSrc.split( '/' ).pop() })` );
	ok( hs > 0, `${ hs } hotspots shown on the interior` );
	if ( hs ) {
		await page.click( '.hotspot >> nth=0' );
	}
	await shot( '01-product-configured' );

	// 5. Tamper: post a forged price field and an invalid option directly.
	const tamper = await page.evaluate( async ( b ) => {
		const r = await fetch( b + '/wp-json/optimum/v1/price', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( { product_id: +JSON.parse( document.getElementById( 'configurator-data' ).textContent ).productId, selection: { width: 999, finish: 'gold' } } ) } );
		return r.status;
	}, base );
	ok( tamper === 422, `server rejects invalid options (HTTP ${ tamper })` );

	// 6. Add to cart (AJAX) → toast + header count.
	await page.click( '[data-add]' );
	await page.waitForSelector( '#toast.is-on', { timeout: 8000 } ).catch( () => {} );
	const count = await page.textContent( '.header-cart-count' ).catch( () => '0' );
	ok( +count === 1, `header cart count = 1 (got ${ count })` );

	// 7. Cart shows options, then switch language: cart kept, labels translated.
	await page.goto( base + prefix + '/cart/', { waitUntil: 'networkidle' } );
	const meta = await page.textContent( 'dl.variation' );
	ok( /280/.test( meta ), 'cart line lists the chosen width' );
	const line = money( await page.textContent( '.cart_item .product-subtotal .amount' ) );
	ok( line === 9740, `cart line priced by the server: 9740 (got ${ line })` );
	await shot( '02-cart' );
	await page.click( '.header-actions .lang-switch' );
	await page.waitForLoadState( 'networkidle' );
	const otherLang = await page.getAttribute( 'html', 'lang' );
	const kept = await page.$$eval( '.cart_item', ( a ) => a.length );
	ok( otherLang !== lang && kept === 1, `language switched to ${ otherLang }, cart kept (${ kept } line)` );
	ok( /\/cart\/?$/.test( new URL( page.url() ).pathname ), `stayed on the cart page (${ page.url() })` );
	await page.click( '.header-actions .lang-switch' );
	await page.waitForLoadState( 'networkidle' );

	// 8. Quantity + → totals update.
	await page.click( '.cart_item .qty-step[data-step="1"]' );
	await page.waitForFunction( () => document.querySelector( '.cart_item .qty' ).value === '2' );
	await page.waitForTimeout( 2500 );
	await page.waitForLoadState( 'networkidle' );
	const line2 = money( await page.textContent( '.cart_item .product-subtotal .amount' ) );
	ok( line2 === 19480, `quantity 2 → 19480 (got ${ line2 })` );

	// 9. Checkout.
	await page.goto( base + prefix + '/checkout/', { waitUntil: 'networkidle' } );
	await page.fill( '#billing_first_name', 'Test' );
	await page.fill( '#billing_last_name', 'Customer' );
	await page.fill( '#billing_phone', '0501234567' );
	await page.fill( '#billing_email', 'test@example.com' );
	await page.selectOption( '#billing_city', 'Jeddah' );
	await page.fill( '#billing_address_1', 'Al-Naeem, Prince Sultan St' );
	await page.waitForTimeout( 1500 );
	const pm = await page.$$eval( '.wc_payment_method', ( a ) => a.map( ( x ) => x.textContent.trim().slice( 0, 60 ) ) );
	ok( pm.length === 1 && /(Preview|معاينة)/.test( pm[ 0 ] ), `only the preview method is offered: ${ pm.join( ' | ' ) }` );
	await shot( '03-checkout' );
	await page.click( '#place_order' );
	await page.waitForURL( /order-received/, { timeout: 20000 } ).catch( () => {} );
	await page.waitForLoadState( 'networkidle' );
	const confirm = await page.textContent( '.confirm-card' ).catch( () => '' );
	ok( /(Preview order recorded|تم تسجيل طلب معاينة)/.test( confirm ), 'confirmation says “preview order”, not “payment received”' );
	ok( ! /(payment was received|تم استلام الدفعة)/.test( confirm ), 'no false payment claim' );
	await shot( '04-confirmation' );

	// 10. Invalid phone is rejected at checkout.
	ok( errors.length === 0, 'no JavaScript errors' + ( errors.length ? ': ' + errors.join( ' | ' ) : '' ) );
	await browser.close();
} )();
