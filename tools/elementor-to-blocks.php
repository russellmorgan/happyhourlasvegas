<?php
/**
 * Converts Elementor `_elementor_data` into core Gutenberg blocks (no Elementor needed).
 *
 * wp eval-file tools/elementor-to-blocks.php [ids=1,2] [dry=DIR] [write]  (no leading dashes; WP-CLI eats those)
 *   --dry=DIR  write <id>.html per post into DIR instead of saving
 *   --write    save post_content (old content kept in meta _hhv_pre_blocks_content and as a revision)
 *
 * Styling (colors, typography, spacing) is dropped on purpose; the block theme owns styling now.
 * GeoDirectory widgets become shortcode blocks because GD's blocks only exist in the editor JS.
 */

$opt = array();
foreach ( $args as $a ) {
	if ( preg_match( '/^(?:--)?(\w+)(?:=(.*))?$/', $a, $m ) ) $opt[ $m[1] ] = $m[2] ?? true;
}

global $report;
$report = array();

function e2b_block( $name, $attrs, $html ) {
	$a = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';
	return "<!-- wp:$name$a -->\n$html\n<!-- /wp:$name -->\n";
}

function e2b_align_attr( $align ) {
	return in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : '';
}

function e2b_paragraph( $html, $align = '' ) {
	if ( trim( wp_strip_all_tags( str_replace( '&nbsp;', '', $html ) ) ) === '' && strpos( $html, '<img' ) === false ) return '';
	$attrs = $align ? array( 'align' => $align ) : array();
	$class = $align ? ' class="has-text-align-' . $align . '"' : '';
	return e2b_block( 'paragraph', $attrs, "<p$class>$html</p>" );
}

function e2b_heading( $html, $level = 2, $align = '' ) {
	if ( trim( wp_strip_all_tags( $html ) ) === '' ) return '';
	$attrs = array();
	if ( $align ) $attrs['textAlign'] = $align;
	if ( 2 !== $level ) $attrs['level'] = $level;
	$class = 'wp-block-heading' . ( $align ? " has-text-align-$align" : '' );
	return e2b_block( 'heading', $attrs, "<h$level class=\"$class\">$html</h$level>" );
}

function e2b_list( $items, $ordered = false ) {
	$items = array_filter( $items, function ( $i ) { return trim( wp_strip_all_tags( $i ) ) !== ''; } );
	if ( ! $items ) return '';
	$tag = $ordered ? 'ol' : 'ul';
	$inner = '';
	foreach ( $items as $i ) $inner .= e2b_block( 'list-item', array(), "<li>$i</li>" );
	return e2b_block( 'list', $ordered ? array( 'ordered' => true ) : array(), "<$tag class=\"wp-block-list\">$inner</$tag>" );
}

function e2b_html( $html ) {
	return trim( $html ) === '' ? '' : e2b_block( 'html', array(), trim( $html ) );
}

function e2b_shortcode( $sc ) {
	return e2b_block( 'shortcode', array(), $sc );
}

function e2b_image( $img, $link = '' ) {
	if ( empty( $img['url'] ) ) return '';
	$id    = (int) ( $img['id'] ?? 0 );
	$attrs = array( 'sizeSlug' => 'full', 'linkDestination' => $link ? 'custom' : 'none' );
	if ( $id ) $attrs = array( 'id' => $id ) + $attrs;
	$src  = $id && wp_get_attachment_url( $id ) ? wp_get_attachment_url( $id ) : $img['url'];
	$tag  = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $img['alt'] ?? '' ) . '"' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>';
	if ( $link ) $tag = '<a href="' . esc_url( $link ) . '">' . $tag . '</a>';
	return e2b_block( 'image', $attrs, '<figure class="wp-block-image size-full">' . $tag . '</figure>' );
}

function e2b_details( $summary, $inner, $open = false ) {
	$attrs = $open ? array( 'showContent' => true ) : array();
	return e2b_block( 'details', $attrs, '<details class="wp-block-details"' . ( $open ? ' open' : '' ) . '><summary>' . $summary . '</summary>' . $inner . '</details>' );
}

