<?php
/**
 * Rebuild the About page (ID 209) with the content-page blocks (block styles in inc/content.php).
 * Run: wp eval-file tools/reskin-about.php
 * The original content is kept once in postmeta _hhv_pre_reskin_content; restore with:
 *   wp post update 209 --post_content="$(wp post meta get 209 _hhv_pre_reskin_content)"
 * Re-running rebuilds from the new markup below (it does not overwrite the saved original).
 */
$id = 209;
if ( ! get_post_meta( $id, '_hhv_pre_reskin_content', true ) ) {
	update_post_meta( $id, '_hhv_pre_reskin_content', get_post_field( 'post_content', $id ) );
}
// Keep the existing schema.org script block.
preg_match( '#<!-- wp:html -->.*?<!-- /wp:html -->#s', get_post_meta( $id, '_hhv_pre_reskin_content', true ), $m );
$schema = $m[0] ?? '';

$h2 = fn( $t ) => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$t</h2>\n<!-- /wp:heading -->\n\n";
$p  = fn( $t, $cls = '' ) => '<!-- wp:paragraph' . ( $cls ? ' {"className":"' . $cls . '"}' : '' ) . " -->\n<p" . ( $cls ? " class=\"$cls\"" : '' ) . ">$t</p>\n<!-- /wp:paragraph -->\n\n";
$li = fn( $items ) => implode( '', array_map( fn( $i ) => "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->\n", $items ) );
$ul = fn( $style, $items, $ol = false ) => '<!-- wp:list ' . wp_json_encode( array_filter( array( 'ordered' => $ol, 'className' => "is-style-$style" ) ) ) . " -->\n<" . ( $ol ? 'ol' : 'ul' ) . " class=\"wp-block-list is-style-$style\">\n" . $li( $items ) . '</' . ( $ol ? 'ol' : 'ul' ) . ">\n<!-- /wp:list -->\n\n";
$box = fn( $style, $inner ) => "<!-- wp:group {\"className\":\"is-style-$style\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group is-style-$style\">\n$inner</div>\n<!-- /wp:group -->\n\n";

$c  = $h2( 'The story' );
$c .= $p( 'Happy Hour Vegas started as Summerlin Happy Hour in 2023 with a simple answer to a question that thousands of Las Vegas locals and visitors were asking and nobody was answering well: where’s the best happy hour around here?' );
$c .= $p( 'The requests kept coming. The gap was obvious. There was no independent, accurate, locally sourced guide to happy hours in Las Vegas — just a scattered collection of websites scraping outdated social media posts, AI-generated lists, and information pulled from sources that hadn’t been verified by anyone who actually showed up.' );
$c .= $p( 'So we showed up.', 'is-style-hhv-pull' );
$c .= $p( 'What started in one neighborhood expanded to cover the entire Las Vegas valley — the Strip, Downtown, Summerlin, Henderson, the Arts District, and everywhere in between. Today Happy Hour Vegas is the only independent local media publisher in Las Vegas dedicated entirely to happy hours — with 478 verified listings, a growing audience of 75,000 locals and visitors, and a reputation earned by doing the work that most websites skip entirely.' );

$c .= $h2( 'What we do' );
$c .= $p( 'Happy Hour Vegas is an independent publisher based in Las Vegas covering the city’s hospitality and dining industry with a specific expertise in happy hour. We help locals and visitors discover and plan happy hours that match their location, preferences, vibe, and budget — so they can spend less time guessing and more time enjoying one of the world’s great food and drink destinations.' );
$c .= $p( 'We publish original content on the Happy Hour Vegas website, a weekly email newsletter, and through our network of publisher partners including Las Vegas Advisor, Food &amp; Loathing Las Vegas, and Neon Feast. We also appear regularly on NBC3 news as a recognized local expert — a distinction earned by consistent, credible, locally sourced reporting.' );
$c .= $box( 'hhv-callout', $p( 'What we’re not', 'hhv-label' ) . $p( 'A social media account recycling restaurant promotions. A content farm generating AI lists. A click-driven aggregator pulling information from sources we haven’t verified. There are plenty of those. Happy Hour Vegas exists because they weren’t good enough.' ) );

$c .= $h2( 'The verified difference' );
$c .= $p( 'The word “Verified” on a Happy Hour Vegas listing means something specific. It means we got the information directly from the source — from restaurant owners, chefs, and their marketing and media teams. It means we visited the venue in person and experienced the happy hour ourselves. It means we checked it, confirmed it, and stand behind it.' );
$c .= $p( 'Our three-step verification process:' );
$c .= $ul( 'hhv-steps', array(
	'<strong>Direct sourcing</strong> We contact restaurants directly and build relationships with the people who run them. No scraping. No assumptions.',
	'<strong>In-person verification</strong> We visit. We sit at the bar. We order from the menu. We experience what our audience will experience before we recommend it.',
	'<strong>Ongoing monitoring</strong> Happy hours change. We track updates, confirm hours, and remove or flag listings that are no longer accurate. A verified badge is not permanent — it’s maintained.',
), true );
$c .= "<!-- wp:pullquote -->\n<figure class=\"wp-block-pullquote\"><blockquote><p>When a listing says Verified on Happy Hour Vegas, it means someone was there.</p><cite>That’s the difference between a recommendation and a guess.</cite></blockquote></figure>\n<!-- /wp:pullquote -->\n\n";

