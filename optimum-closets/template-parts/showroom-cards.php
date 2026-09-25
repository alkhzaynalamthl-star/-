<?php
/**
 * Showroom cards (confirmed addresses; hours only once confirmed).
 *
 * @package Optimum
 */

defined( 'ABSPATH' ) || exit;

$optimum_ph    = optimum_phone();
$optimum_hours = optimum_mod_i18n( 'hours' );
foreach ( optimum_showrooms() as $optimum_s ) :
	?>
	<article class="show-card reveal">
		<p class="eyebrow"><?php esc_html_e( 'Showroom', 'optimum' ); ?></p>
		<h3><?php echo esc_html( $optimum_s['city'] ); ?></h3>
		<p class="show-addr"><?php echo optimum_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $optimum_s['address'] ); ?></span></p>
		<p class="show-hours"><?php echo optimum_icon( 'clock', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo $optimum_hours ? esc_html( $optimum_hours ) : esc_html__( 'Please call to confirm opening hours before your visit.', 'optimum' ); ?></span></p>
		<div class="show-actions">
			<?php if ( $optimum_s['map'] ) : ?>
				<a class="btn btn-outline" href="<?php echo esc_url( $optimum_s['map'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Directions', 'optimum' ); ?></a>
			<?php endif; ?>
			<a class="btn btn-primary" href="<?php echo esc_url( $optimum_ph['href'] ); ?>"><?php echo optimum_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><bdi dir="ltr"><?php echo esc_html( $optimum_ph['label'] ); ?></bdi></a>
		</div>
	</article>
	<?php
endforeach;
