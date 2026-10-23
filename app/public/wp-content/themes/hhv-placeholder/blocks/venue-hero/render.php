<?php
/**
 * Venue hero: breadcrumbs, live status, name, subtitle, slogan, meta row, photo filmstrip (carousel on mobile) + <dialog> gallery.
 */
$v = hhv_block_venue( $block );
if ( ! $v ) {
	echo hhv_block_placeholder( 'Venue hero' );
	return;
}
$status   = hhv_venue_status( $v );
$photos   = hhv_venue_photos( $v );
$total    = count( $photos );
$shown    = $total > 4 ? 3 : $total; // Desktop filmstrip: up to 4 photos, or 3 + an "all photos" tile.
$features = array_diff( $v['features'], array( 'No Happy Hour' ) );
$subtitle = implode( ' · ', array_filter( array( implode( ' + ', array_slice( $features, 0, 2 ) ), $v['area']->name ?? '' ) ) );
$link     = fn( $url, $text, $ext = false ) => sprintf( '<a href="%s"%s>%s</a>', esc_url( $url ), $ext ? ' target="_blank" rel="noopener"' : '', esc_html( $text ) );
$meta     = array_filter( array(
	'Menu'    => esc_html( preg_replace( '/\s*,\s*/', ', ', $v['cuisine'] ) ),
	'Vibe'    => esc_html( $v['vibe'] ),
	'Price'   => esc_html( $v['price_range'] ),
	'Phone'   => $v['phone'] ? $link( 'tel:' . preg_replace( '/[^\d+]/', '', $v['phone'] ), hhv_phone_label( $v['phone'] ) ) : '',
	'Website' => $v['website'] ? $link( $v['website'], hhv_url_host( $v['website'] ), true ) : '',
	'Address' => $v['street'] ? $link( hhv_maps_url( $v ), $v['street'], true ) : '',
) );
$back     = $v['area'] ?: $v['hood'];
$dialog   = wp_unique_id( 'hhv-photos-' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-vhero' ) ); ?>>
	<div class="hhv-wrap hhv-vhero__bar">
		<nav class="hhv-crumbs hhv-vhero__crumbs" aria-label="Breadcrumb">
			<?php foreach ( array_filter( array( $v['area'], $v['hood'] ) ) as $t ) : ?>
				<?php $url = hhv_area_url( $t ); // inc/landing.php: the area's landing page, else its GD archive. ?>
				<a href="<?php echo esc_url( is_wp_error( $url ) ? '' : $url ); ?>"<?php echo $t === $back ? ' class="is-back"' : ''; ?>><?php echo esc_html( $t->name ); ?></a><span class="sep" aria-hidden="true">›</span>
			<?php endforeach; ?>
			<span aria-current="page"><?php echo esc_html( $v['name'] ); ?></span>
		</nav>
		<button type="button" class="hhv-vhero__share hhv-label" data-hhv-share hidden>Share</button>
	</div>

	<header class="hhv-wrap hhv-vhero__head">
		<?php if ( $status ) : ?>
			<span class="hhv-vhero__status hhv-label"><span aria-hidden="true">●</span> <?php echo esc_html( hhv_status_label( $status ) ); ?></span>
		<?php endif; ?>
		<h1 class="hhv-display hhv-hero hhv-vhero__name"><?php echo esc_html( $v['name'] ); ?></h1>
		<?php if ( $subtitle ) : ?><p class="hhv-vhero__sub"><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
		<?php if ( $v['slogan'] ) : ?><p class="hhv-vhero__intro"><?php echo esc_html( $v['slogan'] ); ?></p><?php endif; ?>
		<?php if ( $meta ) : ?>
			<dl class="hhv-vhero__meta">
				<?php foreach ( $meta as $k => $html ) : ?>
					<div><dt class="hhv-label"><?php echo esc_html( $k ); ?></dt><dd><?php echo $html; // Escaped above. ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</header>

	<?php if ( $photos ) : ?>
		<div class="hhv-gallery" data-hhv-gallery>
			<ul class="hhv-gallery__strip">
				<?php foreach ( $photos as $i => $p ) : ?>
					<li class="hhv-gallery__item<?php echo $i >= $shown ? ' is-extra' : ''; ?>">
						<button type="button" class="hhv-img" commandfor="<?php echo esc_attr( $dialog ); ?>" command="show-modal" data-photo="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( 'View photo %d of %d', $i + 1, $total ) ); ?>">
							<?php echo hhv_photo_img( $p, '(max-width: 600px) 86vw, 300px', $i > 0 ); ?>
							<?php if ( $total > 1 ) : ?><span class="hhv-gallery__count"><?php echo (int) ( $i + 1 ) . ' / ' . (int) $total; ?></span><?php endif; ?>
						</button>
					</li>
				<?php endforeach; ?>
				<?php if ( $shown < $total ) : ?>
					<li class="hhv-gallery__more">
						<button type="button" commandfor="<?php echo esc_attr( $dialog ); ?>" command="show-modal" data-photo="<?php echo (int) $shown; ?>">
							<span class="hhv-gallery__more-n">+<?php echo (int) ( $total - $shown ); ?></span>
							<span class="hhv-label">All photos</span>
						</button>
					</li>
				<?php endif; ?>
			</ul>
			<dialog id="<?php echo esc_attr( $dialog ); ?>" class="hhv-gallery__dialog" aria-label="<?php echo esc_attr( 'Photos of ' . $v['name'] ); ?>" closedby="any">
				<form method="dialog" class="hhv-gallery__close"><button class="hhv-btn" autofocus>Close</button></form>
				<ul>
					<?php foreach ( $photos as $i => $p ) : ?>
						<li><?php echo hhv_photo_img( array( 'srcset' => '' ) + $p, '100vw' ); ?></li>
					<?php endforeach; ?>
				</ul>
			</dialog>
		</div>
	<?php endif; ?>
</section>
