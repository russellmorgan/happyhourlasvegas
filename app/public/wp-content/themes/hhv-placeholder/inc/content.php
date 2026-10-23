<?php
/**
 * Content / utility pages (page, single, index, archive, search, 404).
 *
 * - H2 anchors + "On this page" data (blocks/toc, blocks/page-head)
 * - Block styles and patterns for the design's content blocks (assets/css/content.css)
 * - Content-page quirks for imported pages (leading H1, old "H1 + intro" columns)
 */

/** Pages that use the content look: not the front page, not landing pages. */
function hhv_content_is_page() {
	return is_singular( 'page' ) && ! is_front_page() && 'page-landing' !== get_page_template_slug();
}

function hhv_content_h2_id( $text, &$seen ) {
	$id = sanitize_title( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES ) ) ?: 'section';
	$n  = $seen[ $id ] = ( $seen[ $id ] ?? 0 ) + 1;
	return $n > 1 ? "$id-$n" : $id;
}

/** H2s of a post as [ [id, text], ... ], from the block tree (same ids the_content filter below adds). */
function hhv_content_headings( $post_id ) {
	$out  = array();
	$seen = array();
	$walk = function ( $blocks ) use ( &$walk, &$out, &$seen ) {
		foreach ( $blocks as $b ) {
			if ( 'core/heading' === $b['blockName'] && 2 === (int) ( $b['attrs']['level'] ?? 2 ) ) {
				$text = trim( html_entity_decode( wp_strip_all_tags( $b['innerHTML'] ), ENT_QUOTES ) );
				if ( $text ) {
					$id    = $b['attrs']['anchor'] ?? hhv_content_h2_id( $text, $seen );
					$out[] = array( $id, $text );
				}
			}
			$walk( $b['innerBlocks'] );
		}
	};
	$walk( parse_blocks( (string) get_post_field( 'post_content', $post_id ) ) );
	return $out;
}

// Add ids to H2s that have none (after do_blocks at 9, shortcodes at 11).
add_filter( 'the_content', function ( $html ) {
	$seen = array();
	return preg_replace_callback( '#<h2(\s[^>]*)?>(.*?)</h2>#is', function ( $m ) use ( &$seen ) {
		if ( preg_match( '/\sid=/', $m[1] ) ) {
			return $m[0];
		}
		return '<h2 id="' . esc_attr( hhv_content_h2_id( $m[2], $seen ) ) . '"' . $m[1] . '>' . $m[2] . '</h2>';
	}, $html );
}, 12 );

// Title band already shows the page title as the H1: drop a leading content H1, and flatten the
// imported "H1 | intro" two-column row to just the intro.
add_filter( 'render_block', function ( $html, $block ) {
	if ( ! hhv_content_is_page() || ! in_the_loop() ) {
		return $html;
	}
	if ( 'core/heading' === $block['blockName'] && 1 === (int) ( $block['attrs']['level'] ?? 2 ) ) {
		return '';
	}
	if ( 'core/columns' === $block['blockName'] && 2 === count( $block['innerBlocks'] ) ) {
		$first = $block['innerBlocks'][0]['innerBlocks'];
		if ( 1 === count( $first ) && 'core/heading' === $first[0]['blockName'] && 1 === (int) ( $first[0]['attrs']['level'] ?? 2 ) ) {
			return '<div class="hhv-lede">' . implode( '', array_map( 'render_block', $block['innerBlocks'][1]['innerBlocks'] ) ) . '</div>';
		}
	}
	return $html;
}, 10, 2 );

/** Reading time, shown only when it is worth saying. */
function hhv_content_read_minutes( $post_id ) {
	return (int) ceil( str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) ) / 220 );
}

/** Breadcrumb trail as [ [label, url|null], ... ]. */
function hhv_content_crumbs( $title, $post = null ) {
	$trail = array( array( 'Home', home_url( '/' ) ) );
	if ( $post && 'page' === $post->post_type ) {
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $id ) {
			$trail[] = array( get_the_title( $id ), get_permalink( $id ) );
		}
	}
	$trail[] = array( $title, null );
	return $trail;
}

