<?php
/** Asserts for the menu food/drink and hours-typo guesses in inc/venue.php. wp eval-file tools/check-heuristics.php */

$type = array(
	'Spicy Prawn Cocktail'         => 'food',
	'COCKTAIL DI GAMBERI'          => 'food',
	'Skye Poppers'                 => 'food',
	'Mai Tai Monday'               => 'drink',
	'House Margarita'              => 'drink',
	'Draft Beer'                   => 'drink',
	'Herbs & Rye Mussels'          => 'food',
	'The Dirty Lil Secret|Martini' => 'drink',
);
foreach ( $type as $in => $want ) {
	list( $name, $desc ) = array_pad( explode( '|', $in ), 2, '' );
	assert( hhv_menu_item_type( $name, $desc ) === $want, "type: $in" );
}

$hours = array( // raw range => [start, end, reverse] in minutes
	'03:00-17:00' => array( 900, 1020, false ),  // 3pm-5pm
	'16:00-06:00' => array( 960, 1080, false ),  // 4pm-6pm
	'21:00-12:00' => array( 1260, 1440, true ),  // 9pm-midnight
	'11:00-02:00' => array( 660, 1560, false ),  // real all-day deal, kept
	'00:00-02:00' => array( 0, 120, true ),      // late night
	'15:00-18:00' => array( 900, 1080, false ),
);
foreach ( $hours as $raw => $want ) {
	$got = hhv_parse_hours( '["Mo ' . $raw . '"]' )['Mo'][0];
	assert( $got === $want, "hours: $raw got " . json_encode( $got ) );
}
echo "ok\n";
