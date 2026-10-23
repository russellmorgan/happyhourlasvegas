<?php
/**
 * Venue (GeoDirectory gd_place) and landing page data helpers.
 *
 * Venue core data lives in wp_geodir_gd_place_detail (edited through GeoDirectory's meta box);
 * design-only extras (deal_summary, editor_take) are ACF postmeta. Areas are gd_placecategory
 * terms (top level = area, child = neighbourhood/casino); vibes and menus are gd_place_tags.
 */

const HHV_DAYS = array( 'Mo' => 'Mon', 'Tu' => 'Tue', 'We' => 'Wed', 'Th' => 'Thu', 'Fr' => 'Fri', 'Sa' => 'Sat', 'Su' => 'Sun' );

/** Full venue record, or null if $post is not a gd_place. */
function hhv_venue( $post = null ) {
	static $cache = array();
	$post = get_post( $post );
	if ( ! $post || 'gd_place' !== $post->post_type ) {
		return null;
	}
	if ( isset( $cache[ $post->ID ] ) ) {
		return $cache[ $post->ID ];
	}
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}geodir_gd_place_detail WHERE post_id = %d", $post->ID ), ARRAY_A ) ?: array();
	$f   = fn( $k ) => trim( (string) ( $row[ $k ] ?? '' ) );

	$area = $hood = null;
	foreach ( get_the_terms( $post, 'gd_placecategory' ) ?: array() as $t ) {
		if ( $t->parent ) {
			$hood = $hood ?: $t;
		} else {
			$area = $area ?: $t;
		}
	}
	if ( $hood && ! $area ) {
		$area = get_term( $hood->parent, 'gd_placecategory' );
	}

	$items = array();
	for ( $i = 1; $i <= 10; $i++ ) {
		if ( '' !== $f( "name_$i" ) ) {
			$items[] = array(
				'name'  => $f( "name_$i" ),
				'desc'  => $f( "description_$i" ),
				'price' => $f( "price_$i" ),
				'type'  => hhv_menu_item_type( $f( "name_$i" ) . ' ' . $f( "description_$i" ) ),
			);
		}
	}

	$features = array_values( array_filter( array_map( 'trim', explode( ',', $f( 'features_' ) ) ) ) );
	$image    = $f( 'featured_image' );
	$hours    = hhv_parse_hours( $f( 'business_hours' ) );

	$v = array(
		'id'           => $post->ID,
		'title'        => get_the_title( $post ),
		'name'         => hhv_venue_name( get_the_title( $post ) ),
		'url'          => get_permalink( $post ),
		'image'        => $image ? content_url( 'uploads' . $image ) : '',
		'slogan'       => $f( 'slogan' ),
		'street'       => $f( 'street' ),
		'city'         => $f( 'city' ),
		'zip'          => $f( 'zip' ),
		'lat'          => $f( 'latitude' ),
		'lng'          => $f( 'longitude' ),
		'phone'        => $f( 'phone' ),
		'website'      => $f( 'website' ),
		'reservations' => $f( 'reservations_link' ),
		'video'        => $f( 'video' ),
		'price_range'  => $f( 'price_range' ),
		'cuisine'      => $f( 'menu_items' ),
		'features'     => $features,
		'vibe'         => $features[0] ?? '',
		'menu_text'    => $f( 'happy_hour_menu_with_prices' ),
		'menu'         => $items,
		'area'         => $area,
		'hood'         => $hood,
		'hours'        => $hours,
		'deal'         => trim( (string) get_post_meta( $post->ID, 'deal_summary', true ) ) ?: hhv_deal_from_menu( $items ),
		'take'         => trim( (string) get_post_meta( $post->ID, 'editor_take', true ) ) ?: $f( 'slogan' ),
	);
	return $cache[ $post->ID ] = $v;
}

/** "Spy Ninjas HQ Happy Hour" -> "Spy Ninjas HQ"; "PF Chang's Happy Hour Summerlin" -> "PF Chang's" (the place line shows the location). */
function hhv_venue_name( $title ) {
	return trim( preg_replace( '/^(.+?)\s*[-–|:]?\s+(Reverse\s+)?Happy\s+Hours?\b.*$/i', '$1', $title ) ) ?: $title;
}

/** Place line for eyebrows: "Cosmopolitan · The Strip". */
function hhv_venue_place( $v ) {
	return implode( ' · ', array_filter( array( $v['hood']->name ?? '', $v['area']->name ?? '' ) ) );
}

/**
 * ponytail: keyword heuristic, since GD menu slots have no drink/food flag. Add a per-item type field if editors need to override it.
 */
function hhv_menu_item_type( $text ) {
	return preg_match( '/\b(beers?|drafts?|wines?|cocktails?|margaritas?|martinis?|mules?|spritz|sangria|mimosas?|sake|soju|shots?|wells?|spirits?|liquor|bourbon|whiske?y|tequila|mezcal|vodka|gin|rum|ipa|lager|ale|seltzers?|prosecco|champagne|bubbles|pints?|pitchers?|drinks?|sips?|highballs?|old fashioned|negroni|paloma|bloody mary|elixir)\b/i', $text ) ? 'drink' : 'food';
}

