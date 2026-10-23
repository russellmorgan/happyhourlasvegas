<?php
/**
 * "On this page" rail: links to the H2s of the post (ids are added by the the_content filter in inc/content.php).
 * Sticky list on desktop, collapsed <details> on mobile (assets/js/content.js).
 */
$post_id = is_singular() ? get_queried_object_id() : ( defined( 'REST_REQUEST' ) ? ( $block->context['postId'] ?? 0 ) : 0 );
$heads   = $post_id ? hhv_content_headings( $post_id ) : array();
if ( count( $heads ) < 3 ) {
	return;
}
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'hhv-toc', 'aria-label' => 'On this page' ) ); ?>>
	<details open>
		<summary class="hhv-toc__title">On this page</summary>
		<ol class="hhv-toc__list">
			<?php foreach ( $heads as list( $id, $text ) ) : ?>
				<li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $text ); ?></a></li>
			<?php endforeach; ?>
		</ol>
	</details>
</nav>
