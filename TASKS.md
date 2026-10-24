# Reskin: refinements and open questions

First pass of the teal + gold reskin (design source in `design/`, theme `app/public/wp-content/themes/hhv-placeholder`). Items below need a decision, data, or a second pass.

## Needs an owner decision or data
- [x] **Newsletter** (resolved in another session): signup forms post to BigMailer (same form as the live site). "Read a sample happy hour newsletter" link is still a stub.
- [x] **Contact form** (resolved in another session): Ninja Forms is not installed (CLAUDE.md says it is). Contact page shows a disabled placeholder form. Pick a form plugin or a mailto.
- [x] **Korean landing (7568)** (resolved in another session): no Korean tag exists, so it lists nothing. Create the tag and tag Cote (8658), or unpublish the page.
Keep what you have, unpublish the page
- [ ] **Casino pages that list a whole area** (their old shortcodes did): California, Binion's, The D, Golden Gate, Main Street Station show all of Downtown; Cadence Crossing shows all of Henderson. Assign venues to those casino categories, then set the landing's Areas to the casino term.
Leave this as is for now, will adjust them in the wordpress later

- [ ] **Misfiled categories**: Boulder Station, Cannery, Durango, Sam's Town are top-level GD categories; move them under the right area.
- [ ] **Untouched pages**: author page 18, Marketing 2395, Featured Happy Hours 2396, the seven day-of-week pages (4072, 4088, 4678–4701), Listing Slider 5756, PF Chang's 7899. Decide: make them landings, content pages, or retire them.
- [ ] **Newsletter Sponsors (7825)** has no content; **Terms (8807)** is placeholder text.
- [x] **Vibe source**: the Vibe line now lists the vibe landings whose tags the venue carries, linked (`hhv_venue_vibes()` in `inc/venue.php`). 121 of 533 venues have no vibe tags, so they show no Vibe line; tag them to fill it in.
- [ ] **Vibe menu length**: 23 vibes (design shows 21). Consider dropping Hot spots and Golf from the header submenu.
- [ ] **"View all menus / vibes" tiles** on the homepage: there is no index page for all menus or vibes. Create one (could be a content page with the keep-browsing block) or point elsewhere.
- [x] **Homepage video** (resolved in another session): set by hand to the latest upload (eBJz2wLU-qE, Oct 16 2026). It does not auto-update; change it on the front page (ACF "Featured YouTube video URL") or add a WP-CLI cron that reads the channel RSS (channel UC2fTNxNP5CeobfizOqPqM5Q). Note the header/footer YouTube link uses @happyhourvegas.
- [x] **Homepage picks**: tick "Featured on homepage" on a venue; the three most recently updated featured venues show (`hhv_home_picks()` in `inc/home.php`). Cote, Tacos & Beer and Glitter Gulch Tiki are flagged. The old front-page "Our recommendations" field is gone.
- [ ] **Blog**: posts are hidden for now; single/archive templates use the content-page look as a stub. Index/archive/search list all post types including venues.

## Mobile pass (2026-10-24, 375px)
- [x] Done on all templates: no horizontal overflow; tap targets raised to 44px (header logo/toggle, footer, chips, selects, pagers, breadcrumbs, post lists, venue meta/map links); inputs 16px (no iOS zoom); header nav alignment, mobile menu alignment, homepage pick-card gap, stacked video-band newsletter form, content-page gutters 16px.
- [x] Mobile menu overlay: submenus collapsed, tap Areas/Vibe/Menu to open one (`assets/js/menu.js` + overlay rules in `style.css`).
- [x] TOC on content pages closes after tapping a link on mobile (`assets/js/content.js`).
- [x] Spacer paragraphs removed: 59 from 7 posts/pages (`tools/strip-spacers.php`; originals in `_hhv_pre_spacer_content`).
- [x] Newsletter "Read a sample" link and hero/keep-browsing chips are 44px; GD badges in posts 13px; page titles 50px on mobile.

## Data quality (content editing)
- [x] Menu food/drink: the guess now reads the item name first and food words win a tie ("Shrimp Cocktail" is food, "Mai Tai Monday" a drink). Still a guess; a per-item GD field is the real fix if editors need overrides.
- [x] Reverse happy hour = a range starting 8pm or later, or late night before 6am.
- [x] Hours typos (spans over 12h) are guessed at render time: early starts become pm ("03:00-17:00" = 3–5pm), early-morning ends become pm ("16:00-06:00" = 4–6pm), late starts close at midnight; 10am–noon all-day deals are kept. The GD data is unchanged; ~45 venues have such typos, worth fixing at the source. Checks: `tools/check-heuristics.php`.
- [x] Venue text: 10 `<h1>` → `<h2>` and 240 emoji removed across 201 venues (`tools/venue-text-cleanup.php`; originals in `_hhv_pre_cleanup_content`; DB backup `backups/pre-cleanup-20261024.sql.gz`).
- [x] Deal lines written for the three featured venues. Others still auto-build; set "Deal line" when featuring a venue.
- [x] Privacy page (2399): 6 bold-paragraph headings converted to H2s; "On this page" rail shows. Original in `_hhv_pre_heading_content`.
- [x] "Last updated" ignores edits made during the migration (before 2026-10-25) and shows the original date until a real edit (`blocks/page-head/render.php`).

## Technical follow-ups
- [ ] "On now" badges and the timeline "now" marker are server-rendered; with a page cache on the live host they go stale. Either exclude venue pages from cache or move the status into a small JS update.
- [ ] Map is an OpenStreetMap iframe (no API key). Swap for Google Maps or GD's map if preferred. GD's `[gd_map]` pages (e.g. Las Vegas Happy Hour Map 2355) were not reskinned.
- [ ] GD search paginates 10 per page (GD setting), other grids 12.
- [ ] Shopping vibe filter on location pages filters by tag only, not mall areas.
- [x] Fonts self-hosted in `assets/fonts` (latin subset) via theme.json `fontFace`; no Google requests.
- [ ] Design formats not built: newsletter email template (600px), infographic and video templates (design system sections 06–09).

## Re-runnable scripts (from repo root, via WP-CLI `eval-file`; dry run unless `write`)
- `tools/landings-setup.php [write] [nav] [ids=1,2]`: landing meta, content cleanup, header nav.
- `tools/home-setup.php [write]`: front page, FAQ content, picks, video.
- `tools/reskin-about.php`: About page content.
- `tools/venue-text-cleanup.php [write]`: venue `<h1>` → `<h2>`, emoji removed.
- `tools/check-heuristics.php`: asserts for the menu food/drink and hours-typo guesses (run with `php -d zend.assertions=1`).
- `tools/strip-spacers.php [write]`: removes empty `<p>&nbsp;</p>` spacer paragraphs from posts and pages.
Original content of every changed page is in postmeta `_hhv_pre_reskin_content`; DB backup `backups/pre-reskin-20261023.sql.gz`.
