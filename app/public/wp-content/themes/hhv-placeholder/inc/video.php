<?php
/**
 * Reusable YouTube video: the "Video" box in the editor, the click-to-play player and its VideoObject schema.
 *
 * Used by: blog posts (blocks/video in templates/single.html, schema in inc/post.php) and the homepage block
 * (blocks/home-video). For listings later: add 'gd_place' to HHV_VIDEO_POST_TYPES, put <!-- wp:hhv/video /--> in
 * templates/single-gd_place.html (or call hhv_video_player() from blocks/venue-about) and add hhv_video_schema()
 * to GeoDirectory's schema with the 'geodir_details_schema' filter (as "subjectOf").
 *
 *   hhv_video( $post_id )           everything about a post's video (null when the URL box is empty)
 *   hhv_video_player( $video )      click-to-play player HTML (thumbnail + play button; YouTube loads on click)
 *   hhv_video_schema( $video, $url ) VideoObject array for JSON-LD
 *
 * Shape: vertical when the link is a /shorts/ link, when YouTube says the video is a Short (checked once when the
 * URL is saved, see hhv_video_check()), or when "Vertical video" is ticked.
 */

/** Post types that get the Video box. */
const HHV_VIDEO_POST_TYPES = array( 'post' );

/** YouTube video ID from a watch / youtu.be / embed / shorts / live URL, else ''. */
function hhv_youtube_id( $url ) {
	return preg_match( '#(?:[?&]v=|youtu\.be/|/embed/|/shorts/|/live/)([\w-]{11})#', (string) $url, $m ) ? $m[1] : '';
}

/**
 * Video data from a URL plus optional details. Returns null when the URL isn't a YouTube link.
 *
 * $args: title, description, date (ISO 8601), credit, vertical (bool, forced), check (array saved by hhv_video_check()).
 */
function hhv_video_data( $url, $args = array() ) {
	$id = hhv_youtube_id( $url );
	if ( ! $id ) {
		return null;
	}
	$args  = wp_parse_args( $args, array( 'title' => '', 'description' => '', 'date' => '', 'credit' => '', 'vertical' => false, 'check' => array() ) );
	$check = is_array( $args['check'] ) && ( $args['check']['id'] ?? '' ) === $id ? $args['check'] : array();
	$vert  = $args['vertical'] || false !== strpos( (string) $url, '/shorts/' ) || ! empty( $check['vertical'] );
	return array(
		'id'          => $id,
		'vertical'    => $vert,
		'title'       => $args['title'],
		'description' => $args['description'],
		'date'        => $args['date'],
		'credit'      => $args['credit'],
		'thumb'       => $check['thumb'] ?? ( 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg' ),
		'watch'       => $vert ? 'https://www.youtube.com/shorts/' . $id : 'https://www.youtube.com/watch?v=' . $id,
		'embed'       => 'https://www.youtube.com/embed/' . $id,
	);
}

/** A post's video from its Video box, with the defaults filled in. Null when the URL is empty. */
function hhv_video( $post_id ) {
	$post = get_post( $post_id );
	$url  = $post ? trim( (string) get_post_meta( $post->ID, 'hhv_video_url', true ) ) : '';
	if ( '' === $url ) {
		return null;
	}
	$meta  = fn( $key ) => trim( (string) get_post_meta( $post->ID, $key, true ) );
	$day   = $meta( 'hhv_video_date' ); // ACF date picker saves Ymd.
	$date  = $day ? DateTime::createFromFormat( '!Ymd', $day, wp_timezone() ) : false;
	$plain = fn( $s ) => trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES, 'UTF-8' ) ) );
	return hhv_video_data( $url, array(
		'title'       => $meta( 'hhv_video_title' ) ?: $plain( get_the_title( $post ) ),
		'description' => $meta( 'hhv_video_description' ) ?: ( $plain( $post->post_excerpt ) ?: wp_trim_words( $plain( strip_shortcodes( $post->post_content ) ), 40, '...' ) ),
		'date'        => $date ? $date->format( 'Y-m-d' ) : get_the_date( 'c', $post ),
		'credit'      => $meta( 'hhv_video_credit' ),
		'vertical'    => (bool) get_post_meta( $post->ID, 'hhv_video_vertical', true ),
		'check'       => get_post_meta( $post->ID, '_hhv_video_check', true ),
	) );
}

/**
 * Click-to-play player: the thumbnail is a plain link to YouTube; assets/js/video.js swaps it for the player on click.
 * $lazy: false for a video near the top of the page (loads the thumbnail straight away).
 */
function hhv_video_player( $v, $lazy = true ) {
	$w = $v['vertical'] ? 540 : 1280;
	$h = $v['vertical'] ? 960 : 720;
	return sprintf(
		'<div class="hhv-yt%s" data-video="%s" data-title="%s"><a class="hhv-yt__play" href="%s" target="_blank" rel="noopener">'
		. '<img src="%s" alt="" width="%d" height="%d"%s decoding="async">'
		. '<span class="hhv-yt__btn"><svg width="18" height="20" viewBox="0 0 18 20" aria-hidden="true"><path d="M0 0l18 10L0 20z" fill="#fff"/></svg></span>'
		. '<span class="hhv-yt__pill">Watch on <b>YouTube</b></span>'
		. '<span class="screen-reader-text">Play video: %s</span></a></div>',
		$v['vertical'] ? ' hhv-yt--vertical' : '',
		esc_attr( $v['id'] ),
		esc_attr( $v['title'] ?: 'Happy Hour Vegas video' ),
		esc_url( $v['watch'] ),
		esc_url( $v['thumb'] ),
		$w,
		$h,
		$lazy ? ' loading="lazy"' : ' fetchpriority="high"',
		esc_html( $v['title'] ?: 'Happy Hour Vegas video' )
	);
}

