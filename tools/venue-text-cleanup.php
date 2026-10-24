<?php
/**
 * Venue text clean-up: <h1> in venue content becomes <h2> (the page already has the venue name as its h1) and
 * emoji are removed (style guide: no emoji).
 *
 * wp eval-file tools/venue-text-cleanup.php          dry run: counts per venue
 * wp eval-file tools/venue-text-cleanup.php write    saves; original content kept in postmeta _hhv_pre_cleanup_content
 */

$write = in_array( 'write', $args ?? array(), true );
$emoji = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{2300}-\x{23FF}\x{FE0F}\x{200D}\x{20E3}]/u';
$ids   = get_posts( array( 'post_type' => 'gd_place', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
$tally = array( 'h1' => 0, 'emoji' => 0, 'venues' => 0 );
foreach ( $ids as $id ) {
	$old = get_post_field( 'post_content', $id, 'raw' );
	$new = preg_replace( '#<(/?)h1\b#i', '<$1h2', $old, -1, $h );
	$new = preg_replace( $emoji, '', $new, -1, $e );
	if ( ! $h && ! $e ) {
		continue;
	}
	$new = preg_replace( '/ {2,}/', ' ', $new ); // Gaps left where an emoji sat between words.
	$tally['h1'] += $h;
	$tally['emoji'] += $e;
	++$tally['venues'];
	if ( $write ) {
		if ( ! get_post_meta( $id, '_hhv_pre_cleanup_content', true ) ) {
			update_post_meta( $id, '_hhv_pre_cleanup_content', wp_slash( $old ) );
		}
		// Direct update: skips kses and GeoDirectory's save hooks (they expect the admin form's fields).
		$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_content' => $new ), array( 'ID' => $id ) );
		clean_post_cache( $id );
	}
}
printf( "%s %d h1 tags and %d emoji in %d venues.\n", $write ? 'Fixed' : 'Would fix', $tally['h1'] / 2, $tally['emoji'], $tally['venues'] );
