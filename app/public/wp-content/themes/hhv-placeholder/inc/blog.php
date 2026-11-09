<?php
/**
 * Blog cards: the private "Show On" taxonomy for posts and the [hhv_blog_posts] shortcode.
 *
 * Homepage:  [hhv_blog_posts show_on="homepage" count="3"]  (templates/front-page.html)
 * Blog page: [hhv_blog_posts list="all"]                    (content of the "Blog" page, /blog/)
 * Other pages later: add a "Show On" term (Posts > Show On), then [hhv_blog_posts show_on="that-term-slug"].
 *
 * Attributes:
 *   show_on   term slug(s) for this page, comma separated (default "homepage")
 *   count     cards to show (default 3)
 *   fallback  "latest" (default): fill empty spots with the newest posts. "hide": only ticked posts; no section if none.
 *   title     section heading (default "Top Shelf"; title="" for none), e.g. title="Top Shelf in Summerlin"
 *   tagline   line under the heading (default "Happy hour news, events and local finds."; tagline="" for none)
 *   button    button text under the cards (default "See All Top Shelf"; button="" for none)
 * On the Blog page the heading and tagline come from the page itself (its title and excerpt), styled to match.
 *   list      "all": every post, newest first, 12 per page with page numbers (for the Blog page)
 */

const HHV_SHOW_ON = 'hhv_show_on';

add_action( 'init', function () {
	register_taxonomy( HHV_SHOW_ON, 'post', array(
		'labels'             => array(
			'name'          => 'Show On',
			'singular_name' => 'Show On',
			'menu_name'     => 'Show On',
			'all_items'     => 'All places',
			'add_new_item'  => 'Add new place',
			'edit_item'     => 'Edit place',
			'search_items'  => 'Search places',
			'not_found'     => 'None yet',
		),
		'description'        => 'Where a post appears in blog card sections. Private: no public pages.',
		'public'             => false, // no archive pages, not indexed, not in Rank Math sitemaps
		'publicly_queryable' => false,
		'rewrite'            => false,
		'query_var'          => false,
		'show_ui'            => true,  // Posts > Show On, and the box in the editor sidebar
		'show_in_rest'       => true,  // needed for the block editor sidebar
		'show_in_nav_menus'  => false,
		'show_tagcloud'      => false,
		'show_admin_column'  => true,
		'hierarchical'       => true,  // hierarchical = checkbox list in the editor (not a tag box)
	) );
} );

// Starting terms, created once (on the first admin page load after this code arrives).
add_action( 'admin_init', function () {
	if ( get_option( 'hhv_show_on_terms' ) ) {
		return;
	}
	foreach ( array( 'homepage' => 'Homepage', 'everywhere' => 'Everywhere' ) as $slug => $name ) {
		if ( ! term_exists( $slug, HHV_SHOW_ON ) ) {
			wp_insert_term( $name, HHV_SHOW_ON, array( 'slug' => $slug ) );
		}
	}
	update_option( 'hhv_show_on_terms', 1 );
} );

// Belt and braces: keep it out of Rank Math's sitemap even if its settings change.
add_filter( 'rank_math/sitemap/exclude_taxonomy', function ( $exclude, $taxonomy ) {
	return HHV_SHOW_ON === $taxonomy ? true : $exclude;
}, 10, 2 );

/**
 * Post IDs for a blog card section: "Everywhere" first, then the page's own terms, then (fallback "latest") the newest
 * posts. Each step newest first, no repeats.
 */
