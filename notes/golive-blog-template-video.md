# Go-live steps: branch blog-template-video

Theme files: copy the theme (version 0.2.2) to live the usual way (tar over ssh). Then on LIVE, after a database backup:

1. Authors: move posts from "admin" (user 1) and "no author" (0) to Andrew Morgan. Check the user ID on live first
   (`wp user list`), then: `UPDATE wp_posts SET post_author=<andrew> WHERE post_type='post' AND post_author IN (0,1)`
   (SQL on purpose: changes only the author, not the "last modified" dates).
2. Publisher logo: `wp media import hhv-logo-teal.png --title="Happy Hour Vegas logo (teal)" --alt="Happy Hour Vegas" --porcelain`
   (file: design/design-system/assets/hhv-logo-teal.png on branch new-logo-design-system), then set Rank Math
   `rank-math-options-titles` keys `knowledgegraph_logo` (the new URL) and `knowledgegraph_logo_id` (the new ID).
   Old value on staging was the 2024 SVG, attachment 5718.
3. Staging only: delete draft test posts 8844 and 8845 (they were never on live).
4. `wp cache flush`, then Andrew purges Cloudflare.
5. Check one post: byline, video (if any), "More from Top Shelf", divider, and run Google's Rich Results Test.
