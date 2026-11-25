<?php
/**
 * Single blog posts: breadcrumbs and schema.
 *
 * Breadcrumbs: Home > Top Shelf > Post title, the same trail on the page (blocks/page-head via hhv_content_crumbs())
 * and in Rank Math's BreadcrumbList schema. Top Shelf is the page with the slug "blog" (/blog/).
 *
 * Schema: Rank Math outputs the one JSON-LD graph per post (Organization with logo, WebSite, WebPage, image,
 * BreadcrumbList, Person author, BlogPosting). The theme adds no second copy; it only fills gaps in Rank Math's
 * BlogPosting and adds the post's VideoObject (inc/video.php) to the same graph, linked from BlogPosting.video.
 * Any other VideoObject on a post (Rank Math's Schema tab) is removed so the Video box is the only source.
 */

/** Top Shelf (the blog page) as [ title, url ], or null if that page is missing. */
function hhv_top_shelf() {
	$page = get_page_by_path( 'blog' );
	return $page && 'publish' === $page->post_status ? array( wp_strip_all_tags( get_the_title( $page ) ), get_permalink( $page ) ) : null;
}

// Rank Math's breadcrumb trail for posts (used for its BreadcrumbList schema): Home > Top Shelf > Post title.
add_filter( 'rank_math/frontend/breadcrumb/items', function ( $crumbs ) {
	if ( ! is_singular( 'post' ) || count( $crumbs ) < 2 || ! ( $shelf = hhv_top_shelf() ) ) {
		return $crumbs;
	}
	return array( reset( $crumbs ), array( $shelf[0], $shelf[1], 'hide_in_schema' => false ), end( $crumbs ) );
}, 20 );

/** True when a schema entity has this @type (or one of these types). */
function hhv_schema_is( $entity, $types ) {
	return is_array( $entity ) && array_intersect( (array) ( $entity['@type'] ?? array() ), (array) $types );
}

add_filter( 'rank_math/json_ld', function ( $data ) {
	if ( ! is_singular( 'post' ) ) {
		return $data;
	}
	$post = get_post( get_queried_object_id() );
	$url  = get_permalink( $post );

	// One video source only.
	foreach ( $data as $key => $entity ) {
		if ( hhv_schema_is( $entity, 'VideoObject' ) ) {
			unset( $data[ $key ] );
		}
	}

	$article = null;
	foreach ( $data as $key => $entity ) {
		if ( hhv_schema_is( $entity, array( 'BlogPosting', 'Article', 'NewsArticle' ) ) ) {
			$article = $key;
			break;
		}
	}
	$video = hhv_video( $post->ID );

	if ( null !== $article ) {
		$a = &$data[ $article ];
		$org = home_url( '/' ) . '#organization';
		foreach ( $data as $entity ) {
			if ( hhv_schema_is( $entity, 'Organization' ) && ! empty( $entity['@id'] ) ) {
				$org = $entity['@id'];
			}
		}
		$a['headline']         = $a['headline'] ?? wp_strip_all_tags( get_the_title( $post ) );
		$a['datePublished']    = $a['datePublished'] ?? get_the_date( 'c', $post );
		$a['dateModified']     = $a['dateModified'] ?? get_the_modified_date( 'c', $post );
		$a['mainEntityOfPage'] = $a['mainEntityOfPage'] ?? array( '@id' => $url . '#webpage' );
		$a['publisher']        = $a['publisher'] ?? array( '@id' => $org );
		if ( empty( $a['description'] ) ) {
			$a['description'] = $video['description'] ?? wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: strip_shortcodes( $post->post_content ) ), 40, '...' );
		}
		if ( empty( $a['image'] ) && $video ) {
			$a['image'] = array( '@type' => 'ImageObject', 'url' => $video['thumb'] );
		}
		if ( $video ) {
			$a['video'] = array( '@id' => $url . '#video' );
		}
		unset( $a );
	}

	if ( $video ) {
		$data['hhvVideo'] = hhv_video_schema( $video, $url );
	}

	// Plain text in names (Rank Math leaves "&#8217;" etc. in titles; Google would show them as typed).
	array_walk_recursive( $data, function ( &$value, $key ) {
		if ( is_string( $value ) && in_array( $key, array( 'name', 'headline', 'description', 'caption', 'alternateName' ), true ) ) {
			$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
	} );
	return $data;
}, 99 );
