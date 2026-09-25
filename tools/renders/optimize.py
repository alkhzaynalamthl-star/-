"""
Turn raw Blender renders into web-ready responsive images + a manifest.

    python optimize.py --src ./out --dest ../../optimum-closets/assets/img/renders

Writes <name>-<width>.webp for each width and one <name>-<width>.jpg fallback,
plus images.json (sizes, Arabic/English alt text, "render" provenance) and
copies hotspots.json.
"""

import argparse
import json
import os
import shutil

from PIL import Image, ImageEnhance

FIN_AR = {"oak": "بلوط طبيعي", "walnut": "جوز مدخّن", "cashmere": "كشميري مطفي", "graphite": "جرافيت"}
FIN_EN = {"oak": "natural oak", "walnut": "smoked walnut", "cashmere": "matt cashmere", "graphite": "graphite"}
FRAME_AR = {"bronze": "برونزي", "black": "أسود", "champagne": "شمبانيا"}
FRAME_EN = {"bronze": "bronze", "black": "black", "champagne": "champagne"}
GLASS_AR = {"bronze": "زجاج برونزي", "smoked": "زجاج مدخّن", "clear": "زجاج شفاف", "fluted": "زجاج ساتان"}
GLASS_EN = {"bronze": "bronze glass", "smoked": "smoked glass", "clear": "clear glass", "fluted": "satin glass"}


def alt_for(name):
    p = name.split("-")
    state_ar = {"closed": "بأبواب مغلقة", "open": "بأبواب مفتوحة تُظهر التقسيم الداخلي"}
    state_en = {"closed": "with doors closed", "open": "with doors open showing the interior layout"}
    if p[0] == "hinged":
        lay = p[3] if len(p) > 3 else "classic"
        lay_ar = {"classic": "علّاقتان مزدوجتان وأرفف", "long": "علّاقة طويلة للثياب والعبايات وأرفف", "drawers": "أدراج داخلية وأرفف"}[lay]
        lay_en = {"classic": "double hanging and shelves", "long": "long hanging for thobes and abayas plus shelves", "drawers": "interior drawers and shelves"}[lay]
        if p[2] == "open":
            return (f"خزانة ملابس بأبواب خشبية مفصلية بتشطيب {FIN_AR[p[1]]}، بأبواب مفتوحة تُظهر {lay_ar}",
                    f"Hinged wooden-door wardrobe in {FIN_EN[p[1]]} with doors open showing {lay_en}")
        return (f"خزانة ملابس بأبواب خشبية مفصلية بتشطيب {FIN_AR[p[1]]} ومقابض نحاسية، {state_ar[p[2]]}",
                f"Hinged wooden-door wardrobe in {FIN_EN[p[1]]} with brass bar handles, {state_en[p[2]]}")
    if p[0] == "glass":
        return (f"خزانة ملابس بأبواب زجاجية وإطار ألمنيوم نحيف {FRAME_AR[p[1]]} وإضاءة داخلية، {state_ar[p[2]]}",
                f"Glass-door wardrobe with a slim {FRAME_EN[p[1]]} aluminium frame and interior lighting, {state_en[p[2]]}")
    if p[0] == "sliding":
        st_ar = "بأبواب منزلقة مغلقة" if p[2] == "closed" else "مع باب منزلق مفتوح يُظهر الرف والعلّاقات"
        st_en = "with sliding doors closed" if p[2] == "closed" else "with one sliding panel open showing shelves and hanging space"
        return (f"خزانة ملابس بأبواب منزلقة بتشطيب {FIN_AR[p[1]]} ولوح زجاجي أوسط، {st_ar}",
                f"Sliding-door wardrobe in {FIN_EN[p[1]]} with a glass centre panel, {st_en}")
    if p[0] == "walkin" and p[1] == "oak" and p[2] in ("wide", "mobile"):
        return ("غرفة ملابس (ووك إن) من البلوط الطبيعي بواجهة زجاجية وجزيرة أدراج وإضاءة معلّقة",
                "Natural oak walk-in closet with a glass-fronted wall, drawer island and pendant light")
    if p[0] == "walkin":
        return (f"غرفة ملابس (ووك إن) بتشطيب {FIN_AR[p[1]]} مع جزيرة أدراج وإضاءة معلّقة",
                f"Walk-in closet in {FIN_EN[p[1]]} with a drawer island and pendant light")
    if p[0] == "bespoke":
        return ("جدار خزائن مفصّل بالمقاس بلون كشميري مع تسريحة من البلوط ومرآة دائرية",
                "Made-to-measure wardrobe wall in cashmere with an oak vanity niche and round mirror")
    if p[0] == "hero":
        return ("خزانة زجاجية بإطار برونزي وإضاءة دافئة في غرفة نوم هادئة",
                "Bronze-framed glass wardrobe with warm lighting in a calm bedroom")
    if p[0] == "detail":
        k = "-".join(p[1:])
        d = {
            "handle": ("مقبض نحاسي مصقول على باب من البلوط الطبيعي", "Brushed brass bar handle on a natural oak door"),
            "glass-frame": ("زاوية إطار ألمنيوم برونزي نحيف مع زجاج برونزي", "Corner of a slim bronze aluminium frame with bronze glass"),
            "led-shelf": ("أرفف بإضاءة LED مدمجة داخل خزانة زجاجية", "Shelves with integrated LED strips inside a glass wardrobe"),
            "drawer": ("أدراج داخلية من الجوز بمقابض برونزية", "Walnut interior drawers with bronze pulls"),
            "sliding-profile": ("مقطع الباب المنزلق مع البروفايل البرونزي والمسار العلوي", "Sliding door edge profile and top track in bronze"),
        }
        return d[k]
    if p[0] == "swatch":
        k = "-".join(p[1:])
        if k in FIN_AR:
            return (f"عينة تشطيب {FIN_AR[k]}", f"{FIN_EN[k].capitalize()} finish sample")
        if k.startswith("frame-"):
            return (f"عينة إطار {FRAME_AR[k[6:]]}", f"{FRAME_EN[k[6:]].capitalize()} frame sample")
        return (f"عينة {GLASS_AR[k[6:]]}", f"{GLASS_EN[k[6:]].capitalize()} sample")
    return (name, name)