/** Rich text HTML (Elementor text-editor) → paragraph/heading/list/html blocks. */
function e2b_rich( $html ) {
	$html = wpautop( $html );
	$doc  = new DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?><div id="e2b">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	$root = $doc->getElementById( 'e2b' );
	$out  = '';
	$inline = '';
	$inner = function ( $n ) use ( $doc ) {
		$s = '';
		foreach ( $n->childNodes as $c ) $s .= $doc->saveHTML( $c );
		return $s;
	};
	$flush = function () use ( &$inline, &$out ) {
		$out .= e2b_paragraph( trim( $inline ) );
		$inline = '';
	};
	foreach ( iterator_to_array( $root->childNodes ) as $n ) {
		$tag = $n instanceof DOMElement ? strtolower( $n->tagName ) : '';
		if ( ! $tag || in_array( $tag, array( 'strong', 'em', 'b', 'i', 'a', 'span', 'br', 'u', 'sup', 'sub', 'small', 'img' ), true ) ) {
			$inline .= $doc->saveHTML( $n );
			continue;
		}
		$flush();
		$align = '';
		if ( preg_match( '/text-align:\s*(left|center|right)/', $n->getAttribute( 'style' ), $m ) ) $align = $m[1];
		if ( 'p' === $tag ) {
			$out .= e2b_paragraph( trim( $inner( $n ) ), $align );
		} elseif ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
			$out .= e2b_heading( trim( $inner( $n ) ), (int) $m[1], $align );
		} elseif ( 'ul' === $tag || 'ol' === $tag ) {
			$items = array();
			foreach ( $n->childNodes as $li ) if ( $li instanceof DOMElement && 'li' === strtolower( $li->tagName ) ) $items[] = trim( $inner( $li ) );
			$out .= e2b_list( $items, 'ol' === $tag );
		} elseif ( 'hr' === $tag ) {
			$out .= e2b_block( 'separator', array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>' );
		} else {
			$out .= e2b_html( $doc->saveHTML( $n ) );
		}
	}
	$flush();
	return $out;
}

/** GD widget settings (settings.wp) → [shortcode k='v'] */
function e2b_gd_shortcode( $tag, $wp ) {
	$parts = array();
	foreach ( (array) $wp as $k => $v ) {
		if ( is_array( $v ) ) $v = implode( ',', array_filter( $v, function ( $x ) { return '' !== $x && '0' !== $x; } ) );
		if ( '' === $v || null === $v || 'title' === $k ) continue;
		$parts[] = $k . "='" . str_replace( "'", '&#39;', $v ) . "'";
	}
	return "[$tag " . implode( ' ', $parts ) . ']';
}

function e2b_children( $el, $post_id ) {
	$out = '';
	foreach ( $el['elements'] ?? array() as $c ) $out .= e2b_element( $c, $post_id );
	return $out;
}

