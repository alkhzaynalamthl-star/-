"""Bundle the concepts + comparison page as a static site (for sharing a preview link).

    python tools/qa/build_preview.py --out build/preview
Produces index.html (the comparison page) plus concepts/, assets/fonts and only
the renders the concepts use. Prints the file map as JSON on the last line.
"""
import argparse
import json
import os
import re
import shutil

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "../.."))
THEME = os.path.join(ROOT, "optimum-closets")

ap = argparse.ArgumentParser()
ap.add_argument("--out", required=True)
a = ap.parse_args()
out = os.path.abspath(a.out)
shutil.rmtree(out, ignore_errors=True)
os.makedirs(out)

html = open(os.path.join(THEME, "concepts/index.html"), encoding="utf-8").read()
# The publisher adds its own document skeleton.
html = re.sub(r"<!doctype html>\s*", "", html, flags=re.I)
html = re.sub(r"</?(html|head|body)[^>]*>\s*", "", html)
html = re.sub(r'<meta charset="utf-8">\s*|<meta name="viewport"[^>]*>\s*', "", html)
html = html.replace("../assets/", "assets/").replace('src="shots/', 'src="concepts/shots/').replace('href="shots/', 'href="concepts/shots/')
for c in ("atelier", "showroom", "bayt"):
    html = html.replace(f'href="{c}.html', f'href="concepts/{c}.html')
# Title first.
m = re.search(r"<title>.*?</title>", html)
html = m.group(0) + "\n" + html.replace(m.group(0), "", 1)
open(os.path.join(out, "index.html"), "w", encoding="utf-8").write(html)

files = {}


def add(rel_src, rel_dest=None):
    rel_dest = rel_dest or rel_src
    src = os.path.join(THEME, rel_src)
    dst = os.path.join(out, rel_dest)
    os.makedirs(os.path.dirname(dst), exist_ok=True)
    shutil.copy(src, dst)
    files[rel_dest] = os.path.relpath(dst, ROOT)


for f in sorted(os.listdir(os.path.join(THEME, "concepts"))):
    if f.endswith((".html", ".css", ".js")) and f != "index.html":
        add("concepts/" + f)
for f in sorted(os.listdir(os.path.join(THEME, "concepts/shared"))):
    add("concepts/shared/" + f)
for f in sorted(os.listdir(os.path.join(THEME, "concepts/shots"))):
    add("concepts/shots/" + f)
for f in sorted(os.listdir(os.path.join(THEME, "assets/fonts"))):
    if f.endswith((".css", ".woff2")):
        add("assets/fonts/" + f)

# Renders referenced by the concepts' data and markup.
src = ""
for f in ("concepts/shared/data.js", "concepts/atelier.html", "concepts/atelier.js", "concepts/showroom.js", "concepts/bayt.js", "concepts/showroom.html", "concepts/bayt.html"):
    src += open(os.path.join(THEME, f), encoding="utf-8").read()
manifest = json.load(open(os.path.join(THEME, "assets/img/renders/images.json"), encoding="utf-8"))
used = set()
for name in manifest:
    base = name.split("-")[0]
    if name in src or f"'{base}-" in src or f"{base}-' +" in src:
        used.add(name)
add("assets/img/renders/images.js")
for name in sorted(used):
    m = manifest[name]
    for w in m["sizes"]:
        add(f"assets/img/renders/{name}-{w}.webp")
    add(f"assets/img/renders/{name}-{m['fallback']}.jpg")
print(len(files), "files")
json.dump(files, open(os.path.join(out, "files.json"), "w"), indent=0)
