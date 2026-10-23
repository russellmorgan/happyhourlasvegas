<?php
/**
 * Happy Hour Vegas theme bootstrap.
 *
 * - inc/venue.php   venue + landing data helpers (GeoDirectory table + ACF meta)
 * - inc/fields.php  ACF field groups (venue extras, landing settings, front page)
 * - inc/*.php       every file here is loaded (page-specific helpers)
 * - blocks/*        dynamic blocks (block.json + render.php), previewed in the editor via assets/editor.js
 * - assets/css/*    page-specific CSS, all enqueued
 */

foreach ( glob( __DIR__ . '/inc/*.php' ) as $hhv_inc ) {
	require $hhv_inc;
}

add_action( 'wp_enqueue_scripts', function () {
	$v = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'hhv-fonts', 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;500;600&family=Barlow+Condensed:wght@500;600&family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&display=swap', array(), null );
	wp_enqueue_style( 'hhv', get_stylesheet_uri(), array(), $v );
	foreach ( glob( __DIR__ . '/assets/css/*.css' ) as $file ) {
		wp_enqueue_style( 'hhv-' . basename( $file, '.css' ), get_theme_file_uri( 'assets/css/' . basename( $file ) ), array( 'hhv' ), filemtime( $file ) );
	}
	foreach ( glob( __DIR__ . '/assets/js/*.js' ) as $file ) {
		wp_enqueue_script( 'hhv-' . basename( $file, '.js' ), get_theme_file_uri( 'assets/js/' . basename( $file ) ), array(), filemtime( $file ), array( 'strategy' => 'defer' ) );
	}
} );

// Same look inside the block editor.
add_action( 'after_setup_theme', function () {
	add_editor_style( array( 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;500;600&family=Barlow+Condensed:wght@500;600&family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&display=swap', 'style.css' ) );
} );

add_action( 'init', function () {
	$names = array();
	foreach ( glob( __DIR__ . '/blocks/*/block.json' ) as $json ) {
		$type = register_block_type( dirname( $json ) );
		if ( $type ) {
			$names[] = $type->name;
		}
	}
	// ponytail: one shared editor script previews every block with ServerSideRender; add per-block edit UIs only if a block needs controls beyond ACF fields.
	wp_register_script( 'hhv-editor', get_theme_file_uri( 'assets/editor.js' ), array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor' ), filemtime( __DIR__ . '/assets/editor.js' ), true );
	wp_add_inline_script( 'hhv-editor', 'window.hhvBlocks = ' . wp_json_encode( $names ) . ';', 'before' );
} );
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script( 'hhv-editor' );
} );

add_filter( 'block_categories_all', function ( $cats ) {
	array_unshift( $cats, array( 'slug' => 'hhv', 'title' => 'Happy Hour Vegas' ) );
	return $cats;
} );
