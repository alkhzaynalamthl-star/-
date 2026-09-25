/**
 * Screenshots + basic QA (console errors, horizontal overflow, broken images).
 *
 *   node tools/qa/shots.js --base http://localhost:8080 --out docs/screenshots --pages home,shop --widths 375,768,1440 --langs ar,en
 */
const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );

const args = Object.fromEntries( process.argv.slice( 2 ).reduce( ( a, v, i, arr ) => ( v.startsWith( '--' ) ? a.concat( [ [ v.slice( 2 ), arr[ i + 1 ] ] ] ) : a ), [] ) );
const base = args.base || 'http://localhost:8080';
const out = args.out || 'shots';
const widths = ( args.widths || '375,768,1440' ).split( ',' ).map( Number );
const langs = ( args.langs || 'ar,en' ).split( ',' );
const full = args.full !== 'no';
const PAGES = {
	home: '/',
	shop: '/shop/',
	category: '/product-category/hinged-glass/',
	filtered: '/shop/?door%5B%5D=hinged-wood&fit=250',
	product: '/product/sidra-hinged-wooden-wardrobe/',
	product_glass: '/product/noor-glass-wardrobe/',
	bespoke: '/product/marsam-made-to-measure-wall-with-vanity/',
	cart: '/cart/',
	checkout: '/checkout/',
	account: '/my-account/',
	mtm: '/made-to-measure/',
	work: '/our-work/',
	showrooms: '/showrooms/',
	contact: '/contact/',
	search: '/?s=%D8%B2%D8%AC%D8%A7%D8%AC&post_type=product',
	notfound: '/nope/',
	c_atelier: 'CONCEPT:atelier.html',
	c_showroom: 'CONCEPT:showroom.html',
	c_bayt: 'CONCEPT:bayt.html',
	c_index: 'CONCEPT:index.html',
};
const pages = ( args.pages || 'home' ).split( ',' );

( async () => {
	fs.mkdirSync( out, { recursive: true } );
	const browser = await chromium.launch( { executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' } ).catch( () => chromium.launch() );
	const report = [];
	for ( const lang of langs ) {
		for ( const w of widths ) {
			const ctx = await browser.newContext( { viewport: { width: w, height: w < 700 ? 812 : 900 }, deviceScaleFactor: w < 700 ? 2 : 1, reducedMotion: 'reduce' } );
			if ( args.cookie ) {
				await ctx.addCookies( [ { name: 'oc_concept', value: args.cookie, url: base } ] );
			}
			const page = await ctx.newPage();
			const errors = [];
			page.on( 'console', ( m ) => m.type() === 'error' && errors.push( m.text() ) );
			page.on( 'pageerror', ( e ) => errors.push( String( e ) ) );
			for ( const key of pages ) {
				let p = PAGES[ key ] || key;
				let url;
				if ( p.startsWith( 'CONCEPT:' ) ) {
					url = base.replace( /\/$/, '' ) + '/wp-content/themes/optimum-closets/concepts/' + p.slice( 8 ) + '?lang=' + lang;
				} else {
					url = base + ( lang === 'en' ? '/en' : '' ) + p;
				}
				errors.length = 0;
				await page.goto( url, { waitUntil: 'networkidle' } ).catch( ( e ) => errors.push( 'goto ' + e.message ) );
				await page.waitForLoadState( 'networkidle' );
				await page.evaluate( async () => {
					document.querySelectorAll( '.reveal' ).forEach( ( el ) => el.classList.add( 'is-in' ) );
					// Headless full-page captures don't paint lazy images: load them all up front.
					document.querySelectorAll( 'img' ).forEach( ( i ) => {
						i.removeAttribute( 'loading' );
						if ( ( i.getAttribute( 'sizes' ) || '' ).indexOf( 'auto' ) === 0 ) { i.setAttribute( 'sizes', i.getAttribute( 'sizes' ).replace( /^auto,\s*/, '' ) ); }
						const s = i.currentSrc || i.src; if ( s ) { const n = new Image(); n.src = s; }
					} );
					for ( let y = 0; y < document.body.scrollHeight; y += 700 ) { window.scrollTo( 0, y ); await new Promise( ( r ) => setTimeout( r, 60 ) ); }
					window.scrollTo( 0, 0 );
				} ).catch( ( e ) => errors.push( 'scroll: ' + e.message.slice( 0, 80 ) ) );
				await page.waitForLoadState( 'networkidle' );
				await page.waitForFunction( () => [ ...document.images ].every( ( i ) => i.complete ), null, { timeout: 15000 } ).catch( () => {} );
				await page.waitForTimeout( 300 );
				const info = await page.evaluate( () => {
					const se = document.scrollingElement;
					const over = [];
					if ( se.scrollWidth > window.innerWidth + 1 ) {
						document.querySelectorAll( 'body *' ).forEach( ( el ) => {
							const r = el.getBoundingClientRect();
							if ( ( r.right > window.innerWidth + 1 || r.left < -1 ) && r.width > 0 && getComputedStyle( el ).position !== 'fixed' ) {
								let hidden = false;
								for ( let a = el.parentElement; a; a = a.parentElement ) {
									const o = getComputedStyle( a ).overflowX;
									if ( o === 'auto' || o === 'hidden' || o === 'scroll' || o === 'clip' ) { hidden = true; break; }
								}
								if ( ! hidden ) { over.push( el.tagName + '.' + el.className.toString().slice( 0, 40 ) ); }
							}
						} );
					}
					const broken = [ ...document.images ].filter( ( i ) => i.complete && i.naturalWidth === 0 && i.src ).map( ( i ) => i.src.slice( -60 ) );
					return { sw: se.scrollWidth, iw: window.innerWidth, over: over.slice( 0, 6 ), broken, dir: document.documentElement.dir, title: document.title };
				} );
				const file = path.join( out, `${ key }-${ lang }-${ w }.png` );
				await page.screenshot( { path: file, fullPage: full } );
				report.push( { key, lang, w, ...info, errors: errors.slice( 0, 5 ) } );
				console.log( `${ key } ${ lang } ${ w }: dir=${ info.dir } overflow=${ info.sw > info.iw ? info.sw + '>' + info.iw + ' ' + info.over.join( ',' ) : 'no' } broken=${ info.broken.length } errors=${ errors.length ? errors.join( ' | ' ).slice( 0, 300 ) : 0 } title="${ info.title }"` );
			}
			await ctx.close();
		}
	}
	fs.writeFileSync( path.join( out, 'report.json' ), JSON.stringify( report, null, 1 ) );
	await browser.close();
} )();