/** Fallback deal line from the first three priced menu items: "$2 oysters · $9 oyster elixir". */
function hhv_deal_from_menu( $items ) {
	$bits = array();
	foreach ( $items as $it ) {
		if ( $it['price'] && preg_match( '/\$\s?\d/', $it['price'] ) && count( $bits ) < 3 ) {
			$price  = preg_replace( '/\s*(each|ea\.?)$/i', '', $it['price'] );
			$bits[] = $price . ' ' . strtolower( wp_trim_words( $it['name'], 3, '' ) );
		}
	}
	return implode( ' · ', $bits );
}

/**
 * GD business_hours -> array( 'Mo' => array( array( start, end, reverse ), ... ), ... ) in minutes from midnight.
 * Ends past midnight become > 1440. A range starting 8pm or later counts as a reverse happy hour.
 */
function hhv_parse_hours( $raw ) {
	$out = array_fill_keys( array_keys( HHV_DAYS ), array() );
	if ( ! preg_match_all( '/"(Mo|Tu|We|Th|Fr|Sa|Su) ([0-9:,\- ]+)"/', (string) $raw, $m, PREG_SET_ORDER ) ) {
		return $out;
	}
	foreach ( $m as $day ) {
		foreach ( explode( ',', $day[2] ) as $range ) {
			if ( ! preg_match( '/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $range, $r ) ) {
				continue;
			}
			$s = $r[1] * 60 + $r[2];
			$e = $r[3] * 60 + $r[4];
			if ( $e <= $s ) {
				$e += 1440;
			}
			if ( $e - $s > 12 * 60 ) { // Typos like "21:00-12:00": treat as closing at midnight.
				$e = 1440;
			}
			$out[ $day[1] ][] = array( $s, $e, $s >= 20 * 60 );
		}
	}
	return $out;
}

/** "4pm", "4:30pm", "12am"; $short gives "4p". */
function hhv_time_label( $min, $short = false ) {
	$min = $min % 1440;
	$h   = intdiv( $min, 60 );
	$mm  = $min % 60;
	$ap  = $h < 12 ? 'am' : 'pm';
	$h12 = $h % 12 ?: 12;
	return $h12 . ( $mm ? sprintf( ':%02d', $mm ) : '' ) . ( $short ? $ap[0] : $ap );
}

/** "4–7pm", "10pm–12am". */
function hhv_range_label( $range ) {
	$a = hhv_time_label( $range[0] );
	$b = hhv_time_label( $range[1] );
	if ( substr( $a, -2 ) === substr( $b, -2 ) ) {
		$a = substr( $a, 0, -2 );
	}
	return $a . '–' . $b;
}

/**
 * Live status in site time: array( 'on' => bool, 'reverse' => bool, 'ends_in' => minutes ) or null if nothing on now.
 */
function hhv_venue_status( $v, $now = null ) {
	$now  = $now ?: current_datetime();
	$keys = array_keys( HHV_DAYS );
	$dow  = ( (int) $now->format( 'N' ) ) - 1;
	$min  = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
	$checks = array(
		array( $keys[ $dow ], $min ),
		array( $keys[ ( $dow + 6 ) % 7 ], $min + 1440 ), // Yesterday's ranges running past midnight.
	);
	foreach ( $checks as list( $day, $t ) ) {
		foreach ( $v['hours'][ $day ] as $r ) {
			if ( $t >= $r[0] && $t < $r[1] ) {
				return array( 'on' => true, 'reverse' => $r[2], 'ends_in' => $r[1] - $t );
			}
		}
	}
	return null;
}

/** One-line weekly summary: "Mon–Fri 4–7pm". */
function hhv_hours_summary( $v ) {
	$groups = array();
	foreach ( $v['hours'] as $day => $ranges ) {
		$label = implode( ', ', array_map( 'hhv_range_label', $ranges ) );
		if ( '' === $label ) {
			continue;
		}
		$last = count( $groups ) - 1;
		if ( $last >= 0 && $groups[ $last ]['label'] === $label && $groups[ $last ]['to_next'] === $day ) {
			$groups[ $last ]['to'] = $day;
		} else {
			$groups[] = array( 'from' => $day, 'to' => $day, 'label' => $label );
			$last++;
		}
		$keys = array_keys( HHV_DAYS );
		$groups[ $last ]['to_next'] = $keys[ ( array_search( $day, $keys, true ) + 1 ) % 7 ];
	}
	return implode( '; ', array_map( fn( $g ) => ( 'Mo' === $g['from'] && 'Su' === $g['to'] ? 'Daily' : HHV_DAYS[ $g['from'] ] . ( $g['to'] !== $g['from'] ? '–' . HHV_DAYS[ $g['to'] ] : '' ) ) . ' ' . $g['label'], $groups ) );
}

/** Image well markup. */
function hhv_img( $src, $alt = '', $label = '', $attrs = '' ) {
	return sprintf(
		'<span class="hhv-img"%s>%s%s</span>',
		$attrs ? ' ' . $attrs : '',
		$src ? sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async">', esc_url( $src ), esc_attr( $alt ) ) : '',
		$label ? '<span class="hhv-img__label">' . esc_html( $label ) . '</span>' : ''
	);
}

/**
 * Venue card used by grids and pickers.
 * $args: size ('' | 'lg'), take (bool), deal (bool), place (eyebrow override).
 */
function hhv_venue_card( $post, $args = array() ) {
	$v = hhv_venue( $post );
	if ( ! $v ) {
		return '';
	}
	$args = wp_parse_args( $args, array( 'size' => '', 'take' => false, 'deal' => false, 'place' => null ) );
	$place = $args['place'] ?? hhv_venue_place( $v );
	return sprintf(
		'<a class="hhv-card%s" href="%s">%s%s<span class="hhv-card__title">%s</span>%s%s</a>',
		$args['size'] ? ' hhv-card--' . esc_attr( $args['size'] ) : '',
		esc_url( $v['url'] ),
		hhv_img( $v['image'], '' ),
		$place ? '<span class="hhv-eyebrow">' . esc_html( $place ) . '</span>' : '',
		esc_html( $v['name'] ),
		$args['take'] && $v['take'] ? '<p class="hhv-card__take">' . esc_html( wp_trim_words( $v['take'], 22 ) ) . '</p>' : '',
		$args['deal'] && $v['deal'] ? '<span class="hhv-deal">' . esc_html( $v['deal'] ) . '</span>' : ''
	);
}

/* ---------- Landing pages (pages using the page-landing template + ACF "Landing settings") ---------- */

/** Landing settings for a page: type (location|vibe|menu), label, areas, tags, featured, image. */
function hhv_landing( $page ) {
	$page = get_post( $page );
	if ( ! $page ) {
		return null;
	}
	$ids = fn( $k ) => array_values( array_filter( array_map( 'intval', (array) get_post_meta( $page->ID, $k, true ) ) ) );
	$label = trim( (string) get_post_meta( $page->ID, 'landing_label', true ) );
	if ( '' === $label ) {
		$label = trim( preg_replace( '/\s*(happy\s+hours?|las\s+vegas|in|-|\d+\s+verified)\s*/i', ' ', $page->post_title ) );
	}
	return array(
		'id'       => $page->ID,
		'url'      => get_permalink( $page ),
		'title'    => get_the_title( $page ),
		'label'    => $label,
		'type'     => get_post_meta( $page->ID, 'landing_type', true ) ?: '',
		'areas'    => $ids( 'landing_areas' ),
		'tags'     => $ids( 'landing_tags' ),
		'featured' => (int) get_post_meta( $page->ID, 'landing_featured', true ),
		'image'    => (int) get_post_meta( $page->ID, 'landing_image', true ) ?: get_post_thumbnail_id( $page ),
	);
}

/** WP_Query args for the venues a landing lists. $extra merges on top (paged, extra tax filters...). */
function hhv_landing_query_args( $landing, $extra = array() ) {
	$tax = array( 'relation' => 'AND' );
	if ( $landing['areas'] ) {
		$tax[] = array( 'taxonomy' => 'gd_placecategory', 'terms' => $landing['areas'], 'include_children' => true );
	}
	if ( $landing['tags'] ) {
		$tax[] = array( 'taxonomy' => 'gd_place_tags', 'terms' => $landing['tags'] );
	}
	$args = array(
		'post_type'      => 'gd_place',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'modified',
		'tax_query'      => $tax,
	);
	if ( ! empty( $extra['tax_query'] ) ) {
		$args['tax_query'] = array_merge( $tax, $extra['tax_query'] );
	}
	unset( $extra['tax_query'] );
	return array_merge( $args, $extra );
}

/** Number of venues on a landing ("31 Happy Hours"). */
function hhv_landing_count( $landing ) {
	if ( ! $landing['areas'] && ! $landing['tags'] ) {
		return 0; // No filter set yet: don't claim the whole site.
	}
	$key   = 'hhv_count_' . $landing['id'];
	$count = wp_cache_get( $key, 'hhv' );
	if ( false === $count ) {
		$q     = new WP_Query( hhv_landing_query_args( $landing, array( 'posts_per_page' => 1, 'fields' => 'ids' ) ) );
		$count = (int) $q->found_posts;
		wp_cache_set( $key, $count, 'hhv', HOUR_IN_SECONDS );
	}
	return $count;
}

/** All published landings of a type, ordered by menu_order then title. */
function hhv_landings( $type ) {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'landing_type',
		'meta_value'     => $type,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	) );
	return array_map( 'hhv_landing', $pages );
}

/** "31 Happy Hours". */
function hhv_count_label( $n ) {
	return sprintf( _n( '%s Happy Hour', '%s Happy Hours', $n ), number_format_i18n( $n ) );
}
