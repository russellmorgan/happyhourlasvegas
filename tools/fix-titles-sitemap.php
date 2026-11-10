<?php
// wp eval-file fix-titles-sitemap.php [titles|sitemap|cosmo]...  Safe to re-run: checks current values first.
global $wpdb;
$do = $args ?: array( 'titles', 'sitemap' );

if ( in_array( 'titles', $do, true ) ) {
	// post ID => array( Rank Math SEO title expected now (or a list of accepted values; '' = none, uses the default template), new SEO title )
	$titles = array(
		4628 => array( 'A Sous Chef and Barista Walk into a Bar', 'A Sous Chef and Barista Walk Into a Bar' ),
		3254 => array( '%title% %page% %sep% Restaurants, menus, hours', '%title% %page% %sep% Restaurants, Menus, Hours' ),
		4292 => array( array( 'Durango Casino Anniversary: A year of Southwest Sizzle Vegas', 'Durango Casino Anniversary: A Year of Southwest Sizzle Vegas' ), 'Durango Casino Anniversary: A Year of Southwest Sizzle' ),
		8811 => array( '', 'Happy Hour by Menu %currentmonth% %currentyear%' ),
		8810 => array( '', 'Happy Hour by Vibe %currentmonth% %currentyear%' ),
		4499 => array( 'Everything you need to know about the Best Las Vegas Wine', 'Everything You Need to Know About the Best Las Vegas Wine' ),
		6631 => array( array( '', 'Mexican Happy Hours in Las Vegas - 45 Verified %currentmonth% %currentyear%' ), 'Mexican Happy Hours in Las Vegas %sep% 45 Verified %currentmonth% %currentyear%' ),
		7928 => array( '', 'Ai Pazzi Pizza %currentmonth% %currentyear%' ),
		6608 => array( '%title% %sep% Las Vegas happy hours with a view', '%title% %sep% Las Vegas Happy Hours with a View' ),
		4768 => array( '%title% %page% %sep% Four Vegas Locations', 'Yard House Las Vegas Happy Hour %page% %sep% Four Vegas Locations' ),
		3241 => array( '%title% %page% %sep% No Happy Hours at OYO', 'OYO Las Vegas Happy Hour %page% %sep% No Happy Hours at OYO' ),
	);
	foreach ( $titles as $id => list( $old, $new ) ) {
		$now = (string) get_post_meta( $id, 'rank_math_title', true );
		if ( $now === $new ) {
			WP_CLI::log( "$id already done" );
		} elseif ( in_array( $now, (array) $old, true ) ) {
			update_post_meta( $id, 'rank_math_title', $new );
			WP_CLI::log( "$id set: $new" );
		} else {
			WP_CLI::warning( "$id SKIPPED, current SEO title is unexpected: '$now'" );
		}
	}
}

if ( in_array( 'sitemap', $do, true ) ) {
	// Sample Page (ID 2): trash only if it is still the WordPress demo page.
	$p = get_post( 2 );
	if ( $p && 'sample-page' === $p->post_name && 'publish' === $p->post_status && false !== strpos( $p->post_content, 'This is an example page' ) ) {
		wp_trash_post( 2 );
		WP_CLI::log( 'Sample Page moved to trash' );
	}
	// Terms and Conditions (empty, redirected to the privacy page): exclude from the Rank Math sitemap.
	$o  = get_option( 'rank-math-options-sitemap' );
	$ex = array_filter( array_map( 'trim', explode( ',', (string) ( $o['exclude_posts'] ?? '' ) ) ) );
	if ( ! in_array( '8807', $ex, true ) ) {
		$ex[]               = '8807';
		$o['exclude_posts'] = implode( ',', $ex );
		update_option( 'rank-math-options-sitemap', $o );
	}
	WP_CLI::log( 'Sitemap excluded posts: ' . $o['exclude_posts'] );
	delete_transient( 'rank_math_sitemap_cache' );
}

if ( in_array( 'cosmo', $do, true ) ) {
	// Staging only: rule 317 lists the Cosmopolitan page's own address as a source (redirect loop). Match live.
	$t   = $wpdb->prefix . 'rank_math_redirections';
	$dst = home_url( '/las-vegas-strip-happy-hours/cosmopolitan-las-vegas-happy-hour/' );
	$src = array(
		array( 'ignore' => '', 'pattern' => 'las-vegas-strip-happy-hours/cosmopolitan-happy-hours/', 'comparison' => 'exact' ),
		array( 'ignore' => '', 'pattern' => 'cosmopolitan-las-vegas-happy-hour/', 'comparison' => 'exact' ),
	);
	$wpdb->update( $t, array( 'sources' => maybe_serialize( $src ) ), array( 'id' => 317 ) );
	foreach ( array( 216, 217, 218, 377, 460 ) as $id ) { // point straight at the final address, like live
		$wpdb->update( $t, array( 'url_to' => $dst ), array( 'id' => $id ) );
	}
	$wpdb->query( "TRUNCATE {$wpdb->prefix}rank_math_redirections_cache" );
	WP_CLI::log( 'Cosmopolitan redirects fixed' );
}
