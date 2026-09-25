"""Turn QA screenshots into (1) thumbnails for the concepts comparison page and
(2) compressed full-page screenshots for docs/screenshots.

    python tools/qa/make_previews.py --shots /tmp/shots --flow /tmp/flow
"""
import argparse
import os

from PIL import Image

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "../.."))
CONCEPT_SHOTS = os.path.join(ROOT, "optimum-closets/concepts/shots")
DOC_SHOTS = os.path.join(ROOT, "docs/screenshots")


def crop_top(src, w, h, dest, width=None, q=78):
    im = Image.open(src).convert("RGB")
    scale = im.width / w
    im = im.crop((0, 0, im.width, min(im.height, int(h * scale))))
    if width:
        im = im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)
    im.save(dest, "WEBP", quality=q, method=6)


def full(src, dest, max_w, q=62):
    im = Image.open(src).convert("RGB")
    if im.width > max_w:
        im = im.resize((max_w, round(im.height * max_w / im.width)), Image.LANCZOS)
    # WebP is limited to 16383 px in height.
    if im.height > 16000:
        im = im.crop((0, 0, im.width, 16000))
    im.save(dest, "WEBP", quality=q, method=6)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--shots", required=True)
    ap.add_argument("--flow", required=True)
    a = ap.parse_args()
    os.makedirs(CONCEPT_SHOTS, exist_ok=True)
    os.makedirs(DOC_SHOTS, exist_ok=True)
    s = lambda n: os.path.join(a.shots, n)
    f = lambda n: os.path.join(a.flow, n)

    # Concept thumbnails (Arabic, first screen).
    for c in ("atelier", "showroom", "bayt"):
        crop_top(s(f"c_{c}-ar-1440.png"), 1440, 900, os.path.join(CONCEPT_SHOTS, f"{c}-1440.webp"), 1440)
        crop_top(s(f"c_{c}-ar-375.png"), 375, 812, os.path.join(CONCEPT_SHOTS, f"{c}-375.webp"), 375)

    # Store thumbnails (4:3 crops).
    store = {
        "home": s("home-ar-1440.png"),
        "shop": s("shop-ar-1440.png"),
        "product": f("01-product-configured-ar-1440.png"),
        "cart": f("02-cart-ar-1440.png"),
        "checkout": f("03-checkout-ar-1440.png"),
        "confirm": f("04-confirmation-ar-1440.png"),
        "mtm": s("mtm-ar-1440.png"),
    }
    for k, p in store.items():
        if os.path.exists(p):
            crop_top(p, 1440, 1080, os.path.join(CONCEPT_SHOTS, f"store-{k}.webp"), 1200)
    mob = s("product-ar-375.png")
    if os.path.exists(mob):
        im = Image.open(mob).convert("RGB")
        im = im.crop((0, 0, im.width, int(im.width * 0.75 * 2.4)))
        # Two-column contact sheet of the mobile page, 4:3 overall.
        top = im.crop((0, 0, im.width, im.height // 2))
        bot = im.crop((0, im.height // 2, im.width, im.height))
        sheet = Image.new("RGB", (im.width * 2 + 40, top.height), "#f4efe8")
        sheet.paste(top, (0, 0))
        sheet.paste(bot, (im.width + 40, 0))
        sheet.thumbnail((1200, 1200))
        sheet.save(os.path.join(CONCEPT_SHOTS, "store-mobile.webp"), "WEBP", quality=78, method=6)

    # Documentation screenshots: every page, both languages, three widths.
    for name in sorted(os.listdir(a.shots)):
        if name.endswith(".png"):
            w = int(name.rsplit("-", 1)[1][:-4])
            full(os.path.join(a.shots, name), os.path.join(DOC_SHOTS, name[:-4] + ".webp"), 1000 if w > 1000 else (768 if w > 400 else 750))
    for name in sorted(os.listdir(a.flow)):
        if name.endswith(".png"):
            full(os.path.join(a.flow, name), os.path.join(DOC_SHOTS, "flow-" + name[:-4] + ".webp"), 1000)
    print("ok")


if __name__ == "__main__":
    main()
