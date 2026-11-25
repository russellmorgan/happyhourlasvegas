<?php
/**
 * Homepage video: the front page's "Featured YouTube video" field, shown with the shared click-to-play player (inc/video.php).
 * No server-side HTTP (the thumbnail is loaded by the browser from YouTube).
 */
$v = hhv_video_data( get_post_meta( hhv_home_id( $block ), 'home_video', true ), array( 'title' => 'Foodie Friday on NBC Las Vegas' ) );
if ( ! $v ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); ?>><?php echo hhv_video_player( $v ); // Escaped in hhv_video_player(). ?></div>
