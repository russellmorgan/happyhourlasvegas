<?php
/**
 * Newsletter signup form.
 * Posts to the BigMailer signup form used on the live site (opens BigMailer's confirmation in a new tab).
 */
$action = apply_filters( 'hhv_newsletter_action', 'https://app.bigmailer.io/t/f/b67e1e5f-3f2b-4fd3-a2b8-2efd1d636529' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-newsletter' ) ); ?>>
	<?php if ( $attributes['title'] ) : ?><p class="hhv-newsletter__title"><?php echo esc_html( $attributes['title'] ); ?></p><?php endif; ?>
	<form action="<?php echo esc_url( $action ); ?>" method="post" target="_blank">
		<?php $hhv_id = wp_unique_id( 'hhv-email-' ); ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $hhv_id ); ?>">Email address</label>
		<input class="hhv-input" id="<?php echo esc_attr( $hhv_id ); ?>" type="email" name="field[ad926654-ea73-4bf9-a1df-30fcfbeee1a2]" required placeholder="Your email address" autocomplete="email">
		<button class="hhv-btn" type="submit"><?php echo esc_html( $attributes['button'] ); ?></button>
	</form>
	<?php if ( $attributes['note'] ) : ?><p class="hhv-newsletter__note"><?php echo esc_html( $attributes['note'] ); ?></p><?php endif; ?>
</div>
