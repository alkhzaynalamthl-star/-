<?php
/**
 * Made-to-measure design requests and contact messages.
 *
 * - Stored as a private post type (Dashboard → Design requests), so nothing is
 *   lost even if e-mail is not configured.
 * - Photos go to a non-listed uploads folder and are only served to logged-in
 *   staff through an authenticated download handler.
 * - Hand-off to a request handling service: action `optimum_request_received`
 *   and an optional signed webhook (Customizer → Requests & integrations).
 * - The customer is told exactly what happened: saved, and whether it was
 *   actually delivered to the team. Preview mode never sends anything.
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

const OPTIMUM_MAX_PHOTOS     = 6;
const OPTIMUM_MAX_PHOTO_SIZE = 8388608; // 8 MB.

/**
 * Post type.
 */
function optimum_register_request_type() {
	register_post_type(
		'oc_request',
		array(
			'labels'          => array(
				'name'          => __( 'Design requests', 'optimum' ),
				'singular_name' => __( 'Design request', 'optimum' ),
				'menu_name'     => __( 'Design requests', 'optimum' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-clipboard',
			'menu_position'   => 56,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'optimum_register_request_type' );

/**
 * Choice lists for the form (keys stored, labels translated).
 *
 * @return array
 */
function optimum_request_choices() {
	return array(
		'space'   => array(
			'bedroom' => __( 'Bedroom wall', 'optimum' ),
			'walkin'  => __( 'Walk-in / dressing room', 'optimum' ),
			'kids'    => __( 'Children’s room', 'optimum' ),
			'other'   => __( 'Other space', 'optimum' ),
		),
		'door'    => array(
			'hinged-wood'  => __( 'Hinged wooden doors', 'optimum' ),
			'hinged-glass' => __( 'Hinged glass doors', 'optimum' ),
			'sliding'      => __( 'Sliding doors', 'optimum' ),
			'open'         => __( 'Open system, no doors', 'optimum' ),
			'unsure'       => __( 'Not sure yet — advise me', 'optimum' ),
		),
		'contact' => array(
			'call'     => __( 'Phone call', 'optimum' ),
			'whatsapp' => __( 'WhatsApp', 'optimum' ),
			'email'    => __( 'E-mail', 'optimum' ),
		),
	);
}

/**
 * Normalise a Saudi mobile number (Arabic-Indic digits accepted).
 *
 * @param string $phone Input.
 * @return string Empty when invalid.
 */
function optimum_normalise_mobile( $phone ) {
	$phone = strtr( (string) $phone, array_combine( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), range( 0, 9 ) ) );
	$phone = preg_replace( '/[^\d+]/', '', $phone );
	if ( preg_match( '/^(?:\+?966|0)(5\d{8})$/', $phone, $m ) ) {
		return '+966' . $m[1];
	}
	return '';
}

/**
 * Handle a submitted form (admin-post.php, logged in or not).
 */
function optimum_handle_request() {
	// admin-post.php has no language in its URL: use the language of the page the form was on.
	$GLOBALS['optimum_lang'] = isset( $_POST['oc_lang'] ) && 'en' === $_POST['oc_lang'] ? 'en' : 'ar'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	switch_to_locale( 'en' === $GLOBALS['optimum_lang'] ? 'en_US' : 'ar' );
	unload_textdomain( 'optimum' );
	if ( 'ar' === $GLOBALS['optimum_lang'] ) {
		load_textdomain( 'optimum', OPTIMUM_DIR . '/languages/ar.mo', 'ar' );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified just below.
	$kind     = isset( $_POST['oc_kind'] ) && 'contact' === $_POST['oc_kind'] ? 'contact' : 'design';
	$back     = isset( $_POST['oc_back'] ) ? esc_url_raw( wp_unslash( $_POST['oc_back'] ) ) : home_url( '/' );
	$back     = wp_validate_redirect( $back, home_url( '/' ) );
	$errors   = array();
	$posted   = wp_unslash( $_POST );
	// phpcs:enable

	if ( ! isset( $posted['oc_nonce'] ) || ! wp_verify_nonce( $posted['oc_nonce'], 'optimum_request_' . $kind ) ) {
		$errors['form'] = __( 'Your session expired. Please submit the form again.', 'optimum' );
	}
	// Spam traps: hidden field must stay empty, and humans take more than 3 seconds.
	if ( ! empty( $posted['oc_website'] ) || ( isset( $posted['oc_t'] ) && time() - (int) $posted['oc_t'] < 3 ) ) {
		$errors['form'] = __( 'We could not accept this submission. Please try again.', 'optimum' );
	}

	$choices = optimum_request_choices();
	$data    = array(
		'kind'    => $kind,
		'name'    => sanitize_text_field( isset( $posted['oc_name'] ) ? $posted['oc_name'] : '' ),
		'phone'   => optimum_normalise_mobile( isset( $posted['oc_phone'] ) ? $posted['oc_phone'] : '' ),
		'email'   => sanitize_email( isset( $posted['oc_email'] ) ? $posted['oc_email'] : '' ),
		'city'    => isset( $posted['oc_city'] ) && array_key_exists( $posted['oc_city'], optimum_request_cities() ) ? $posted['oc_city'] : '',
		'message' => sanitize_textarea_field( isset( $posted['oc_message'] ) ? $posted['oc_message'] : '' ),
		'lang'    => optimum_lang(),
	);
	if ( '' === $data['name'] ) {
		$errors['oc_name'] = __( 'Please enter your name.', 'optimum' );
	}
	if ( '' === $data['phone'] ) {
		$errors['oc_phone'] = __( 'Please enter a Saudi mobile number, for example 05XXXXXXXX.', 'optimum' );
	}
	if ( ! empty( $posted['oc_email'] ) && ! is_email( $data['email'] ) ) {
		$errors['oc_email'] = __( 'Please check the e-mail address.', 'optimum' );
	}
	if ( 'contact' === $kind && '' === $data['message'] ) {
		$errors['oc_message'] = __( 'Please write your message.', 'optimum' );
	}

	if ( 'design' === $kind ) {
		foreach ( array( 'width' => array( 50, 1500 ), 'height' => array( 100, 400 ), 'depth' => array( 0, 200 ) ) as $dim => $range ) {
			$raw          = isset( $posted[ 'oc_' . $dim ] ) ? strtr( (string) $posted[ 'oc_' . $dim ], array_combine( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), range( 0, 9 ) ) ) : '';
			$val          = '' === trim( $raw ) ? 0 : (int) $raw;
			$data[ $dim ] = $val;
			$required     = 'depth' !== $dim;
			if ( ( $required && ! $val ) || ( $val && ( $val < $range[0] || $val > $range[1] ) ) ) {
				/* translators: 1: minimum, 2: maximum */
				$errors[ 'oc_' . $dim ] = sprintf( __( 'Please enter a value between %1$s and %2$s cm.', 'optimum' ), optimum_num( max( 1, $range[0] ) ), optimum_num( $range[1] ) );
			}
		}
		foreach ( array( 'space', 'door', 'contact' ) as $c ) {
			$val        = isset( $posted[ 'oc_' . $c ] ) ? sanitize_key( $posted[ 'oc_' . $c ] ) : '';
			$data[ $c ] = array_key_exists( $val, $choices[ $c ] ) ? $val : '';
		}
		if ( '' === $data['city'] ) {
			$errors['oc_city'] = __( 'Please choose your city.', 'optimum' );
		}
		if ( empty( $posted['oc_consent'] ) ) {
			$errors['oc_consent'] = __( 'Please agree so we can contact you about your request.', 'optimum' );
		}
	}

	// Photos: validate everything before storing anything.
	$files = optimum_collect_uploads( 'oc_photos' );
	if ( is_wp_error( $files ) ) {
		$errors['oc_photos'] = $files->get_error_message();
		$files               = array();
	}

	if ( $errors ) {
		$token = wp_generate_password( 20, false );
		set_transient( 'oc_req_' . $token, array( 'errors' => $errors, 'old' => array_diff_key( $data, array( 'lang' => 1 ) ) ), 15 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'oc_result', $token, $back ) . '#request-form' );
		exit;
	}

	$ref     = strtoupper( 'OC-' . wp_date( 'ymd' ) . '-' . wp_generate_password( 4, false, false ) );
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'oc_request',
			'post_status' => 'private',
			'post_title'  => $ref . ' — ' . $data['name'] . ( $data['city'] ? ' — ' . $data['city'] : '' ),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		$token = wp_generate_password( 20, false );
		set_transient( 'oc_req_' . $token, array( 'errors' => array( 'form' => __( 'We could not save your request. Please call us or try again.', 'optimum' ) ), 'old' => $data ), 15 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'oc_result', $token, $back ) . '#request-form' );
		exit;
	}
	foreach ( $data as $k => $v ) {
		update_post_meta( $post_id, '_oc_' . $k, $v );
	}
	update_post_meta( $post_id, '_oc_ref', $ref );
	update_post_meta( $post_id, '_oc_preview', optimum_preview_mode() ? 1 : 0 );
	$stored = optimum_store_uploads( $files, $post_id );
	update_post_meta( $post_id, '_oc_photos', $stored );

	$delivered = array();
	if ( ! optimum_preview_mode() ) {
		$delivered = optimum_notify_request( $post_id, $data, $ref, $stored );
	}
	do_action( 'optimum_request_received', $post_id, $data, $stored );

	$token = wp_generate_password( 20, false );
	set_transient(
		'oc_req_' . $token,
		array(
			'ok'        => true,
			'ref'       => $ref,
			'delivered' => ! empty( array_filter( $delivered ) ),
			'preview'   => optimum_preview_mode(),
		),
		HOUR_IN_SECONDS
	);
	wp_safe_redirect( add_query_arg( 'oc_result', $token, $back ) . '#request-form' );
	exit;
}
add_action( 'admin_post_optimum_request', 'optimum_handle_request' );
add_action( 'admin_post_nopriv_optimum_request', 'optimum_handle_request' );

