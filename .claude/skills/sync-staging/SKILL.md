---
name: sync-staging
description: Push the local theme, database and/or uploads to the GreenGeeks staging site (happyhourvegas.com/staging) over ssh and WP-CLI. Use when the user asks to update, sync or push staging.
---

# Sync local to staging

Staging only. Never touch live (`/home/happyho7/public_html` itself) unless the user explicitly asks.
Staging: `/home/happyho7/public_html/staging`, URL `https://happyhourvegas.com/staging`, DB `happyho7_oyz66`, prefix `wp_`, MariaDB.

Ask which parts to push if unclear: **theme**, **database**, **uploads**. The theme is safe to push on request; the DB overwrites staging content, so confirm before importing.

## Rules (see CLAUDE.md SSH gotcha)

- Always `ssh greengeeks` / `rsync` / `scp` against the alias (multiplexed). Never poll in a loop; batch steps into one call.
- Check the key is loaded first: `ssh-add -l` must show the `id_rsa_green` fingerprint (`ssh-keygen -lf ~/.ssh/id_rsa_green`). If not, ask the user to run `! ssh-add ~/.ssh/id_rsa_green`. Never ask for, use or store the passphrase.
- If port 22 times out, stop and tell the user.
- Filter `mesg: ttyname failed` noise with `| grep -v ttyname`.
- Scratch files go in the session scratchpad, not `/tmp`.

## 1. Backup (always, before any write)

```bash
ssh greengeeks 'cd /home/happyho7/public_html/staging && mkdir -p ~/backups &&
  wp db export ~/backups/staging-$(date +%Y%m%d-%H%M%S).sql &&
  tar --warning=no-file-changed -czf ~/backups/staging-theme-$(date +%Y%m%d-%H%M%S).tgz -C wp-content/themes hhv-placeholder'
```

## 2. Theme

Dry run first and show the file list; `--delete` in the dry run only reveals stray staging files, leave it off the real run unless the user wants them removed.

```bash
T=app/public/wp-content/themes/hhv-placeholder/
R=greengeeks:/home/happyho7/public_html/staging/wp-content/themes/hhv-placeholder/
rsync -azn --delete --itemize-changes --exclude .DS_Store $T $R | grep -v ttyname   # dry run
rsync -az --itemize-changes --exclude .DS_Store $T $R | grep -v -e ttyname -e '^\.d'
ssh greengeeks 'cd /home/happyho7/public_html/staging && wp cache flush && wp rewrite flush'
```

## 3. Database (local -> staging)

Staging differs from local, so these are preserved:
- `wp_users` / `wp_usermeta` are excluded from the dump (staging has its own admins and passwords).
- `blog_public` (staging is 0, noindex), `active_plugins` (Rank Math is active only on staging) and `rank%math%` options are copied aside before the import and restored after.
- Local URL `http://happyhourlasvegas.local` is replaced with the staging URL (plain and JSON-escaped forms).

Dump with Local's `mysqldump` and socket (`wp db export` can't find the socket outside Local's shell):

```bash
S="$HOME/Library/Application Support/Local/run/hiaFb9H-m/mysql/mysqld.sock"
D=<scratchpad>
"$HOME/Library/Application Support/Local/lightning-services/mysql-8.4.0+2/bin/darwin-arm64/bin/mysqldump" \
  -S "$S" -uroot -proot --no-tablespaces --default-character-set=utf8mb4 \
  --ignore-table=local.wp_users --ignore-table=local.wp_usermeta local > $D/local.sql
tail -c 200 $D/local.sql | grep -c "Dump completed"        # must print 1
grep -c 0900_ai_ci $D/local.sql                             # must print 0 (MariaDB can't import it)
gzip -kf $D/local.sql && scp -q $D/local.sql.gz greengeeks:/home/happyho7/backups/local-push.sql.gz
```

Don't chain the upload after a failed dump (`;` or `&&` after an unchecked redirect uploads an empty file). Then import in one SSH call:

```bash
ssh greengeeks 'set -e; cd /home/happyho7/public_html/staging
gunzip -kf ~/backups/local-push.sql.gz
wp db query "DROP TABLE IF EXISTS hhv_keep_opts; CREATE TABLE hhv_keep_opts AS SELECT option_name,option_value,autoload FROM wp_options WHERE option_name IN (\"blog_public\",\"active_plugins\") OR option_name LIKE \"rank%math%\";"
wp db import ~/backups/local-push.sql
wp db query "DELETE FROM wp_options WHERE option_name IN (SELECT option_name FROM hhv_keep_opts); INSERT INTO wp_options (option_name,option_value,autoload) SELECT option_name,option_value,autoload FROM hhv_keep_opts; DROP TABLE hhv_keep_opts;"
wp search-replace "http://happyhourlasvegas.local" "https://happyhourvegas.com/staging" --all-tables-with-prefix --skip-columns=guid --report-changed-only
wp search-replace "http:\/\/happyhourlasvegas.local" "https:\/\/happyhourvegas.com\/staging" --all-tables-with-prefix --skip-columns=guid --report-changed-only --precise
wp cache flush; wp rewrite flush' 2>&1 | grep -v ttyname
```

Verify: `siteurl`, `blog_public` = 0, active plugins include `seo-by-rank-math`, `wp user list` unchanged, `gd_place` count (542 at last push), and
`SELECT COUNT(*) FROM wp_posts WHERE post_content LIKE '%happyhourlasvegas.local%'` = 0.
If the import goes wrong, roll back: `wp db import ~/backups/staging-<stamp>.sql` (the step 1 file).

## 4. Uploads

```bash
rsync -azn --stats --exclude .DS_Store app/public/wp-content/uploads/ greengeeks:/home/happyho7/public_html/staging/wp-content/uploads/
```

Only run the real sync (drop `-n`) if the dry run shows files to transfer. No `--delete`: staging holds more media than local (2.8G vs 483M) and that is expected.

## 5. Final check

```bash
curl -s https://happyhourvegas.com/staging/ | grep -o 'href="https\?://[^"]*"' | grep -v 'happyhourvegas.com/staging' | sort -u
```

Should list only social profiles, the newsletter subdomain and `gmpg.org`. Root-relative header/footer links are prefixed with `/staging` by `inc/subdir.php` in the theme, so a live-site link there means that file didn't deploy. Pages return 200 (301 is normal for canonical redirects). Remind the user to eyeball a venue page and a landing page's "Our picks" in a browser.
