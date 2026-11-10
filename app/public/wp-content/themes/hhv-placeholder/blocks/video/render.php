<?php
/**
 * Post video: the current post's Video box (inc/video.php). Nothing at all when the Video URL is empty.
 * Schema for it is added to the page's JSON-LD in inc/post.php.
 */
$post_id = is_singular() ? get_queried_object_id() : ( defined( 'REST_REQUEST' ) ? (int) ( $block->context['postId'] ?? 0 ) : 0 );
$v       = $post_id ? hhv_video( $post_id ) : null;
if ( ! $v ) {
	return;
}
?>
<figure <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-video' . ( $v['vertical'] ? ' hhv-video--vertical' : '' ) ) ); ?>>
	<?php echo hhv_video_player( $v, false ); // Escaped in hhv_video_player(). ?>
	<?php if ( $v['credit'] ) : ?><figcaption class="hhv-video__credit"><?php echo esc_html( $v['credit'] ); ?></figcaption><?php endif; ?>
</figure>
