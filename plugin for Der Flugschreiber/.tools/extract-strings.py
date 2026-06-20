import re, glob, os, sys

base = os.path.join(os.path.dirname(__file__), '..', 'der-flugschreiber-subscriptions')
base = os.path.abspath(base)

# function name, opening paren, single-quoted string (codebase uses single quotes for i18n)
pat = re.compile(r"(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x)\(\s*'((?:[^'\\]|\\.)*)'")

strings = set()
files = glob.glob(os.path.join(base, 'includes', '**', '*.php'), recursive=True)
files += glob.glob(os.path.join(base, '*.php'))
for f in files:
    txt = open(f, encoding='utf-8').read()
    for m in pat.finditer(txt):
        strings.add(m.group(1))

out = os.path.join(os.path.dirname(__file__), 'strings.txt')
with open(out, 'w', encoding='utf-8') as fh:
    for s in sorted(strings):
        fh.write(s + '\n')
print('TOTAL', len(strings))
print('written to', out)
