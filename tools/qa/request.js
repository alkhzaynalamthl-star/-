/**
 * Made-to-measure request: server validation, photo upload, honest result.
 *   node tools/qa/request.js --photo path/to/image.jpg [--lang en]
 */
const { chromium } = require( 'playwright' );

const argv = process.argv.slice( 2 );
const arg = ( k, d ) => ( argv.indexOf( '--' + k ) > -1 ? argv[ argv.indexOf( '--' + k ) + 1 ] : d );
const base = arg( 'base', 'http://localhost:8080' );
const lang = arg( 'lang', 'ar' );
const photo = arg( 'photo' );
const out = arg( 'out', '.' );
const ok = ( c, m ) => { console.log( ( c ? 'PASS ' : 'FAIL ' ) + m ); if ( ! c ) { process.exitCode = 1; } };

( async () => {
	const browser = await chromium.launch( { executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' } );
	const page = await browser.newPage( { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 } );
	await page.goto( base + ( lang === 'en' ? '/en' : '' ) + '/made-to-measure/', { waitUntil: 'networkidle' } );
	await page.waitForTimeout( 3200 ); // the form rejects submissions faster than a human could type

	// 1. Invalid submission.
	await page.fill( '#oc_width', '20' );
	await page.fill( '#oc_phone', '12345' );
	await page.click( '.request-form button[type="submit"]' );
	await page.waitForLoadState( 'networkidle' );
	const errs = await page.$$eval( '.field-error', ( a ) => a.map( ( e ) => e.textContent ) );
	ok( errs.length >= 4, `server returned ${ errs.length } field errors: ${ errs.join( ' | ' ).slice( 0, 160 ) }` );
	ok( ( await page.inputValue( '#oc_width' ) ) === '20', 'entered values are kept after an error' );
	ok( lang === 'en' ? /Please/.test( errs.join( '' ) ) : /[\u0600-\u06FF]/.test( errs.join( '' ) ) && ! /Please/.test( errs.join( '' ) ), 'errors written in the page language' );
	await page.screenshot( { path: `${ out }/request-errors-${ lang }.png`, fullPage: true } );

	// 2. Valid submission with a photo.
	await page.waitForTimeout( 3200 );
	await page.fill( '#oc_width', '320' );
	await page.fill( '#oc_height', '٢٨٠' ); // Arabic-Indic digits are accepted
	await page.fill( '#oc_name', 'عميل تجريبي' );
	await page.fill( '#oc_phone', '0551234567' );
	await page.selectOption( '#oc_city', 'Makkah' );
	await page.click( 'label.choice:has(input[value="sliding"])' );
	if ( photo ) {
		await page.setInputFiles( '#oc_photos', photo );
		ok( ( await page.$$eval( '#oc-previews li', ( a ) => a.length ) ) === 1, 'photo preview shown before sending' );
	}
	await page.check( 'input[name="oc_consent"]' );
	await page.click( '.request-form button[type="submit"]' );
	await page.waitForLoadState( 'networkidle' );
	const done = await page.textContent( '.request-done' ).catch( () => '' );
	ok( /OC-\d{6}-/.test( done ), 'request saved with a reference number' );
	ok( /(Preview mode|وضع المعاينة)/.test( done ) && ! /(delivered to our team|وصل الطلب إلى فريقنا)/.test( done ), 'preview mode: says it was not sent, no false "delivered" claim' );
	await page.screenshot( { path: `${ out }/request-done-${ lang }.png`, fullPage: true } );
	await browser.close();
} )();
