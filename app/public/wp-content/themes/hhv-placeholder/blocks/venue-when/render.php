<?php
/**
 * Venue hours: one timeline bar per weekday from noon to 2am (solid = happy hour, dashed = reverse), today highlighted, "now" marker.
 */
$v = hhv_block_venue( $block );
if ( ! $v ) {
	echo hhv_block_placeholder( 'Venue hours timeline' );
	return;
}
$rows    = hhv_week_rows( $v );
$any     = array_filter( array_column( $rows, 'ranges' ) );
$kinds   = array_column( array_merge( ...array_column( $rows, 'bars' ) ), 'reverse' );
$regular = in_array( false, $kinds, true );
$reverse = in_array( true, $kinds, true );
$now     = in_array( true, array_map( fn( $r ) => null !== $r['now'], $rows ), true );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-band--deep hhv-vwhen' ) ); ?>>
	<div class="hhv-wrap">
		<div class="hhv-vwhen__head">
			<h2 class="hhv-display hhv-h2">When</h2>
			<?php if ( $any ) : ?><span class="hhv-eyebrow">Times shown noon to 2am</span><?php endif; ?>
		</div>
		<?php if ( ! $any ) : ?>
			<p class="hhv-muted">We don't have this venue's happy hour times yet.</p>
		<?php else : ?>
			<div class="hhv-week">
				<span></span>
				<div class="hhv-week__ticks" aria-hidden="true"><?php foreach ( array( '12p', '2p', '4p', '6p', '8p', '10p', '12a', '2a' ) as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?></div>
				<span></span>
				<?php foreach ( $rows as $r ) : ?>
					<span class="hhv-week__day hhv-label<?php echo $r['today'] ? ' is-today' : ''; ?>"><?php echo esc_html( $r['day'] ); ?><?php echo $r['today'] ? '<span class="screen-reader-text"> (today)</span>' : ''; ?></span>
					<div class="hhv-week__track" aria-hidden="true">
						<?php foreach ( $r['bars'] as $b ) : ?>
							<span class="hhv-week__bar<?php echo $b['reverse'] ? ' is-reverse' : ''; ?>" style="left:<?php echo esc_attr( $b['left'] ); ?>%;width:<?php echo esc_attr( $b['width'] ); ?>%"></span>
						<?php endforeach; ?>
						<?php if ( null !== $r['now'] ) : ?><span class="hhv-week__now" style="left:<?php echo esc_attr( $r['now'] ); ?>%"></span><?php endif; ?>
					</div>
					<span class="hhv-week__times<?php echo $r['today'] ? ' is-today' : ''; ?>"><?php echo $r['ranges'] ? implode( ', ', array_map( fn( $s ) => '<span>' . esc_html( $s ) . '</span>', $r['ranges'] ) ) : 'None'; ?></span>
				<?php endforeach; ?>
			</div>
			<ul class="hhv-week__legend">
				<?php if ( $regular ) : ?><li><span class="hhv-week__key"></span>Happy hour</li><?php endif; ?>
				<?php if ( $reverse ) : ?><li><span class="hhv-week__key is-reverse"></span>Reverse happy hour</li><?php endif; ?>
				<?php if ( $now ) : ?><li><span class="hhv-week__key is-now"></span>Now</li><?php endif; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
