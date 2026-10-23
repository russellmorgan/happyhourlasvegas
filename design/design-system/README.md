# Happy Hour Vegas — Design System

Source: the teal + gold direction in this project (`Happy Hour Homepage`, `Detail`, `Content Page`, `Landings`). Repo: russellmorgan/happyhourlasvegas. Visual reference page: `HHV Design System.dc.html`. Tokens: `design-system/tokens.css`.

## Brand
Local, insider Las Vegas happy hour guide. Retro lounge-sign feel: deep teal, cream, gold. Mark = olive-on-a-pick (`assets/olive-mark.svg`); lockup = olive + "HAPPY HOUR VEGAS" set live in Bebas Neue, gold, .06em tracking, .2em word gap (teal on gold). `assets/hhv-logo.svg` is the legacy black/red wordmark — do not use in this direction. Olive drops in with an overshoot (`--ease-pop`, .9s), words fade in after at 150ms steps.

## Content fundamentals
- Voice: a local friend. "we" for the editors, "you" for the reader. Plain, confident, short. "Three happy hours we'd go to this week, and why."
- Casing: Bebas headlines render uppercase by nature; author in sentence case. Labels/buttons are uppercase Barlow Condensed ("JOIN THE CLUB").
- Deals use middots: `$6 beers · $9 cocktails · ½ off apps`. Prices with `$`, halves as `½`.
- Counts: "31 Happy Hours" (title case unit). Times: "4–7pm".
- Taglines: "No Spam. No Junk. No Pressure." Footer: "Made with ♥ in Las Vegas".
- No emoji. Unicode only for ♥, ½, ·, −/+ (accordion).

## Visual foundations
- Color: teal surfaces, cream ink, gold accent. Alternate bands teal → deep teal (olive 1px hairlines top/bottom) → teal → cream. Gold is for eyebrows, CTAs, rules and active states, never body text on cream.
- Type: Bebas Neue display (64/40/30/20), Barlow body 15/1.4, Barlow Condensed 600 uppercase labels, Libre Caslon Text gold eyebrows 11–12px.
- Headlines on teal carry a hard black drop shadow (`--shadow-display`). Section heads on cream use a 3px gold rule extending to the right.
- Imagery: warm, low-light bar photography; always on a `#15292d` well, with a vignette on cards and a bottom black scrim under overlaid labels.
- Texture: gold halftone dots (`--halftone`) masked to fade downward — used on stat/number blocks.
- Radii: 4px buttons/inputs, 6px tiles/images, 10px accordions/frames. Mostly flat; only frames get `--shadow-frame`.
- Borders: 1px gold at 35% under header; 1.5px cream-55% input outlines on teal, 1.5px teal on cream.
- Motion: minimal. Logo pop-in, fades. No bounces elsewhere.
- Hover: links go gold → cream. Active tabs fill gold with teal ink.

## Formats
- **Web**: 780 desktop / 340 mobile artboards, 28px / 12px gutters.
- **Newsletter**: 600px single column. Teal header with lockup, cream body, gold CTA, teal footer. Bebas for heads (fallback 'Arial Narrow' bold → Roboto Condensed → Arial; Impact is too heavy), Barlow → Arial fallback.
- **Infographic**: 1080×1350 (feed) or 1080×1920 (story). Teal ground, giant Bebas numbers on gold halftone blocks, Libre Caslon eyebrows, olive mark + URL footer.
- **Video**: 1920×1080 and 1280×720 thumbnails. Title card, lower third (gold bar + cream name on teal), end card with subscribe + newsletter. Safe area 96px.

## Iconography
Filled glyphs, gold `#c9a034`, 20–22px: Instagram, Facebook, YouTube (inline SVG in the pages). YouTube play uses red `#f00` 64×44 rounded 12px. No icon font; no other icons in use.
