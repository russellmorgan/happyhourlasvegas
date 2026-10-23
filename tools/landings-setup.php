<?php
/**
 * Sets up the destination landing pages (area / neighbourhood / casino, vibe and menu pages) for the
 * `page-landing` template, from the [gd_listings category='..' tags='..'] shortcode in their content.
 *
 * wp eval-file tools/landings-setup.php [write] [ids=1,2] [dry=DIR] [nav]   (no leading dashes; WP-CLI eats those)
 *   (default)  dry run: prints one line per landing (id, type, label, areas, tags, featured) and what is skipped
 *   dry=DIR    also writes the cleaned content of each landing to DIR/<id>.html
 *   write      saves: template, ACF landing fields, menu_order, excerpt (vibe/menu intro) and cleaned content
 *              (original content kept once in postmeta _hhv_pre_reskin_content; re-runs start from that copy)
 *   nav        also rebuilds header navigation post 8763 (AREAS / VIBE / MENU), old content in _hhv_pre_reskin_content
 *
 * Re-runnable: the source is always _hhv_pre_reskin_content when present, so running `write` twice gives the same result.
 * Editors' later changes to the landing fields are overwritten by `write`; use ids=... to limit a re-run.
 */

$opt = array();
foreach ( $args as $a ) {
	if ( preg_match( '/^(?:--)?(\w+)(?:=(.*))?$/', $a, $m ) ) {
		$opt[ $m[1] ] = $m[2] ?? true;
	}
}
$write = ! empty( $opt['write'] );
if ( ! current_user_can( 'unfiltered_html' ) ) { // Keep JSON-LD <script> blocks: save as an administrator so kses leaves them alone.
	wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 0 );
}
$only  = ! empty( $opt['ids'] ) ? array_map( 'intval', explode( ',', $opt['ids'] ) ) : array();

const HHVL_NAV_ID = 8763;

/* Pages that list venues but are not landings (homepage, author page, multi-area day pages, sliders...) are skipped by rule;
   these are skipped explicitly too. */
const HHVL_SKIP = array( 142, 5756, 7899 );

/* Top-level areas, in the order of the design's "Happy hour locations" chips (menu_order 1..n). */
const HHVL_AREAS = array(
	3132 => 'The Strip',
	3289 => 'Off-Strip',
	3275 => 'Downtown LV',
	4621 => 'Arts District',
	5955 => 'Southwest',
	3287 => 'Summerlin',
	3288 => 'Henderson',
	3686 => 'Centennial',
	3682 => 'North Vegas',
	4350 => 'South Vegas',
);

/* Vibe and menu landings in design order, with their chip labels. Anything not listed gets menu_order 100. */
const HHVL_VIBES = array(
	7525 => 'Foodie', 7590 => 'Weekend', 6608 => 'Rooftop', 7646 => 'Healthy', 6886 => 'Happy hour deals',
	7921 => 'Hidden gem', 6601 => 'Sports bar', 6734 => 'Reverse', 3596 => 'Speakeasy', 7323 => 'Shopping',
	7616 => 'Upscale', 7615 => 'Cheap', 7650 => 'Vintage Vegas', 7643 => 'Lounge', 7647 => 'Date night',
	7613 => 'Beer', 7624 => 'Wine', 7625 => 'Cocktail', 7642 => 'Pub', 7656 => 'Gaming', 7611 => 'Dive bar',
	3290 => 'Hot spots', 8183 => 'Golf',
);
const HHVL_MENUS = array(
	6633 => 'Italian', 6991 => 'BBQ', 3602 => 'Sushi', 6631 => 'Mexican', 6807 => 'Oyster', 7165 => 'Thai',
	7169 => 'French', 7584 => 'Burger', 7583 => 'Pizza', 7582 => 'Steak', 7575 => 'Brazilian', 7573 => 'Vegetarian',
	7572 => 'Mediterranean', 7571 => 'Spanish', 7570 => 'Asian', 7569 => 'Japanese', 7568 => 'Korean',
);