function hhv_blog_post_ids( $terms, $count, $fallback = 'latest' ) {
	$ids  = array();
	$base = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'orderby'             => 'date',
		'order'               => 'DESC',
		'fields'              => 'ids',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	$steps = array( array( 'everywhere' ), array_diff( $terms, array( 'everywhere' ) ) );
	if ( 'hide' !== $fallback ) {
		$steps[] = null; // newest overall
	}
	foreach ( $steps as $step ) {
		if ( count( $ids ) >= $count ) {
			break;
		}
		if ( array() === $step ) {
			continue;
		}
		$args = $base + array( 'posts_per_page' => $count - count( $ids ), 'post__not_in' => $ids );
		if ( $step ) {
			$args['tax_query'] = array( array( 'taxonomy' => HHV_SHOW_ON, 'field' => 'slug', 'terms' => $step ) );
		}
		$ids = array_merge( $ids, get_posts( $args ) );
	}
	return array_values( array_unique( $ids ) );
}

/** One blog card: featured image + title, the whole card links to the post. Same look as the venue cards. */
function hhv_blog_card( $id ) {
	$title = get_the_title( $id );
	$thumb = get_post_thumbnail_id( $id );
	$img   = '';
	if ( $thumb ) {
		$alt = trim( (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) );
		$img = wp_get_attachment_image( $thumb, 'medium_large', false, array(
			'alt'      => '' !== $alt ? $alt : wp_strip_all_tags( $title ),
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 700px) 120px, 360px',
		) );
	}
	return sprintf(
		'<a class="hhv-card hhv-blog__card" href="%s"><span class="hhv-img">%s</span><span class="hhv-card__title">%s</span></a>',
		esc_url( get_permalink( $id ) ),
		$img,
		esc_html( $title )
	);
}

add_shortcode( 'hhv_blog_posts', function ( $atts ) {
	$a = shortcode_atts( array(
		'show_on'  => 'homepage',
		'count'    => 3,
		'fallback' => 'latest',
		'title'    => 'Top Shelf',
		'tagline'  => 'Happy hour news, events and local finds.',
		'button'   => 'See All Top Shelf',
		'list'     => '',
	), $atts, 'hhv_blog_posts' );

	$pager = '';
	if ( 'all' === $a['list'] ) {
		$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		$q     = new WP_Query( array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'posts_per_page'      => 12,
			'paged'               => $paged,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
		) );
		$ids   = $q->posts;
		$links = paginate_links( array(
			'total'     => $q->max_num_pages,
			'current'   => $paged,
			'prev_text' => 'Newer',
			'next_text' => 'Older',
		) );
		$pager = $links ? '<nav class="hhv-pager" aria-label="Top Shelf pages">' . $links . '</nav>' : '';
		$a['button'] = '';
	} else {
		$terms = array_filter( array_map( 'sanitize_title', explode( ',', $a['show_on'] ) ) );
		$ids   = hhv_blog_post_ids( $terms, max( 1, (int) $a['count'] ), $a['fallback'] );
	}
	if ( ! $ids ) {
		return ''; // never an empty section
	}

	$blog = get_page_by_path( 'blog' );
	$out  = '<section class="hhv-band hhv-blog' . ( 'all' === $a['list'] ? ' hhv-blog--all' : '' ) . '"><div class="hhv-wrap">';
	if ( 'all' !== $a['list'] && ( '' !== $a['title'] || '' !== $a['tagline'] ) ) {
		$out .= '<div class="hhv-blog__head">';
		$out .= '' !== $a['title'] ? '<h2 class="hhv-display hhv-h2">' . esc_html( $a['title'] ) . '</h2>' : '';
		$out .= '' !== $a['tagline'] ? '<p class="hhv-blog__tagline">' . esc_html( $a['tagline'] ) . '</p>' : '';
		$out .= '</div>';
	}
	$out .= '<div class="hhv-cards hhv-blog__cards">' . implode( '', array_map( 'hhv_blog_card', $ids ) ) . '</div>';
	$out .= $pager;
	if ( '' !== $a['button'] && $blog ) {
		$out .= '<p class="hhv-blog__more"><a class="hhv-btn" href="' . esc_url( get_permalink( $blog ) ) . '">' . esc_html( $a['button'] ) . '</a></p>';
	}
	return $out . '</div></section>';
} );