def widths_for(name, w):
    if name.startswith("swatch"):
        return [160, 320], 320
    if name.endswith("-wide"):
        return [960, 1600, 2400], 1600
    if name.endswith("-mobile"):
        return [540, 1080], 1080
    if name.startswith("detail"):
        return [600, 1200], 1200
    return [400, 800, 1200], 800


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--src", required=True)
    ap.add_argument("--dest", required=True)
    a = ap.parse_args()
    os.makedirs(a.dest, exist_ok=True)
    manifest = {}
    for fn in sorted(os.listdir(a.src)):
        if not fn.endswith(".png"):
            continue
        name = fn[:-4]
        im = Image.open(os.path.join(a.src, fn)).convert("RGB")
        # gentle, consistent grade: a touch cooler and crisper than raw AgX
        r, g, b = im.split()
        r = r.point(lambda v: v * 0.985)
        b = b.point(lambda v: min(255, v * 1.02))
        im = Image.merge("RGB", (r, g, b))
        im = ImageEnhance.Sharpness(im).enhance(1.15)
        ws, fb = widths_for(name, im.width)
        ratio = im.height / im.width
        sizes = []
        for w in ws:
            w = min(w, im.width)
            h = round(w * ratio)
            rs = im.resize((w, h), Image.LANCZOS)
            rs.save(os.path.join(a.dest, f"{name}-{w}.webp"), "WEBP", quality=80, method=6)
            if w == fb:
                rs.save(os.path.join(a.dest, f"{name}-{w}.jpg"), "JPEG", quality=82, optimize=True, progressive=True)
            sizes.append(w)
        ar, en = alt_for(name)
        manifest[name] = {"w": im.width, "h": im.height, "sizes": sizes, "fallback": fb, "alt": {"ar": ar, "en": en}, "source": "render"}
    json.dump(manifest, open(os.path.join(a.dest, "images.json"), "w"), ensure_ascii=False, indent=1)
    hs = os.path.join(a.src, "hotspots.json")
    hotspots = {}
    if os.path.exists(hs):
        shutil.copy(hs, os.path.join(a.dest, "hotspots.json"))
        hotspots = json.load(open(hs))
    # Same data for static pages (concept previews) that cannot read JSON from PHP.
    with open(os.path.join(a.dest, "images.js"), "w") as f:
        f.write("window.OC_IMAGES=" + json.dumps(manifest, ensure_ascii=False) + ";\n")
        f.write("window.OC_HOTSPOTS=" + json.dumps(hotspots) + ";\n")
    print(f"{len(manifest)} images")


if __name__ == "__main__":
    main()
