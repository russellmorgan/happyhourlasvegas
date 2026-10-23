<?php
/**
 * Click-to-play YouTube embed: the thumbnail is a plain link to YouTube; assets/js/home.js swaps it for the iframe.
 * No server-side HTTP (the thumbnail is loaded by the browser from img.youtube.com).
 */
$id = hhv_youtube_id( get_post_meta( hhv_home_id( $block ), 'home_video', true ) );
if ( ! $id ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-yt', 'data-video' => $id ) ); ?>>
	<a class="hhv-yt__play" href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $id ); ?>" target="_blank" rel="noopener">
		<img src="<?php echo esc_url( 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg' ); ?>" alt="" loading="lazy" decoding="async">
		<span class="hhv-yt__btn"><svg width="18" height="20" viewBox="0 0 18 20" aria-hidden="true"><path d="M0 0l18 10L0 20z" fill="#fff"/></svg></span>
		<span class="hhv-yt__pill">Watch on <b>YouTube</b></span>
		<span class="screen-reader-text">Play the video</span>
	</a>
</div>
