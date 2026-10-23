<?php
/**
 * Sets up the homepage (page 142, template front-page): makes it the front page, reduces its content to the FAQ
 * (core/details blocks in a .hhv-faq group, plus the JSON-LD block) and seeds the ACF fields home_picks / home_video.
 *
 * wp eval-file tools/home-setup.php           dry run: prints what would change
 * wp eval-file tools/home-setup.php write     saves
 *
 * Re-runnable: the FAQ is always rebuilt from postmeta _hhv_pre_reskin_content (the old content, saved on the first write).
 * Re-running resets home_picks / home_video to the values below.
 */

const HHVH_PAGE  = 142;
const HHVH_PICKS = array( 8658, 8493, 8282 ); // Cote (The Strip), Tacos & Beer (Off-Strip), Glitter Gulch Tiki (Downtown).
const HHVH_VIDEO = 'https://www.youtube.com/watch?v=eEzD-Y97ges';

$write = in_array( 'write', $args, true );
if ( ! current_user_can( 'unfiltered_html' ) ) { // Keep JSON-LD <script> blocks: save as an administrator so kses leaves them alone.
	wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 0 );
}

$page = get_post( HHVH_PAGE );
$old  = get_post_meta( HHVH_PAGE, '_hhv_pre_reskin_content', true );
if ( ! $old ) {
	$old = $page->post_content;
}

// Collect the FAQ (details blocks that hold a paragraph, not the link lists of the old menu) and the JSON-LD html blocks.
$faq = array();
$ld  = array();
$walk = function ( $blocks ) use ( &$walk, &$faq, &$ld ) {
	foreach ( $blocks as $b ) {
		if ( 'core/details' === $b['blockName'] && in_array( 'core/paragraph', array_column( $b['innerBlocks'], 'blockName' ), true ) ) {
			$faq[] = $b;
		} elseif ( 'core/html' === $b['blockName'] && false !== strpos( $b['innerHTML'], 'application/ld+json' ) ) {
			$ld[] = $b;
		} else {
			$walk( $b['innerBlocks'] );
		}
	}
};
$walk( parse_blocks( $old ) );

$content = '<!-- wp:group {"className":"hhv-faq","layout":{"type":"default"}} -->' . "\n"
	. '<div class="wp-block-group hhv-faq">' . "\n" . serialize_blocks( $faq ) . "\n</div>\n<!-- /wp:group -->\n\n"
	. serialize_blocks( $ld );

printf( "FAQ questions: %d, JSON-LD blocks: %d, content %d bytes\n", count( $faq ), count( $ld ), strlen( $content ) );
foreach ( HHVH_PICKS as $id ) {
	printf( "pick %d %s\n", $id, get_the_title( $id ) );
}
if ( ! $write ) {
	echo "Dry run. Add `write` to save.\n";
	return;
}

if ( ! get_post_meta( HHVH_PAGE, '_hhv_pre_reskin_content', true ) ) {
	update_post_meta( HHVH_PAGE, '_hhv_pre_reskin_content', wp_slash( $old ) );
}
wp_update_post( wp_slash( array( 'ID' => HHVH_PAGE, 'post_content' => $content ) ) );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', HHVH_PAGE );
update_post_meta( HHVH_PAGE, 'home_picks', array_map( 'strval', HHVH_PICKS ) );
update_post_meta( HHVH_PAGE, '_home_picks', 'field_hhv_home_picks' );
update_post_meta( HHVH_PAGE, 'home_video', HHVH_VIDEO );
update_post_meta( HHVH_PAGE, '_home_video', 'field_hhv_home_video' );
echo "Saved.\n";