/* Shortcode tag names that are menus (cuisine / food) rather than vibes. */
const HHVL_MENU_TAGS = array( 'sushi happy hour', 'mexican', 'italian', 'oyster happy hours', 'bbq happy hour', 'thai happy hour', 'french happy hour', 'korean happy hour', 'japanese happy hours', 'asian happy hour', 'spanish happy hour', 'mediterranean happy hour', 'vegetarian happy hour', 'vegan happy hour', 'brazilian happy hour', 'steak happy hour', 'pizza happy hour', 'burger' );

/* Shortcode tag names whose gd_place_tags term has a different name. */
const HHVL_TAG_ALIASES = array( 'spanish happy hour' => 'Spanish-Tapas', 'vegan happy hour' => 'vegan', 'upscale happy hour' => 'Upscale Casual' );

/* Label overrides for location children whose titles don't shorten well. */
const HHVL_LABELS = array(
	3283 => 'Main Street Station', 3227 => 'Vanderpump Hotel', 4361 => 'The District at GVR', 3250 => 'Green Valley Ranch',
	3273 => 'Suncoast', 3264 => 'Boyd Gaming', 3248 => 'Station Casinos', 3274 => 'The Orleans', 3272 => "Sam's Town",
	3276 => "Binion's", 4341 => 'JW Marriott Summerlin', 3286 => 'Fremont Street', 3268 => 'Fremont Hotel', 3281 => 'Golden Gate',
);

function hhvl_source( $p ) {
	$saved = get_post_meta( $p->ID, '_hhv_pre_reskin_content', true );
	return '' !== $saved ? $saved : $p->post_content;
}

/** The single [gd_listings] filter of a page: array( cats[], tags[] ) or null if it has none or several different ones. */
function hhvl_filter( $content ) {
	preg_match_all( '/\[gd_listings([^\]]*)\]/', $content, $m );
	$found = array();
	foreach ( $m[1] as $raw ) {
		$a    = shortcode_parse_atts( $raw );
		$cats = array_values( array_filter( array_map( 'intval', explode( ',', $a['category'] ?? '' ) ) ) );
		$tags = array_values( array_filter( array_map( 'trim', explode( ',', $a['tags'] ?? '' ) ), fn( $t ) => '' !== $t && 'happy hour' !== strtolower( $t ) ) );
		if ( $cats || $tags ) {
			$found[ wp_json_encode( array( $cats, $tags ) ) ] = array( $cats, $tags );
		}
	}
	return 1 === count( $found ) ? reset( $found ) : null;
}

function hhvl_tag_id( $name ) {
	$name = HHVL_TAG_ALIASES[ strtolower( $name ) ] ?? $name;
	$t    = get_term_by( 'name', $name, 'gd_place_tags' ) ?: get_term_by( 'slug', sanitize_title( $name ), 'gd_place_tags' );
	return $t ? (int) $t->term_id : 0;
}

function hhvl_label( $p, $type ) {
	$map = HHVL_AREAS + HHVL_VIBES + HHVL_MENUS + HHVL_LABELS;
	if ( isset( $map[ $p->ID ] ) ) {
		return $map[ $p->ID ];
	}
	$t = html_entity_decode( get_the_title( $p ), ENT_QUOTES );
	$t = preg_replace( '/\s*[-–]\s*(the strip|\d+ verified)\s*$/iu', '', $t );
	$t = preg_replace( '/\b(happy\s+hours?|las\s+vegas|hotel\s+casino|casino|hotel)\b/i', ' ', $t );
	return trim( preg_replace( '/\s+/', ' ', $t ) );
}

/* ---------- Content cleaning ---------- */

function hhvl_block( $name, $attrs, $html ) {
	$a = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
	return "<!-- wp:$name$a -->\n$html\n<!-- /wp:$name -->\n";
}

