<?php
/**
 * Removes empty spacer paragraphs (<p>&nbsp;</p>, imported from the old site) from posts and pages.
 *
 * wp eval-file tools/strip-spacers.php          dry run: counts per post
 * wp eval-file tools/strip-spacers.php write    saves; original content kept in postmeta _hhv_pre_spacer_content
 */

$write = in_array( 'write', $args ?? array(), true );
$re    = '#<!-- wp:paragraph -->\s*<p>(?:\s|&nbsp;|\x{00A0})*</p>\s*<!-- /wp:paragraph -->\s*#u';
$ids   = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
$total = 0;
foreach ( $ids as $id ) {
	$old = get_post_field( 'post_content', $id, 'raw' );
	$new = preg_replace( $re, '', $old, -1, $n );
	if ( ! $n ) {
		continue;
	}
	$total += $n;
	echo "$id: $n\n";
	if ( $write ) {
		if ( ! get_post_meta( $id, '_hhv_pre_spacer_content', true ) ) {
			update_post_meta( $id, '_hhv_pre_spacer_content', wp_slash( $old ) );
		}
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $new ) ) );
	}
}
echo ( $write ? 'Removed' : 'Would remove' ) . " $total spacer paragraphs.\n";
