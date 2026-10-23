# Shared brief for reskin subagents

Project: WordPress 7.1 site "Happy Hour Vegas" served by Local at http://happyhourlasvegas.local (repo root `/Users/morgan/Local Sites/happyhourlasvegas`, read its CLAUDE.md first). We are reskinning it to the teal + gold design in `design/` (rendered at http://localhost:8765/<file name>, e.g. `Happy%20Hour%20Detail.dc.html`; each file has a 780px desktop and a 340px mobile artboard). Read `design/design-system/README.md` and `tokens.css`. The `.dc.html` files are huge because of inline SVG: read them with a script that strips `<svg>…</svg>` first.

Theme: `app/public/wp-content/themes/hhv-placeholder` (block child theme of Twenty Twenty-Five). Already in place, DO NOT EDIT these shared files: `style.css` (tokens + shared components: .hhv-wrap, .hhv-band(--deep|--cream), .hhv-display/.hhv-hero/.hhv-h2, .hhv-label, .hhv-eyebrow, .hhv-deal, .hhv-section-head, .hhv-btn, .hhv-input, .hhv-chips/.hhv-chip, .hhv-tiles/.hhv-tile, .hhv-img (+__label), .hhv-cards/.hhv-card, .hhv-crumbs, .hhv-pager, FAQ accordion styling for core/details), `theme.json`, `functions.php`, `inc/venue.php`, `inc/fields.php`, `parts/header.html`, `parts/footer.html`, `assets/editor.js`, `blocks/newsletter`. If you need a shared change, put it in your own file and mention it in your report.

How the theme works:
- `inc/venue.php`: `hhv_venue($post)` returns the full venue record (GeoDirectory table `wp_geodir_gd_place_detail` + ACF `deal_summary`/`editor_take`): name, url, image, slogan, street/city/zip/lat/lng, phone, website, reservations, price_range, cuisine, features/vibe, menu (items with name/desc/price/type drink|food), menu_text, area/hood (WP_Term), hours (parsed, per day ranges [start,end,reverse] in minutes), deal, take. Also `hhv_venue_place()`, `hhv_venue_status()` (on now / ends_in), `hhv_hours_summary()`, `hhv_range_label()`, `hhv_time_label()`, `hhv_img()`, `hhv_venue_card()`, and landing helpers `hhv_landing($page)`, `hhv_landing_query_args()`, `hhv_landing_count()`, `hhv_landings($type)`, `hhv_count_label()`. Read the file.
- `inc/fields.php`: ACF (free, no repeater/options) field groups: venue (deal_summary, editor_take), landing pages using template `page-landing` (landing_type location|vibe|menu, landing_label, landing_areas = gd_placecategory term IDs, landing_tags = gd_place_tags term IDs, landing_featured, landing_image), front page (home_picks, home_video).
- Every `inc/*.php` is auto-loaded: add your page helpers as `inc/<area>.php`.
- Every `blocks/<name>/block.json` is auto-registered (use apiVersion 3, `"category": "hhv"`, `"render": "file:./render.php"`, `"usesContext": ["postId"]` when needed, name `hhv/<name>`). The editor previews them with ServerSideRender automatically (no JS build).
- Every `assets/css/*.css` and `assets/js/*.js` is enqueued on the front end. Put your styles in `assets/css/<area>.css` only.
- Templates are block HTML in `templates/`. Wrap: `<!-- wp:template-part {"slug":"header","area":"header"} /-->`, `<main class="hhv-main">`…, `<!-- wp:template-part {"slug":"footer","area":"footer"} /-->`. Bands are full-width; inner content uses `.hhv-wrap`.
- Data: venues are `gd_place` (533 published). Areas = `gd_placecategory` (top level = area, children = neighbourhood/casino). Vibes/menus = `gd_place_tags`. Landing pages are WP pages.
- Content voice and formatting rules are in the design README (middots in deals, "4–7pm", no emoji).

Running things:
- WP-CLI: `"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -d "mysqli.default_socket=$HOME/Library/Application Support/Local/run/hiaFb9H-m/mysql/mysqld.sock" /Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar --path=app/public <command>` (from repo root). Use `eval-file` for scripts; keep reusable data scripts in `tools/`.
- Never make outbound HTTP requests from PHP during web requests (crashes PHP-FPM, see CLAUDE.md).
- A DB backup exists in backups/pre-reskin-20261023.sql.gz. Before bulk content changes, save the old post_content in postmeta (e.g. `_hhv_pre_reskin_content`).
- Verify in the browser: open YOUR OWN tab with `mcp__Claude_Browser__tabs_create` (other agents share the pane; never drive a tab you didn't create) and check desktop and mobile widths (resize_window on your tab). `curl` is fine for quick checks. Check `logs/php/error.log` for PHP errors.
- Do not commit to git. Do not install plugins without saying why in your report.

Judgement: when the design and data disagree, prefer what the data supports, keep the design's look, and stub what can't be built yet. Keep code small and readable (match inc/venue.php style; no frameworks, no build step).

Final report (your last message, concise): files created/changed, data changes made (with how to re-run), anything stubbed or needing a decision from the site owner (bulleted, one line each).