$c .= $h2( 'The numbers' );
$c .= $ul( 'hhv-stats', array(
	'<strong>478</strong> verified happy hour listings across the Las Vegas valley',
	'<strong>125+</strong> verified happy hours on the Las Vegas Strip alone',
	'<strong>75,000</strong> locals and visitors reach Happy Hour Vegas each month',
	'<strong>40 million</strong> people visit Las Vegas every year — many of them looking for exactly what we publish',
	'<strong>1</strong> city. More great food and drink per square mile than almost anywhere on earth.',
) );

$c .= $h2( 'Why happy hour' );
$c .= $p( 'Happy hour is not just about discounted drinks. At its best it’s one of the most efficient and social experiences you can have — a way to try new food, meet people, discover new places, and get more out of a limited amount of time and money without sacrificing quality.' );
$c .= $p( 'In Las Vegas that proposition is amplified. Forty million people visit every year for conferences, conventions, weddings, and weekend getaways. They have a finite amount of time in a city with an almost infinite number of dining options — many of them recognized on a global stage. Happy hour is the unlock. The right happy hour at the right place is three courses, two drinks, and a room worth Instagramming for under $30. Happy Hour Vegas is the guide that finds those moments and makes them accessible.' );
$c .= $p( 'For locals the value is different but equally real. Las Vegas has one of the most dynamic and diverse restaurant scenes in the world — and most of it goes undiscovered because the city moves fast and the information is unreliable. Happy Hour Vegas is the antidote to that. We surface the hidden gems, the new openings, the iconic spots, and the neighborhood finds that make living in Las Vegas one of the better kept secrets in the country.' );

$c .= $h2( 'What we believe' );
$c .= $p( 'We are not about cheap drinks and generic food. We are not here to help you find the lowest price. We are here to help you find the best experience at a price that makes it accessible.' );
$c .= $p( 'The best happy hours in Las Vegas are world-class restaurants offering a curated window into their kitchen and bar at a fraction of dinner prices. They are celebrity chef concepts, James Beard nominees, farm-to-table kitchens, and neighborhood gems run by people who care deeply about what they serve. Happy hour is their invitation. We make sure you know about it.' );
$c .= $p( 'We prioritize original, high-quality, authentic Las Vegas experiences. We write with honesty — which means we’ll tell you when a property has one great happy hour and five mediocre ones, or when a famous resort hasn’t prioritized happy hour at all. Our audience trusts us because we don’t pretend everything is great. We tell you what’s actually worth your time.' );

$c .= $h2( 'Who trusts us' );
$c .= $p( 'When industry experts and recognized media brands trust Happy Hour Vegas as a source, it signals something to our audience — and to the algorithms that determine whether you find us.' );
$c .= $ul( 'hhv-partners', array(
	'<strong>Las Vegas Advisor</strong> One of Las Vegas’s most respected independent publications has featured Happy Hour Vegas content and recommendations.',
	'<strong>Food &amp; Loathing Las Vegas / Neon Feast</strong> Al Mancini, one of Las Vegas’s most recognized food critics and media personalities, is a distribution and editorial partner.',
	'<strong>NBC3 News Las Vegas</strong> Happy Hour Vegas appears as a recognized local expert on NBC3’s regular happy hour segment — third-party validation from one of Las Vegas’s primary news stations.',
	'<strong>Our audience</strong> 75,000 locals and visitors who choose to find their next happy hour through us rather than anywhere else. That number grows every month without paid advertising because the content earns it.',
) );

$c .= $h2( 'How we make money' );
$c .= $p( 'Transparency matters. Here’s how Happy Hour Vegas generates revenue:' );
$c .= $p( 'We partner with restaurants to introduce new customers to their happy hours — helping venues reach the exact audience most likely to visit and convert. We also offer advertising services through our parent company i1G Media, connecting local businesses with Las Vegas’s most engaged dining audience across the Happy Hour Vegas website and our publisher network.' );
$c .= $p( '<strong>We do not accept payment to change, inflate, or manufacture a verified rating.</strong> Advertising relationships do not influence editorial decisions. A verified listing is earned — not bought.' );

$c .= $h2( 'Our editorial standards' );
$c .= $p( 'Happy Hour Vegas follows a three-step verification process for all listings. Our recommendations are transparent and clearly marked. We distinguish between verified listings (confirmed through direct contact and in-person visits), recommended listings (editorially selected by our team based on quality and experience), and listings that are unverified or pending confirmation.' );
$c .= $p( 'We update listings regularly. When a happy hour changes, ends, or cannot be confirmed we reflect that accurately rather than leaving outdated information published.' );
$c .= $box( 'hhv-standard', $p( 'Our standard', 'hhv-label' ) . $p( 'If we wouldn’t send a friend there based on the information we have, we don’t publish it.' ) );

$c .= $h2( 'Get in touch' );
$c .= $p( 'We read everything.', 'is-style-hhv-dek' );
$c .= $ul( 'hhv-contact', array(
	'<strong>Email</strong> <a href="mailto:happy@happyhourvegas.com">happy@happyhourvegas.com</a>',
	'<strong>Instagram</strong> DM us @happyhourvegas',
	'<strong>Contact form</strong> <a href="/contact-happy-hour-vegas/">happyhourvegas.com/contact</a>',
) );
$c .= $p( 'If you’re a restaurant or venue and want to get your happy hour listed and verified — reach out. If you’ve visited a listing and something has changed — tell us. If you just want to say hi — we’re here for that too.' );
$c .= $schema;

wp_update_post( array(
	'ID'           => $id,
	'post_content' => wp_slash( $c ),
	'post_excerpt' => 'An independent Las Vegas publisher covering happy hours since 2023.',
) );
echo "About page rebuilt (" . strlen( $c ) . " bytes)\n";
