# -*- coding: utf-8 -*-
import re, os, json, html as htmllib

HERE = os.path.dirname(__file__)
raw = open(os.path.join(HERE, 'source.html'), encoding='utf-8', errors='replace').read()
html = htmllib.unescape(raw)

MONTHS = {
    'januar': '01', 'februar': '02', 'märz': '03', 'april': '04', 'mai': '05',
    'juni': '06', 'juli': '07', 'august': '08', 'september': '09',
    'oktober': '10', 'november': '11', 'dezember': '12',
}
PDF = r'https?://www\.derflugschreiber\.at/_files/ugd/[a-z0-9_]+\.pdf'
IMG = r'https?://static\.wixstatic\.com/media/[A-Za-z0-9_~.%-]+'

# All pdf link positions.
pdf_pos = [(m.start(), m.group(0)) for m in re.finditer(PDF, html)]

# Cover cards = portrait images (magazine covers are 254x362 on the page).
# Each <img ...src="IMG"... width="254" height="362"> belongs to one issue card;
# its PDF is the nearest pdf link BEFORE the image (the wrapping <a href>).
cards = []
for m in re.finditer(r'<img\b[^>]*?width="254"[^>]*?height="362"[^>]*?>', html):
    block = m.group(0)
    src = re.search(r'src="(' + IMG + r')"', block)
    if not src:
        # try data-src or any wixstatic url in the tag
        src = re.search('(' + IMG + ')', block)
    cover = src.group(1) if src else None
    # nearest pdf before this image
    pos = m.start()
    pdf = None
    for p, u in pdf_pos:
        if p < pos:
            pdf = u
        else:
            break
    cards.append({'pos': pos, 'cover': cover, 'pdf': pdf})

# Titles in document order, dedup by number.
title_re = re.compile(r'#\s?(\d{1,2})\s?[I|]\s?([A-Za-zÄäÖöÜüß]+)\s?(20\d{2})')
seen = {}
for m in title_re.finditer(html):
    num = int(m.group(1))
    seen.setdefault(num, {'num': num, 'month_word': m.group(2).lower(), 'year': m.group(3),
                          'pos': m.start()})
titles = sorted(seen.values(), key=lambda t: t['pos'])  # document order (newest first)

cards_sorted = sorted(cards, key=lambda c: c['pos'])

print("cards(portrait imgs):", len(cards), "| distinct card pdfs:",
      len(set(c['pdf'] for c in cards)), "| distinct covers:", len(set(c['cover'] for c in cards)))
print("titles:", len(titles))

# Zip by order if counts match.
issues = []
if len(cards_sorted) == len(titles):
    for t, c in zip(titles, cards_sorted):
        mm = MONTHS.get(t['month_word'], '00')
        issues.append({
            'num': t['num'], 'title': '# %02d | %s %s' % (t['num'], t['month_word'].capitalize(), t['year']),
            'date': '%s-%s-01' % (t['year'], mm), 'year': t['year'],
            'pdf': c['pdf'], 'cover': c['cover'],
        })
    issues.sort(key=lambda i: i['num'])
    json.dump(issues, open(os.path.join(HERE, 'issues.json'), 'w', encoding='utf-8'),
              ensure_ascii=False, indent=2)
    print("distinct pdfs:", len(set(i['pdf'] for i in issues)),
          "distinct covers:", len(set(i['cover'] for i in issues)))
    for i in issues:
        print("#%02d %s pdf=...%s cover=...%s" % (i['num'], i['date'],
              (i['pdf'] or '')[-20:], (i['cover'] or '')[-26:]))
else:
    print("COUNT MISMATCH - inspect")
    for c in cards_sorted:
        print("CARD pos=%d pdf=...%s cover=...%s" % (c['pos'], (c['pdf'] or '')[-20:], (c['cover'] or '')[-26:]))
