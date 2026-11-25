<?php
/**
 * Database side of the homepage "Top Shelf" blog section (run once theme files are in place):
 *   wp eval-file tools/golive-blog-section.php
 * Re-runnable: every step checks first. Then run tools/fix-titles-sitemap.php titles sitemap and `wp rankmath sitemap generate`.
 */
global $wpdb;
$tagline = 'Happy hour news, events and local finds.';

// 1. Homepage schema: close the CollectionPage/ItemList/FAQPage JSON, remove the Foodie Friday VideoObject script.
$home = (int) get_option( 'page_on_front' );
$c    = get_post_field( 'post_content', $home, 'raw' );
preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $c, $m, PREG_OFFSET_CAPTURE );
if ( 2 === count( $m[0] ) && false !== strpos( $m[1][1][0], 'VideoObject' ) ) {
	$main  = $m[1][0][0];
	$fixed = null === json_decode( $main ) ? rtrim( $main ) . "\n      ]\n    }\n  ]\n}\n" : $main;
	$data  = json_decode( $fixed, true );
	if ( ! $data ) {
		WP_CLI::error( 'Schema still does not parse; nothing changed.' );
	}
	$c = rtrim( substr( $c, 0, $m[0][1][1] ) ) . "\n" . ltrim( substr( $c, $m[0][1][1] + strlen( $m[0][1][0] ) ) );
	$c = substr_replace( $c, $fixed, $m[1][0][1], strlen( $main ) );
	$wpdb->update( $wpdb->posts, array( 'post_content' => $c ), array( 'ID' => $home ) ); // direct: wp_update_post would strip <script>
	clean_post_cache( $home );
	WP_CLI::log( '1. Homepage schema fixed, now: ' . implode( ', ', wp_list_pluck( $data['@graph'], '@type' ) ) );
} else {
	WP_CLI::log( '1. Homepage schema: already done (or not as expected), skipped' );
}

// 2. "Show On" starting terms.
foreach ( array( 'homepage' => 'Homepage', 'everywhere' => 'Everywhere' ) as $slug => $name ) {
	if ( ! term_exists( $slug, 'hhv_show_on' ) ) {
		$r = wp_insert_term( $name, 'hhv_show_on', array( 'slug' => $slug ) );
		if ( is_wp_error( $r ) ) {
			WP_CLI::error( 'Term error: ' . $r->get_error_message() );
		}
	}
}
update_option( 'hhv_show_on_terms', 1 );
WP_CLI::log( '2. Show On terms: ' . implode( ', ', get_terms( array( 'taxonomy' => 'hhv_show_on', 'hide_empty' => false, 'fields' => 'names' ) ) ) );

// 3. Turn off the Rank Math redirect blog/ -> the-vegas-social/.
$t   = $wpdb->prefix . 'rank_math_redirections';
$ids = $wpdb->get_col( "SELECT id FROM $t WHERE status = 'active' AND sources LIKE '%\"blog/\"%' AND url_to LIKE '%the-vegas-social%'" );
foreach ( $ids as $id ) {
	$wpdb->update( $t, array( 'status' => 'inactive' ), array( 'id' => $id ) );
}
$wpdb->query( "DELETE FROM {$wpdb->prefix}rank_math_redirections_cache WHERE from_url LIKE 'blog%'" );
WP_CLI::log( '3. /blog/ redirect turned off: ' . ( $ids ? implode( ',', $ids ) : 'none active' ) );

// 4. Blog page (slug stays "blog"): Top Shelf cards, title, tagline (excerpt), Rank Math title/description, index.
$blog = get_page_by_path( 'blog' );
$wpdb->update( $wpdb->posts, array(
	'post_title'   => 'Top Shelf',
	'post_excerpt' => $tagline,
	'post_content' => "<!-- wp:shortcode -->\n[hhv_blog_posts list=\"all\"]\n<!-- /wp:shortcode -->",
), array( 'ID' => $blog->ID ) );
clean_post_cache( $blog->ID );
update_post_meta( $blog->ID, 'rank_math_title', 'Top Shelf | Las Vegas Happy Hour News, Events and Guides' );
update_post_meta( $blog->ID, 'rank_math_description', 'Happy hour news, events and local finds from Happy Hour Vegas. Fresh picks, Foodie Friday and the best deals in town.' );
update_post_meta( $blog->ID, 'rank_math_robots', array( 'index' ) );
WP_CLI::log( "4. Page {$blog->ID} is now 'Top Shelf' at /" . get_post_field( 'post_name', $blog->ID ) . '/' );

// 5. Header menu: "Top Shelf" as the last top-level item.
$nav = get_post( 8763 );
if ( false === strpos( $nav->post_content, '"label":"Top Shelf"' ) ) {
	$link = '<!-- wp:navigation-link {"label":"Top Shelf","type":"page","id":' . $blog->ID . ',"url":"/blog/","kind":"post-type"} /-->';
	$wpdb->update( $wpdb->posts, array( 'post_content' => rtrim( $nav->post_content ) . "\n" . $link ), array( 'ID' => $nav->ID ) );
	clean_post_cache( $nav->ID );
}
$top = array();
foreach ( parse_blocks( get_post_field( 'post_content', 8763 ) ) as $b ) {
	if ( $b['blockName'] ) {
		$top[] = $b['attrs']['label'] ?? '?';
	}
}
WP_CLI::log( '5. Menu: ' . implode( ' | ', $top ) );

// 6. Tick "Everywhere" on the Sunset Station event post.
$event = get_page_by_path( 'the-biggest-happy-hour-in-las-vegas-at-sunset-station-on-national-happy-hour-day', OBJECT, 'post' );
wp_set_object_terms( $event->ID, 'everywhere', 'hhv_show_on', true );
WP_CLI::log( "6. Post {$event->ID} Show On: " . implode( ', ', wp_get_post_terms( $event->ID, 'hhv_show_on', array( 'fields' => 'names' ) ) ) );

// 7. Rank Math: Capitalize Titles off.
$o                      = get_option( 'rank-math-options-titles' );
$o['capitalize_titles'] = 'off';
update_option( 'rank-math-options-titles', $o );
WP_CLI::log( '7. Capitalize Titles: ' . get_option( 'rank-math-options-titles' )['capitalize_titles'] );
