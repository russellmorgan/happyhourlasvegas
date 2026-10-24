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

## Remote server (GreenGeeks)

Live site and staging are on GreenGeeks (host `chi207`, account `happyho7`). Staging is `/home/happyho7/public_html/staging` with its own DB (`happyho7_oyz66`); live is `/home/happyho7/public_html` (DB `happyho7_qtpson`). Never change live without being asked. Connect with `ssh greengeeks` (alias in `~/.ssh/config`: port 22 on the IP, key `~/.ssh/id_rsa_green`; the key's passphrase is not stored here, ask the user). WP-CLI is installed there (`wp`).

**Gotcha:** the server firewall blocks the client IP on port 22 after a burst of SSH logins (a 15-second `ssh` polling loop did it once; web ports kept working and cPanel's IP Blocker showed nothing). To avoid it:
- The alias multiplexes connections (`ControlMaster`/`ControlPersist 10m`). Always use `ssh greengeeks`, `scp` or `rsync` against that alias, never raw host/IP forms that skip it.
- Never poll over SSH in a loop or Monitor. Start long jobs (tar, imports, big copies) in the background on the server (`nohup ... > log 2>&1 &`), then check the log occasionally, minutes apart.
- Batch steps into one `ssh greengeeks '...'` call. Give `tar` `--warning=no-file-changed` so a changing cache file doesn't abort a `set -e` script.
- If port 22 times out, stop and tell the user. Don't try other ports or hosts; the fix is GreenGeeks support whitelisting the IP in CSF.
