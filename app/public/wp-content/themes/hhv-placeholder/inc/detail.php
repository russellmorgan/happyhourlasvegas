<?php
/**
 * Venue detail page (templates/single-gd_place.html) helpers for the hhv/venue-* blocks.
 */

const HHV_WEEK_FROM = 720; // Timeline starts at noon...
const HHV_WEEK_SPAN = 840; // ...and runs 14 hours, to 2am.

/** Venue for a dynamic block: postId context, else the current post. */
function hhv_block_venue( $block ) {
	return hhv_venue( $block->context['postId'] ?? get_the_ID() );
}

/** Editor preview text when a venue block has no venue (e.g. editing the template itself). */
function hhv_block_placeholder( $label ) {
	return wp_is_serving_rest_request() ? '<p class="hhv-wrap hhv-muted" style="padding-block:20px">' . esc_html( $label ) . ' (filled in on each venue page)</p>' : '';
}

/** GeoDirectory listing photos (plain upload files): array of src, srcset, alt. */
function hhv_venue_photos( $v ) {
	$out  = array();
	$base = wp_upload_dir()['baseurl'];
	$imgs = function_exists( 'geodir_get_images' ) ? geodir_get_images( $v['id'], '', false, '', array( 'post_images' ), array() ) : array();
	foreach ( $imgs ?: array() as $img ) {
		if ( empty( $img->file ) ) {
			continue;
		}
		$meta   = maybe_unserialize( $img->metadata );
		$med    = $meta['sizes']['medium'] ?? null;
		$srcset = $med && ! empty( $meta['width'] ) ? sprintf( '%s %dw, %s %dw', $base . dirname( $img->file ) . '/' . $med['file'], $med['width'], $base . $img->file, $meta['width'] ) : '';
		$out[]  = array( 'src' => $base . $img->file, 'srcset' => $srcset, 'alt' => trim( (string) $img->title ) ?: $v['name'] );
	}
	if ( ! $out && $v['image'] ) {
		$out[] = array( 'src' => $v['image'], 'srcset' => '', 'alt' => $v['name'] );
	}
	return $out;
}

/** Photo <img> with medium/full srcset. */
function hhv_photo_img( $p, $sizes, $lazy = true ) {
	return sprintf(
		'<img src="%s"%s sizes="%s" alt="%s"%s decoding="async">',
		esc_url( $p['src'] ),
		$p['srcset'] ? ' srcset="' . esc_attr( $p['srcset'] ) . '"' : '',
		esc_attr( $sizes ),
		esc_attr( $p['alt'] ),
		$lazy ? ' loading="lazy"' : ''
	);
}

/** "On now · ends 3h 48m". */
function hhv_status_label( $status ) {
	$h = intdiv( $status['ends_in'], 60 );
	$m = $status['ends_in'] % 60;
	return 'On now · ends ' . trim( ( $h ? $h . 'h ' : '' ) . ( $m || ! $h ? $m . 'm' : '' ) );
}

/** "702 590 8664" -> "(702) 590-8664". */
function hhv_phone_label( $phone ) {
	$d = preg_replace( '/\D/', '', $phone );
	if ( 11 === strlen( $d ) && '1' === $d[0] ) {
		$d = substr( $d, 1 );
	}
	return 10 === strlen( $d ) ? sprintf( '(%s) %s-%s', substr( $d, 0, 3 ), substr( $d, 3, 3 ), substr( $d, 6 ) ) : $phone;
}

/** "https://www.foo.com/menu/" -> "foo.com". */
function hhv_url_host( $url ) {
	return preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) ) ?: $url;
}

/** Google Maps search link for the venue (name + street + city). */
function hhv_maps_url( $v ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( implode( ', ', array_filter( array( $v['name'], $v['street'], $v['city'] ) ) ) );
}

/** OpenStreetMap embed URL (loaded by the browser, no API key), or '' without coordinates. */
function hhv_osm_embed_url( $v ) {
	if ( ! is_numeric( $v['lat'] ) || ! is_numeric( $v['lng'] ) ) {
		return '';
	}
	$lat = (float) $v['lat'];
	$lng = (float) $v['lng'];
	return sprintf( 'https://www.openstreetmap.org/export/embed.html?bbox=%F,%F,%F,%F&layer=mapnik&marker=%F,%F', $lng - .008, $lat - .005, $lng + .008, $lat + .005, $lat, $lng );
}

/**
 * Google Static Maps image URL (one small <img>, no JS), or '' without coordinates or HHV_GOOGLE_MAPS_KEY.
 * The key is referrer-restricted and not allowed on localhost, so locally the OSM embed is used (see hhv_is_local()).
 */
function hhv_static_map_url( $v ) {
	if ( ! defined( 'HHV_GOOGLE_MAPS_KEY' ) || hhv_is_local() || ! is_numeric( $v['lat'] ) || ! is_numeric( $v['lng'] ) ) {
		return '';
	}
	$styles = array( 'feature:all|element:geometry|color:0x15292d', 'feature:all|element:labels.text.fill|color:0xa9bfc2', 'feature:all|element:labels.text.stroke|color:0x15292d', 'feature:road|element:geometry|color:0x2b4a50', 'feature:water|element:geometry|color:0x0d1c1f', 'feature:poi|element:labels|visibility:off' );
	$url    = add_query_arg(
		array(
			'center'   => $v['lat'] . ',' . $v['lng'],
			'zoom'     => 15,
			'size'     => '640x260',
			'scale'    => 2,
			'format'   => 'jpg',
			'markers'  => 'color:0xd9a441|' . $v['lat'] . ',' . $v['lng'],
			'key'      => HHV_GOOGLE_MAPS_KEY,
		),
		'https://maps.googleapis.com/maps/api/staticmap'
	);
	foreach ( $styles as $s ) {
		$url .= '&style=' . rawurlencode( $s );
	}
	return $url;
}

