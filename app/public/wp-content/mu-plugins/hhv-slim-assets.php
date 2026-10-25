<?php
// Drop AyeCode UI's Bootstrap CSS/JS on the front end; the theme styles GeoDirectory output itself.
// ponytail: kills Bootstrap everywhere on the front end; if a GD widget (modal, tooltip, carousel) breaks, re-enable per page.
add_filter( 'ayecode-ui-settings', function ( $s ) {
	if ( ! is_admin() ) {
		$s['css'] = '';
		$s['js']  = '';
	}
	return $s;
} );

// Font Awesome is only used by GD's favourites heart, which the theme doesn't show; saves a render-blocking CDN request.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'font-awesome' );
	wp_dequeue_style( 'font-awesome-shims' );
}, 5001 ); // after AyeCode's priority-5000 enqueue
