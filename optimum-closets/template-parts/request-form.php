<?php
/**
 * Request form: made-to-measure design (kind=design) or contact message (kind=contact).
 *
 * Works without JavaScript; the server validates everything and reports back
 * through a one-time token (see inc/requests.php).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

$optimum_kind    = isset( $args['kind'] ) && 'contact' === $args['kind'] ? 'contact' : 'design';
$optimum_result  = optimum_request_result();
$optimum_errors  = isset( $optimum_result['errors'] ) ? $optimum_result['errors'] : array();
$optimum_old     = isset( $optimum_result['old'] ) ? $optimum_result['old'] : array();
$optimum_choices = optimum_request_choices();
$optimum_back    = remove_query_arg( 'oc_result', optimum_lang_url( optimum_lang() ) );

$optimum_val = function ( $key, $default = '' ) use ( $optimum_old ) {
	return isset( $optimum_old[ $key ] ) && '' !== $optimum_old[ $key ] && 0 !== $optimum_old[ $key ] ? $optimum_old[ $key ] : $default;
};
$optimum_err = function ( $key ) use ( $optimum_errors ) {
	return isset( $optimum_errors[ $key ] ) ? '<p class="field-error" id="' . esc_attr( $key ) . '-err">' . esc_html( $optimum_errors[ $key ] ) . '</p>' : '';
};
$optimum_aria = function ( $key ) use ( $optimum_errors ) {
	return isset( $optimum_errors[ $key ] ) ? ' aria-invalid="true" aria-describedby="' . esc_attr( $key ) . '-err"' : '';
};

if ( ! empty( $optimum_result['ok'] ) ) :
	?>
	<div class="request-done" id="request-form" tabindex="-1">
		<?php echo optimum_icon( 'check', 32 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h2><?php echo 'contact' === $optimum_kind ? esc_html__( 'Your message was saved', 'optimum' ) : esc_html__( 'Your request was saved', 'optimum' ); ?></h2>
		<p>
			<?php
			/* translators: %s: reference number */
			printf( esc_html__( 'Reference: %s', 'optimum' ), '<strong><bdi dir="ltr">' . esc_html( $optimum_result['ref'] ) . '</bdi></strong>' );
			?>
		</p>
		<?php if ( ! empty( $optimum_result['preview'] ) ) : ?>
			<p class="notice-preview"><?php esc_html_e( 'Preview mode: the request was stored on this site only. It was not sent to the Optimum Closets team.', 'optimum' ); ?></p>
		<?php elseif ( ! empty( $optimum_result['delivered'] ) ) : ?>
			<p><?php esc_html_e( 'It has been delivered to our team. We will contact you to arrange the free measurement visit.', 'optimum' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'It is in our system, but the automatic notification to the team could not be confirmed. If you do not hear from us within one working day, please call us.', 'optimum' ); ?></p>
		<?php endif; ?>
		<?php $optimum_ph = optimum_phone(); ?>
		<p><a class="btn btn-outline" href="<?php echo esc_url( $optimum_ph['href'] ); ?>"><?php echo optimum_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><bdi dir="ltr"><?php echo esc_html( $optimum_ph['label'] ); ?></bdi></a></p>
	</div>
	<?php
	return;
