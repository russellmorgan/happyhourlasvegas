# Happy Hour Las Vegas

WordPress site for Happy Hour Las Vegas, run locally with the [Local](https://localwp.com) app. Content was imported from the live site (pages, posts, media and GeoDirectory venue data). The live site was built with Elementor; this repo is the Gutenberg rebuild, without Elementor.

WordPress 7.1.3 (see `CLAUDE.md` for architecture notes).

## Layout

- `app/public/wp-content/themes/hhv-placeholder/`: the custom child theme (Twenty Twenty-Five parent).
- `app/public/wp-content/mu-plugins/`: custom must-use plugins.
- `app/public/wp-content/plugins/`: third-party plugins. Not versioned; see below.
- `tools/elementor-to-blocks.php`: converts `_elementor_data` page content to core blocks.
- `gd_*.csv`: GeoDirectory import files (venues, categories, events, promotions).
- `CLAUDE.md`: guidance for AI-assisted work in this repo.

## Setup

1. Install WordPress 7.1.3 into `app/public/` (core is not versioned).
2. Install the third-party plugins GeoDirectory and Ninja Forms into `app/public/wp-content/plugins/`.
3. Create a Local site pointing at `app/public/`, which generates `wp-config.php`.
4. Import the database or content as needed. Database dumps are not versioned.

Start and stop the site from the Local app. For WP-CLI, use Local's "Open site shell".

## Not versioned

- WordPress core, the default Twenty themes and third-party plugins (upstream code).
- `wp-config.php` and other Local-generated files (contain local credentials and salts).
- `app/public/wp-content/uploads/` (about 480 MB of media, including venue images).
- Database dumps in `app/sql/` and `backups/`.
- Logs and Local config in `logs/` and `conf/`.
