<?php
/**
 * Venue grid for a landing or GD archive: 12 per page (?pg=), filters as plain GET links/forms.
 * Location: "All vibes" / "All menus" selects (?vibe= / ?menu= landing slugs). Vibe/menu: area chips (?area= term slug).
 * GD search: the main query as GeoDirectory built it (its own paging).
 */
$l = hhv_landing_context( $block );
if ( ! $l ) {
	return;
}
$f        = hhv_landing_filters();
$per_page = 12;

if ( 'search' === $l['mode'] ) {
	global $wp_query;
	$q       = $wp_query;
	$current = max( 1, (int) get_query_var( 'paged' ) );
	$per_page = (int) $q->get( 'posts_per_page' ) ?: $per_page;
} elseif ( hhv_landing_is_empty( $l ) ) {
	$q       = new WP_Query();
	$current = 1;
} else {
	$tax = array();
	if ( 'location' !== $l['type'] && $f['area'] ) {
		$tax[] = array( 'taxonomy' => 'gd_placecategory', 'terms' => array( $f['area']->term_id ), 'include_children' => true );
	}
	foreach ( array( 'vibe', 'menu' ) as $k ) {
		if ( 'location' === $l['type'] && $f[ $k ] && $f[ $k ]['tags'] ) {
			$tax[] = array( 'taxonomy' => 'gd_place_tags', 'terms' => $f[ $k ]['tags'] );
		}
	}
	$current = $f['pg'];
	$extra   = array( 'posts_per_page' => $per_page, 'paged' => $current );
	if ( $tax ) { // hhv_landing_query_args() would replace its own tax_query with an empty one.
		$extra['tax_query'] = $tax;
	}
	$q = new WP_Query( hhv_landing_query_args( $l, $extra ) );
}

$total = (int) $q->found_posts;
$first = ( $current - 1 ) * $per_page + 1;
$last  = min( $total, $current * $per_page );
$count = $total ? sprintf( 'Showing listings %s–%s of %s', number_format_i18n( $first ), number_format_i18n( $last ), number_format_i18n( $total ) ) : 'No listings found.';
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-vgrid', 'id' => 'listings' ) ); ?>>
	<?php if ( in_array( $l['type'], array( 'vibe', 'menu' ), true ) ) : ?>
		<?php $areas = hhv_landing_area_options( $l ); ?>
		<?php if ( count( $areas ) > 1 ) : ?>
			<nav class="hhv-wrap hhv-vgrid__chips" aria-label="Filter by area">
				<ul class="hhv-chips hhv-chips--outline">
					<?php
					echo hhv_chip( 'All areas', hhv_landing_url( array( 'area' => null ) ), ! $f['area'] ); // phpcs:ignore WordPress.Security.EscapeOutput
					foreach ( $areas as list( $t ) ) {
						echo hhv_chip( $t->name, hhv_landing_url( array( 'area' => $t->slug ) ), $f['area'] && $f['area']->term_id === $t->term_id ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</ul>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

	<div class="hhv-vgrid__bar">
		<div class="hhv-wrap hhv-vgrid__barin">
			<p class="hhv-vgrid__count" aria-live="polite"><?php echo esc_html( $count ); ?></p>
			<?php if ( 'location' === $l['type'] && ! hhv_landing_is_empty( $l ) ) : ?>
				<form class="hhv-vgrid__filters" method="get" action="<?php echo esc_url( strtok( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), '?' ) ); ?>#listings">
					<?php
					foreach ( array( 'vibe' => 'All vibes', 'menu' => 'All menus' ) as $k => $all ) :
						$opts = hhv_landing_tag_options( $l, $k );
						if ( ! $opts ) {
							continue;
						}
						?>
						<label class="screen-reader-text" for="hhv-filter-<?php echo esc_attr( $k ); ?>">Filter by <?php echo esc_html( $k ); ?></label>
						<span class="hhv-select">
							<select id="hhv-filter-<?php echo esc_attr( $k ); ?>" name="<?php echo esc_attr( $k ); ?>">
								<option value=""><?php echo esc_html( $all ); ?></option>
								<?php foreach ( $opts as list( $m, $n ) ) : ?>
									<?php $slug = get_post_field( 'post_name', $m['id'] ); ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f[ $k ] && $f[ $k ]['id'] === $m['id'] ); ?>><?php echo esc_html( $m['label'] . " ($n)" ); ?></option>
								<?php endforeach; ?>
							</select>
						</span>
					<?php endforeach; ?>
					<button class="hhv-btn hhv-vgrid__go" type="submit">Filter</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<div class="hhv-wrap hhv-vgrid__body">
		<?php if ( $q->have_posts() ) : ?>
			<div class="hhv-cards hhv-vgrid__cards">
				<?php
				foreach ( $q->posts as $p ) {
					$v = hhv_venue( $p );
					if ( $v ) {
						echo hhv_venue_card( $p, array( 'place' => $v['hood']->name ?? $v['area']->name ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
				?>
			</div>
			<?php
			if ( $q->max_num_pages > 1 ) {
				$args = array(
					'current'   => $current,
					'total'     => $q->max_num_pages,
					'mid_size'  => 1,
					'prev_text' => 'Prev',
					'next_text' => 'Next',
				);
				if ( 'search' !== $l['mode'] ) {
					$args['base']   = str_replace( 'PGNUM', '%#%', hhv_landing_url( array( 'pg' => 'PGNUM' ) ) );
					$args['format'] = '';
				}
				echo '<nav class="hhv-pager" aria-label="Listings pages">' . paginate_links( $args ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		<?php else : ?>
			<p class="hhv-vgrid__empty">
				<?php echo $f['area'] || $f['vibe'] || $f['menu'] ? 'Nothing matches those filters yet. <a href="' . esc_url( hhv_landing_url( array( 'area' => null, 'vibe' => null, 'menu' => null ) ) ) . '">Show all</a>' : 'We haven’t verified any happy hours here yet. Check back soon.'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</p>
		<?php endif; ?>
	</div>
</section>
