"""Build languages/ar.po from the theme POT + tools/i18n/ar.py, then compile with WP-CLI.

    python3 tools/i18n/build.py /path/to/optimum.pot
    wp i18n make-mo optimum-closets/languages && wp i18n make-php optimum-closets/languages
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
from ar import AR  # noqa: E402

pot = open(sys.argv[1], encoding="utf-8").read()
blocks = pot.split("\n\n")
out = ['msgid ""\nmsgstr ""\n"Project-Id-Version: Optimum Closets 2.0.0\\n"\n"Language: ar\\n"\n"MIME-Version: 1.0\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Content-Transfer-Encoding: 8bit\\n"\n"Plural-Forms: nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);\\n"\n"X-Domain: optimum\\n"\n']
missing = []


def unesc(s):
    return s.replace('\\"', '"').replace("\\\\", "\\")


def esc(s):
    return s.replace("\\", "\\\\").replace('"', '\\"')


for b in blocks[1:]:
    m = re.search(r'^msgid "((?:[^"\\]|\\.)*)"$', b, re.M)
    if not m or not m.group(1):
        continue
    mid = unesc(m.group(1))
    pl = re.search(r'^msgid_plural "((?:[^"\\]|\\.)*)"$', b, re.M)
    ctx = re.search(r'^msgctxt "((?:[^"\\]|\\.)*)"$', b, re.M)
    tr = AR.get(mid)
    if tr is None:
        missing.append(mid)
        continue
    entry = ""
    if ctx:
        entry += f'msgctxt "{ctx.group(1)}"\n'
    entry += f'msgid "{esc(mid)}"\n'
    if pl:
        entry += f'msgid_plural "{pl.group(1)}"\n'
        for i in range(6):
            entry += f'msgstr[{i}] "{esc(tr)}"\n'
    else:
        entry += f'msgstr "{esc(tr)}"\n'
    out.append(entry)

dest = os.path.join(os.path.dirname(__file__), "../../optimum-closets/languages/ar.po")
open(dest, "w", encoding="utf-8").write("\n".join(out))
print(f"{len(out) - 1} translated")
if missing:
    print("MISSING:", *missing, sep="\n  ")