/** True on the Local dev host (*.local). */
function hhv_is_local() {
	return str_ends_with( (string) wp_parse_url( home_url(), PHP_URL_HOST ), '.local' );
}

/** Menu card groups: array( 'Drinks' => items, 'Food' => items ), or array( 'Menu' => items ) when all items are one type. */
function hhv_menu_groups( $v ) {
	$groups = array( 'Drinks' => array(), 'Food' => array() );
	foreach ( $v['menu'] as $it ) {
		if ( strlen( $it['name'] ) > 4 && strtoupper( $it['name'] ) === $it['name'] ) { // "OYSTER ELIXIR" -> "Oyster Elixir".
			$it['name'] = ucwords( strtolower( $it['name'] ) );
		}
		$groups[ 'drink' === $it['type'] ? 'Drinks' : 'Food' ][] = $it;
	}
	$groups = array_filter( $groups );
	return count( $groups ) > 1 ? $groups : ( $groups ? array( 'Menu' => reset( $groups ) ) : array() );
}

/** Post content through the_content, without GeoDirectory swapping in its "GD Details" page layout. */
function hhv_venue_content( $id ) {
	add_filter( 'geodir_bypass_setup_singular_page', '__return_true' );
	$html = apply_filters( 'the_content', get_post_field( 'post_content', $id ) );
	remove_filter( 'geodir_bypass_setup_singular_page', '__return_true' );
	return trim( $html );
}

/**
 * Weekly timeline rows (Mon..Sun), positions in % of the noon -> 2am window:
 * day, today, bars (left, width, reverse), ranges (labels), now (% or null).
 * Before 2am the "now" marker belongs to last night's row.
 */
function hhv_week_rows( $v, $now = null ) {
	$now  = $now ?: current_datetime();
	$min  = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
	$dow  = (int) $now->format( 'N' ) - 1;
	if ( $min < 120 ) {
		$dow  = ( $dow + 6 ) % 7;
		$min += 1440;
	}
	$pct  = fn( $m ) => round( $m / HHV_WEEK_SPAN * 100, 2 );
	$rows = array();
	foreach ( array_keys( HHV_DAYS ) as $i => $day ) {
		$bars = array();
		foreach ( $v['hours'][ $day ] as $r ) {
			$s = max( $r[0], HHV_WEEK_FROM );
			$e = min( $r[1], HHV_WEEK_FROM + HHV_WEEK_SPAN );
			if ( $e > $s ) {
				$bars[] = array( 'left' => $pct( $s - HHV_WEEK_FROM ), 'width' => $pct( $e - $s ), 'reverse' => $r[2] );
			}
		}
		$rows[] = array(
			'day'    => HHV_DAYS[ $day ],
			'today'  => $i === $dow,
			'bars'   => $bars,
			'ranges' => array_map( fn( $r ) => hhv_time_label( $r[0], true ) . '–' . hhv_time_label( $r[1], true ), $v['hours'][ $day ] ),
			'now'    => $i === $dow && $min >= HHV_WEEK_FROM ? $pct( $min - HHV_WEEK_FROM ) : null,
		);
	}
	return $rows;
}

/** Menu card row: name ..... price (no leader without a price). */
function hhv_menu_row( $name, $price ) {
	return sprintf(
		'<li class="hhv-menu__row"><span>%s</span>%s</li>',
		esc_html( $name ),
		'' !== $price ? '<span class="hhv-menu__dots" aria-hidden="true"></span><b>' . esc_html( $price ) . '</b>' : ''
	);
}

/**
 * Free-text menu (GD "happy hour menu with prices") as menu card markup:
 * "Drink Specials:" lines become headings, "* Margaritas $8" lines become rows with leaders, anything else a note.
 */
function hhv_menu_text_html( $text ) {
	$html = '';
	$open = false;
	foreach ( preg_split( '/\R/', $text ) as $line ) {
		$line = trim( $line );
		$item = preg_match( '/^[*•]\s*[^*]/u', $line ) ? trim( ltrim( $line, '*• ' ) ) : '';
		if ( $item ) {
			$html .= $open ? '' : '<ul class="hhv-menu__list">';
			$open  = true;
			$html .= preg_match( '/^(.+?)[\s:–-]+(\$[\d.,]+(?:\s*(?:off|each|ea))?|\d+%\s*off|½ off)$/iu', $item, $m ) ? hhv_menu_row( $m[1], $m[2] ) : hhv_menu_row( $item, '' );
			continue;
		}
		$html .= $open ? '</ul>' : '';
		$open  = false;
		if ( '' !== $line ) {
			$html .= str_ends_with( $line, ':' ) ? '<h3 class="hhv-menu__head">' . esc_html( rtrim( $line, ':' ) ) . '</h3>' : '<p class="hhv-menu__note">' . esc_html( ltrim( $line, '* ' ) ) . '</p>';
		}
	}
	return $html . ( $open ? '</ul>' : '' );
}