endif;
?>
<form class="request-form" id="request-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" novalidate>
	<input type="hidden" name="action" value="optimum_request">
	<input type="hidden" name="oc_kind" value="<?php echo esc_attr( $optimum_kind ); ?>">
	<input type="hidden" name="oc_lang" value="<?php echo esc_attr( optimum_lang() ); ?>">
	<input type="hidden" name="oc_back" value="<?php echo esc_url( $optimum_back ); ?>">
	<input type="hidden" name="oc_t" value="<?php echo esc_attr( time() ); ?>">
	<?php wp_nonce_field( 'optimum_request_' . $optimum_kind, 'oc_nonce' ); ?>
	<p class="hp" aria-hidden="true"><label><?php esc_html_e( 'Leave empty', 'optimum' ); ?><input type="text" name="oc_website" tabindex="-1" autocomplete="off"></label></p>

	<?php if ( isset( $optimum_errors['form'] ) ) : ?>
		<div class="form-alert" role="alert"><?php echo esc_html( $optimum_errors['form'] ); ?></div>
	<?php elseif ( $optimum_errors ) : ?>
		<div class="form-alert" role="alert"><?php esc_html_e( 'Please check the highlighted fields.', 'optimum' ); ?></div>
	<?php endif; ?>

	<?php if ( 'design' === $optimum_kind ) : ?>
		<fieldset class="form-section">
			<legend><span class="n">1</span><?php esc_html_e( 'Your space', 'optimum' ); ?></legend>
			<div class="field-grid cols-3">
				<?php
				foreach ( array(
					'width'  => array( __( 'Wall width', 'optimum' ), '320', true ),
					'height' => array( __( 'Ceiling height', 'optimum' ), '280', true ),
					'depth'  => array( __( 'Available depth (optional)', 'optimum' ), '60', false ),
				) as $optimum_k => $optimum_f ) :
					?>
					<div class="field">
						<label for="oc_<?php echo esc_attr( $optimum_k ); ?>"><?php echo esc_html( $optimum_f[0] ); ?><?php echo $optimum_f[2] ? ' <span class="req" aria-hidden="true">*</span>' : ''; ?></label>
						<div class="field-unit">
							<input type="text" inputmode="numeric" pattern="[0-9٠-٩]*" id="oc_<?php echo esc_attr( $optimum_k ); ?>" name="oc_<?php echo esc_attr( $optimum_k ); ?>" value="<?php echo esc_attr( $optimum_val( $optimum_k ) ); ?>" placeholder="<?php echo esc_attr( $optimum_f[1] ); ?>" <?php echo $optimum_f[2] ? 'required' : ''; ?><?php echo $optimum_aria( 'oc_' . $optimum_k ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> dir="ltr">
							<span><?php esc_html_e( 'cm', 'optimum' ); ?></span>
						</div>
						<?php echo $optimum_err( 'oc_' . $optimum_k ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="field-help"><?php esc_html_e( 'Approximate measurements are fine: we measure precisely at the free visit.', 'optimum' ); ?></p>

			<div class="field-grid cols-2">
				<div class="field">
					<span class="label" id="lbl-space"><?php esc_html_e( 'Type of space', 'optimum' ); ?></span>
					<div class="chips-choice" role="radiogroup" aria-labelledby="lbl-space">
						<?php foreach ( $optimum_choices['space'] as $optimum_k => $optimum_l ) : ?>
							<label class="choice"><input type="radio" name="oc_space" value="<?php echo esc_attr( $optimum_k ); ?>" <?php checked( $optimum_val( 'space', 'bedroom' ), $optimum_k ); ?>><span><?php echo esc_html( $optimum_l ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="field">
					<span class="label" id="lbl-door"><?php esc_html_e( 'Preferred doors', 'optimum' ); ?></span>
					<div class="chips-choice" role="radiogroup" aria-labelledby="lbl-door">
						<?php foreach ( $optimum_choices['door'] as $optimum_k => $optimum_l ) : ?>
							<label class="choice"><input type="radio" name="oc_door" value="<?php echo esc_attr( $optimum_k ); ?>" <?php checked( $optimum_val( 'door', 'unsure' ), $optimum_k ); ?>><span><?php echo esc_html( $optimum_l ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset class="form-section">
			<legend><span class="n">2</span><?php esc_html_e( 'Photos of the space', 'optimum' ); ?></legend>
			<label class="dropzone" for="oc_photos">
				<?php echo optimum_icon( 'camera', 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<strong><?php esc_html_e( 'Add photos', 'optimum' ); ?></strong>
				<span>
					<?php
					/* translators: 1: max photos, 2: max size in MB */
					echo esc_html( sprintf( __( 'Up to %1$s photos, %2$s MB each (JPG, PNG, WebP, HEIC). A photo of the whole wall and one of the corners helps most.', 'optimum' ), optimum_num( OPTIMUM_MAX_PHOTOS ), optimum_num( OPTIMUM_MAX_PHOTO_SIZE / 1048576 ) ) );
					?>
				</span>
				<input type="file" id="oc_photos" name="oc_photos[]" accept="image/jpeg,image/png,image/webp,image/heic,.heic" multiple data-previews="oc-previews"<?php echo $optimum_aria( 'oc_photos' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			</label>
			<ul class="previews" id="oc-previews" aria-live="polite"></ul>
			<?php echo $optimum_err( 'oc_photos' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</fieldset>
	<?php endif; ?>

	<fieldset class="form-section">
		<legend><span class="n"><?php echo 'design' === $optimum_kind ? '3' : '1'; ?></span><?php esc_html_e( 'Your details', 'optimum' ); ?></legend>
		<div class="field-grid cols-2">
			<div class="field">
				<label for="oc_name"><?php esc_html_e( 'Full name', 'optimum' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input type="text" id="oc_name" name="oc_name" autocomplete="name" required value="<?php echo esc_attr( $optimum_val( 'name' ) ); ?>"<?php echo $optimum_aria( 'oc_name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo $optimum_err( 'oc_name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="field">
				<label for="oc_phone"><?php esc_html_e( 'Mobile number', 'optimum' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input type="tel" id="oc_phone" name="oc_phone" autocomplete="tel" inputmode="tel" dir="ltr" placeholder="05XXXXXXXX" required value="<?php echo esc_attr( $optimum_val( 'phone' ) ); ?>"<?php echo $optimum_aria( 'oc_phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo $optimum_err( 'oc_phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="field">
				<label for="oc_email"><?php esc_html_e( 'E-mail (optional)', 'optimum' ); ?></label>
				<input type="email" id="oc_email" name="oc_email" autocomplete="email" dir="ltr" value="<?php echo esc_attr( $optimum_val( 'email' ) ); ?>"<?php echo $optimum_aria( 'oc_email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo $optimum_err( 'oc_email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="field">
				<label for="oc_city"><?php esc_html_e( 'City', 'optimum' ); ?><?php echo 'design' === $optimum_kind ? ' <span class="req" aria-hidden="true">*</span>' : ''; ?></label>
				<select id="oc_city" name="oc_city"<?php echo 'design' === $optimum_kind ? ' required' : ''; ?><?php echo $optimum_aria( 'oc_city' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<option value=""><?php esc_html_e( 'Choose your city', 'optimum' ); ?></option>
					<?php foreach ( optimum_request_cities() as $optimum_k => $optimum_l ) : ?>
						<option value="<?php echo esc_attr( $optimum_k ); ?>" <?php selected( $optimum_val( 'city' ), $optimum_k ); ?>><?php echo esc_html( $optimum_l ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo $optimum_err( 'oc_city' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php if ( 'design' === $optimum_kind ) : ?>
			<div class="field">
				<span class="label" id="lbl-contact"><?php esc_html_e( 'How should we contact you?', 'optimum' ); ?></span>
				<div class="chips-choice" role="radiogroup" aria-labelledby="lbl-contact">
					<?php foreach ( $optimum_choices['contact'] as $optimum_k => $optimum_l ) : ?>
						<label class="choice"><input type="radio" name="oc_contact" value="<?php echo esc_attr( $optimum_k ); ?>" <?php checked( $optimum_val( 'contact', 'call' ), $optimum_k ); ?>><span><?php echo esc_html( $optimum_l ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="field">
			<label for="oc_message"><?php echo 'contact' === $optimum_kind ? esc_html__( 'Your message', 'optimum' ) . ' <span class="req" aria-hidden="true">*</span>' : esc_html__( 'Anything else we should know? (optional)', 'optimum' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			<textarea id="oc_message" name="oc_message" rows="4" placeholder="<?php echo esc_attr( 'design' === $optimum_kind ? __( 'e.g. long hanging for abayas, a place for perfumes, a mirror inside the door…', 'optimum' ) : '' ); ?>"<?php echo $optimum_aria( 'oc_message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_textarea( $optimum_val( 'message' ) ); ?></textarea>
			<?php echo $optimum_err( 'oc_message' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php if ( 'design' === $optimum_kind ) : ?>
			<label class="check consent">
				<input type="checkbox" name="oc_consent" value="1" required<?php echo $optimum_aria( 'oc_consent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span><?php esc_html_e( 'I agree that Optimum Closets may contact me about this request and keep my photos to prepare the design.', 'optimum' ); ?></span>
			</label>
			<?php echo $optimum_err( 'oc_consent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</fieldset>

	<div class="form-submit">
		<button class="btn btn-primary btn-lg" type="submit"><?php echo 'contact' === $optimum_kind ? esc_html__( 'Send message', 'optimum' ) : esc_html__( 'Send my request', 'optimum' ); ?></button>
		<?php if ( optimum_preview_mode() ) : ?>
			<p class="form-note"><?php esc_html_e( 'Preview mode: submissions are stored on this site and not sent to the team.', 'optimum' ); ?></p>
		<?php endif; ?>
	</div>
</form>
