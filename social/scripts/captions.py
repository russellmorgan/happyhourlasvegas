"""Caption file for a listing, following the HHV Instagram rules:
discovery first (not deals), an SEO sentence (area, venue, 2 to 3 items with prices, days and times),
8 to 12 targeted hashtags at the bottom, save / tag / follow calls to action, and the venue's Instagram tags.
No em dashes or hyphens in copy; dashes only in time and day ranges (3–6pm, Mon–Thu).
"""
import ig_tags


def seo_sentence(slug, L):
    c = L['caption']
    t = ig_tags.lookup(slug)
    h, kind = t['handle'], t['kind']
    venue = L['name']
    where = ''
    if c.get('casino'):
        where = f" inside {c['casino']}" + (f' {h}' if h and kind == 'Property' else '')
    if h and kind in ('Venue', 'Brand'):
        venue = f'{venue} {h}'
    return f"Checking out one of the best {c['area']} happy hours: {venue}{where}. Happy hour has {c['items']}. {c['when']}."


def write(slug, L):
    c = L['caption']
    url = f'https://happyhourvegas.com/places/{slug}/'
    utm = lambda src, med: f'{url}?utm_source={src}&utm_medium={med}&utm_campaign={c.get("campaign", "listing_spotlight")}&utm_content={slug}'
    ev = L.get('event', {}).get('captionLine', '')
    body = '\n\n'.join(x for x in [
        '[HOOK]',
        seo_sentence(slug, L),
        c.get('disc', ''),
        ev,
        f"📌 Save this for your next night out\n{c['tagline']}\n➕ Follow @happyhourvegas to discover new Vegas happy hours every week",
        c['tags'],
    ] if x)
    hooks = '\n'.join(f'{k}) {x}' for k, x in zip('ABC', c['hooks']))
    check = '\n'.join([
        f"- Location tag: {c['location']} (the venue, not just \"Las Vegas\")",
        *ig_tags.checklist(slug),
        '- Topics (up to 3): Restaurants · Cocktails/Wine/Beer · Travel Destinations',
        '- Post as a Reel, share it to your Story, then save the Story to a Highlight',
    ])
    fb = body.replace('Follow @happyhourvegas', 'Follow Happy Hour Vegas').replace('link in bio', '[paste link]')
    return f"""{L['name'].upper()} · READY TO POST
{'=' * 50}

PICK ONE HOOK (it replaces [HOOK] below)
{hooks}

BEFORE YOU HIT SHARE
{check}


REEL CAPTION (also works for the carousel)
-------------------------------------------
{body}


STORY
-----
Link sticker:
{utm('instagram', 'story')}


FACEBOOK
--------
{fb}

Full details: {utm('facebook', 'social')}
"""
