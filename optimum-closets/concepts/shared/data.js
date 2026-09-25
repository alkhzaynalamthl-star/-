/*
 * Shared content for the three design concepts.
 * The concepts differ in composition, type, presentation and motion; the
 * content below is identical so the comparison is fair.
 *
 * DEMO DATA: prices and product names are placeholders for preview only.
 * Confirmed company facts (showrooms, phone, e-mail, lead time, free
 * consultation) are marked `confirmed: true` and come from optimum-closets.com
 * as indexed by search engines (see docs/HANDOVER.md).
 */
window.OC_DATA = {
	brand: { ar: 'الخزائن الأمثل', en: 'Optimum Closets' },
	contact: {
		confirmed: true,
		phone: '+966541200434',
		phoneDisplay: '+966 54 120 0434',
		email: 'info@optimum-closets.com'
	},
	showrooms: [
		{
			id: 'jeddah',
			confirmed: true,
			city: { ar: 'جدة', en: 'Jeddah' },
			address: { ar: 'شارع الأمير سلطان، حي النعيم', en: 'Prince Sultan Street, Al-Naeem District' },
			map: 'https://www.google.com/maps/search/?api=1&query=Optimum+Closets+Prince+Sultan+Street+Al+Naeem+Jeddah'
		},
		{
			id: 'makkah',
			confirmed: true,
			city: { ar: 'مكة المكرمة', en: 'Makkah' },
			address: { ar: 'الطريق الدائري الثالث', en: 'Third Ring Road' },
			map: 'https://www.google.com/maps/search/?api=1&query=Optimum+Closets+Third+Ring+Road+Makkah'
		}
	],
	categories: [
		{ id: 'hinged-wood', img: 'hinged-oak-closed', ar: 'خزائن بأبواب خشبية مفصلية', en: 'Hinged wooden-door wardrobes', short: { ar: 'أبواب خشبية', en: 'Wooden doors' } },
		{ id: 'hinged-glass', img: 'glass-bronze-closed', ar: 'خزائن بأبواب زجاجية مفصلية', en: 'Hinged glass-door wardrobes', short: { ar: 'أبواب زجاجية', en: 'Glass doors' } },
		{ id: 'sliding', img: 'sliding-oak-closed', ar: 'خزائن بأبواب منزلقة', en: 'Sliding-door wardrobes', short: { ar: 'أبواب منزلقة', en: 'Sliding doors' } },
		{ id: 'walk-in', img: 'walkin-walnut-angle', ar: 'غرف الملابس', en: 'Walk-in closets', short: { ar: 'غرف ملابس', en: 'Walk-in' } },
		{ id: 'made-to-measure', img: 'bespoke-wall', ar: 'خزائن مفصّلة بالمقاس', en: 'Made-to-measure wardrobes', short: { ar: 'بالمقاس', en: 'Made to measure' } }
	],
	finishes: {
		oak: { ar: 'بلوط طبيعي', en: 'Natural oak', swatch: 'swatch-oak' },
		walnut: { ar: 'جوز مدخّن', en: 'Smoked walnut', swatch: 'swatch-walnut' },
		cashmere: { ar: 'كشميري مطفي', en: 'Matt cashmere', swatch: 'swatch-cashmere' },
		bronze: { ar: 'إطار برونزي', en: 'Bronze frame', swatch: 'swatch-frame-bronze' },
		black: { ar: 'إطار أسود', en: 'Black frame', swatch: 'swatch-frame-black' },
		champagne: { ar: 'إطار شمبانيا', en: 'Champagne frame', swatch: 'swatch-frame-champagne' }
	},
	products: [
		{
			id: 'sidra', cat: 'hinged-wood', price: 6900,
			name: { ar: 'خزانة سِدرة 4 أبواب', en: 'Sidra 4-door wardrobe' },
			desc: { ar: 'أبواب مفصلية بقشرة خشب طبيعي ومقابض نحاسية طويلة.', en: 'Real-wood veneer hinged doors with long brass handles.' },
			dims: [240, 255, 60],
			finishes: ['oak', 'walnut', 'cashmere'],
			img: function ( f, s ) { return 'hinged-' + f + '-' + s; }
		},
		{
			id: 'noor', cat: 'hinged-glass', price: 12400,
			name: { ar: 'خزانة نور الزجاجية', en: 'Noor glass wardrobe' },
			desc: { ar: 'إطار ألمنيوم نحيف 22 ملم، زجاج ملوّن وإضاءة LED داخلية.', en: 'Slim 22 mm aluminium frame, tinted glass and interior LED light.' },
			dims: [240, 255, 60],
			finishes: ['bronze', 'black', 'champagne'],
			img: function ( f, s ) { return 'glass-' + f + '-' + s; }
		},
		{
			id: 'rimal', cat: 'sliding', price: 8200,
			name: { ar: 'خزانة رِمال بأبواب منزلقة', en: 'Rimal sliding wardrobe' },
			desc: { ar: 'ثلاثة ألواح منزلقة بلوح زجاجي أوسط ومسار هادئ.', en: 'Three soft-close sliding panels with a glass centre panel.' },
			dims: [240, 255, 65],
			finishes: ['oak', 'cashmere', 'walnut'],
			img: function ( f, s ) { return 'sliding-' + f + '-' + s; }
		},
		{
			id: 'diwan', cat: 'walk-in', price: 27500, from: true,
			name: { ar: 'غرفة ملابس ديوان', en: 'Diwan walk-in closet' },
			desc: { ar: 'تصميم على شكل U مع جزيرة أدراج وإضاءة معلّقة.', en: 'U-shaped layout with a drawer island and pendant light.' },
			dims: [360, 255, 240],
			finishes: ['walnut', 'cashmere'],
			img: function ( f ) { return 'walkin-' + f + '-angle'; }
		},
		{
			id: 'marsam', cat: 'made-to-measure', price: 2950, unit: 'm',
			name: { ar: 'جدار مَرسم بالمقاس مع تسريحة', en: 'Marsam made-to-measure wall with vanity' },
			desc: { ar: 'خزائن بطول جدارك مع تسريحة ومرآة مدمجة.', en: 'Full-wall wardrobes with an integrated vanity and mirror.' },
			dims: [430, 255, 60],
			finishes: ['cashmere'],
			img: function () { return 'bespoke-wall'; }
		}
	],
	strings: {
		demo: { ar: 'معاينة تصميم: الأسعار تجريبية والصور مجسّمات ثلاثية الأبعاد توضيحية.', en: 'Design preview: demo prices, and the images are illustrative 3D renders.' },
		shop: { ar: 'تسوّق الخزائن', en: 'Shop Wardrobes' },
		bespoke: { ar: 'اطلب تصميمًا بمقاساتك', en: 'Request a Made-to-Measure Design' },
		products: { ar: 'منتجاتنا', en: 'Our Products' },
		work: { ar: 'أعمالنا', en: 'Our Work' },
		showrooms: { ar: 'المعارض', en: 'Showrooms' },
		contact: { ar: 'تواصل معنا', en: 'Contact' },
		cart: { ar: 'السلة', en: 'Cart' },
		account: { ar: 'حسابي', en: 'Account' },
		search: { ar: 'بحث', en: 'Search' },
		menu: { ar: 'القائمة', en: 'Menu' },
		close: { ar: 'إغلاق', en: 'Close' },
		heroTitle: { ar: 'خزائن تُصمَّم حول حياتك', en: 'Wardrobes designed around your life' },
		heroText: { ar: 'خشب منتقى، زجاج بإطارات نحيفة، وإضاءة تُظهر كل التفاصيل. نصمّم ونصنع ونركّب في جدة ومكة.', en: 'Selected woods, slim-framed glass and light that reveals every detail. Designed, made and installed in Jeddah and Makkah.' },
		categories: { ar: 'تسوّق حسب النوع', en: 'Shop by type' },
		featured: { ar: 'مختارات من المتجر', en: 'Featured wardrobes' },
		materials: { ar: 'الخامات', en: 'Materials' },
		materialsTitle: { ar: 'خامات تشعر بها قبل أن تراها', en: 'Materials you feel before you see' },
		process: { ar: 'كيف نعمل', en: 'How ordering works' },
		from: { ar: 'يبدأ من', en: 'From' },
		perM: { ar: 'للمتر الطولي', en: 'per linear metre' },
		add: { ar: 'أضف للسلة', en: 'Add to cart' },
		choose: { ar: 'اختر المواصفات', en: 'Choose options' },
		view: { ar: 'عرض', en: 'View' },
		inside: { ar: 'شاهد من الداخل', en: 'See inside' },
		closed: { ar: 'مغلقة', en: 'Closed' },
		open: { ar: 'مفتوحة', en: 'Open' },
		viewAll: { ar: 'عرض الكل', en: 'View all' },
		directions: { ar: 'الاتجاهات', en: 'Directions' },
		call: { ar: 'اتصل بنا', en: 'Call us' },
		cardPreview: { ar: 'معاينة بطاقة المنتج', en: 'Product card preview' },
		dims: { ar: 'العرض × الارتفاع × العمق', en: 'W × H × D' },
		cm: { ar: 'سم', en: 'cm' },
		lang: { ar: 'English', en: 'العربية' },
		rendered: { ar: 'صورة توضيحية مُجسّمة', en: 'Illustrative render' },
		footerNote: { ar: 'جميع الحقوق محفوظة', en: 'All rights reserved' },
		consultFree: { ar: 'استشارة وقياس مجاني', en: 'Free consultation & measurement' },
		leadTime: { ar: 'من 2 إلى 4 أسابيع من اعتماد التصميم حتى التركيب', en: '2–4 weeks from design approval to installation' },
		twoCities: { ar: 'معرضان في جدة ومكة', en: 'Showrooms in Jeddah & Makkah' }
	},
	steps: [
		{ ar: [ 'استشارة وقياس', 'نزور مساحتك مجانًا، نأخذ المقاسات ونفهم احتياجك.' ], en: [ 'Consult & measure', 'A free visit to measure your space and understand how you live.' ] },
		{ ar: [ 'التصميم والاعتماد', 'نقترح التقسيم الداخلي والخامات، وتعتمد التصميم والسعر النهائي.' ], en: [ 'Design & approve', 'We propose interiors and materials; you approve the design and final price.' ] },
		{ ar: [ 'التصنيع', 'من 2 إلى 4 أسابيع من اعتماد التصميم حتى التركيب.' ], en: [ 'Crafting', '2–4 weeks from design approval to installation.' ] },
		{ ar: [ 'التركيب', 'فريقنا يركّب بعناية ويسلّمك خزانة جاهزة للاستخدام.' ], en: [ 'Installation', 'Our team installs with care and hands over a ready-to-use wardrobe.' ] }
	],
	materials: [
		{ img: 'detail-handle', ar: [ 'البلوط الطبيعي', 'قشرة خشب حقيقية بعروق مستقيمة ومقابض نحاسية مصقولة.' ], en: [ 'Natural oak', 'Real straight-grain veneer with brushed brass handles.' ] },
		{ img: 'detail-glass-frame', ar: [ 'الزجاج والإطار النحيف', 'ألمنيوم برونزي بعرض 22 ملم يحيط بزجاج ملوّن.' ], en: [ 'Glass & slim frame', 'A 22 mm bronze aluminium frame around tinted glass.' ] },
		{ img: 'detail-led-shelf', ar: [ 'إضاءة مدمجة', 'شرائط LED دافئة أسفل كل رف تُظهر ملابسك بوضوح.' ], en: [ 'Integrated light', 'Warm LED strips under each shelf show every garment clearly.' ] },
		{ img: 'detail-drawer', ar: [ 'أدراج الجوز', 'أدراج داخلية بإغلاق هادئ ومقابض برونزية.' ], en: [ 'Walnut drawers', 'Soft-close interior drawers with bronze pulls.' ] }
	]
};
