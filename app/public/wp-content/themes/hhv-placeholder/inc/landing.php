<?php
/**
 * Destination landing pages (template page-landing) and GeoDirectory archives/search in the same look.
 *
 * Blocks: hhv/landing-hero, hhv/venue-grid, hhv/area-directory, hhv/keep-browsing. They all read hhv_landing_context(),
 * which is the landing page's settings (hhv_landing()) or, on a GD category/tag archive or GD search, a stand-in built
 * from the queried term. Data setup: tools/landings-setup.php.
 *
 * Filters use plain GET params (progressive enhancement): pg (page), area (gd_placecategory slug),
 * vibe / menu (slug of a vibe / menu landing page).
 */

// Vibe/menu landings show their intro (the excerpt) in the hero.
add_action( 'init', function () {
	add_post_type_support( 'page', 'excerpt' );
} );

// GD search uses our archive template, not GD's gd-search nor the generic search.html. GD resolves it through both the
// search and the page hierarchy; block template priority goes by a slug's last position, so the others are dropped.
$hhv_gd_search_template = function ( $templates ) {
	if ( function_exists( 'geodir_is_page' ) && geodir_is_page( 'search' ) ) {
		$templates = array( 'gd-archive.php' );
	}
	return $templates;
};
add_filter( 'search_template_hierarchy', $hhv_gd_search_template, 20 );
add_filter( 'page_template_hierarchy', $hhv_gd_search_template, 20 );

/** Main landings of a type: location = the top-level areas only (menu_order < 100, set by tools/landings-setup.php). */
function hhv_main_landings( $type ) {
	static $cache = array();
	if ( ! isset( $cache[ $type ] ) ) {
		$cache[ $type ] = array_values( array_filter( hhv_landings( $type ), fn( $l ) => (int) get_post_field( 'menu_order', $l['id'] ) < 100 ) );
	}
	return $cache[ $type ];
}

/** Landing page URL for a single gd_placecategory term (its own location landing), else the term archive. */
function hhv_area_url( $term ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( hhv_landings( 'location' ) as $l ) {
			if ( 1 === count( $l['areas'] ) && ! isset( $map[ $l['areas'][0] ] ) ) {
				$map[ $l['areas'][0] ] = $l['url'];
			}
		}
	}
	$term = get_term( $term, 'gd_placecategory' );
	return $map[ $term->term_id ] ?? get_term_link( $term );
}

/** Is this a GD archive/search request (rendered by templates/gd-archive.html)? */
function hhv_is_gd_archive() {
	return function_exists( 'geodir_is_page' ) && ( geodir_is_page( 'search' ) || is_tax( array( 'gd_placecategory', 'gd_place_tags' ) ) || is_post_type_archive( 'gd_place' ) );
}

/**
 * What the landing blocks are showing: a landing (hhv_landing() + 'mode' => 'page' and 'heading') or a stand-in for
 * a GD archive ('mode' => 'term' | 'search' | 'all').
 */
function hhv_landing_context( $block = null ) {
	static $cache = array();
	$id = (int) ( $block->context['postId'] ?? 0 );
	if ( ! $id && is_singular( 'page' ) ) {
		$id = get_queried_object_id();
	}
	if ( ! $id && ! empty( $_GET['post_id'] ) && defined( 'REST_REQUEST' ) ) { // Editor preview (ServerSideRender).
		$id = (int) $_GET['post_id'];
	}
	$key = $id ?: 'archive';
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	if ( $id && 'page' === get_post_type( $id ) ) {
		$l            = hhv_landing( $id );
		$l['mode']    = 'page';
		$l['heading'] = trim( preg_replace( '/\s+[-–]\s+(the strip|\d+ verified)$/iu', '', html_entity_decode( $l['title'], ENT_QUOTES ) ) );
		$l['intro']   = 'location' === $l['type'] ? '' : get_post_field( 'post_excerpt', $id );
		$l['term']    = null;
		return $cache[ $key ] = $l;
	}

	$l = array( 'id' => 0, 'url' => '', 'title' => '', 'label' => '', 'type' => 'location', 'areas' => array(), 'tags' => array(), 'featured' => 0, 'image' => 0, 'mode' => 'all', 'intro' => '', 'term' => null );
	$term = is_tax() ? get_queried_object() : null;
	if ( $term instanceof WP_Term ) {
		$l['mode']  = 'term';
		$l['term']  = $term;
		$l['label'] = trim( preg_replace( '/\s*happy\s+hours?\s*/i', ' ', $term->name ) ) ?: $term->name;
		$l['url']   = get_term_link( $term );
		$l['intro'] = wp_strip_all_tags( term_description( $term ) );
		if ( 'gd_placecategory' === $term->taxonomy ) {
			$l['areas'] = array( $term->term_id );
		} else {
			$l['tags'] = array( $term->term_id );
			$l['type'] = 'vibe';
			foreach ( hhv_landings( 'menu' ) as $m ) {
				if ( in_array( $term->term_id, $m['tags'], true ) ) {
					$l['type'] = 'menu';
				}
			}
		}
	} elseif ( function_exists( 'geodir_is_page' ) && geodir_is_page( 'search' ) ) {
		$l['mode']  = 'search';
		$l['label'] = get_search_query() ? sprintf( '“%s”', get_search_query() ) : 'All';
		$l['type']  = 'search';
	} else {
		$l['label'] = 'Las Vegas';
	}
	$l['heading'] = 'search' === $l['mode'] ? 'Happy hours matching ' . $l['label'] : $l['label'] . ' Happy Hours';
	return $cache[ $key ] = $l;
}

