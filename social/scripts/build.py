"""Build the social set for one or more listings.

    python3 social/scripts/build.py <slug> [<slug> ...] [--no-video]

Reads social/listings/<slug>.json, photos from social/input/<slug>/, writes to social/out/<slug>/:
  <slug>-carousel-N.jpg (1080x1350), <slug>-story.jpg (1080x1920), <slug>-reel.mp4 (1080x1920, 30fps), <slug>-captions.txt
Needs: Python with Pillow and Playwright (chromium), and ffmpeg.
"""
import asyncio, json, shutil, subprocess, sys, tempfile
from pathlib import Path
from PIL import Image, ImageOps, ImageEnhance, ImageFilter
from playwright.async_api import async_playwright

ROOT = Path(__file__).resolve().parent.parent
TPL = ROOT / 'templates'
FPS = 30
sys.path.insert(0, str(Path(__file__).parent))
import captions  # noqa: E402


def photo_fix(src, dst):
    """Light clean up for social: auto contrast, a touch more color, upscale to 1500px long side, gentle sharpen."""
    im = Image.open(src).convert('RGB')
    im = ImageEnhance.Color(ImageOps.autocontrast(im, cutoff=0.5)).enhance(1.07)
    w, h = im.size
    s = max(1, 1500 / max(w, h))
    im = im.resize((round(w * s), round(h * s)), Image.LANCZOS).filter(ImageFilter.UnsharpMask(radius=2, percent=60, threshold=2))
    im.save(dst, quality=93)
    return w, h


def prepare(slug):
    L = json.loads((ROOT / 'listings' / f'{slug}.json').read_text())
    src, out = ROOT / 'input' / slug, ROOT / 'out' / slug
    work = out / '_photos'
    work.mkdir(parents=True, exist_ok=True)
    photos, small = {}, []
    for role, name in (L.get('photos') or {}).items():
        if not name:
            photos[role] = None
            continue
        dst = work / f'{role}.jpg'
        w, _ = photo_fix(src / name, dst)
        if w < 1080:
            small.append(f'{name} ({w}px wide)')
        photos[role] = dst.as_uri()
    L['photos'] = photos
    return L, out, small


async def render(slug, L, out, video):
    async with async_playwright() as p:
        b = await p.chromium.launch()
        pg = await b.new_page(viewport={'width': 1080, 'height': 1350})
        await pg.add_init_script(f'window.LISTING={json.dumps(L)}')
        await pg.goto((TPL / 'carousel.html').as_uri())
        await pg.wait_for_function('window.FITTED===true')
        await pg.wait_for_timeout(300)
        for i in range(await pg.locator('.slide').count()):
            await pg.locator('.slide').nth(i).screenshot(path=str(out / f'{slug}-carousel-{i + 1}.jpg'), type='jpeg', quality=92)
        pg2 = await b.new_page(viewport={'width': 1080, 'height': 1920})
        await pg2.add_init_script(f'window.LISTING={json.dumps(L)}')
        await pg2.goto((TPL / 'story.html').as_uri())
        await pg2.wait_for_function('window.FITTED===true')
        await pg2.wait_for_timeout(300)
        await pg2.locator('#st').screenshot(path=str(out / f'{slug}-story.jpg'), type='jpeg', quality=92)
        if video:
            await pg2.goto((TPL / 'reel.html').as_uri())
            await pg2.wait_for_function('window.FITTED===true')
            await pg2.wait_for_timeout(300)
            frames = Path(tempfile.mkdtemp(prefix=f'frames_{slug}_'))
            dur = await pg2.evaluate('window.DURATION')
            for f in range(int(round(dur * FPS))):
                await pg2.evaluate(f'renderAt({f / FPS})')
                await pg2.screenshot(path=str(frames / f'f{f:04d}.jpg'), type='jpeg', quality=90)
        await b.close()
    if video:
        subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-framerate', str(FPS), '-i', str(frames / 'f%04d.jpg'),
                        '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '18', '-movflags', '+faststart',
                        str(out / f'{slug}-reel.mp4')], check=True)
        shutil.rmtree(frames, ignore_errors=True)


def main():
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    video = '--no-video' not in sys.argv
    for slug in args:
        L, out, small = prepare(slug)
        asyncio.run(render(slug, L, out, video))
        (out / f'{slug}-captions.txt').write_text(captions.write(slug, json.loads((ROOT / 'listings' / f'{slug}.json').read_text())))
        shutil.rmtree(out / '_photos', ignore_errors=True)
        print(f'done {slug}' + (f'  (small photos, ask the venue for bigger ones: {", ".join(small)})' if small else ''))


if __name__ == '__main__':
    main()
