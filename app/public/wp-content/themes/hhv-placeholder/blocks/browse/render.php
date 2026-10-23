<?php
/**
 * "Browse by area | menu | vibe". Attribute `type`: location | menu | vibe.
 * Location: the first landing is the big image tile and the next nine are small tiles. Menu / vibe: nine small tiles
 * plus a big "View all" image tile. ponytail: no page lists every menu / vibe yet, so "View all" goes to the first
 * landing; point it elsewhere with the `hhv_browse_all_url` filter.
 */
$type  = in_array( $attributes['type'], array( 'location', 'menu', 'vibe' ), true ) ? $attributes['type'] : 'location';
$items = hhv_browse_items( $type );
if ( ! $items ) {
	return;
}
$names = array( 'location' => 'area', 'menu' => 'menu', 'vibe' => 'vibe' );
$title = $attributes['title'] ?: 'Browse by ' . $names[ $type ];
$big   = null; // array( label, url, count, image )
if ( 'location' === $type ) {
	list( $first, $n ) = array_shift( $items );
	$big = array( $first['label'], $first['url'], hhv_count_label( $n ), hhv_landing_image_url( $first ) );
} else {
	$image = '';
	foreach ( array_slice( $items, 0, 9 ) as list( $l ) ) {
		if ( $image = hhv_landing_image_url( $l ) ) {
			break;
		}
	}
	$all = apply_filters( 'hhv_browse_all_url', $items[0][0]['url'], $type );
	$big = array( 'menu' === $type ? 'View all menus' : 'View all the vibes', $all, '', $image );
}
$band = 'menu' === $type ? '' : ' hhv-band--deep';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-browse hhv-browse--' . $type . $band, 'id' => 'browse-' . $type ) ); ?>>
	<div class="hhv-wrap">
		<h2 class="hhv-display hhv-h2"><?php echo esc_html( $title ); ?></h2>
		<div class="hhv-browse__grid">
			<a class="hhv-browse__big<?php echo 'location' === $type ? '' : ' hhv-browse__big--all'; ?>" href="<?php echo esc_url( $big[1] ); ?>">
				<?php echo hhv_img( $big[3] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="hhv-img__label"><?php echo esc_html( $big[0] ); ?><?php echo $big[2] ? '<span class="hhv-tile__count">' . esc_html( $big[2] ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</a>
			<?php foreach ( array_slice( $items, 0, 9 ) as list( $l, $n ) ) : ?>
				<a class="hhv-tile<?php echo 'menu' === $type ? ' hhv-tile--gold' : ''; ?>" href="<?php echo esc_url( $l['url'] ); ?>">
					<span class="hhv-tile__name"><?php echo esc_html( $l['label'] ); ?></span>
					<span class="hhv-tile__count"><?php echo esc_html( hhv_count_label( $n ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
