# HHV social factory

Turns a listing into an on-brand social set using the HHV design system (teal, gold, cream; Bebas Neue, Barlow, Barlow Condensed, Libre Caslon; the new olive + wordmark logo).

For each listing it builds:

| File | Size | Use |
| --- | --- | --- |
| `<slug>-carousel-1..5.jpg` | 1080 x 1350 | Instagram and Facebook feed carousel |
| `<slug>-story.jpg` | 1080 x 1920 | Story (room left for the link sticker) |
| `<slug>-reel.mp4` | 1080 x 1920, 30 fps, 12 s (16 s optional) | Reel |
| `<slug>-captions.txt` | | Hooks, caption, hashtags, tags, posting checklist |

## Run it

```bash
pip install pillow playwright && playwright install chromium   # plus ffmpeg
# 1. listing data: social/listings/<slug>.json   (slug = the listing's URL slug on happyhourvegas.com)
# 2. photos:       social/input/<slug>/          (not tracked in git)
python3 social/scripts/build.py <slug> [<slug> ...] [--no-video]
# 3. results:      social/out/<slug>/            (not tracked in git)
```

`social/listings/triple-7-brewery-happy-hour.json` is the reference example.

## Rules baked in

- **Safe zones:** every Reel scene sits inside Instagram's center 4:5 area (clear of the top 15% and bottom 25%), so nothing is cropped in the feed or on the grid.
- **Reel:** venue name pinned top left the whole time; scenes are hook, venue, hero deal (number counts up), menu (up to 6 items), then days and times with the follow and tag a friend end card. Set `"reel": {"length": 16}` for the slower 16 s cut. Event mode: `"lineup": true` (item over venue) and `"partners": ["@handle", ...]` for stacked handles on the end card.
- **Headlines auto size** to fit; listings with fewer photos fall back gracefully (no room slide, type-led hero).
- **Photo fix:** auto contrast, a touch more color, upscale, gentle sharpen. Photos under 1080 px wide are flagged in the build output. Originals are never changed.
- **Event:** add an `"event"` block to swap the carousel's last slide and add a caption line (tickets etc.). Remove it after the event.
- **Captions:** discovery first, not deals. SEO sentence (area, venue, 2 to 3 items with prices, days and times), 8 to 12 targeted hashtags at the bottom, save / tag a friend / follow lines. No em dashes or hyphens; dashes only in time and day ranges (3–6pm, Mon–Thu). Newsletter day is Tuesday.
- **Instagram tags:** from `data/instagram-research.csv`, matched by listing slug. Venue and property accounts get tagged and invited as Collaborators; brand accounts are tagged only; blank, check or low confidence rows get no tag and a reminder to search Instagram first.

## Posting flow

Reel first (Reel creator, not a regular post), share the Reel to the Story with mention and link stickers, then save the Story to a Highlight. Location tag = the exact venue. Up to 3 Topics: Restaurants, Cocktails/Wine/Beer, Travel Destinations.
