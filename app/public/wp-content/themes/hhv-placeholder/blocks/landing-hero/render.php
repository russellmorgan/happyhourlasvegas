<?php
/**
 * Landing hero. Location: eyebrow, title, last update + verified listings, neighbourhood chips.
 * Vibe/menu: eyebrow, title, intro (page excerpt / term description) and the featured venue on the right.
 */
$l = hhv_landing_context( $block );
if ( ! $l ) {
	return;
}
$eyebrow = array( 'location' => 'Location', 'vibe' => 'Vibe', 'menu' => 'Menu', 'search' => 'Search' )[ $l['type'] ] ?? '';
$ids     = 'search' === $l['mode'] ? array() : hhv_landing_venue_ids( $l );

if ( 'location' === $l['type'] ) {
	// Chips: children of the landing's area, or (for a leaf like a casino) its siblings, or (for a group page) its areas.
	$chips = array();
	$intro = '';
	$terms = array_filter( array_map( fn( $id ) => get_term( $id, 'gd_placecategory' ), $l['areas'] ), fn( $t ) => $t instanceof WP_Term );
	if ( 1 === count( $terms ) ) {
		$term  = reset( $terms );
		$kids  = get_terms( array( 'taxonomy' => 'gd_placecategory', 'parent' => $term->term_id, 'hide_empty' => true ) );
		if ( $kids ) {
			$intro = 'Neighborhoods in ' . $term->name;
			$chips = $kids;
		} elseif ( $term->parent ) {
			$parent = get_term( $term->parent, 'gd_placecategory' );
			$intro  = 'More in ' . $parent->name;
			$chips  = array_merge( array( $parent ), get_terms( array( 'taxonomy' => 'gd_placecategory', 'parent' => $parent->term_id, 'hide_empty' => true ) ) );
		}
	} elseif ( $terms ) {
		$intro = 'Included';
		$chips = $terms;
	}
	$current = 1 === count( $l['areas'] ) ? $l['areas'][0] : 0;
	$updated = $ids ? get_post_modified_time( 'F j, Y', false, $ids[0] ) : '';
	?>
	<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-lhero hhv-lhero--location' ) ); ?>>
		<div class="hhv-wrap">
			<p class="hhv-lhero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 class="hhv-display hhv-hero hhv-lhero__title"><?php echo esc_html( $l['heading'] ); ?></h1>
			<div class="hhv-lhero__stats">
				<?php if ( $updated ) : ?>
					<p><span class="hhv-lhero__stat"><?php echo esc_html( $updated ); ?></span><span class="hhv-label">Last update</span></p>
				<?php endif; ?>
				<p><span class="hhv-lhero__stat"><?php echo esc_html( number_format_i18n( count( $ids ) ) ); ?></span><span class="hhv-label">Verified listings</span></p>
			</div>
			<?php if ( $chips ) : ?>
				<nav class="hhv-lhero__hoods" aria-label="<?php echo esc_attr( $intro ); ?>">
					<p class="hhv-eyebrow"><?php echo esc_html( $intro ); ?></p>
					<ul class="hhv-chips hhv-chips--outline">
						<?php
						foreach ( $chips as $t ) {
							echo hhv_chip( $t->name, hhv_area_url( $t ), $t->term_id === $current ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return;
}

$featured = 'search' === $l['mode'] ? 0 : hhv_landing_featured( $l );
$v        = $featured ? hhv_venue( $featured ) : null;
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-lhero hhv-lhero--' . esc_attr( $l['type'] ) . ( $v ? ' has-featured' : '' ) ) ); ?>>
	<div class="hhv-wrap hhv-lhero__grid">
		<div class="hhv-lhero__text">
			<p class="hhv-lhero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 class="hhv-display hhv-hero hhv-lhero__title"><?php echo esc_html( $l['heading'] ); ?></h1>
			<?php if ( $l['intro'] ) : ?>
				<p class="hhv-lhero__intro"><?php echo esc_html( $l['intro'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $v ) : ?>
			<a class="hhv-lhero__feature" href="<?php echo esc_url( $v['url'] ); ?>">
				<?php echo hhv_img( $v['image'], '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="hhv-lhero__feature-text">
					<span class="hhv-eyebrow"><?php echo esc_html( implode( ' · ', array_filter( array( 'Featured', $v['hood']->name ?? $v['area']->name ?? '' ) ) ) ); ?></span>
					<span class="hhv-lhero__feature-name"><?php echo esc_html( $v['name'] ); ?></span>
				</span>
			</a>
		<?php endif; ?>
	</div>
</section>
