<?php
/**
 * Header/footer links are root-relative ("/about-us/"). When the site lives in a subdirectory (staging at /staging),
 * prefix them with the home path so they don't point at the live site. No-op on a root install.
 */

add_filter( 'render_block_core/template-part', function ( $html ) {
	$base = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
	if ( '' === $base ) {
		return $html;
	}
	return preg_replace( '~(href=["\'])/(?!/|' . preg_quote( ltrim( $base, '/' ), '~' ) . '/)~', '$1' . $base . '/', $html );
} );