add_action( 'init', function () {
	// Block styles (look in assets/css/content.css).
	$styles = array(
		'core/group'     => array( 'hhv-callout' => 'Callout', 'hhv-standard' => 'Standard (double rule)' ),
		'core/list'      => array( 'hhv-steps' => 'Steps', 'hhv-stats' => 'Stat band', 'hhv-partners' => 'Partner list', 'hhv-contact' => 'Contact card' ),
		'core/paragraph' => array( 'hhv-pull' => 'Pull line', 'hhv-dek' => 'Dek' ),
		'core/button'    => array( 'hhv-outline' => 'Outline' ),
	);
	foreach ( $styles as $block => $list ) {
		foreach ( $list as $name => $label ) {
			register_block_style( $block, array( 'name' => $name, 'label' => $label ) );
		}
	}

	register_block_pattern_category( 'hhv', array( 'label' => 'Happy Hour Vegas' ) );

	$item = function ( $html ) {
		return "<!-- wp:list-item -->\n<li>$html</li>\n<!-- /wp:list-item -->\n";
	};
	$list = function ( $style, $items, $ordered = false ) use ( $item ) {
		$tag   = $ordered ? 'ol' : 'ul';
		$attrs = wp_json_encode( array_filter( array( 'ordered' => $ordered, 'className' => "is-style-$style" ) ) );
		return "<!-- wp:list $attrs -->\n<$tag class=\"wp-block-list is-style-$style\">\n" . implode( '', array_map( $item, $items ) ) . "</$tag>\n<!-- /wp:list -->";
	};
	$h2 = function ( $t ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$t</h2>\n<!-- /wp:heading -->\n\n";
	};
	$p = function ( $t, $cls = '' ) {
		$a = $cls ? ' ' . wp_json_encode( array( 'className' => $cls ) ) : '';
		$c = $cls ? " class=\"$cls\"" : '';
		return "<!-- wp:paragraph$a -->\n<p$c>$t</p>\n<!-- /wp:paragraph -->";
	};
	$group = function ( $style, $inner ) {
		return "<!-- wp:group {\"className\":\"is-style-$style\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group is-style-$style\">\n$inner\n</div>\n<!-- /wp:group -->";
	};

	$patterns = array(
		'callout'  => array( 'Callout', $group( 'hhv-callout', $p( 'What we&#8217;re not', 'hhv-label' ) . "\n" . $p( 'A short note that should stand out from the text around it.' ) ) ),
		'standard' => array( 'Our standard (double rule)', $group( 'hhv-standard', $p( 'Our standard', 'hhv-label' ) . "\n" . $p( 'One sentence we stand behind.' ) ) ),
		'pull'     => array( 'Pull line', $p( 'So we showed up.', 'is-style-hhv-pull' ) ),
		'steps'    => array( 'Steps (3 cards)', $list( 'hhv-steps', array( '<strong>Step one</strong> What happens first.', '<strong>Step two</strong> What happens next.', '<strong>Step three</strong> How it ends.' ), true ) ),
		'stats'    => array( 'Stat band', $list( 'hhv-stats', array( '<strong>478</strong> verified happy hour listings', '<strong>125+</strong> on the Las Vegas Strip alone', '<strong>75,000</strong> readers each month' ) ) ),
		'partners' => array( 'Partner list', $list( 'hhv-partners', array( '<strong>Partner name</strong> One sentence on what they do with us.', '<strong>Another partner</strong> One sentence on the relationship.' ) ) ),
		'contact'  => array( 'Contact card', $list( 'hhv-contact', array( '<strong>Email</strong> <a href="mailto:happy@happyhourvegas.com">happy@happyhourvegas.com</a>', '<strong>Instagram</strong> DM us @happyhourvegas' ) ) ),
		'page'     => array( 'Content page starter', $h2( 'The story' ) . $p( 'Open with the plain version.' ) . "\n\n" . $h2( 'The numbers' ) . $list( 'hhv-stats', array( '<strong>478</strong> verified listings', '<strong>75,000</strong> readers each month' ) ) . "\n\n" . $h2( 'Get in touch' ) . $list( 'hhv-contact', array( '<strong>Email</strong> <a href="mailto:happy@happyhourvegas.com">happy@happyhourvegas.com</a>' ) ) ),
	);
	foreach ( $patterns as $slug => list( $title, $content ) ) {
		register_block_pattern( "hhv/content-$slug", array( 'title' => $title, 'categories' => array( 'hhv' ), 'content' => $content ) );
	}
} );
