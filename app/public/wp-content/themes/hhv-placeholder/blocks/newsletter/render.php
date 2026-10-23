<?php
/**
 * Newsletter signup form.
 * ponytail: posts to the URL from the `hhv_newsletter_action` filter (default "#"); wire it to the newsletter provider when chosen.
 */
$action = apply_filters( 'hhv_newsletter_action', '#' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-newsletter' ) ); ?>>
	<?php if ( $attributes['title'] ) : ?><p class="hhv-newsletter__title"><?php echo esc_html( $attributes['title'] ); ?></p><?php endif; ?>
	<form action="<?php echo esc_url( $action ); ?>" method="post">
		<?php $hhv_id = wp_unique_id( 'hhv-email-' ); ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $hhv_id ); ?>">Email address</label>
		<input class="hhv-input" id="<?php echo esc_attr( $hhv_id ); ?>" type="email" name="email" required placeholder="Your email address" autocomplete="email">
		<button class="hhv-btn" type="submit"><?php echo esc_html( $attributes['button'] ); ?></button>
	</form>
	<?php if ( $attributes['note'] ) : ?><p class="hhv-newsletter__note"><?php echo esc_html( $attributes['note'] ); ?></p><?php endif; ?>
</div>
