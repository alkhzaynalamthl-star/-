<?php
/**
 * DEMO catalogue — placeholder names and prices for preview mode only.
 *
 * Nothing here is a confirmed Optimum Closets product or price. Every item
 * created from this file is flagged `_oc_demo` and can be removed with
 * `wp optimum demo remove` (or Tools → Optimum demo data).
 *
 * Image values are bundled render names (assets/img/renders).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

return array(
	'attributes' => array(
		'door'  => array(
			'label' => array( 'نوع الأبواب', 'Door type' ),
			'terms' => array(
				'hinged-wood'  => array( 'أبواب خشبية مفصلية', 'Hinged wooden doors' ),
				'hinged-glass' => array( 'أبواب زجاجية مفصلية', 'Hinged glass doors' ),
				'sliding'      => array( 'أبواب منزلقة', 'Sliding doors' ),
				'open-system'  => array( 'نظام مفتوح بدون أبواب', 'Open system, no doors' ),
			),
		),
		'color' => array(
			'label' => array( 'اللون والتشطيب', 'Colour & finish' ),
			'terms' => array(
				'natural-oak'   => array( 'بلوط طبيعي', 'Natural oak', '#b89872' ),
				'smoked-walnut' => array( 'جوز مدخّن', 'Smoked walnut', '#5a3d2b' ),
				'cashmere'      => array( 'كشميري', 'Cashmere', '#e4dbcf' ),
				'bronze'        => array( 'برونزي', 'Bronze', '#6d5238' ),
				'black'         => array( 'أسود', 'Black', '#1b1b1c' ),
				'champagne'     => array( 'شمبانيا', 'Champagne', '#b89c74' ),
			),
		),
	),

	'categories' => array(
		'hinged-wood'     => array( 'خزائن بأبواب خشبية مفصلية', 'Hinged wooden-door wardrobes', 'hinged-oak-closed', 'قشرة خشب طبيعي، أبواب مفصلية بإغلاق هادئ، وتقسيمات داخلية تختارها بنفسك.', 'Real-wood veneer, soft-close hinged doors and interiors you choose yourself.' ),
		'hinged-glass'    => array( 'خزائن بأبواب زجاجية مفصلية', 'Hinged glass-door wardrobes', 'glass-bronze-closed', 'إطارات ألمنيوم نحيفة وزجاج ملوّن مع إضاءة داخلية تُظهر ملابسك كواجهة عرض.', 'Slim aluminium frames and tinted glass with interior light that turns your wardrobe into a display.' ),
		'sliding'         => array( 'خزائن بأبواب منزلقة', 'Sliding-door wardrobes', 'sliding-oak-closed', 'الحل الأمثل للغرف التي لا تتسع لفتح الأبواب، بمسارات هادئة وألواح خشب وزجاج.', 'Ideal where hinged doors have no room to open, with quiet tracks and wood or glass panels.' ),
		'walk-in'         => array( 'غرف الملابس', 'Walk-in closets', 'walkin-walnut-angle', 'غرف ملابس مفتوحة بتصميم كامل مع جزر أدراج وإضاءة.', 'Complete open dressing rooms with drawer islands and lighting.' ),
		'made-to-measure' => array( 'خزائن مفصّلة بالمقاس', 'Made-to-measure wardrobes', 'bespoke-wall', 'خزائن على مقاس جدارك تمامًا، تبدأ بزيارة قياس مجانية.', 'Wardrobes built exactly to your wall, starting with a free measurement visit.' ),
		'accessories'     => array( 'إكسسوارات', 'Accessories', 'detail-drawer', 'إضافات تنظيم وإضاءة لخزانتك.', 'Organisation and lighting add-ons for your wardrobe.' ),
	),

	'products' => array(
		array(
			'sku'      => 'DEMO-SIDRA',
			'name'     => array( 'خزانة سِدرة بأبواب خشبية', 'Sidra hinged wooden wardrobe' ),
			'short'    => array( 'أربعة أبواب مفصلية بقشرة خشب طبيعي ومقابض نحاسية طويلة، وتقسيم داخلي تختاره.', 'Four hinged doors in real-wood veneer with long brass handles, and the interior layout of your choice.' ),
			'desc'     => array( 'خزانة سِدرة تجمع بساطة الخطوط مع دفء الخشب. الأبواب بإغلاق هادئ، والمقابض النحاسية الطويلة مريحة لليد. اختر العرض والتشطيب والتقسيم الداخلي، وسيؤكد فريقنا المقاسات في زيارة القياس المجانية.', 'Sidra pairs simple lines with the warmth of wood. Doors close softly and the long brass handles are comfortable to hold. Choose the width, finish and interior; our team confirms the measurements at the free visit.' ),
			'cat'      => 'hinged-wood',
			'door'     => array( 'hinged-wood' ),
			'colors'   => array( 'natural-oak', 'smoked-walnut', 'cashmere' ),
			'featured' => true,
			'order'    => 1,
			'config'   => array(
				'door'        => 'hinged-wood',
				'height'      => 255,
				'depth'       => 60,
				'pricing'     => 'table',
				'widths'      => array( array( 160, 4900 ), array( 200, 5900 ), array( 240, 6900 ), array( 280, 7900 ) ),
				'default'     => array( 'width' => 240, 'finish' => 'oak', 'layout' => 'classic' ),
				'groups'      => array(
					'finish' => array( 'oak' => 0, 'walnut' => 900, 'cashmere' => 400 ),
					'layout' => array( 'classic' => 0, 'long' => 250, 'drawers' => 750 ),
				),
				'extras'      => array( 'led' => 690, 'install' => 350 ),
				'image_group' => 'finish',
				'shown'       => array( 'width' => 240 ),
				'images'      => array(
					'oak'      => array( 'closed' => 'hinged-oak-closed', 'open' => 'hinged-oak-open', 'open_by' => array( 'long' => 'hinged-oak-open-long', 'drawers' => 'hinged-oak-open-drawers' ), 'gallery' => array( 'detail-handle' ) ),
					'walnut'   => array( 'closed' => 'hinged-walnut-closed', 'open' => 'hinged-walnut-open', 'open_by' => array( 'long' => 'hinged-walnut-open-long', 'drawers' => 'hinged-walnut-open-drawers' ), 'gallery' => array( 'detail-drawer' ) ),
					'cashmere' => array( 'closed' => 'hinged-cashmere-closed', 'open' => 'hinged-cashmere-open', 'open_by' => array( 'long' => 'hinged-cashmere-open-long', 'drawers' => 'hinged-cashmere-open-drawers' ), 'gallery' => array() ),
				),
			),
		),
		array(
			'sku'      => 'DEMO-NOOR',
			'name'     => array( 'خزانة نور الزجاجية', 'Noor glass wardrobe' ),
			'short'    => array( 'أبواب زجاجية بإطار ألمنيوم نحيف 22 ملم، وإضاءة LED دافئة داخل كل قسم.', 'Glass doors in a slim 22 mm aluminium frame, with warm LED light inside every section.' ),
			'desc'     => array( 'نور خزانة تعرض ملابسك كما تُعرض في المتاجر: زجاج ملوّن يحمي من الغبار، وإضاءة داخلية تُظهر الألوان بوضوح. التقسيم: علّاقتان مزدوجتان وأرفف قابلة للتعديل.', 'Noor displays your clothes the way a boutique does: tinted glass keeps dust out while interior light shows true colours. Layout: double hanging and adjustable shelves.' ),
			'cat'      => 'hinged-glass',
			'door'     => array( 'hinged-glass' ),
			'colors'   => array( 'bronze', 'black', 'champagne' ),
			'featured' => true,
			'order'    => 2,
			'config'   => array(
				'door'        => 'hinged-glass',
				'height'      => 255,
				'depth'       => 60,
				'pricing'     => 'table',
				'widths'      => array( array( 200, 10400 ), array( 240, 12400 ), array( 300, 15200 ) ),
				'default'     => array( 'width' => 240, 'frame' => 'bronze' ),
				'groups'      => array(
					'frame' => array( 'bronze' => 0, 'black' => 0, 'champagne' => 600 ),
				),
				'extras'      => array( 'install' => 350 ),
				'image_group' => 'frame',
				'shown'       => array( 'width' => 240 ),
				'images'      => array(
					'bronze'    => array( 'closed' => 'glass-bronze-closed', 'open' => 'glass-bronze-open', 'gallery' => array( 'detail-glass-frame', 'detail-led-shelf' ) ),
					'black'     => array( 'closed' => 'glass-black-closed', 'open' => 'glass-black-open', 'gallery' => array() ),
					'champagne' => array( 'closed' => 'glass-champagne-closed', 'open' => 'glass-champagne-open', 'gallery' => array() ),
				),
			),
		),
		array(
			'sku'      => 'DEMO-RIMAL',
			'name'     => array( 'خزانة رِمال بأبواب منزلقة', 'Rimal sliding wardrobe' ),
			'short'    => array( 'ثلاثة ألواح منزلقة بإغلاق هادئ ولوح زجاجي أوسط، مثالية للغرف المتوسطة.', 'Three soft-close sliding panels with a glass centre panel, ideal for medium-sized rooms.' ),
			'desc'     => array( 'رِمال توفّر مساحة الفتح بالكامل: الألواح تنزلق على مسار علوي هادئ، والبروفايل المعدني الرفيع يعمل كمقبض. لون البروفايل يُطابق التشطيب المختار.', 'Rimal saves all the swing space: panels glide on a quiet top track, and the slim metal profile doubles as the handle. The profile colour matches the chosen finish.' ),
			'cat'      => 'sliding',
			'door'     => array( 'sliding' ),
			'colors'   => array( 'natural-oak', 'cashmere', 'smoked-walnut' ),
			'featured' => true,
			'order'    => 3,
			'config'   => array(
				'door'        => 'sliding',
				'height'      => 255,
				'depth'       => 65,
				'pricing'     => 'table',
				'widths'      => array( array( 240, 8200 ), array( 270, 8900 ), array( 300, 9900 ) ),
				'default'     => array( 'width' => 240, 'finish' => 'oak' ),
				'groups'      => array(
					'finish' => array( 'oak' => 0, 'cashmere' => 400, 'walnut' => 900 ),
				),
				'extras'      => array( 'led' => 690, 'install' => 350 ),
				'image_group' => 'finish',
				'shown'       => array( 'width' => 240 ),
				'images'      => array(
					'oak'      => array( 'closed' => 'sliding-oak-closed', 'open' => 'sliding-oak-open', 'gallery' => array( 'detail-sliding-profile' ) ),
					'cashmere' => array( 'closed' => 'sliding-cashmere-closed', 'open' => 'sliding-cashmere-open', 'gallery' => array() ),
					'walnut'   => array( 'closed' => 'sliding-walnut-closed', 'open' => 'sliding-walnut-open', 'gallery' => array() ),
				),
			),
		),
		array(
			'sku'      => 'DEMO-DIWAN',
			'name'     => array( 'غرفة ملابس ديوان', 'Diwan walk-in closet' ),
			'short'    => array( 'غرفة ملابس على شكل U مع جزيرة أدراج بسطح زجاجي وإضاءة معلّقة. السعر حسب عرض الغرفة.', 'A U-shaped walk-in with a glass-topped drawer island and pendant light. Priced by room width.' ),
			'desc'     => array( 'ديوان تصميم كامل لغرفة الملابس: جدار خلفي بأبواب زجاجية، وجانبان مفتوحان للتعليق والأحذية والأرفف، وجزيرة أدراج في المنتصف. يبدأ كل مشروع بزيارة قياس مجانية لتأكيد التصميم.', 'Diwan is a complete dressing room: a glass-fronted back wall, open sides for hanging, shoes and shelves, and a drawer island in the middle. Every project starts with a free measurement visit to confirm the design.' ),
			'cat'      => 'walk-in',
			'door'     => array( 'open-system', 'hinged-glass' ),
			'colors'   => array( 'smoked-walnut', 'cashmere', 'natural-oak' ),
			'featured' => true,
			'order'    => 4,
			'config'   => array(
				'door'        => 'walk-in',
				'height'      => 255,
				'depth'       => 60,
				'pricing'     => 'table',
				'widths'      => array( array( 300, 27500 ), array( 360, 32500 ), array( 420, 38000 ) ),
				'default'     => array( 'width' => 360, 'finish' => 'walnut' ),
				'groups'      => array(
					'finish' => array( 'walnut' => 0, 'cashmere' => 0, 'oak' => 0 ),
				),
				'extras'      => array( 'organiser' => 240, 'install' => 350 ),
				'image_group' => 'finish',
				'shown'       => array( 'width' => 360 ),
				'images'      => array(
					'walnut'   => array( 'closed' => 'walkin-walnut-angle', 'gallery' => array( 'detail-drawer' ) ),
					'cashmere' => array( 'closed' => 'walkin-cashmere-angle', 'gallery' => array() ),
					'oak'      => array( 'closed' => 'walkin-oak-mobile', 'gallery' => array( 'walkin-oak-wide' ) ),
				),
			),
		),
		array(
			'sku'      => 'DEMO-MARSAM',
			'name'     => array( 'جدار مَرسم بالمقاس مع تسريحة', 'Marsam made-to-measure wall with vanity' ),
			'short'    => array( 'خزائن بطول جدارك مع تسريحة من البلوط ومرآة دائرية. السعر للمتر الطولي.', 'Wardrobes along your whole wall with an oak vanity and round mirror. Priced per linear metre.' ),
			'desc'     => array( 'أدخل عرض جدارك لترى السعر المبدئي. يؤكد مصممنا التصميم والمقاسات والسعر النهائي في زيارة القياس المجانية قبل التصنيع.', 'Enter your wall width to see the initial price. Our designer confirms the design, measurements and final price at the free measurement visit before manufacturing.' ),
			'cat'      => 'made-to-measure',
			'door'     => array( 'hinged-wood' ),
			'colors'   => array( 'cashmere', 'natural-oak' ),
			'featured' => false,
			'order'    => 5,
			'config'   => array(
				'door'        => 'bespoke',
				'height'      => 255,
				'depth'       => 60,
				'pricing'     => 'per_metre',
				'per_metre'   => array( 'rate' => 2950, 'min' => 150, 'max' => 600, 'step' => 10 ),
				'default'     => array( 'width' => 430, 'finish' => 'cashmere' ),
				'groups'      => array(
					'finish' => array( 'cashmere' => 0 ),
				),
				'extras'      => array( 'led' => 690, 'install' => 350 ),
				'image_group' => 'finish',
				'shown'       => array( 'width' => 430 ),
				'images'      => array(
					'cashmere' => array( 'closed' => 'bespoke-wall', 'gallery' => array( 'bespoke-wall-wide' ) ),
				),
			),
		),
		array(
			'sku'    => 'DEMO-LED',
			'name'   => array( 'طقم إضاءة LED للأرفف', 'Shelf LED lighting kit' ),
			'short'  => array( 'شرائط LED دافئة 3000K مع حساس فتح الباب، لخزانة حتى 240 سم.', 'Warm 3000K LED strips with a door sensor, for wardrobes up to 240 cm.' ),
			'desc'   => array( 'يُركّب أسفل الأرفف ليُظهر الملابس بوضوح عند فتح الباب.', 'Fits under shelves to light your clothes as the door opens.' ),
			'cat'    => 'accessories',
			'door'   => array(),
			'colors' => array(),
			'price'  => 690,
			'image'  => 'detail-led-shelf',
			'order'  => 6,
		),
		array(
			'sku'    => 'DEMO-ORG',
			'name'   => array( 'منظّم أدراج مخملي', 'Velvet drawer organiser' ),
			'short'  => array( 'تقسيمات مخملية للساعات والإكسسوارات والشماغ.', 'Velvet dividers for watches, accessories and shemaghs.' ),
			'desc'   => array( 'يناسب أدراج خزائننا الداخلية بعرض 60 سم.', 'Fits our 60 cm interior drawers.' ),
			'cat'    => 'accessories',
			'door'   => array(),
			'colors' => array(),
			'price'  => 240,
			'image'  => 'detail-drawer',
			'order'  => 7,
		),
	),

	'pages' => array(
		'made-to-measure' => array( 'اطلب تصميمًا بالمقاس', 'Request a made-to-measure design', 'made-to-measure' ),
		'our-work'        => array( 'أعمالنا', 'Our work', 'our-work' ),
		'showrooms'       => array( 'المعارض', 'Showrooms', 'showrooms' ),
		'contact'         => array( 'تواصل معنا', 'Contact us', 'contact' ),
	),
);
