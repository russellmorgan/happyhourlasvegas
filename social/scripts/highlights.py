"""Render Instagram Highlight covers: python3 social/scripts/highlights.py  -> social/out/highlights/"""
import asyncio, json
from pathlib import Path
from playwright.async_api import async_playwright
ROOT = Path(__file__).resolve().parent.parent
COVERS = {'the-strip': 'The Strip', 'downtown': 'Downtown', 'henderson': 'Henderson', 'summerlin': 'Summerlin',
          'southwest': 'Southwest', 'off-strip': 'Off Strip', 'events': 'Events', 'foodie-friday': 'Foodie Fri',
          'late-night': 'Late Night', 'olive': None}
MAX_W = 780  # widest label must fit inside Instagram's circle crop

async def page(b, label, fs):
    pg = await b.new_page(viewport={'width': 1080, 'height': 1920})
    await pg.add_init_script(f'window.HL={json.dumps({"label": label, "fs": fs})}')
    await pg.goto((ROOT / 'templates' / 'highlight.html').as_uri())
    await pg.wait_for_function('window.FITTED===true')
    return pg

async def main():
    out = ROOT / 'out' / 'highlights'; out.mkdir(parents=True, exist_ok=True)
    async with async_playwright() as p:
        b = await p.chromium.launch()
        widths = []
        for label in filter(None, COVERS.values()):  # one size for every cover, set by the widest label
            pg = await page(b, label, 200); widths.append(await pg.evaluate('window.LABEL_W')); await pg.close()
        fs = int(200 * MAX_W / max(widths))
        print('label size', fs, 'px')
        for slug, label in COVERS.items():
            pg = await page(b, label, fs)
            await pg.wait_for_timeout(200)
            await pg.locator('#hl').screenshot(path=str(out / f'highlight-{slug}.png'))
            await pg.close()
        await b.close()
asyncio.run(main())
