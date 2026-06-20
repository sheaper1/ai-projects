# -*- coding: utf-8 -*-
import re, os, json, html as htmllib

HERE = os.path.dirname(__file__)
raw = open(os.path.join(HERE, 'source.html'), encoding='utf-8', errors='replace').read()
html = htmllib.unescape(raw)

MONTHS = {'januar':'01','februar':'02','märz':'03','april':'04','mai':'05','juni':'06',
          'juli':'07','august':'08','september':'09','oktober':'10','november':'11','dezember':'12'}
MONTH_DE = {'01':'Januar','02':'Februar','03':'März','04':'April','05':'Mai','06':'Juni',
            '07':'Juli','08':'August','09':'September','10':'Oktober','11':'November','12':'Dezember'}
PDF = r'https?://www\.derflugschreiber\.at/_files/ugd/[a-z0-9_]+\.pdf'
IMG = r'https?://static\.wixstatic\.com/media/[A-Za-z0-9_~.%-]+'

# --- Covers: portrait 254x362 images in document order ---
pdf_pos = [(m.start(), m.group(0)) for m in re.finditer(PDF, html)]
cards = []
for m in re.finditer(r'<img\b[^>]*?width="254"[^>]*?height="362"[^>]*?>', html):
    src = re.search('(' + IMG + ')', m.group(0))
    cards.append({'pos': m.start(), 'cover': src.group(1) if src else None})
cards.sort(key=lambda c: c['pos'])

# --- Titles in document order ---
title_re = re.compile(r'#\s?(\d{1,2})\s?[I|]\s?([A-Za-zÄäÖöÜüß]+)\s?(20\d{2})')
seen = {}
for m in title_re.finditer(html):
    num = int(m.group(1))
    seen.setdefault(num, {'num': num, 'month': MONTHS.get(m.group(2).lower(), '00'),
                          'year': m.group(3), 'pos': m.start()})
titles = sorted(seen.values(), key=lambda t: t['pos'])

assert len(cards) == len(titles) == 16, (len(cards), len(titles))
cover_by_num = {t['num']: c['cover'] for t, c in zip(titles, cards)}

# --- PDFs: full urls keyed by a tail substring resolved from the analysis ---
full_pdfs = sorted(set(u for _, u in pdf_pos))
def find_pdf(tail):
    hits = [u for u in full_pdfs if tail in u]
    assert len(hits) == 1, (tail, hits)
    return hits[0]

pdf_tail_by_num = {
    1:  '1a884d6e9557365f9195514b',
    2:  '47f9453ca5250b2668a39a92',
    3:  '13d548099b82a41929713b27',
    4:  'e4c83ff902364a1d8e9c0a3f4047e5e4',
    5:  '2aaa72d120f1448d8348bc78ac05a86f',
    6:  'f5b514fcba034fe6926dbda2f7256a6a',
    7:  'c13e1d8d46b942a382f1ab6c75a31c2a',
    8:  '76110c6b79a44eb9aeb39bbf56d2f6e7',
    9:  'da0dfbd777eb48cf8911dacf020e7a63',
    10: '1dcb9344d64640f5b74cce91668db436',
    11: 'dbb0554a622a4b1a88bd35f930aeae5f',
    12: '7054b3d548b945998216f0f33b8e91d4',
    13: '4a66018851b84856a77aa6bb3a4eb7a9',
    14: '924cfa4f9e054deaa14d7ba6b3fa741f',
    15: '976e0329f63d44ffbd2435c43538d7c1',
    16: '32bdab67871b4d71919143c86ee2d0bc',
}

issues = []
for num in range(1, 17):
    t = seen[num]
    mm = t['month']
    issues.append({
        'num': num,
        'number_label': '# %02d' % num,
        'title': '# %02d | %s %s' % (num, MONTH_DE[mm], t['year']),
        'date': '%s-%s-01' % (t['year'], mm),
        'year': t['year'],
        'pdf': find_pdf(pdf_tail_by_num[num]),
        'cover': cover_by_num[num],
    })

json.dump(issues, open(os.path.join(HERE, 'issues.json'), 'w', encoding='utf-8'),
          ensure_ascii=False, indent=2)
print("issues:", len(issues),
      "| distinct pdfs:", len(set(i['pdf'] for i in issues)),
      "| distinct covers:", len(set(i['cover'] for i in issues)))
for i in issues:
    print("%-18s %s" % (i['title'], i['date']))
