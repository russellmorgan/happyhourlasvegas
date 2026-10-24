<?php
/**
 * ACF field groups (ACF free: no repeaters/options pages). Values are plain postmeta, read by inc/venue.php.
 */

add_action( 'acf/include_fields', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'      => 'group_hhv_venue',
		'title'    => 'Happy Hour Vegas: card + homepage copy',
		'position' => 'side',
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'gd_place' ) ) ),
		'fields'   => array(
			array(
				'key'          => 'field_hhv_featured',
				'name'         => 'hhv_featured',
				'label'        => 'Featured on homepage',
				'type'         => 'true_false',
				'ui'           => 1,
				'instructions' => 'Shows in "Our recommendations" on the homepage (the three most recently updated featured venues).',
			),
			array(
				'key'          => 'field_hhv_deal_summary',
				'name'         => 'deal_summary',
				'label'        => 'Deal line',
				'type'         => 'text',
				'instructions' => 'Short deal line shown on cards, e.g. "$6 beers · $9 cocktails · ½ off apps". Leave blank to build it from the happy hour menu prices.',
			),
			array(
				'key'          => 'field_hhv_editor_take',
				'name'         => 'editor_take',
				'label'        => 'Editor take',
				'type'         => 'textarea',
				'rows'         => 3,
				'instructions' => 'One line on why it\'s worth the trip (used on homepage picks). Falls back to the slogan.',
			),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_hhv_landing',
		'title'    => 'Landing settings',
		'position' => 'side',
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-landing' ) ) ),
		'fields'   => array(
			array(
				'key'     => 'field_hhv_landing_type',
				'name'    => 'landing_type',
				'label'   => 'Landing type',
				'type'    => 'select',
				'choices' => array( 'location' => 'Location (area)', 'vibe' => 'Vibe', 'menu' => 'Menu (cuisine)' ),
				'instructions' => 'Decides which homepage section and header menu this page appears in.',
			),
			array(
				'key'   => 'field_hhv_landing_label',
				'name'  => 'landing_label',
				'label' => 'Short label',
				'type'  => 'text',
				'instructions' => 'Tile/chip text, e.g. "Summerlin". Defaults to the title without "Happy Hours".',
			),
			array(
				'key'        => 'field_hhv_landing_areas',
				'name'       => 'landing_areas',
				'label'      => 'Areas',
				'type'       => 'taxonomy',
				'taxonomy'   => 'gd_placecategory',
				'field_type' => 'multi_select',
				'return_format' => 'id',
				'add_term'   => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'instructions' => 'Venues in any of these areas (sub-areas included).',
			),
			array(
				'key'        => 'field_hhv_landing_tags',
				'name'       => 'landing_tags',
				'label'      => 'Tags',
				'type'       => 'taxonomy',
				'taxonomy'   => 'gd_place_tags',
				'field_type' => 'multi_select',
				'return_format' => 'id',
				'add_term'   => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'instructions' => 'Venues with any of these tags. Combined with Areas when both are set.',
			),
			array(
				'key'           => 'field_hhv_landing_featured',
				'name'          => 'landing_featured',
				'label'         => 'Featured venue',
				'type'          => 'post_object',
				'post_type'     => array( 'gd_place' ),
				'return_format' => 'id',
				'allow_null'    => 1,
				'ui'            => 1,
			),
			array(
				'key'           => 'field_hhv_landing_image',
				'name'          => 'landing_image',
				'label'         => 'Tile image',
				'type'          => 'image',
				'return_format' => 'id',
				'instructions'  => 'Used on the large homepage tile. Defaults to the featured image.',
			),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_hhv_home',
		'title'    => 'Homepage',
		'location' => array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ) ) ),
		'fields'   => array(
			array(
				'key'           => 'field_hhv_home_video',
				'name'          => 'home_video',
				'label'         => 'Featured YouTube video URL',
				'type'          => 'url',
			),
		),
	) );
} );
