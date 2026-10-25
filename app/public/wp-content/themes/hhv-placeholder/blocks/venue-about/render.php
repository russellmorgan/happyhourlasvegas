<?php
/**
 * Venue about: post content (collapses behind "Read more" on mobile), menu card (structured items or free text) and map.
 */
$v = hhv_block_venue( $block );
if ( ! $v ) {
	echo hhv_block_placeholder( 'Venue about, menu and map' );
	return;
}
$content = hhv_venue_content( $v['id'] );
$groups  = hhv_menu_groups( $v );
$menu    = $groups ? '' : ( $v['menu_text'] ? hhv_menu_text_html( $v['menu_text'] ) : '' );
$static  = hhv_static_map_url( $v );
$map     = $static ? '' : hhv_osm_embed_url( $v );
$text_id = wp_unique_id( 'hhv-about-' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-vabout' ) ); ?>>
	<div class="hhv-wrap hhv-vabout__grid<?php echo $content ? '' : ' is-aside-only'; ?>">
		<?php if ( $content ) : ?>
			<div class="hhv-vabout__main">
				<h2 class="hhv-display hhv-h2 hhv-vabout__title">About this happy hour</h2>
				<div class="hhv-vabout__text" id="<?php echo esc_attr( $text_id ); ?>" data-hhv-more><?php echo $content; // the_content output. ?></div>
				<button type="button" class="hhv-vabout__more hhv-label" aria-controls="<?php echo esc_attr( $text_id ); ?>" aria-expanded="false" hidden>Read more</button>
			</div>
		<?php endif; ?>
		<div class="hhv-vabout__aside">
			<?php if ( $groups || $menu ) : ?>
				<div class="hhv-menu">
					<?php if ( $groups ) : ?>
						<?php foreach ( $groups as $label => $items ) : ?>
							<h3 class="hhv-menu__head"><?php echo esc_html( $label ); ?></h3>
							<ul class="hhv-menu__list">
								<?php foreach ( $items as $it ) { echo hhv_menu_row( $it['name'], $it['price'] ); } ?>
							</ul>
						<?php endforeach; ?>
					<?php else : ?>
						<?php echo $menu; // Escaped in hhv_menu_text_html(). ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( $map || $v['street'] ) : ?>
				<div class="hhv-vmap">
					<?php if ( $static ) : ?>
						<a class="hhv-vmap__img" href="<?php echo esc_url( hhv_maps_url( $v ) ); ?>" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
							<img src="<?php echo esc_url( $static ); ?>" alt="" width="640" height="260" loading="lazy" decoding="async">
						</a>
					<?php elseif ( $map ) : ?>
						<iframe src="<?php echo esc_url( $map ); ?>" title="<?php echo esc_attr( 'Map: ' . $v['name'] ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
					<?php endif; ?>
					<a class="hhv-vmap__link" href="<?php echo esc_url( hhv_maps_url( $v ) ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html( $v['street'] ); ?> <span class="hhv-label">Directions</span>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
