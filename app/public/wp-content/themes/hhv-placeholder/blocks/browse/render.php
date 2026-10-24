<?php
/**
 * "Browse by area | menu | vibe". Attribute `type`: location | menu | vibe.
 * Location: the first landing is the big image tile and the next nine are small tiles. Menu / vibe: nine small tiles
 * plus a big "View all" image tile linking the index page (slug happy-hour-vibes / happy-hour-menus, made by
 * tools/home-setup.php). Attribute `all`: every landing as small tiles, no big tile or heading (used on those index pages).
 */
$type  = in_array( $attributes['type'], array( 'location', 'menu', 'vibe' ), true ) ? $attributes['type'] : 'location';
$items = hhv_browse_items( $type );
if ( ! $items ) {
	return;
}
$names = array( 'location' => 'area', 'menu' => 'menu', 'vibe' => 'vibe' );
$title = $attributes['title'] ?: 'Browse by ' . $names[ $type ];
$all   = ! empty( $attributes['all'] );
$shown = $all ? $items : array_slice( $items, 0, 9 );
$big   = null; // array( label, url, count, image )
if ( $all ) {
	// No big tile.
} elseif ( 'location' === $type ) {
	list( $first, $n ) = array_shift( $items );
	$big = array( $first['label'], $first['url'], hhv_count_label( $n ), hhv_landing_image_url( $first ) );
} else {
	$image = '';
	foreach ( array_slice( $items, 0, 9 ) as list( $l ) ) {
		if ( $image = hhv_landing_image_url( $l ) ) {
			break;
		}
	}
	$index = get_page_by_path( 'happy-hour-' . $type . 's' );
	$big   = array( 'menu' === $type ? 'View all menus' : 'View all the vibes', $index ? get_permalink( $index ) : $items[0][0]['url'], '', $image );
}
$band = 'menu' === $type || $all ? '' : ' hhv-band--deep';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-browse hhv-browse--' . $type . $band, 'id' => 'browse-' . $type ) ); ?>>
	<div class="hhv-wrap">
		<?php if ( ! $all ) : // The index page's title band names it. ?>
		<h2 class="hhv-display hhv-h2"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<div class="hhv-browse__grid">
			<?php if ( $big ) : ?>
			<a class="hhv-browse__big<?php echo 'location' === $type ? '' : ' hhv-browse__big--all'; ?>" href="<?php echo esc_url( $big[1] ); ?>">
				<?php echo hhv_img( $big[3] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="hhv-img__label"><?php echo esc_html( $big[0] ); ?><?php echo $big[2] ? '<span class="hhv-tile__count">' . esc_html( $big[2] ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</a>
			<?php endif; ?>
			<?php foreach ( $shown as list( $l, $n ) ) : ?>
				<a class="hhv-tile<?php echo 'menu' === $type ? ' hhv-tile--gold' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>">
					<span class="hhv-tile__name"><?php echo esc_html( $l['label'] ); ?></span>
					<span class="hhv-tile__count"><?php echo esc_html( hhv_count_label( $n ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