/** Inline HTML of a block's root element, tidied: no emoji, no editor junk attributes, site-relative links. */
function hhvl_inline( $html ) {
	$html = preg_replace( '/^\s*<(p|h[1-6]|summary|li)[^>]*>|<\/(p|h[1-6]|summary|li)>\s*$/i', '', trim( $html ) );
	$html = preg_replace( '/\s(data-[a-z-]+|class|style|id)="[^"]*"/i', '', $html );
	$html = preg_replace( '#https?://(www\.)?happyhourvegas\.com/#i', '/', $html );
	$html = preg_replace( '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F1E6}-\x{1F1FF}\x{FE0F}\x{200D}]/u', '', $html );
	$html = preg_replace( '/<br\s*\/?>/i', ' ', $html );
	$html = str_replace( '&nbsp;', ' ', $html );
	return trim( preg_replace( '/[ \t]+/', ' ', $html ) );
}

function hhvl_text( $html ) {
	return trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES ) ) );
}

/** Flattens group/columns/column wrappers into a list of leaf blocks. */
function hhvl_flatten( $blocks ) {
	$out = array();
	foreach ( $blocks as $b ) {
		if ( ! $b['blockName'] ) {
			if ( '' !== trim( $b['innerHTML'] ) ) {
				$out[] = array( 'blockName' => 'core/freeform', 'innerHTML' => $b['innerHTML'], 'innerBlocks' => array(), 'attrs' => array() );
			}
			continue;
		}
		if ( in_array( $b['blockName'], array( 'core/group', 'core/columns', 'core/column' ), true ) ) {
			$out = array_merge( $out, hhvl_flatten( $b['innerBlocks'] ) );
			continue;
		}
		$out[] = $b;
	}
	return $out;
}

