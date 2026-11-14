"""Instagram tags for a listing, from social/data/instagram-research.csv (matched by the listing page URL slug).

Rules by the CSV's "Account type":
  Venue    -> caption tag, Reel tag, Story mention sticker, Collaborator invite
  Property -> same, written as "inside <casino> @handle"
  Brand    -> caption and Reel tag only (national brands rarely accept collabs)
  LEAVE BLANK / CHECK / Low confidence / not listed -> no tag; the checklist says to search Instagram first
"""
import csv, re
from pathlib import Path

CSV = Path(__file__).resolve().parent.parent / 'data' / 'instagram-research.csv'


def _slug(url):
    return (url or '').rstrip('/').split('/')[-1]


def _handle(url):
    m = re.search(r'instagram\.com/([^/?#]+)', url or '')
    return '@' + m.group(1) if m else None


ROWS = {_slug(r['Listing page URL']): r for r in csv.DictReader(open(CSV, encoding='utf-8'))}


def lookup(slug):
    r = ROWS.get(slug)
    if not r or r['Action'] != 'SAVE' or r['Confidence'] == 'Low':
        return {'handle': None, 'kind': None}
    return {'handle': _handle(r['Instagram link to save']), 'kind': r['Account type']}


def checklist(slug):
    t = lookup(slug)
    h, k = t['handle'], t['kind']
    if not h:
        return ['- No confirmed Instagram account for this venue. Search it on Instagram before posting and add it to the research CSV.']
    if k == 'Brand':
        return [f'- Tag {h} on the Reel (brand account, so skip the Collaborator invite)']
    who = 'the property account' if k == 'Property' else 'the venue'
    return [f'- Tag {h} ({who}) on the Reel and add a {h} mention sticker on the Story',
            f'- Invite {h} as a Collaborator (it shows on their grid if they accept)']