/** A landing with neither areas nor tags lists nothing (hhv_landing_query_args() would list every venue). */
function hhv_landing_is_empty( $l ) {
	return 'page' === $l['mode'] && ! $l['areas'] && ! $l['tags'];
}

/** IDs of every venue on a landing (cached per request). */
function hhv_landing_venue_ids( $l ) {
	static $cache = array();
	$key = wp_json_encode( array( $l['areas'], $l['tags'] ) );
	if ( ! isset( $cache[ $key ] ) ) {
		$cache[ $key ] = hhv_landing_is_empty( $l ) ? array() : get_posts( hhv_landing_query_args( $l, array( 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) ) );
	}
	return $cache[ $key ];
}

/**
 * Facets of a landing's venues: array( 'tags' => [term_id => [post ids]], 'areas' => [term_id => [post ids]] ).
 * Areas include each venue under its own term and under that term's top-level parent.
 */
function hhv_landing_facets( $l ) {
	static $cache = array();
	$ids = hhv_landing_venue_ids( $l );
	$key = md5( implode( ',', $ids ) );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$out = array( 'tags' => array(), 'areas' => array() );
	if ( $ids ) {
		foreach ( wp_get_object_terms( $ids, array( 'gd_place_tags', 'gd_placecategory' ), array( 'fields' => 'all_with_object_id' ) ) as $t ) {
			if ( 'gd_place_tags' === $t->taxonomy ) {
				$out['tags'][ $t->term_id ][ $t->object_id ] = true;
				continue;
			}
			$out['areas'][ $t->term_id ][ $t->object_id ] = true;
			if ( $t->parent ) {
				$out['areas'][ $t->parent ][ $t->object_id ] = true;
			}
		}
	}
	return $cache[ $key ] = $out;
}

/** Venue IDs in a facet bucket for any of $term_ids. */
function hhv_facet_ids( $bucket, $term_ids ) {
	$ids = array();
	foreach ( $term_ids as $t ) {
		$ids += $bucket[ $t ] ?? array();
	}
	return $ids;
}

/** Top-level areas that have venues on this landing: array of array( term, count ), in main-landing order. */
function hhv_landing_area_options( $l ) {
	$facets = hhv_landing_facets( $l );
	$out    = array();
	$order  = array();
	foreach ( hhv_main_landings( 'location' ) as $m ) {
		$order = array_merge( $order, $m['areas'] );
	}
	// Only the areas of the main location landings (a few casinos are top-level GD terms by mistake).
	foreach ( array_unique( $order ) as $id ) {
		$t = get_term( $id, 'gd_placecategory' );
		if ( ! $t instanceof WP_Term || $t->parent ) {
			continue;
		}
		$n = count( $facets['areas'][ $t->term_id ] ?? array() );
		if ( $n ) {
			$out[] = array( $t, $n );
		}
	}
	return $out;
}

/** Vibe or menu landings that have venues on this landing: array of array( landing, count ). */
function hhv_landing_tag_options( $l, $type ) {
	$facets = hhv_landing_facets( $l );
	$out    = array();
	foreach ( hhv_main_landings( $type ) as $m ) {
		$n = count( hhv_facet_ids( $facets['tags'], $m['tags'] ) );
		if ( $m['tags'] && $n && $m['id'] !== $l['id'] ) {
			$out[] = array( $m, $n );
		}
	}
	return $out;
}

/** Current GET filters, validated: array( 'area' => WP_Term|null, 'vibe' => landing|null, 'menu' => landing|null, 'pg' => int ). */
function hhv_landing_filters() {
	$f    = array( 'area' => null, 'vibe' => null, 'menu' => null, 'pg' => max( 1, (int) ( $_GET['pg'] ?? 1 ) ) );
	$slug = sanitize_title( wp_unslash( $_GET['area'] ?? '' ) );
	if ( $slug ) {
		$f['area'] = get_term_by( 'slug', $slug, 'gd_placecategory' ) ?: null;
	}
	foreach ( array( 'vibe', 'menu' ) as $k ) {
		$slug = sanitize_title( wp_unslash( $_GET[ $k ] ?? '' ) );
		$page = $slug ? get_page_by_path( $slug ) : null;
		if ( $page && get_post_meta( $page->ID, 'landing_type', true ) === $k ) {
			$f[ $k ] = hhv_landing( $page );
		}
	}
	return $f;
}

/** Featured venue for a vibe/menu hero: the landing's pick, else the most recently updated venue with a photo. */
function hhv_landing_featured( $l ) {
	if ( $l['featured'] && 'publish' === get_post_status( $l['featured'] ) ) {
		return $l['featured'];
	}
	foreach ( array_slice( hhv_landing_venue_ids( $l ), 0, 20 ) as $id ) {
		$v = hhv_venue( $id );
		if ( $v && $v['image'] ) {
			return $id;
		}
	}
	return 0;
}

/** Chip markup. */
function hhv_chip( $label, $url, $active = false ) {
	return sprintf( '<li><a class="hhv-chip%s" href="%s"%s>%s</a></li>', $active ? ' is-active' : '', esc_url( $url ), $active ? ' aria-current="page"' : '', esc_html( $label ) );
}

/** URL of the current landing/archive with the given GET params (null removes one); always jumps to the grid. */
function hhv_landing_url( $params ) {
	$base = remove_query_arg( array( 'pg', 'paged' ), wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
	$base = preg_replace( '#/page/\d+/?#', '/', strtok( $base, '#' ) );
	foreach ( $params as $k => $v ) {
		$base = null === $v || '' === $v ? remove_query_arg( $k, $base ) : add_query_arg( $k, rawurlencode( $v ), $base );
	}
	return $base . '#listings';
}
