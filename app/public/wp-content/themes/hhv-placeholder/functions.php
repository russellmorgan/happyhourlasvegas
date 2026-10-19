<?php
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'hhv-placeholder', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );
