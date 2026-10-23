<?php
/**
 * "Keep browsing": chip groups of the main location, vibe and menu landings; the current one is active.
 * Attribute `types` (comma list, e.g. "location") limits which landing types show this instance; empty = always.
 */
$l = hhv_landing_context( $block );
if ( $attributes['types'] && $l && ! in_array( $l['type'], array_map( 'trim', explode( ',', $attributes['types'] ) ), true ) ) {
	return;
}
$groups = array( 'location' => 'Happy hour locations', 'vibe' => 'Happy hour vibe', 'menu' => 'Happy hour menus' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-band hhv-band--deep hhv-keep' ) ); ?>>
	<div class="hhv-wrap">
		<h2 class="hhv-display hhv-h2">Keep browsing</h2>
		<?php foreach ( $groups as $type => $title ) : ?>
			<?php $items = hhv_main_landings( $type ); ?>
			<?php if ( $items ) : ?>
				<nav class="hhv-keep__group" aria-label="<?php echo esc_attr( $title ); ?>">
					<p class="hhv-eyebrow"><?php echo esc_html( $title ); ?></p>
					<ul class="hhv-chips">
						<?php
						foreach ( $items as $m ) {
							$active = $l && ( $m['id'] === $l['id'] || ( ! $l['id'] && $l['term'] && ( array( $l['term']->term_id ) === $m['areas'] || array( $l['term']->term_id ) === $m['tags'] ) ) );
							echo hhv_chip( $m['label'], $m['url'], $active ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</ul>
				</nav>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</section>