function e2b_element( $el, $post_id ) {
	global $report;
	$s = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
	if ( ( $el['elType'] ?? '' ) !== 'widget' ) {
		$kids = $el['elements'] ?? array();
		if ( count( $kids ) === 1 ) return e2b_element( $kids[0], $post_id ); // ponytail: unwrap single-child containers, styling is gone anyway
		$all_containers = $kids && ! array_filter( $kids, function ( $k ) { return ( $k['elType'] ?? '' ) === 'widget'; } );
		if ( $all_containers && ( ( $s['flex_direction'] ?? '' ) === 'row' || ( $el['elType'] ?? '' ) === 'section' ) ) {
			$cols = '';
			foreach ( $kids as $k ) $cols .= e2b_block( 'column', array(), '<div class="wp-block-column">' . e2b_element( $k, $post_id ) . '</div>' );
			return e2b_block( 'columns', array(), '<div class="wp-block-columns">' . $cols . '</div>' );
		}
		$inner = e2b_children( $el, $post_id );
		return $inner === '' ? '' : e2b_block( 'group', array( 'layout' => array( 'type' => 'constrained' ) ), '<div class="wp-block-group">' . $inner . '</div>' );
	}

	$type  = $el['widgetType'] ?? '';
	$align = e2b_align_attr( $s['align'] ?? '' );
	switch ( $type ) {
		case 'heading':
			$t = $s['title'] ?? '';
			if ( ! empty( $s['link']['url'] ) ) $t = '<a href="' . esc_url( $s['link']['url'] ) . '">' . $t . '</a>';
			$size = $s['header_size'] ?? 'h2';
			return preg_match( '/^h([1-6])$/', $size, $m ) ? e2b_heading( $t, (int) $m[1], $align ) : e2b_paragraph( "<strong>$t</strong>", $align );
		case 'animated-headline':
			$mid = $s['highlighted_text'] ?? '';
			if ( ( $s['headline_style'] ?? 'highlight' ) === 'rotate' || ( ! $mid && ! empty( $s['rotating_text'] ) ) ) $mid = strtok( $s['rotating_text'] ?? '', "\n" );
			$size = $s['tag'] ?? 'h3';
			return e2b_heading( trim( ( $s['before_text'] ?? '' ) . ' <strong>' . $mid . '</strong> ' . ( $s['after_text'] ?? '' ) ), preg_match( '/^h([1-6])$/', $size, $m ) ? (int) $m[1] : 3, $align );
		case 'text-editor':
			return e2b_rich( $s['editor'] ?? '' );
		case 'icon-list':
			$items = array();
			foreach ( $s['icon_list'] ?? array() as $i ) {
				$t = $i['text'] ?? '';
				$items[] = ! empty( $i['link']['url'] ) ? '<a href="' . esc_url( $i['link']['url'] ) . '">' . $t . '</a>' : $t;
			}
			return e2b_list( $items );
		case 'nested-accordion':
		case 'nested-tabs':
			$items = 'nested-tabs' === $type ? ( $s['tabs'] ?? array() ) : ( $s['items'] ?? array() );
			$kids  = $el['elements'] ?? array();
			$open_first = 'nested-tabs' === $type || ( $s['default_state'] ?? 'expanded' ) === 'expanded';
			$out = '';
			foreach ( $items as $n => $item ) {
				$title = $item['tab_title'] ?? $item['item_title'] ?? '';
				$out  .= e2b_details( $title, isset( $kids[ $n ] ) ? e2b_element( $kids[ $n ], $post_id ) : '', $open_first && 0 === $n );
			}
			return $out;
		case 'html':
			return e2b_html( $s['html'] ?? '' );
		case 'shortcode':
			return e2b_shortcode( $s['shortcode'] ?? '' );
		case 'divider':
			return e2b_block( 'separator', array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>' );
		case 'image':
			$link = ( $s['link_to'] ?? '' ) === 'custom' ? ( $s['link']['url'] ?? '' ) : '';
			return e2b_image( $s['image'] ?? array(), $link );
		case 'media-carousel':
			$imgs = '';
			foreach ( $s['slides'] ?? array() as $sl ) $imgs .= e2b_image( $sl['image'] ?? array(), ( $sl['image_link_to_type'] ?? '' ) === 'custom' ? ( $sl['image_link_to']['url'] ?? '' ) : '' );
			return $imgs ? e2b_block( 'gallery', array( 'linkTo' => 'none' ), '<figure class="wp-block-gallery has-nested-images columns-default is-cropped">' . $imgs . '</figure>' ) : '';
		case 'video':
			// ponytail: iframe instead of core/embed — oEmbed lookups are outbound HTTP, which 502s this local PHP-FPM
			$url = $s['youtube_url'] ?? '';
			if ( ( $s['video_type'] ?? 'youtube' ) !== 'youtube' || ! preg_match( '~(?:youtu\.be/|v=|embed/)([\w-]{11})~', $url, $m ) ) {
				$report[ $post_id ][] = 'video (non-YouTube) skipped';
				return '';
			}
			return e2b_html( '<iframe width="560" height="315" style="width:100%;aspect-ratio:16/9;height:auto" src="https://www.youtube-nocookie.com/embed/' . $m[1] . '" title="YouTube video" frameborder="0" allow="encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe>' );
		case 'google_maps':
			$zoom = (int) ( $s['zoom']['size'] ?? 10 );
			$h    = (int) ( $s['height']['size'] ?? 300 );
			return e2b_html( '<iframe loading="lazy" style="width:100%;height:' . $h . 'px;border:0" src="https://maps.google.com/maps?q=' . rawurlencode( $s['address'] ?? '' ) . '&amp;t=m&amp;z=' . $zoom . '&amp;output=embed&amp;iw=near" title="' . esc_attr( $s['address'] ?? '' ) . '"></iframe>' );
		case 'wp-widget-gd_listings':
			return e2b_shortcode( e2b_gd_shortcode( 'gd_listings', $s['wp'] ?? array() ) );
		case 'wp-widget-gd_map':
			return e2b_shortcode( e2b_gd_shortcode( 'gd_map', $s['wp'] ?? array() ) );
		case 'loop-carousel':
			if ( ( $s['post_query_post_type'] ?? '' ) === 'gd_place' ) {
				$report[ $post_id ][] = 'loop-carousel → gd_listings carousel (check query)';
				return e2b_shortcode( "[gd_listings post_type='gd_place' post_limit='" . (int) ( $s['posts_per_page'] ?? 6 ) . "' layout='3' with_carousel='1' with_controls='1']" );
			}
			$report[ $post_id ][] = 'loop-carousel → latest posts';
			return "<!-- wp:latest-posts {\"postsToShow\":" . (int) ( $s['posts_per_page'] ?? 6 ) . ",\"displayFeaturedImage\":true} /-->\n";
		case 'posts':
			return '<!-- wp:latest-posts ' . wp_json_encode( array( 'postsToShow' => (int) ( $s['classic_posts_per_page'] ?? 6 ), 'displayPostContent' => true, 'excerptLength' => (int) ( $s['classic_excerpt_length'] ?? 25 ), 'displayFeaturedImage' => true, 'displayPostDate' => true, 'postLayout' => 'grid', 'columns' => 3 ) ) . " /-->\n";
		case 'form':
			$report[ $post_id ][] = 'Elementor Pro form removed — needs a Ninja Form';
			return e2b_html( '<!-- TODO: Elementor Pro form was here; replace with a Ninja Forms shortcode -->' );
		case 'breadcrumbs':
			return '';
		default:
			$report[ $post_id ][] = "unknown widget $type skipped";
			return '';
	}
}

global $wpdb;
$where = "meta_key='_elementor_data' AND p.post_status <> 'trash' AND p.post_type IN ('page','post')";
if ( ! empty( $opt['ids'] ) ) $where .= ' AND p.ID IN (' . implode( ',', array_map( 'intval', explode( ',', $opt['ids'] ) ) ) . ')';
$rows = $wpdb->get_results( "SELECT p.ID, m.meta_value FROM $wpdb->postmeta m JOIN $wpdb->posts p ON p.ID=m.post_id WHERE $where" );

$done = 0;
foreach ( $rows as $r ) {
	$data = json_decode( $r->meta_value, true );
	if ( ! $data ) { $report[ $r->ID ][] = 'empty Elementor data, left as is'; continue; }
	$content = '';
	foreach ( $data as $el ) $content .= e2b_element( $el, $r->ID );
	$content = trim( preg_replace( "/\n{3,}/", "\n\n", $content ) );

	if ( ! empty( $opt['dry'] ) ) {
		wp_mkdir_p( $opt['dry'] );
		file_put_contents( $opt['dry'] . "/{$r->ID}.html", $content );
	} elseif ( ! empty( $opt['write'] ) ) {
		if ( ! get_post_meta( $r->ID, '_hhv_pre_blocks_content', true ) ) {
			add_post_meta( $r->ID, '_hhv_pre_blocks_content', wp_slash( get_post_field( 'post_content', $r->ID ) ) );
		}
		$res = wp_update_post( array( 'ID' => $r->ID, 'post_content' => wp_slash( $content ) ), true );
		if ( is_wp_error( $res ) ) { $report[ $r->ID ][] = 'save failed: ' . $res->get_error_message(); continue; }
	}
	$done ++;
}

foreach ( $report as $id => $notes ) {
	$c = array_count_values( $notes );
	WP_CLI::log( "$id " . get_the_title( $id ) . ': ' . implode( '; ', array_map( function ( $k, $v ) { return $v > 1 ? "$k ×$v" : $k; }, array_keys( $c ), $c ) ) );
}
WP_CLI::success( "$done posts converted" . ( ! empty( $opt['write'] ) ? ' and saved' : ( ! empty( $opt['dry'] ) ? ' (dry run)' : ' (no --dry/--write, nothing saved)' ) ) );