/**
 * VideoObject for JSON-LD. $page_url: the page the video is shown on (its @id is "<page>#video").
 * No contentUrl: YouTube doesn't give a direct video file; Google uses embedUrl for YouTube videos.
 */
function hhv_video_schema( $v, $page_url ) {
	return array_filter( array(
		'@type'        => 'VideoObject',
		'@id'          => $page_url . '#video',
		'name'         => $v['title'],
		'description'  => $v['description'] ?: $v['title'],
		'thumbnailUrl' => array( $v['thumb'] ),
		'uploadDate'   => $v['date'],
		'embedUrl'     => $v['embed'],
		'inLanguage'   => 'en-US',
	) );
}

/**
 * Asks YouTube once, when the URL is saved: is it a Short (tall), and which thumbnail is the biggest that exists?
 * Saved in _hhv_video_check as [ id, vertical, thumb ]. If YouTube doesn't answer, the page still works
 * (link based shape + standard thumbnail).
 */
function hhv_video_check( $post_id, $url ) {
	$id = hhv_youtube_id( $url );
	if ( ! $id ) {
		delete_post_meta( $post_id, '_hhv_video_check' );
		return;
	}
	$vertical = false !== strpos( $url, '/shorts/' );
	// Asked as a /shorts/ link, YouTube reports a Short as tall (113 x 200) and a regular video as wide.
	$res = wp_remote_get( 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode( 'https://www.youtube.com/shorts/' . $id ), array( 'timeout' => 6 ) );
	$o   = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! empty( $o['width'] ) && ! empty( $o['height'] ) ) {
		$vertical = $o['height'] > $o['width'];
	}
	$thumb = 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg'; // always exists
	foreach ( $vertical ? array( 'oar2' ) : array( 'maxresdefault', 'sddefault' ) as $name ) {
		$try = 'https://i.ytimg.com/vi/' . $id . '/' . $name . '.jpg';
		if ( 200 === wp_remote_retrieve_response_code( wp_remote_head( $try, array( 'timeout' => 4 ) ) ) ) {
			$thumb = $try;
			break;
		}
	}
	update_post_meta( $post_id, '_hhv_video_check', array( 'id' => $id, 'vertical' => $vertical, 'thumb' => $thumb ) );
}

// Run the check whenever the URL is added or changed (editor, WP-CLI or code). Unchanged URLs don't trigger it.
foreach ( array( 'added_post_meta', 'updated_post_meta' ) as $hhv_hook ) {
	add_action( $hhv_hook, function ( $meta_id, $post_id, $key, $value ) {
		if ( 'hhv_video_url' === $key && in_array( get_post_type( $post_id ), HHV_VIDEO_POST_TYPES, true ) ) {
			hhv_video_check( $post_id, (string) $value );
		}
	}, 10, 4 );
}

// The "Video" box in the editor (ACF free; values are plain postmeta named hhv_video_*).
add_action( 'acf/include_fields', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	$has_url = array( array( array( 'field' => 'field_hhv_video_url', 'operator' => '!=empty' ) ) );
	acf_add_local_field_group( array(
		'key'        => 'group_hhv_video',
		'title'      => 'Video',
		'position'   => 'normal',
		'menu_order' => 0,
		'location'   => array_map( fn( $pt ) => array( array( 'param' => 'post_type', 'operator' => '==', 'value' => $pt ) ), HHV_VIDEO_POST_TYPES ),
		'fields'     => array(
			array(
				'key'          => 'field_hhv_video_url',
				'name'         => 'hhv_video_url',
				'label'        => 'Video URL',
				'type'         => 'url',
				'placeholder'  => 'https://www.youtube.com/watch?v=...',
				'instructions' => 'Paste a YouTube or YouTube Shorts link. Leave empty for no video.',
			),
			array(
				'key'               => 'field_hhv_video_title',
				'name'              => 'hhv_video_title',
				'label'             => 'Video title',
				'type'              => 'text',
				'instructions'      => 'Optional. Leave blank to use the post title.',
				'conditional_logic' => $has_url,
			),
			array(
				'key'               => 'field_hhv_video_description',
				'name'              => 'hhv_video_description',
				'label'             => 'Short video description',
				'type'              => 'textarea',
				'rows'              => 2,
				'instructions'      => 'Optional. One or two sentences for Google. Leave blank to use the post excerpt.',
				'conditional_logic' => $has_url,
			),
			array(
				'key'               => 'field_hhv_video_date',
				'name'              => 'hhv_video_date',
				'label'             => 'Upload date',
				'type'              => 'date_picker',
				'display_format'    => 'F j, Y',
				'return_format'     => 'Y-m-d',
				'first_day'         => 0,
				'instructions'      => 'The day the video went up on YouTube. Leave blank to use the post date.',
				'conditional_logic' => $has_url,
			),
			array(
				'key'               => 'field_hhv_video_credit',
				'name'              => 'hhv_video_credit',
				'label'             => 'Credit line',
				'type'              => 'text',
				'placeholder'       => 'As seen on NBC News 3 Foodie Friday',
				'instructions'      => 'Optional. Shown in small text under the video.',
				'conditional_logic' => $has_url,
			),
			array(
				'key'               => 'field_hhv_video_vertical',
				'name'              => 'hhv_video_vertical',
				'label'             => 'Vertical video',
				'type'              => 'true_false',
				'ui'                => 1,
				'instructions'      => 'Shorts are found automatically. Only switch this on if a vertical video shows up wide.',
				'conditional_logic' => $has_url,
			),
		),
	) );
} );
