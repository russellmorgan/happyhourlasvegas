<?php
/**
 * Homepage (templates/front-page.html): blocks hhv/home-picks, hhv/browse, hhv/home-video. Data: tools/home-setup.php.
 */

/** The front page's ID (the block's post context, else the page set in Settings > Reading). */
function hhv_home_id( $block = null ) {
	return (int) ( $block->context['postId'] ?? 0 ) ?: (int) get_option( 'page_on_front' );
}

/** Published venue IDs picked for "Our recommendations" (ACF home_picks), in order. */
function hhv_home_picks( $page_id ) {
	$ids = array_map( 'intval', (array) get_post_meta( $page_id, 'home_picks', true ) );
	return array_values( array_filter( $ids, fn( $id ) => 'publish' === get_post_status( $id ) ) );
}

/** YouTube video ID from a watch / youtu.be / embed URL, else ''. */
function hhv_youtube_id( $url ) {
	return preg_match( '#(?:[?&]v=|youtu\.be/|/embed/)([\w-]{11})#', (string) $url, $m ) ? $m[1] : '';
}

/** Image URL for a landing's big tile: its tile/featured image, else the photo of its featured or first venue. */
function hhv_landing_image_url( $l ) {
	if ( $l['image'] && ( $url = wp_get_attachment_image_url( $l['image'], 'large' ) ) ) {
		return $url;
	}
	$venue = hhv_landing_featured( $l + array( 'mode' => 'page' ) );
	$v     = $venue ? hhv_venue( $venue ) : null;
	return $v['image'] ?? '';
}

/**
 * Landings for a "Browse by ..." section: array of array( landing, count ) in menu order. Hides tiles with no venues and
 * tiles that count the whole site (no real filter). Locations are only the main areas (top-level gd_placecategory terms).
 */
function hhv_browse_items( $type ) {
	$site = (int) wp_count_posts( 'gd_place' )->publish;
	$out  = array();
	foreach ( hhv_main_landings( $type ) as $l ) {
		if ( 'location' === $type ) {
			$top = array_filter( $l['areas'], fn( $id ) => ( $t = get_term( $id, 'gd_placecategory' ) ) instanceof WP_Term && ! $t->parent );
			if ( ! $l['areas'] || count( $top ) !== count( $l['areas'] ) ) {
				continue;
			}
		}
		$n = hhv_landing_count( $l );
		if ( $n && $n < $site ) {
			$out[] = array( $l, $n );
		}
	}
	return $out;
}
