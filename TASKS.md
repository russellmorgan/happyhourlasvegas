# Reskin: refinements and open questions

First pass of the teal + gold reskin (design source in `design/`, theme `app/public/wp-content/themes/hhv-placeholder`). Items below need a decision, data, or a second pass.

## Needs an owner decision or data
- [ ] **Newsletter provider**: every signup form (`blocks/newsletter`) posts to `#`. Hook the provider with the `hhv_newsletter_action` filter (or replace the form markup) once chosen. "Read a sample happy hour newsletter" link is a stub.
- [ ] **Contact form**: Ninja Forms is not installed (CLAUDE.md says it is). Contact page shows a disabled placeholder form. Pick a form plugin or a mailto.
- [ ] **Facebook URL**: header/footer use `https://www.facebook.com/happyhourvegas` (guessed). Instagram and YouTube URLs came from site content.
- [ ] **Korean landing (7568)**: no Korean tag exists, so it lists nothing. Create the tag and tag Cote (8658), or unpublish the page.
- [ ] **Casino pages that list a whole area** (their old shortcodes did): California, Binion's, The D, Golden Gate, Main Street Station show all of Downtown; Cadence Crossing shows all of Henderson. Assign venues to those casino categories, then set the landing's Areas to the casino term.
- [ ] **Misfiled categories**: Boulder Station, Cannery, Durango, Sam's Town are top-level GD categories; move them under the right area.
- [ ] **Untouched pages**: author page 18, Marketing 2395, Featured Happy Hours 2396, the seven day-of-week pages (4072, 4088, 4678–4701), Listing Slider 5756, PF Chang's 7899. Decide: make them landings, content pages, or retire them.
- [ ] **Newsletter Sponsors (7825)** has no content; **Terms (8807)** is placeholder text.
- [ ] **Vibe source**: the detail page subtitle and the "Vibe" meta both come from the GD Features field, so the vibe often repeats. Option: take Vibe from the vibe tags instead.
- [ ] **Vibe menu length**: 23 vibes (design shows 21). Consider dropping Hot spots and Golf from the header submenu.
- [ ] **"View all menus / vibes" tiles** on the homepage: there is no index page for all menus or vibes. Create one (could be a content page with the keep-browsing block) or point elsewhere.
- [ ] **Homepage video**: `home_video` is eEzD-Y97ges, whose thumbnail reads "PLACEHOLDER VIDEO"; set the real video on the front page. The page's old JSON-LD still describes a different video (eBJz2wLU-qE).
- [ ] **Homepage picks**: seeded with Cote Korean BBQ, Tacos & Beer, Glitter Gulch Tiki; edit on the front page (ACF "Our recommendations").
- [ ] **Blog**: posts are hidden for now; single/archive templates use the content-page look as a stub. Index/archive/search list all post types including venues.

## Data quality (content editing)
- [ ] Menu items have no drink/food flag; a keyword guess sorts them (`hhv_menu_item_type()` in `inc/venue.php`). Some food lands under Drinks (e.g. Herbs & Rye mussels). If it matters, add a per-item type field in GeoDirectory's custom fields and read it there.
- [ ] Reverse happy hour = any time range starting 8pm or later (heuristic in `hhv_parse_hours()`).
- [ ] Hours typos like `21:00-12:00` are treated as ending at midnight.
- [ ] 8 venues have an `<h1>` in their content; some venue text has emoji (style guide says none).
- [ ] Deal lines are auto-built from menu prices unless "Deal line" is set on the venue. Fill them in for featured venues (Places list shows a Deal line column, "(auto)" when generated).
- [ ] Privacy page uses bold paragraphs as headings, so no "On this page" rail. Convert to H2s.
- [ ] "Last updated" on pages uses the modified date (2026 for imported pages).

## Technical follow-ups
- [ ] "On now" badges and the timeline "now" marker are server-rendered; with a page cache on the live host they go stale. Either exclude venue pages from cache or move the status into a small JS update.
- [ ] Map is an OpenStreetMap iframe (no API key). Swap for Google Maps or GD's map if preferred. GD's `[gd_map]` pages (e.g. Las Vegas Happy Hour Map 2355) were not reskinned.
- [ ] GD search paginates 10 per page (GD setting), other grids 12.
- [ ] Shopping vibe filter on location pages filters by tag only, not mall areas.
- [ ] Google Fonts load from Google's CDN; self-host them if privacy/perf requires (WP Font Library can do it).
- [ ] Design formats not built: newsletter email template (600px), infographic and video templates (design system sections 06–09).
- [ ] No git commit has been made for this work yet.

## Re-runnable scripts (from repo root, via WP-CLI `eval-file`; dry run unless `write`)
- `tools/landings-setup.php [write] [nav] [ids=1,2]`: landing meta, content cleanup, header nav.
- `tools/home-setup.php [write]`: front page, FAQ content, picks, video.
- `tools/reskin-about.php`: About page content.
Original content of every changed page is in postmeta `_hhv_pre_reskin_content`; DB backup `backups/pre-reskin-20261023.sql.gz`.
