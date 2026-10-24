<?php
/** Link to the latest newsletter (front page field "Latest newsletter URL", updated weekly). Renders nothing if empty. */
$url = get_post_meta( hhv_home_id( $block ), 'home_newsletter', true );
if ( ! $url ) {
	return;
}
?>
<p <?php echo get_block_wrapper_attributes(); ?>><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">Read the latest happy hour newsletter</a></p>
