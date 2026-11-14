"""Render Instagram Highlight covers: python3 social/scripts/highlights.py  -> social/out/highlights/"""
import asyncio, json
from pathlib import Path
from playwright.async_api import async_playwright
ROOT = Path(__file__).resolve().parent.parent
COVERS = {'the-strip': 'Strip', 'downtown': 'Downtown', 'henderson': 'Henderson', 'summerlin': 'Summerlin',
          'southwest': 'Southwest', 'off-strip': 'Off Strip', 'events': 'Events', 'foodie-friday': 'Foodie Fri',
          'late-night': 'Late Night', 'olive': None}
async def main():
    out = ROOT / 'out' / 'highlights'; out.mkdir(parents=True, exist_ok=True)
    async with async_playwright() as p:
        b = await p.chromium.launch()
        for slug, label in COVERS.items():
            pg = await b.new_page(viewport={'width': 1080, 'height': 1920})
            await pg.add_init_script(f'window.HL={json.dumps({"label": label})}')
            await pg.goto((ROOT / 'templates' / 'highlight.html').as_uri())
            await pg.wait_for_function('window.FITTED===true'); await pg.wait_for_timeout(200)
            await pg.locator('#hl').screenshot(path=str(out / f'highlight-{slug}.png'))
            await pg.close()
        await b.close()
asyncio.run(main())