/**
 * Cities for requests (same keys as checkout).
 *
 * @return array
 */
function optimum_request_cities() {
	return array(
		'Jeddah' => __( 'Jeddah', 'optimum' ),
		'Makkah' => __( 'Makkah', 'optimum' ),
		'Other'  => __( 'Another city', 'optimum' ),
	);
}

/**
 * Validate uploaded photos.
 *
 * @param string $field Field name.
 * @return array|WP_Error List of [tmp_name, name, type].
 */
function optimum_collect_uploads( $field ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked by the caller.
	if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ]['name'] ) ) {
		return array();
	}
	$f   = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing
	$out = array();
	foreach ( $f['name'] as $i => $name ) {
		if ( UPLOAD_ERR_NO_FILE === (int) $f['error'][ $i ] ) {
			continue;
		}
		if ( UPLOAD_ERR_OK !== (int) $f['error'][ $i ] || $f['size'][ $i ] > OPTIMUM_MAX_PHOTO_SIZE ) {
			/* translators: %s: file size limit */
			return new WP_Error( 'size', sprintf( __( 'Each photo must be smaller than %s MB.', 'optimum' ), optimum_num( OPTIMUM_MAX_PHOTO_SIZE / 1048576 ) ) );
		}
		$check = wp_check_filetype_and_ext( $f['tmp_name'][ $i ], $name, array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic' ) );
		if ( empty( $check['type'] ) || ( 'image/heic' !== $check['type'] && ! @getimagesize( $f['tmp_name'][ $i ] ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'type', __( 'Photos must be JPG, PNG, WebP or HEIC images.', 'optimum' ) );
		}
		$out[] = array( $f['tmp_name'][ $i ], sanitize_file_name( $name ), $check['type'], $check['ext'] );
	}
	if ( count( $out ) > OPTIMUM_MAX_PHOTOS ) {
		/* translators: %s: maximum number of photos */
		return new WP_Error( 'count', sprintf( __( 'Please send up to %s photos.', 'optimum' ), optimum_num( OPTIMUM_MAX_PHOTOS ) ) );
	}
	return $out;
}

/**
 * Private folder for request photos.
 *
 * @return string Absolute path.
 */
function optimum_request_dir() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'oc-requests';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		// Apache: deny direct access. On nginx add: location ^~ /wp-content/uploads/oc-requests/ { deny all; }
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/**
 * Move validated uploads into the private folder with random names.
 *
 * @param array $files   From optimum_collect_uploads().
 * @param int   $post_id Request ID.
 * @return array Stored file names.
 */
function optimum_store_uploads( $files, $post_id ) {
	$dir    = optimum_request_dir();
	$stored = array();
	foreach ( $files as $f ) {
		$name = $post_id . '-' . wp_generate_password( 16, false, false ) . '.' . $f[3];
		if ( move_uploaded_file( $f[0], $dir . '/' . $name ) ) { // phpcs:ignore Generic.PHP.ForbiddenFunctions
			$stored[] = $name;
		}
	}
	return $stored;
}

/**
 * Staff-only photo download.
 */
add_action(
	'admin_post_optimum_request_file',
	function () {
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$file = isset( $_GET['f'] ) ? basename( sanitize_file_name( wp_unslash( $_GET['f'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'oc_file_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || ! in_array( $file, (array) get_post_meta( $id, '_oc_photos', true ), true ) ) {
			wp_die( esc_html__( 'Not allowed.', 'optimum' ), 403 );
		}
		$path = optimum_request_dir() . '/' . $file;
		$type = wp_check_filetype( $path );
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/**
 * Notify the team. Returns what was actually delivered.
 *
 * @param int    $post_id Request ID.
 * @param array  $data    Data.
 * @param string $ref     Reference.
 * @param array  $photos  Stored photo names.
 * @return array email => bool, webhook => bool
 */
function optimum_notify_request( $post_id, $data, $ref, $photos ) {
	$result = array(
		'email'   => false,
		'webhook' => false,
	);
	$to     = optimum_mod( 'request_email' ) ? optimum_mod( 'request_email' ) : get_option( 'admin_email' );
	$lines  = array();
	foreach ( $data as $k => $v ) {
		if ( '' !== $v && 0 !== $v ) {
			$lines[] = ucfirst( $k ) . ': ' . $v;
		}
	}
	$lines[] = 'Photos: ' . count( $photos );
	$lines[] = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	/* translators: %s: request reference */
	$result['email'] = (bool) wp_mail( $to, sprintf( __( 'New request %s', 'optimum' ), $ref ), implode( "\n", $lines ) );

	$hook = optimum_mod( 'request_webhook' );
	if ( $hook ) {
		$body = wp_json_encode(
			array(
				'reference' => $ref,
				'request'   => $data,
				'photos'    => count( $photos ),
				'admin_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			)
		);
		$headers = array( 'Content-Type' => 'application/json' );
		if ( defined( 'OPTIMUM_WEBHOOK_SECRET' ) && OPTIMUM_WEBHOOK_SECRET ) {
			$headers['X-Optimum-Signature'] = 'sha256=' . hash_hmac( 'sha256', $body, OPTIMUM_WEBHOOK_SECRET );
		}
		$res               = wp_remote_post( $hook, array( 'timeout' => 8, 'headers' => $headers, 'body' => $body ) );
		$code              = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		$result['webhook'] = $code >= 200 && $code < 300;
	}
	update_post_meta( $post_id, '_oc_delivered', $result );
	return $result;
}

/**
 * Result of the last submission for this form (from the one-time token).
 *
 * @return array
 */
function optimum_request_result() {
	static $r = null;
	if ( null !== $r ) {
		return $r;
	}
	$r = array();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- opaque token lookup.
	if ( isset( $_GET['oc_result'] ) ) {
		$tok = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['oc_result'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
		$got = get_transient( 'oc_req_' . $tok );
		$r   = is_array( $got ) ? $got : array();
	}
	return $r;
}

/**
 * Admin: show the request details.
 */
add_action(
	'add_meta_boxes_oc_request',
	function () {
		add_meta_box(
			'oc_request_details',
			__( 'Request details', 'optimum' ),
			function ( $post ) {
				$choices = optimum_request_choices();
				$rows    = array(
					__( 'Reference', 'optimum' )   => get_post_meta( $post->ID, '_oc_ref', true ),
					__( 'Type', 'optimum' )        => 'contact' === get_post_meta( $post->ID, '_oc_kind', true ) ? __( 'Contact message', 'optimum' ) : __( 'Made-to-measure design', 'optimum' ),
					__( 'Name', 'optimum' )        => get_post_meta( $post->ID, '_oc_name', true ),
					__( 'Mobile', 'optimum' )      => get_post_meta( $post->ID, '_oc_phone', true ),
					__( 'E-mail', 'optimum' )      => get_post_meta( $post->ID, '_oc_email', true ),
					__( 'City', 'optimum' )        => get_post_meta( $post->ID, '_oc_city', true ),
					__( 'Space', 'optimum' )       => ( $v = get_post_meta( $post->ID, '_oc_space', true ) ) && isset( $choices['space'][ $v ] ) ? $choices['space'][ $v ] : '',
					__( 'Doors', 'optimum' )       => ( $v = get_post_meta( $post->ID, '_oc_door', true ) ) && isset( $choices['door'][ $v ] ) ? $choices['door'][ $v ] : '',
					__( 'Width × height × depth', 'optimum' ) => implode( ' × ', array_filter( array( get_post_meta( $post->ID, '_oc_width', true ), get_post_meta( $post->ID, '_oc_height', true ), get_post_meta( $post->ID, '_oc_depth', true ) ) ) ),
					__( 'Contact by', 'optimum' )  => ( $v = get_post_meta( $post->ID, '_oc_contact', true ) ) && isset( $choices['contact'][ $v ] ) ? $choices['contact'][ $v ] : '',
					__( 'Language', 'optimum' )    => get_post_meta( $post->ID, '_oc_lang', true ),
					__( 'Message', 'optimum' )     => get_post_meta( $post->ID, '_oc_message', true ),
					__( 'Preview mode', 'optimum' ) => get_post_meta( $post->ID, '_oc_preview', true ) ? __( 'Yes — test submission', 'optimum' ) : __( 'No', 'optimum' ),
				);
				echo '<table class="widefat striped"><tbody>';
				foreach ( $rows as $k => $v ) {
					echo '<tr><th style="width:200px">' . esc_html( $k ) . '</th><td>' . nl2br( esc_html( (string) $v ) ) . '</td></tr>';
				}
				echo '</tbody></table>';
				$photos = (array) get_post_meta( $post->ID, '_oc_photos', true );
				if ( $photos ) {
					echo '<p><strong>' . esc_html__( 'Photos', 'optimum' ) . '</strong></p><p>';
					foreach ( $photos as $i => $f ) {
						$url = wp_nonce_url( admin_url( 'admin-post.php?action=optimum_request_file&id=' . $post->ID . '&f=' . rawurlencode( $f ) ), 'oc_file_' . $post->ID );
						echo '<a class="button" target="_blank" href="' . esc_url( $url ) . '">' . esc_html( sprintf( '%s %d', __( 'Photo', 'optimum' ), $i + 1 ) ) . '</a> ';
					}
					echo '</p>';
				}
			},
			'oc_request',
			'normal',
			'high'
		);
	}
);
