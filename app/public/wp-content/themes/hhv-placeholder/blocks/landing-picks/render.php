<?php
/** "Our picks" on a landing: the venues featured on this page, as large cards (homepage recommendations styling). */
$l   = hhv_landing_context( $block );
$ids = $l ? hhv_landing_picks( $l ) : array();
if ( ! $ids ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-home-picks hhv-lpicks' ) ); ?>>
	<div class="hhv-wrap">
		<h2 class="hhv-display hhv-h2"><?php echo esc_html( sprintf( '%s: %s', $attributes['title'], $l['label'] ) ); ?></h2>
		<div class="hhv-cards hhv-home-picks__cards">
			<?php
			foreach ( $ids as $id ) {
				echo hhv_venue_card( $id, array( 'size' => 'lg', 'take' => true, 'deal' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
	</div>
</section>
