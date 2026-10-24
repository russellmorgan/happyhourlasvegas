<?php
/**
 * Admin helpers: landing type column on Pages, deal column on venues, and a short how-to on the dashboard.
 */

add_filter( 'manage_page_posts_columns', fn( $cols ) => array_merge( $cols, array( 'hhv_landing' => 'Landing' ) ) );
add_action( 'manage_page_posts_custom_column', function ( $col, $id ) {
	if ( 'hhv_landing' === $col && ( $type = get_post_meta( $id, 'landing_type', true ) ) ) {
		$l = hhv_landing( $id );
		printf( '%s · %s', esc_html( ucfirst( $type ) ), esc_html( hhv_count_label( hhv_landing_count( $l ) ) ) );
	}
}, 10, 2 );

add_filter( 'manage_gd_place_posts_columns', fn( $cols ) => array_merge( $cols, array( 'hhv_deal' => 'Deal line' ) ) );
add_action( 'manage_gd_place_posts_custom_column', function ( $col, $id ) {
	if ( 'hhv_deal' === $col ) {
		$v = hhv_venue( $id );
		echo esc_html( $v['deal'] ?? '' );
		if ( $v && ! get_post_meta( $id, 'deal_summary', true ) && $v['deal'] ) {
			echo ' <em style="opacity:.6">(auto)</em>';
		}
	}
}, 10, 2 );

add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'hhv_howto', 'Happy Hour Vegas: how content fits together', function () {
		?>
		<ul style="list-style:disc;padding-left:1.2em">
			<li><strong>Venues</strong> are <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gd_place' ) ); ?>">Places</a>. Hours go in GeoDirectory's Business Hours (a range starting 8pm or later shows as a reverse happy hour). Menu items 1–10 fill the menu card (drinks vs food is guessed from the item text). Set <em>Deal line</em> and <em>Editor take</em> in the sidebar box.</li>
			<li><strong>Areas</strong> are Place Categories (top level = area, child = neighbourhood or casino). <strong>Vibes and menus</strong> are Place Tags.</li>
			<li><strong>Landing pages</strong> are Pages using the <em>Landing (venue listings)</em> template. The <em>Landing settings</em> box picks the type (location, vibe, menu) and which areas/tags it lists. New landings appear on the homepage automatically; add them to the header menu in Appearance → Editor → Navigation.</li>
			<li><strong>FAQs</strong> are Details blocks inside the page content.</li>
			<li><strong>Homepage picks</strong>: tick <em>Featured on homepage</em> on a venue (the three most recently updated featured venues show). The featured video is set on the <a href="<?php echo esc_url( get_edit_post_link( (int) get_option( 'page_on_front' ) ) ?: admin_url( 'edit.php?post_type=page' ) ); ?>">front page</a>.</li>
		</ul>
		<?php
	} );
} );