/** True for a list whose every item is just a link (old navigation lists). */
function hhvl_is_link_list( $b ) {
	foreach ( $b['innerBlocks'] as $li ) {
		$html = preg_replace( '/<!--.*?-->/s', '', serialize_block( $li ) );
		if ( '' !== hhvl_text( preg_replace( '/<a\b[^>]*>.*?<\/a>/is', '', $html ) ) && false === stripos( $html, 'verified' ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Cleans a landing's content. Returns array( content, intro, featured_slug, dropped[] ).
 * Keeps: paragraphs/headings/non-link lists (guide text), FAQ details (as a .hhv-faq group under a generated title), JSON-LD.
 * Drops: H1 titles, shortcodes, nav link lists and their details wrappers, map iframes, "PHOTO:" credits, emoji.
 */
function hhvl_clean( $content, $label, $take_intro ) {
	$leaves   = hhvl_flatten( parse_blocks( $content ) );
	$guide    = '';
	$faq      = '';
	$tail     = '';
	$intro    = array();
	$featured = '';
	$dropped  = array();
	$before_listing = true;
	$in_faq   = false;

	$keep_text = function ( $b, &$into ) use ( &$dropped ) {
		$name = $b['blockName'];
		$html = hhvl_inline( $b['innerHTML'] );
		if ( '' === hhvl_text( $html ) ) {
			return;
		}
		if ( 'core/paragraph' === $name ) {
			$into .= hhvl_block( 'paragraph', array(), "<p>$html</p>" );
		} elseif ( 'core/heading' === $name ) {
			$into .= hhvl_block( 'heading', array( 'className' => 'hhv-section-head' ), "<h2 class=\"wp-block-heading hhv-section-head\">$html</h2>" );
		} elseif ( 'core/list' === $name ) {
			$items = '';
			foreach ( $b['innerBlocks'] as $li ) {
				$items .= hhvl_block( 'list-item', array(), '<li>' . hhvl_inline( $li['innerHTML'] ) . '</li>' );
			}
			$tag    = empty( $b['attrs']['ordered'] ) ? 'ul' : 'ol';
			$into  .= hhvl_block( 'list', 'ol' === $tag ? array( 'ordered' => true ) : array(), "<$tag class=\"wp-block-list\">$items</$tag>" );
		} else {
			$dropped[] = $name . ': ' . substr( hhvl_text( $html ), 0, 60 );
		}
	};

	foreach ( $leaves as $b ) {
		$name = $b['blockName'];
		$text = hhvl_text( $b['innerHTML'] );

		if ( 'core/shortcode' === $name ) {
			$before_listing = false;
			continue;
		}
		if ( 'core/heading' === $name && ( $b['attrs']['level'] ?? 2 ) === 1 ) {
			continue; // Duplicated page title.
		}
		if ( 'core/heading' === $name && preg_match( '/frequently asked|\bFAQs?\b/i', $text ) ) {
			$in_faq = true;
			continue; // Replaced by the generated FAQ title.
		}
		if ( preg_match( '/[\x{2190}-\x{21FF}\x{2794}-\x{27BF}]\s*$|^THE VEGAS HAPPY HOUR CLUB$|^We don.t have jackets/u', $text ) ) {
			continue; // Headings of removed listing carousels, old newsletter promo.
		}
		if ( in_array( $name, array( 'core/paragraph', 'core/heading' ), true ) && preg_match( '/^PHOTO:/i', $text ) ) {
			if ( preg_match( '#href="[^"]*/happy-hour/([^/"]+)/?"#', $b['innerHTML'], $m ) ) {
				$featured = $m[1];
			}
			continue;
		}
		if ( 'core/html' === $name ) {
			if ( false !== stripos( $b['innerHTML'], 'ld+json' ) || preg_match( '/^\s*\{\s*"@context"/', wp_strip_all_tags( $b['innerHTML'] ) ) ) {
				$tail .= hhvl_block( 'html', array(), trim( $b['innerHTML'] ) );
			} elseif ( '' !== trim( $b['innerHTML'] ) ) {
				$dropped[] = 'html: ' . substr( preg_replace( '/\s+/', ' ', trim( $b['innerHTML'] ) ), 0, 60 );
			}
			continue;
		}
		if ( 'core/list' === $name && hhvl_is_link_list( $b ) ) {
			continue;
		}
		if ( 'core/details' === $name ) {
			$inner = array_filter( hhvl_flatten( $b['innerBlocks'] ), fn( $ib ) => 'core/freeform' !== $ib['blockName'] );
			$names = array_unique( array_map( fn( $ib ) => $ib['blockName'], $inner ) );
			if ( ! array_diff( $names, array( 'core/list' ) ) ) {
				continue; // Nav accordion (link lists only).
			}
			preg_match( '/<summary[^>]*>(.*?)<\/summary>/is', $b['innerHTML'], $sm );
			$q = hhvl_inline( $sm[1] ?? '' );
			if ( $in_faq || '?' === substr( hhvl_text( $q ), -1 ) ) {
				$in_faq = true;
				$body   = '';
				foreach ( $inner as $ib ) {
					$keep_text( $ib, $body );
				}
				$faq .= hhvl_block( 'details', array(), "<details class=\"wp-block-details\"><summary>$q</summary>$body</details>" );
			} else {
				foreach ( $inner as $ib ) { // Guide wrapper ("LOUNGE HAPPY HOURS"): keep its text, lose the accordion.
					$keep_text( $ib, $guide );
				}
			}
			continue;
		}
		if ( $take_intro && $before_listing && 'core/paragraph' === $name ) {
			$intro[] = hhvl_inline( $b['innerHTML'] );
			continue;
		}
		if ( in_array( $name, array( 'core/paragraph', 'core/heading', 'core/list' ), true ) ) {
			$keep_text( $b, $guide );
			continue;
		}
		if ( ! in_array( $name, array( 'core/spacer', 'core/separator' ), true ) ) {
			$dropped[] = $name . ': ' . substr( $text, 0, 60 );
		}
	}

	if ( $faq ) {
		$title = preg_match( '/happy hour/i', $label ) ? $label : $label . ' Happy Hours';
		$faq   = hhvl_block( 'group', array( 'className' => 'hhv-faq', 'layout' => array( 'type' => 'default' ) ),
			"<div class=\"wp-block-group hhv-faq\">" . hhvl_block( 'heading', array( 'className' => 'hhv-section-head' ), "<h2 class=\"wp-block-heading hhv-section-head\">" . esc_html( $title ) . " — Frequently asked questions</h2>" ) . $faq . '</div>' );
	}
	return array( trim( $guide . $faq . $tail ), implode( ' ', $intro ), $featured, $dropped );
}

/* ---------- Collect landings ---------- */

$pages    = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
$landings = array();
$skipped  = array();
foreach ( $pages as $p ) {
	$src    = hhvl_source( $p );
	$filter = in_array( $p->ID, HHVL_SKIP, true ) ? null : hhvl_filter( $src );
	if ( ! $filter ) {
		if ( false !== strpos( $src, '[gd_listings' ) ) {
			$skipped[] = $p->ID . ' ' . $p->post_title;
		}
		continue;
	}
	list( $cats, $tag_names ) = $filter;
	$tags    = array();
	$missing = array();
	foreach ( $tag_names as $n ) {
		$id = hhvl_tag_id( $n );
		$id ? $tags[] = $id : $missing[] = $n;
	}
	if ( $tag_names ) {
		$type = in_array( strtolower( $tag_names[0] ), HHVL_MENU_TAGS, true ) ? 'menu' : 'vibe';
	} else {
		$type = 'location';
	}
	$order = array_search( $p->ID, array_keys( HHVL_AREAS ), true );
	if ( 'vibe' === $type ) {
		$order = array_search( $p->ID, array_keys( HHVL_VIBES ), true );
	} elseif ( 'menu' === $type ) {
		$order = array_search( $p->ID, array_keys( HHVL_MENUS ), true );
	}
	$landings[ $p->ID ] = array(
		'post'    => $p,
		'type'    => $type,
		'label'   => hhvl_label( $p, $type ),
		'areas'   => $cats,
		'tags'    => array_values( array_unique( $tags ) ),
		'missing' => $missing,
		'order'   => false === $order ? 100 : $order + 1,
		'src'     => $src,
	);
}

/* ---------- Report / write ---------- */

$counts = array_count_values( array_column( $landings, 'type' ) );
WP_CLI::log( sprintf( '%d landings (%s). %s', count( $landings ), implode( ', ', array_map( fn( $k, $v ) => "$v $k", array_keys( $counts ), $counts ) ), $write ? 'WRITING' : 'dry run' ) );

foreach ( $landings as $id => $l ) {
	if ( $only && ! in_array( $id, $only, true ) ) {
		continue;
	}
	$p = $l['post'];
	list( $content, $intro, $featured_slug, $dropped ) = hhvl_clean( $l['src'], $l['label'], 'location' !== $l['type'] );
	$featured = 0;
	foreach ( $featured_slug ? array( $featured_slug, "$featured_slug-happy-hour", preg_replace( '/-happy-hours?$/', '', $featured_slug ) . '-happy-hour' ) : array() as $slug ) {
		$fp = get_page_by_path( $slug, OBJECT, 'gd_place' );
		if ( $fp && 'publish' === $fp->post_status && ( ! $l['tags'] || has_term( $l['tags'], 'gd_place_tags', $fp ) ) ) {
			$featured = $fp->ID;
			break;
		}
	}

	WP_CLI::log( sprintf( "%-5d %-8s %-24s ord=%-3d areas=%-14s tags=%-10s feat=%-5s %s%s",
		$id, $l['type'], $l['label'], $l['order'], implode( ',', $l['areas'] ), implode( ',', $l['tags'] ), $featured ?: '-',
		$l['missing'] ? 'MISSING TAG: ' . implode( ', ', $l['missing'] ) . ' ' : '',
		$dropped ? 'dropped: ' . implode( ' | ', $dropped ) : ''
	) );

	if ( ! empty( $opt['dry'] ) ) {
		wp_mkdir_p( $opt['dry'] );
		file_put_contents( rtrim( $opt['dry'], '/' ) . "/$id.html", "<!-- intro: $intro -->\n" . $content );
	}
	if ( ! $write ) {
		continue;
	}

	if ( '' === get_post_meta( $id, '_hhv_pre_reskin_content', true ) ) {
		update_post_meta( $id, '_hhv_pre_reskin_content', wp_slash( $p->post_content ) );
	}
	$update = array( 'ID' => $id, 'post_content' => $content, 'menu_order' => $l['order'] );
	if ( $intro && '' === trim( $p->post_excerpt ) ) {
		$update['post_excerpt'] = wp_strip_all_tags( $intro );
	}
	wp_update_post( wp_slash( $update ) );
	update_post_meta( $id, '_wp_page_template', 'page-landing' );

	$fields = array(
		'field_hhv_landing_type'  => $l['type'],
		'field_hhv_landing_label' => $l['label'],
		'field_hhv_landing_areas' => array_map( 'strval', $l['areas'] ),
		'field_hhv_landing_tags'  => array_map( 'strval', $l['tags'] ),
	);
	if ( $featured && ! get_post_meta( $id, 'landing_featured', true ) ) {
		$fields['field_hhv_landing_featured'] = $featured;
	}
	foreach ( $fields as $key => $value ) {
		$name = substr( $key, strlen( 'field_hhv_' ) );
		if ( function_exists( 'update_field' ) && acf_get_field( $key ) ) {
			update_field( $key, $value, $id );
		} else {
			update_post_meta( $id, $name, $value );
			update_post_meta( $id, '_' . $name, $key );
		}
	}
}

if ( $skipped ) {
	WP_CLI::log( "\nNot landings (several or no venue filters, left untouched):\n  " . implode( "\n  ", $skipped ) );
}

/* ---------- Header navigation ---------- */

if ( ! empty( $opt['nav'] ) ) {
	$groups = array( 'Areas' => 'location', 'Vibe' => 'vibe', 'Menu' => 'menu' );
	$nav    = '';
	foreach ( $groups as $title => $type ) {
		$items = array_filter( $landings, fn( $l ) => $l['type'] === $type && $l['order'] < 100 );
		uasort( $items, fn( $a, $b ) => $a['order'] <=> $b['order'] );
		$links = '';
		foreach ( $items as $id => $l ) {
			$links .= '<!-- wp:navigation-link ' . wp_json_encode( array( 'label' => $l['label'], 'type' => 'page', 'id' => $id, 'url' => wp_make_link_relative( get_permalink( $id ) ), 'kind' => 'post-type' ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . " /-->\n";
		}
		$nav .= '<!-- wp:navigation-submenu ' . wp_json_encode( array( 'label' => $title, 'url' => '#', 'kind' => 'custom' ) ) . " -->\n$links<!-- /wp:navigation-submenu -->\n";
	}
	$old = get_post( HHVL_NAV_ID );
	WP_CLI::log( "\nNavigation " . HHVL_NAV_ID . ":\n" . $nav );
	if ( $write && $old ) {
		if ( '' === get_post_meta( HHVL_NAV_ID, '_hhv_pre_reskin_content', true ) ) {
			update_post_meta( HHVL_NAV_ID, '_hhv_pre_reskin_content', wp_slash( $old->post_content ) );
		}
		wp_update_post( wp_slash( array( 'ID' => HHVL_NAV_ID, 'post_content' => $nav ) ) );
		WP_CLI::success( 'Navigation rebuilt.' );
	}
}
