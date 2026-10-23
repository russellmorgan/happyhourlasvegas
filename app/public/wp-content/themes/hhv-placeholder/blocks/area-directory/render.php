<?php
/**
 * "<Label> happy hours by area" for vibe/menu landings and tag archives: one exclusive <details> per top-level area
 * (accordion on mobile; tabs + panel on desktop via CSS, assets/js/landing.js keeps one open), listing the area's
 * neighbourhoods/casinos that have matching venues, plus "View all <area>" which filters the grid above.
 */
$l = hhv_landing_context( $block );
if ( ! $l || ! in_array( $l['type'], array( 'vibe', 'menu' ), true ) ) {
	return;
}
$areas = hhv_landing_area_options( $l );
if ( count( $areas ) < 2 ) {
	return;
}
$facets = hhv_landing_facets( $l );
$title  = preg_match( '/happy hour/i', $l['label'] ) ? $l['label'] : $l['label'] . ' happy hours';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-areadir' ) ); ?>>
	<div class="hhv-wrap">
		<h2 class="hhv-display hhv-h2"><?php echo esc_html( $title ); ?> by area</h2>
		<div class="hhv-areadir__list">
			<?php foreach ( $areas as $i => list( $area, $n ) ) : ?>
				<?php
				$kids = array_filter(
					get_terms( array( 'taxonomy' => 'gd_placecategory', 'parent' => $area->term_id, 'hide_empty' => true ) ),
					fn( $t ) => ! empty( $facets['areas'][ $t->term_id ] )
				);
				?>
				<details class="hhv-areadir__item" name="hhv-areadir"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary><span class="hhv-areadir__name"><?php echo esc_html( $area->name ); ?></span><span class="hhv-areadir__count"><?php echo esc_html( $n ); ?></span></summary>
					<div class="hhv-areadir__panel">
						<p class="hhv-areadir__title"><?php echo esc_html( $area->name ); ?></p>
						<?php if ( $kids ) : ?>
							<ul class="hhv-areadir__links">
								<?php foreach ( $kids as $t ) : ?>
									<li><a href="<?php echo esc_url( hhv_area_url( $t ) ); ?>"><span><?php echo esc_html( $t->name ); ?></span><span aria-hidden="true">→</span></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<a class="hhv-areadir__all" href="<?php echo esc_url( hhv_landing_url( array( 'area' => $area->slug ) ) ); ?>">View all <?php echo esc_html( $area->name ); ?> →</a>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
