# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A WordPress 7.1 site ("Happy Hour Las Vegas") run by the **Local** app (by Flywheel/WP Engine). Content was imported from the live site via WXR (pages, posts and media) plus GeoDirectory CSVs (`gd_place` venues, categories). Term and post IDs match the live site. The live site was built with Elementor; the goal is to rebuild on Gutenberg without reinstalling Elementor. Pages have been converted from `_elementor_data` to core blocks by `tools/elementor-to-blocks.php` (re-runnable: `eval-file tools/elementor-to-blocks.php dry=DIR | write [ids=1,2]`). The pre-conversion HTML is kept in postmeta `_hhv_pre_blocks_content`. GD listings and maps are `[gd_listings]` / `[gd_map]` shortcode blocks. Active plugins: GeoDirectory (venues and maps; data in `wp_geodir_gd_place_detail` and `wp_geodir_attachments`) and ACF (free). Ninja Forms is not installed. Listing images are mostly plain upload files referenced only by `wp_geodir_attachments`, not media library items. Deleting a `gd_place` post deletes its image files. There is no build step, no tests, and no linter (git tracks custom code only; see README).

## Layout

- `app/public/` — WordPress root (core files; don't edit `wp-admin/` or `wp-includes/`).
- `app/public/wp-content/` — the only place custom work belongs. Active theme is `themes/hhv-placeholder`, a block child of Twenty Twenty-Five implementing the teal + gold design in `design/` (see `design/AGENT-BRIEF.md` for how the theme is organised: `inc/*.php` auto-loaded helpers, `blocks/*` auto-registered dynamic blocks, `assets/css|js/*` auto-enqueued, ACF fields in `inc/fields.php`). Header nav is navigation post 8763 (Areas / Vibe / Menu). Landing pages are pages on the `page-landing` template with ACF landing meta; venues render via `templates/single-gd_place.html`.
- `design/` — design source pulled from Claude Design (open the `.dc.html` files via a local static server). `TASKS.md` lists open refinements.
- `conf/` — Local-managed nginx, PHP, and MySQL config templates. Change site settings through the Local app rather than here.
- `logs/` — nginx/PHP/MySQL logs; check here when debugging.

## Running

Start/stop the site from the Local app; it serves nginx + PHP + MySQL. For WP-CLI, use Local's "Open site shell" (it sets the right PHP and DB socket), then run `wp ...` from `app/public/`.

Outside Local's site shell, plain `wp`/`mysql` can't reach the DB. Use Local's binaries with its socket:

```bash
"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" -d "mysqli.default_socket=$HOME/Library/Application Support/Local/run/hiaFb9H-m/mysql/mysqld.sock" /Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar --path=app/public <command>
```

`mysql`/`mysqldump` live in `~/Library/Application Support/Local/lightning-services/mysql-8.4.0+2/bin/darwin-arm64/bin/` (pass `-S` with the same socket).

**Gotcha:** any outbound HTTP request made inside a PHP-FPM web request crashes the worker on this Mac (SIGABRT, objc fork-safety error in `logs/php/php-fpm.log`), so the site returns 502. WP-CLI requests are unaffected. Setting `env[OBJC_DISABLE_INITIALIZE_FORK_SAFETY]` in the pool config does not help (it's applied after the fork). `mu-plugins/local-no-fa-version-check.php` stops GeoDirectory's Font Awesome GitHub check. If 502s return after adding a plugin, look for its remote calls (log them with a `pre_http_request` filter).

Database: name `local`, user/password `root`/`root`, table prefix `wp_` (see `app/public/wp-config.php`). `WP_DEBUG` is off by default.
