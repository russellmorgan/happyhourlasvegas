"""Experience + roundup Reel: real footage for the intro and outro, design system cards in between.

    python3 social/scripts/experience.py <config-name> <clip.mov|mp4>   -> social/out/<slug>/<slug>-reel.mp4
    python3 social/scripts/experience.py <config-name> --preview         -> stills only, gray stand-in for the footage

The clip can be any orientation; it is scaled and center cropped to 1080 x 1920. Its audio is dropped
(add music in Instagram). The intro uses the start of the clip, the outro its last seconds.
"""
import asyncio, json, shutil, subprocess, sys, tempfile
from pathlib import Path
from playwright.async_api import async_playwright

ROOT = Path(__file__).resolve().parent.parent
FPS = 30
sys.path.insert(0, str(Path(__file__).parent))
from build import photo_fix  # noqa: E402


def prep_photos(cfg, work):
    """Clean up each card's photo (social/input/<slug>/<file>) and point the card at the fixed copy."""
    small = []
    for i, v in enumerate(cfg['items']):
        if v.get('photo'):
            dst = work / f'photo{i}.jpg'
            w, _ = photo_fix(ROOT / 'input' / cfg['slug'] / v['photo'], dst)
            if w < 1080:
                small.append(f"{v['name']} ({w}px wide)")
            v['photo'] = dst.as_uri()
    return small


async def render_frames(cfg, frames, times):
    async with async_playwright() as p:
        b = await p.chromium.launch()
        pg = await b.new_page(viewport={'width': 1080, 'height': 1920})
        await pg.add_init_script(f'window.EXP={json.dumps(cfg)}')
        await pg.goto((ROOT / 'templates' / 'experience.html').as_uri())
        await pg.wait_for_function('window.FITTED===true')
        await pg.wait_for_timeout(300)
        dur = await pg.evaluate('window.DURATION')
        segs = await pg.evaluate('window.SEGMENTS')
        for i, t in enumerate(times(dur)):
            await pg.evaluate(f'renderAt({t})')
            await pg.locator('#x').screenshot(path=str(frames / f'f{i:04d}.png'), omit_background=True)
        await b.close()
    return dur, segs


def probe(clip):
    out = subprocess.run(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', str(clip)],
                         capture_output=True, text=True, check=True).stdout.strip()
    return float(out)


def main():
    name, src = sys.argv[1], sys.argv[2]
    cfg = json.loads((ROOT / 'listings' / f'{name}.json').read_text())
    slug = cfg['slug']
    out = ROOT / 'out' / slug
    out.mkdir(parents=True, exist_ok=True)
    frames = Path(tempfile.mkdtemp(prefix=f'exp_{slug}_'))
    work = Path(tempfile.mkdtemp(prefix=f'exp_ph_{slug}_'))
    small = prep_photos(cfg, work)
    if small:
        print('small photos, ask the venue for bigger ones:', ', '.join(small))
    if src == '--preview':
        T = cfg['timing']; n = len(cfg['items'])
        picks = [3.6] + [T['intro'] + i * T['card'] + 1.6 for i in range(n)] + [T['intro'] + n * T['card'] + 3.0]
        asyncio.run(render_frames(cfg, frames, lambda d: picks))
        from PIL import Image
        for i, _ in enumerate(picks):
            fg = Image.open(frames / f'f{i:04d}.png').convert('RGBA')
            bg = Image.new('RGBA', fg.size, (90, 90, 90, 255)); bg.alpha_composite(fg)
            bg.convert('RGB').save(out / f'{slug}-preview-{i + 1}.jpg', quality=90)
        print('preview stills in', out)
    else:
        clip = Path(src)
        dur, segs = asyncio.run(render_frames(cfg, frames, lambda d: [f / FPS for f in range(int(round(d * FPS)))]))
        cd = probe(clip)
        a_len = segs['intro'][1]
        b_len = segs['outro'][1] - segs['outro'][0]
        mid = segs['cards'][1] - segs['cards'][0]
        a0 = min(cfg.get('clip', {}).get('intro_start', 0), max(0, cd - a_len))
        b0 = max(0, cd - b_len)
        fit = 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,setsar=1,fps=30,format=yuv420p'
        enc = ['-an', '-c:v', 'libx264', '-crf', '17', '-preset', 'veryfast', '-pix_fmt', 'yuv420p', '-r', str(FPS)]
        parts = []
        # intro and outro: pieces of the clip (last frame held if the clip runs short); middle: brand teal under the cards
        for name, start, length in (('a', a0, a_len), ('b', b0, b_len)):
            dst = frames / f'{name}.mp4'
            subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-ss', str(start), '-i', str(clip),
                            '-vf', f'{fit},tpad=stop_mode=clone:stop_duration={length}', '-t', str(length), *enc, str(dst)], check=True)
            parts.append(dst)
        mid_file = frames / 'm.mp4'
        subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'lavfi', '-i', f'color=c=0x1C363B:s=1080x1920:r={FPS}:d={mid}',
                        *enc, str(mid_file)], check=True)
        parts.insert(1, mid_file)
        lst = frames / 'list.txt'
        lst.write_text(''.join(f"file '{p}'\n" for p in parts))
        bg = frames / 'bg.mp4'
        subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', str(lst), '-c', 'copy', str(bg)], check=True)
        kbps = min(6000, int(9.0 * 8000 / dur))  # stay under 9 MB so Google Drive uploads from here work (10 MB limit)
        subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', str(bg), '-framerate', str(FPS), '-i', str(frames / 'f%04d.png'),
                        '-filter_complex', '[0:v][1:v]overlay=0:0,format=yuv420p[v]', '-map', '[v]', '-t', str(dur),
                        '-an', '-c:v', 'libx264', '-preset', 'slow', '-b:v', f'{kbps}k', '-maxrate', f'{int(kbps * 1.1)}k',
                        '-bufsize', f'{kbps * 2}k', '-pix_fmt', 'yuv420p', '-movflags', '+faststart',
                        str(out / f'{slug}-reel.mp4')], check=True)
        print(f'done {slug}: {dur:.1f}s, clip {cd:.1f}s')
    shutil.rmtree(frames, ignore_errors=True)
    shutil.rmtree(work, ignore_errors=True)


if __name__ == '__main__':
    main()
