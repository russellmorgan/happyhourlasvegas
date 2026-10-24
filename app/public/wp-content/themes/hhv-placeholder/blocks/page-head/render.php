<?php
/**
 * Title band for content pages, posts, archives, search and 404.
 * Dek = the manual excerpt (pages and posts only). Meta = last updated + reading time.
 */
$post = null;
// Front end: only singular views (template-level postId context can leak from the first loop post). Editor preview: the edited post.
if ( is_singular() ) {
	$post = get_post( get_queried_object_id() );
} elseif ( defined( 'REST_REQUEST' ) && ! empty( $block->context['postId'] ) ) {
	$post = get_post( $block->context['postId'] );
}

$eyebrow = $dek = $meta = '';
if ( $post ) {
	$title = get_the_title( $post );
	$dek   = $post->post_excerpt;
	if ( 'post' === $post->post_type ) {
		$cats    = get_the_category( $post->ID );
		$eyebrow = $cats && 'uncategorized' !== $cats[0]->slug ? $cats[0]->name : '';
	} elseif ( $post->post_parent ) {
		$eyebrow = get_the_title( $post->post_parent );
	}
	$bits = array();
	// ponytail: edits made during the Gutenberg migration (to 2026-10-25) don't count as updates; show the original date until a real edit.
	$updated = $post->post_modified > '2026-10-25' ? get_the_modified_date( 'F Y', $post ) : get_the_date( 'F Y', $post );
	$bits[]  = 'post' === $post->post_type ? get_the_date( 'F j, Y', $post ) : 'Last updated ' . $updated;
	$mins   = hhv_content_read_minutes( $post->ID );
	if ( $mins >= 3 ) {
		$bits[] = $mins . ' min read';
	}
	$meta = implode( '<span class="hhv-pagehead__dot">&middot;</span>', array_map( 'esc_html', $bits ) );
} elseif ( is_search() ) {
	$title = 'Search: ' . get_search_query();
} elseif ( is_404() ) {
	$title = 'Page not found';
	$dek   = 'That page has moved on, or never existed. Try the search or head back to the listings.';
} elseif ( is_archive() || is_home() ) {
	$title = is_home() ? 'Latest' : wp_strip_all_tags( get_the_archive_title() );
	$dek   = wp_strip_all_tags( get_the_archive_description() );
} else {
	$title = get_bloginfo( 'name' );
}
$trail = hhv_content_crumbs( $title, $post );
?>
<header <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-pagehead' ) ); ?>>
	<div class="hhv-wrap">
		<nav class="hhv-crumbs" aria-label="Breadcrumb">
			<?php foreach ( $trail as $i => list( $label, $url ) ) : ?>
				<?php if ( $i ) : ?><span class="sep">&rsaquo;</span><?php endif; ?>
				<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a><?php else : ?><span aria-current="page"><?php echo esc_html( $label ); ?></span><?php endif; ?>
			<?php endforeach; ?>
		</nav>
		<?php if ( $eyebrow ) : ?><p class="hhv-eyebrow hhv-pagehead__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
		<h1 class="hhv-display hhv-hero hhv-pagehead__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $dek ) : ?><p class="hhv-pagehead__dek"><?php echo esc_html( $dek ); ?></p><?php endif; ?>
		<?php if ( $meta ) : ?><p class="hhv-pagehead__meta"><?php echo $meta; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></p><?php endif; ?>
	</div>
</header>
