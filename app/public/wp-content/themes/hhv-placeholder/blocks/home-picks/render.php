<?php
/** "Our recommendations": hero heading + the three picked venues as large cards. */
$ids = hhv_home_picks( hhv_home_id( $block ) );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-home-picks' ) ); ?>>
	<div class="hhv-wrap">
		<h1 class="hhv-display hhv-hero"><?php echo esc_html( $attributes['title'] ); ?></h1>
		<?php if ( $attributes['dek'] ) : ?><p class="hhv-home-picks__dek"><?php echo esc_html( $attributes['dek'] ); ?></p><?php endif; ?>
		<?php if ( $ids ) : ?>
			<div class="hhv-cards hhv-home-picks__cards">
				<?php
				foreach ( $ids as $id ) {
					echo hhv_venue_card( $id, array( 'size' => 'lg', 'take' => true, 'deal' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
