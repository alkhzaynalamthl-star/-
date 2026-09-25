/**
 * مصمم الخزانة التفاعلي - يرسم الخزانة بـ SVG ويتحدث مع كل اختيار.
 * كل الوحدات بالسنتيمتر: 1 وحدة في الرسم = 1 سم.
 */
( function () {
	'use strict';

	var NS = 'http://www.w3.org/2000/svg';

	var LAYOUTS = {
		hang: 'تعليق طويل',
		hang2: 'تعليق مزدوج',
		shelves: 'رفوف',
		drawers: 'رفوف وأدراج',
		mixed: 'تعليق وأدراج'
	};
	var LAYOUT_ORDER = [ 'hang', 'hang2', 'shelves', 'drawers', 'mixed' ];
	var DEFAULT_PATTERN = [ 'hang', 'drawers', 'hang2', 'shelves', 'mixed' ];

	var DOOR_NAMES = { hinged: 'مفصلية', sliding: 'سحّابة', none: 'مفتوحة بدون أبواب' };
	var HANDLE_NAMES = { brass: 'ذهبية', black: 'سوداء', hidden: 'مخفية (ضغط)' };
	var HANDLE_COLORS = { brass: '#c9a45c', black: '#1a1816' };
	var EXTRA_NAMES = { led: 'إضاءة LED داخلية', mirror: 'مرآة على باب', jewelry: 'درج مجوهرات مبطّن' };
	var CLOTHES = [ '#2b2723', '#c9a45c', '#7d8b80', '#e9e2d6', '#6b4f3a', '#a3a7ad', '#8c3b2f', '#35455c' ];

	var uid = 0;

	function el( name, attrs, parent ) {
		var node = document.createElementNS( NS, name );
		for ( var k in attrs ) {
			if ( Object.prototype.hasOwnProperty.call( attrs, k ) ) {
				node.setAttribute( k, attrs[ k ] );
			}
		}
		if ( parent ) {
			parent.appendChild( node );
		}
		return node;
	}

	function shade( hex, amt ) {
		var n = parseInt( hex.slice( 1 ), 16 );
		var r = Math.max( 0, Math.min( 255, ( n >> 16 ) + amt ) );
		var g = Math.max( 0, Math.min( 255, ( ( n >> 8 ) & 255 ) + amt ) );
		var b = Math.max( 0, Math.min( 255, ( n & 255 ) + amt ) );
		return '#' + ( ( 1 << 24 ) + ( r << 16 ) + ( g << 8 ) + b ).toString( 16 ).slice( 1 );
	}

	function isLight( hex ) {
		var n = parseInt( hex.slice( 1 ), 16 );
		return ( ( n >> 16 ) * 0.299 + ( ( n >> 8 ) & 255 ) * 0.587 + ( n & 255 ) * 0.114 ) > 160;
	}

	function formatNumber( n ) {
		try {
			return new Intl.NumberFormat( document.documentElement.lang || 'ar' ).format( n );
		} catch ( e ) {
			return String( n );
		}
	}

	function Configurator( root ) {
		this.root = root;
		this.cfg = JSON.parse( root.getAttribute( 'data-optimum-config' ) || '{}' );
		this.form = root.querySelector( '.cfg-panel' );
		this.canvas = root.querySelector( '.cfg-canvas' );
		this.view = 'open';
		this.layouts = [];
		this.id = 'cfg' + ( ++uid );
		this.bind();
		this.update();
	}

	Configurator.prototype.state = function () {
		var f = this.form;
		var extras = [];
		f.querySelectorAll( 'input[name="extras"]:checked' ).forEach( function ( i ) {
			extras.push( i.value );
		} );
		return {
			width: parseInt( f.width.value, 10 ),
			height: parseInt( f.height.value, 10 ),
			doors: f.querySelector( 'input[name="doors"]:checked' ).value,
			finish: f.querySelector( 'input[name="finish"]:checked' ).value,
			handle: f.querySelector( 'input[name="handle"]:checked' ).value,
			extras: extras
		};
	};

	Configurator.prototype.bind = function () {
		var self = this;
		var defaults = new FormData( this.form );

		// تغيير المظهر الخارجي يعرض الخزانة مغلقة، وتغيير المقاس يعرضها مفتوحة.
		this.form.addEventListener( 'input', function ( e ) {
			var name = e.target.name;
			if ( 'finish' === name || 'handle' === name || ( 'doors' === name && 'none' !== e.target.value ) || ( 'extras' === name && 'mirror' === e.target.value && e.target.checked ) ) {
				self.setView( 'closed' );
			} else if ( 'width' === name || 'height' === name || ( 'extras' === name && e.target.checked ) ) {
				self.setView( 'open' );
			}
			self.update();
		} );

		this.root.querySelectorAll( '[data-view]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				self.setView( btn.getAttribute( 'data-view' ) );
			} );
		} );

		var reset = this.root.querySelector( '[data-reset]' );
		if ( reset ) {
			reset.addEventListener( 'click', function () {
				self.form.querySelectorAll( 'input' ).forEach( function ( input ) {
					var values = defaults.getAll( input.name );
					if ( 'range' === input.type ) {
						input.value = values[ 0 ];
					} else {
						input.checked = values.indexOf( input.value ) !== -1;
					}
				} );
				self.layouts = [];
				self.setView( 'open' );
				self.update();
			} );
		}

		this.canvas.addEventListener( 'click', function ( e ) {
			var target = e.target.closest( '[data-section]' );
			if ( target ) {
				self.cycle( parseInt( target.getAttribute( 'data-section' ), 10 ) );
			}
		} );
		this.canvas.addEventListener( 'keydown', function ( e ) {
			var target = e.target.closest( '[data-section]' );
			if ( target && ( 'Enter' === e.key || ' ' === e.key ) ) {
				e.preventDefault();
				self.cycle( parseInt( target.getAttribute( 'data-section' ), 10 ), true );
			}
		} );
	};

	// يبدّل العرض بحركة (بدون إعادة رسم)، حتى تنفتح الأبواب وتنغلق بسلاسة.
	Configurator.prototype.setView = function ( view ) {
		this.view = view;
		this.root.querySelectorAll( '[data-view]' ).forEach( function ( btn ) {
			var active = btn.getAttribute( 'data-view' ) === view;
			btn.classList.toggle( 'is-active', active );
			btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
		var svg = this.canvas.querySelector( 'svg' );
		if ( svg ) {
			svg.classList.toggle( 'is-closed', 'closed' === view && 'none' !== this.state().doors );
		}
	};

	Configurator.prototype.cycle = function ( index, keepFocus ) {
		if ( 'closed' === this.view && 'none' !== this.state().doors ) {
			this.setView( 'open' );
			return;
		}
		var current = LAYOUT_ORDER.indexOf( this.layouts[ index ] );
		this.layouts[ index ] = LAYOUT_ORDER[ ( current + 1 ) % LAYOUT_ORDER.length ];
		this.update();
		if ( keepFocus ) {
			var node = this.canvas.querySelector( '[data-section="' + index + '"]' );
			if ( node ) {
				node.focus();
			}
		}
	};

	Configurator.prototype.sectionCount = function ( width ) {
		return Math.max( 2, Math.min( 6, Math.round( width / 80 ) ) );
	};

	Configurator.prototype.doorCount = function ( s ) {
		if ( 'sliding' === s.doors ) {
			return s.width <= 200 ? 2 : ( s.width <= 320 ? 3 : 4 );
		}
		return Math.max( 2, Math.round( s.width / 50 ) );
	};

	Configurator.prototype.update = function () {
		var s = this.state();
		var n = this.sectionCount( s.width );
		for ( var i = 0; i < n; i++ ) {
			if ( ! this.layouts[ i ] ) {
				this.layouts[ i ] = DEFAULT_PATTERN[ i % DEFAULT_PATTERN.length ];
			}
		}
		this.layouts.length = n;

		this.render( s, n );
		this.updateOutputs( s );
	};

	Configurator.prototype.updateOutputs = function ( s ) {
		var root = this.root;
		var finish = this.cfg.finishes[ s.finish ];
		var set = function ( key, value ) {
			var out = root.querySelector( '[data-out="' + key + '"]' );
			if ( out ) {
				out.textContent = value;
			}
		};
		set( 'width', formatNumber( s.width ) );
		set( 'height', formatNumber( s.height ) );
		set( 'finish', finish ? finish.name : '' );

		var estimate = root.querySelector( '[data-estimate]' );
		var price = 0;
		if ( this.cfg.priceM2 > 0 && estimate ) {
			price = Math.round( ( s.width * s.height / 10000 ) * this.cfg.priceM2 / 50 ) * 50;
			set( 'price', formatNumber( price ) + ' ' + ( this.cfg.currency || '' ) );
			estimate.hidden = false;
		}

		var send = root.querySelector( '[data-send]' );
		if ( send && this.cfg.whatsapp ) {
			send.href = 'https://wa.me/' + this.cfg.whatsapp + '?text=' + encodeURIComponent( this.message( s, price ) );
		}
	};

	Configurator.prototype.message = function ( s, price ) {
		var lines = [
			'مرحباً، صممت خزانة على موقعكم:',
			'• المقاس: ' + s.width + ' × ' + s.height + ' سم (عرض × ارتفاع)',
			'• الأبواب: ' + DOOR_NAMES[ s.doors ] + ( 'none' !== s.doors ? ' (' + this.doorCount( s ) + ( 'sliding' === s.doors ? ' ألواح)' : ' أبواب)' ) : '' ),
			'• اللون: ' + ( this.cfg.finishes[ s.finish ] ? this.cfg.finishes[ s.finish ].name : s.finish )
		];
		if ( 'none' !== s.doors ) {
			lines.push( '• المقابض: ' + HANDLE_NAMES[ s.handle ] );
		}
		lines.push( '• التوزيع الداخلي:' );
		this.layouts.forEach( function ( layout, i ) {
			lines.push( '   - القسم ' + ( i + 1 ) + ': ' + LAYOUTS[ layout ] );
		} );
		if ( s.extras.length ) {
			lines.push( '• الإضافات: ' + s.extras.map( function ( e ) {
				return EXTRA_NAMES[ e ];
			} ).join('، ') );
		}
		if ( price ) {
			lines.push( '• السعر التقديري: ' + formatNumber( price ) + ' ' + ( this.cfg.currency || '' ) );
		}
		lines.push( '', 'أرجو التواصل معي لعرض سعر دقيق وموعد للقياس.' );
		return lines.join( '\n' );
	};

	/* ------------------------------------------------------------------ */
	/* الرسم                                                              */
	/* ------------------------------------------------------------------ */

	Configurator.prototype.render = function ( s, n ) {
		var W = s.width;
		var H = s.height;
		var finish = this.cfg.finishes[ s.finish ] || { color: '#b98a55', grain: true };
		var color = finish.color;
		var edge = shade( color, isLight( color ) ? -40 : 30 );
		var id = this.id;
		var T = 3; // سماكة الألواح
		var PLINTH = 8;
		var pad = 34;

		var svg = el( 'svg', {
			viewBox: ( -pad ) + ' ' + ( -pad ) + ' ' + ( W + pad * 2 ) + ' ' + ( H + pad * 2 + 10 ),
			role: 'img',
			'aria-label': 'خزانة بعرض ' + W + ' سم وارتفاع ' + H + ' سم، لون ' + ( finish.name || '' ),
			'class': 'cfg-svg' + ( 'closed' === this.view && 'none' !== s.doors ? ' is-closed' : '' )
		} );

		var defs = el( 'defs', {}, svg );
		var grain = el( 'pattern', { id: id + 'grain', width: 40, height: 300, patternUnits: 'userSpaceOnUse' }, defs );
		el( 'rect', { width: 40, height: 300, fill: color }, grain );
		if ( finish.grain ) {
			[ 4, 11, 19, 26, 33 ].forEach( function ( x, i ) {
				el( 'path', {
					d: 'M' + x + ' 0 C ' + ( x + 3 ) + ' 80, ' + ( x - 3 ) + ' 160, ' + ( x + 2 ) + ' 300',
					stroke: shade( color, i % 2 ? -18 : 14 ),
					'stroke-width': i % 2 ? 0.8 : 0.5,
					fill: 'none',
					opacity: 0.55
				}, grain );
			} );
		}
		var sheen = el( 'linearGradient', { id: id + 'sheen', x1: 0, y1: 0, x2: 1, y2: 1 }, defs );
		el( 'stop', { offset: 0, 'stop-color': '#fff', 'stop-opacity': 0.18 }, sheen );
		el( 'stop', { offset: 0.5, 'stop-color': '#fff', 'stop-opacity': 0 }, sheen );
		el( 'stop', { offset: 1, 'stop-color': '#000', 'stop-opacity': 0.12 }, sheen );
		var mirror = el( 'linearGradient', { id: id + 'mirror', x1: 0, y1: 0, x2: 1, y2: 1 }, defs );
		el( 'stop', { offset: 0, 'stop-color': '#e9eef1' }, mirror );
		el( 'stop', { offset: 0.45, 'stop-color': '#bcc7cd' }, mirror );
		el( 'stop', { offset: 0.55, 'stop-color': '#dfe6ea' }, mirror );
		el( 'stop', { offset: 1, 'stop-color': '#a9b5bc' }, mirror );
		var led = el( 'linearGradient', { id: id + 'led', x1: 0, y1: 0, x2: 0, y2: 1 }, defs );
		el( 'stop', { offset: 0, 'stop-color': '#ffe9b0', 'stop-opacity': 0.9 }, led );
		el( 'stop', { offset: 1, 'stop-color': '#ffe9b0', 'stop-opacity': 0 }, led );

		// ظل الأرضية
		el( 'ellipse', { cx: W / 2, cy: H + 4, rx: W * 0.56, ry: 7, fill: '#000', opacity: 0.25, 'class': 'cfg-floor' }, svg );

		// الهيكل
		el( 'rect', { x: 0, y: 0, width: W, height: H, rx: 1.5, fill: 'url(#' + id + 'grain)', stroke: edge, 'stroke-width': 0.6 }, svg );
		el( 'rect', { x: 0, y: H - PLINTH, width: W, height: PLINTH, fill: shade( color, -35 ) }, svg );

		// الداخل
		var inner = el( 'g', { 'class': 'cfg-inner' }, svg );
		var innerTop = T;
		var innerBottom = H - PLINTH - T;
		var innerH = innerBottom - innerTop;
		var secW = ( W - T * ( n + 1 ) ) / n;
		var self = this;

		for ( var i = 0; i < n; i++ ) {
			var x = T + i * ( secW + T );
			var g = el( 'g', {
				'class': 'cfg-section',
				'data-section': i,
				tabindex: 0,
				role: 'button',
				'aria-label': 'القسم ' + ( i + 1 ) + ': ' + LAYOUTS[ this.layouts[ i ] ] + '. اضغط للتغيير'
			}, inner );
			el( 'rect', { x: x, y: innerTop, width: secW, height: innerH, fill: '#efe7db' }, g );
			el( 'rect', { x: x, y: innerTop, width: secW, height: innerH, fill: 'url(#' + id + 'sheen)', opacity: 0.6 }, g );
			self.drawLayout( g, this.layouts[ i ], x, innerTop, secW, innerH, color, s, i );
			if ( s.extras.indexOf( 'led' ) !== -1 ) {
				el( 'rect', { x: x, y: innerTop, width: secW, height: Math.min( 60, innerH * 0.3 ), fill: 'url(#' + id + 'led)', 'class': 'cfg-led' }, g );
			}
			el( 'rect', { x: x, y: innerTop, width: secW, height: innerH, 'class': 'cfg-hit' }, g );
			var label = el( 'text', { x: x + secW / 2, y: innerBottom - 6, 'class': 'cfg-label', 'text-anchor': 'middle' }, g );
			label.textContent = LAYOUTS[ this.layouts[ i ] ];
		}

		// الأبواب
		if ( 'none' !== s.doors ) {
			var doors = el( 'g', { 'class': 'cfg-doors' }, svg );
			this.drawDoors( doors, s, id, color, edge, T, PLINTH );
		}

		// خطوط الأبعاد
		var dims = el( 'g', { 'class': 'cfg-dims' }, svg );
		el( 'path', { d: 'M0 ' + ( H + 18 ) + 'H' + W + 'M0 ' + ( H + 13 ) + 'v10M' + W + ' ' + ( H + 13 ) + 'v10' }, dims );
		var wt = el( 'text', { x: W / 2, y: H + 30, 'text-anchor': 'middle' }, dims );
		wt.textContent = formatNumber( W ) + ' سم';
		el( 'path', { d: 'M' + ( W + 16 ) + ' 0V' + H + 'M' + ( W + 11 ) + ' 0h10M' + ( W + 11 ) + ' ' + H + 'h10' }, dims );
		var ht = el( 'text', { x: W + 22, y: H / 2, 'text-anchor': 'middle', transform: 'rotate(90 ' + ( W + 22 ) + ' ' + ( H / 2 ) + ')' }, dims );
		ht.textContent = formatNumber( H ) + ' سم';

		this.canvas.replaceChildren( svg );
	};

	Configurator.prototype.drawLayout = function ( g, layout, x, y, w, h, color, s, index ) {
		var shelfColor = shade( color, isLight( color ) ? -25 : 20 );
		var self = this;
		var at = function ( f ) {
			return y + h * f;
		};

		// رف علوي في كل الأقسام
		var topShelf = at( 0.14 );
		el( 'rect', { x: x, y: topShelf, width: w, height: 2, fill: shelfColor }, g );
		this.drawBoxes( g, x, topShelf, w, Math.min( 22, h * 0.1 ), index );

		switch ( layout ) {
			case 'hang':
				this.drawHanging( g, x, topShelf + 6, w, at( 0.93 ), index );
				break;
			case 'hang2':
				this.drawHanging( g, x, topShelf + 6, w, at( 0.5 ), index );
				el( 'rect', { x: x, y: at( 0.53 ), width: w, height: 2, fill: shelfColor }, g );
				this.drawHanging( g, x, at( 0.53 ) + 6, w, at( 0.9 ), index + 3 );
				break;
			case 'shelves':
				[ 0.3, 0.46, 0.62, 0.78 ].forEach( function ( f, k ) {
					el( 'rect', { x: x, y: at( f ), width: w, height: 2, fill: shelfColor }, g );
					self.drawStacks( g, x, at( f ), w, index + k );
				} );
				this.drawStacks( g, x, at( 0.97 ), w, index + 5 );
				break;
			case 'drawers':
				[ 0.34, 0.54 ].forEach( function ( f, k ) {
					el( 'rect', { x: x, y: at( f ), width: w, height: 2, fill: shelfColor }, g );
					self.drawStacks( g, x, at( f ), w, index + k );
				} );
				this.drawDrawers( g, x, at( 0.56 ), w, at( 1 ) - at( 0.56 ), 3, color, s );
				break;
			case 'mixed':
				this.drawHanging( g, x, topShelf + 6, w, at( 0.55 ), index );
				this.drawDrawers( g, x, at( 0.62 ), w, at( 1 ) - at( 0.62 ), 2, color, s );
				break;
		}
	};

	Configurator.prototype.drawHanging = function ( g, x, top, w, bottom, seed ) {
		el( 'rect', { x: x + 3, y: top, width: w - 6, height: 1.6, rx: 0.8, fill: '#b9a27f' }, g );
		var count = Math.max( 2, Math.floor( ( w - 8 ) / 9 ) );
		var step = ( w - 8 ) / count;
		for ( var i = 0; i < count; i++ ) {
			var cx = x + 4 + step * ( i + 0.5 );
			var len = ( bottom - top - 6 ) * ( 0.55 + 0.45 * ( ( ( seed * 7 + i * 13 ) % 10 ) / 10 ) );
			var gw = Math.min( step * 0.85, 11 );
			var c = CLOTHES[ ( seed * 3 + i ) % CLOTHES.length ];
			el( 'path', { d: 'M' + cx + ' ' + ( top + 1 ) + 'v3', stroke: '#8a7a66', 'stroke-width': 0.6 }, g );
			el( 'path', {
				d: 'M' + ( cx - gw / 2 ) + ' ' + ( top + 5 ) + 'L' + ( cx + gw / 2 ) + ' ' + ( top + 5 ) + 'L' + ( cx + gw / 2 + 0.8 ) + ' ' + ( top + 5 + len ) + 'L' + ( cx - gw / 2 - 0.8 ) + ' ' + ( top + 5 + len ) + 'Z',
				fill: c,
				opacity: 0.95
			}, g );
		}
	};

	Configurator.prototype.drawBoxes = function ( g, x, shelfY, w, maxH, seed ) {
		var bw = Math.min( 26, w * 0.38 );
		var h1 = Math.min( maxH, 14 );
		el( 'rect', { x: x + 4, y: shelfY - h1, width: bw, height: h1, rx: 1, fill: [ '#d9cbb8', '#2b2723', '#c9a45c' ][ seed % 3 ] }, g );
		if ( w > 45 ) {
			el( 'rect', { x: x + 8 + bw, y: shelfY - h1 * 0.7, width: bw * 0.8, height: h1 * 0.7, rx: 1, fill: [ '#6b4f3a', '#e9e2d6', '#7d8b80' ][ seed % 3 ] }, g );
		}
	};

	Configurator.prototype.drawStacks = function ( g, x, shelfY, w, seed ) {
		var stacks = Math.max( 1, Math.floor( w / 26 ) );
		var sw = ( w - 8 ) / stacks;
		for ( var i = 0; i < stacks; i++ ) {
			var layers = 2 + ( ( seed + i ) % 3 );
			for ( var l = 0; l < layers; l++ ) {
				el( 'rect', {
					x: x + 4 + i * sw + 2,
					y: shelfY - ( l + 1 ) * 4.2,
					width: sw - 4,
					height: 3.8,
					rx: 1.2,
					fill: CLOTHES[ ( seed + i * 2 + l ) % CLOTHES.length ]
				}, g );
			}
		}
	};

	Configurator.prototype.drawDrawers = function ( g, x, y, w, h, count, color, s ) {
		var gap = 1.2;
		var dh = ( h - gap * ( count + 1 ) ) / count;
		for ( var i = 0; i < count; i++ ) {
			var dy = y + gap + i * ( dh + gap );
			el( 'rect', { x: x + 1.5, y: dy, width: w - 3, height: dh, rx: 0.8, fill: color, stroke: shade( color, -30 ), 'stroke-width': 0.4 }, g );
			el( 'rect', { x: x + 1.5, y: dy, width: w - 3, height: dh, rx: 0.8, fill: 'url(#' + this.id + 'sheen)' }, g );
			if ( 'hidden' !== s.handle ) {
				el( 'rect', { x: x + w / 2 - 7, y: dy + dh * 0.3, width: 14, height: 1.6, rx: 0.8, fill: HANDLE_COLORS[ s.handle ] }, g );
			}
			if ( 0 === i && s.extras.indexOf( 'jewelry' ) !== -1 ) {
				el( 'rect', { x: x + 4, y: dy + dh * 0.62, width: w - 8, height: dh * 0.2, rx: 0.6, fill: '#6d2233', opacity: 0.55 }, g );
			}
		}
	};

	Configurator.prototype.drawDoors = function ( g, s, id, color, edge, T, PLINTH ) {
		var W = s.width;
		var top = T * 0.5;
		var h = s.height - PLINTH - T;
		var count = this.doorCount( s );
		var handleColor = HANDLE_COLORS[ s.handle ];
		var hasMirror = s.extras.indexOf( 'mirror' ) !== -1;
		var i;
		var x;
		var dw;

		if ( 'sliding' === s.doors ) {
			el( 'rect', { x: 0, y: 0, width: W, height: 3, fill: shade( color, -45 ) }, g );
			var overlap = 3;
			dw = ( W - T + overlap * ( count - 1 ) ) / count;
			for ( i = 0; i < count; i++ ) {
				x = T / 2 + i * ( dw - overlap );
				var front = 0 === i % 2;
				var panel = el( 'g', { 'class': 'cfg-door cfg-door-slide', style: '--slide:' + ( front ? -1 : 1 ) * Math.min( 30, dw * 0.35 ) + 'px' }, g );
				el( 'rect', { x: x, y: top, width: dw, height: h, fill: 'url(#' + id + 'grain)', stroke: edge, 'stroke-width': 0.5 }, panel );
				if ( hasMirror && 1 === i ) {
					el( 'rect', { x: x + 8, y: top + 10, width: dw - 16, height: h - 20, rx: 1, fill: 'url(#' + id + 'mirror)' }, panel );
				}
				el( 'rect', { x: x, y: top, width: dw, height: h, fill: 'url(#' + id + 'sheen)' }, panel );
				if ( 'hidden' !== s.handle ) {
					el( 'rect', { x: x + ( front ? dw - 5 : 3 ), y: top + h * 0.35, width: 2, height: h * 0.3, rx: 1, fill: handleColor }, panel );
				}
			}
			return;
		}

		dw = ( W - T ) / count;
		for ( i = 0; i < count; i++ ) {
			x = T / 2 + i * dw;
			// الأبواب تتجمع أزواجاً، والمقبض على الحافة المشتركة
			var pairLeft = 0 === i % 2 && i < count - 1;
			var handleX = pairLeft ? x + dw - 5 : x + 3;
			var door = el( 'g', { 'class': 'cfg-door', style: 'transform-origin:' + ( pairLeft ? x : x + dw ) + 'px 0' }, g );
			el( 'rect', { x: x, y: top, width: dw, height: h, fill: 'url(#' + id + 'grain)', stroke: edge, 'stroke-width': 0.5 }, door );
			if ( hasMirror && 1 === i ) {
				el( 'rect', { x: x + 6, y: top + 10, width: dw - 12, height: h - 20, rx: 1, fill: 'url(#' + id + 'mirror)' }, door );
			}
			el( 'rect', { x: x, y: top, width: dw, height: h, fill: 'url(#' + id + 'sheen)' }, door );
			if ( 'hidden' !== s.handle ) {
				el( 'rect', { x: handleX, y: top + h * 0.42, width: 2, height: Math.min( 36, h * 0.16 ), rx: 1, fill: handleColor }, door );
			}
		}
	};

	document.querySelectorAll( '.configurator[data-optimum-config]' ).forEach( function ( root ) {
		new Configurator( root );
	} );
}() );
