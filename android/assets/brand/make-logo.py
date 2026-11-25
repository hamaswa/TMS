"""Draws the BuyNStitch logo: a shopping bag with a stitched seam and a gold needle.

Everything is drawn at 4x on a 1024 grid and scaled down for smooth edges.
Run with Pillow installed:  python assets/brand/make-logo.py <output-folder>
"""
import math
import sys
from pathlib import Path
from PIL import Image, ImageDraw

K = 4                      # supersampling factor
N = 1024                   # design grid
BLUE_TOP = (31, 106, 160)
BLUE_BOTTOM = (16, 58, 92)
BRAND = (23, 79, 120)      # #174f78, the web app theme colour
WHITE = (255, 255, 255)
GOLD = (242, 179, 61)


def s(v):
    return int(round(v * K))


def gradient(size):
    img = Image.new("RGBA", (size, size))
    d = ImageDraw.Draw(img)
    for y in range(size):
        t = y / (size - 1)
        c = tuple(int(BLUE_TOP[i] + (BLUE_BOTTOM[i] - BLUE_TOP[i]) * t) for i in range(3))
        d.line([(0, y), (size, y)], fill=c + (255,))
    return img


def dashed(d, a, b, color, width, dash=26, gap=18):
    (x1, y1), (x2, y2) = a, b
    length = math.hypot(x2 - x1, y2 - y1)
    ux, uy = (x2 - x1) / length, (y2 - y1) / length
    pos = 0.0
    while pos < length:
        end = min(pos + dash, length)
        d.line([(s(x1 + ux * pos), s(y1 + uy * pos)), (s(x1 + ux * end), s(y1 + uy * end))],
               fill=color, width=s(width))
        pos = end + gap


def draw_art(img, scale=1.0, mono=False):
    """Draws the mark centred on the grid; scale < 1 shrinks it toward the centre."""
    layer = Image.new("RGBA", (s(N), s(N)), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    bag = WHITE
    seam = (0, 0, 0, 0) if mono else BRAND + (255,)
    needle = WHITE if mono else GOLD

    # Handle: a slim arc rising out of two punch holes in the bag's top.
    d.arc([s(410), s(268), s(614), s(500)], start=192, end=348, fill=bag, width=s(26))

    # Bag body: a tapered shopping bag, wider at the bottom.
    # Inner outline traced with a thick round-jointed line gives evenly rounded corners.
    inner = [(362, 418), (662, 418), (704, 772), (320, 772)]
    d.polygon([(s(x), s(y)) for x, y in inner], fill=bag)
    d.line([(s(x), s(y)) for x, y in inner + inner[:1]], fill=bag, width=s(36), joint="curve")
    for x, y in inner:
        d.ellipse([s(x - 18), s(y - 18), s(x + 18), s(y + 18)], fill=bag)
    # Handle ends running down to the punch holes.
    d.line([(s(412), s(360)), (s(420), s(430))], fill=bag, width=s(24))
    d.line([(s(612), s(360)), (s(604), s(430))], fill=bag, width=s(24))
    # Punch holes where the handle meets the bag.
    hole = (0, 0, 0, 0) if mono else BRAND + (255,)
    for hx in (420, 604):
        d.ellipse([s(hx - 13), s(436 - 13), s(hx + 13), s(436 + 13)], fill=hole)
    # Handle ends tucked into the holes.
        
    # Stitched seam across the bag, like a sewn fold.
    dashed(d, (318, 478), (706, 478), seam if not mono else (0, 0, 0, 0), 12, dash=28, gap=18)
    # Dashed hem following the slanted sides and bottom.
    dashed(d, (340, 520), (318, 768), hole, 12)
    dashed(d, (684, 520), (706, 768), hole, 12)
    dashed(d, (346, 776), (678, 776), hole, 12)

    # Needle: slim gold shaft lying across the bag, eye up-right, point down-left.
    needle_col = WHITE if mono else GOLD
    key_col = (0, 0, 0, 0) if mono else BRAND + (255,)
    ex, ey = 760, 300
    tx, ty = 450, 700
    length = math.hypot(tx - ex, ty - ey)
    ux, uy = (tx - ex) / length, (ty - ey) / length
    px, py = -uy, ux
    half = 13
    shaft = [
        (ex + px * half, ey + py * half),
        (ex - px * half, ey - py * half),
        (ex + ux * length * 0.6 - px * half, ey + uy * length * 0.6 - py * half),
        (tx, ty),
        (ex + ux * length * 0.6 + px * half, ey + uy * length * 0.6 + py * half),
    ]
    if True:
        # A thin keyline (a cut-out gap in monochrome) so the gold needle reads on the white bag.
        wide = [(x + (px * 6 if i in (0, 4) else -px * 6 if i in (1, 2) else 0),
                 y + (py * 6 if i in (0, 4) else -py * 6 if i in (1, 2) else 0)) for i, (x, y) in enumerate(shaft)]
        d.polygon([(s(x), s(y)) for x, y in wide], fill=key_col)
        d.ellipse([s(ex - half - 6), s(ey - half - 6), s(ex + half + 6), s(ey + half + 6)], fill=key_col)
    d.polygon([(s(x), s(y)) for x, y in shaft], fill=needle_col)
    d.ellipse([s(ex - half), s(ey - half), s(ex + half), s(ey + half)], fill=needle_col)
    # Eye: a long slot near the top end.
    eye_col = key_col
    d.line([(s(ex + ux * 4), s(ey + uy * 4)), (s(ex + ux * 46), s(ey + uy * 46))], fill=eye_col, width=s(8))

    # Thread from the eye, sweeping round in a loose loop.
    if not mono:
        pts = []
        p0, p1, p2, p3 = (ex, ey), (860, 240), (880, 520), (760, 600)
        for i in range(81):
            t = i / 80
            x = (1-t)**3*p0[0] + 3*(1-t)**2*t*p1[0] + 3*(1-t)*t**2*p2[0] + t**3*p3[0]
            y = (1-t)**3*p0[1] + 3*(1-t)**2*t*p1[1] + 3*(1-t)*t**2*p2[1] + t**3*p3[1]
            pts.append((s(x), s(y)))
        d.line(pts, fill=GOLD, width=s(10), joint="curve")

    layer = layer.transform(layer.size, Image.AFFINE, (1, 0, s(36), 0, 1, 0))
    if scale != 1.0:
        size = s(N * scale)
        layer = layer.resize((size, size), Image.LANCZOS)
        off = (s(N) - size) // 2
        canvas = Image.new("RGBA", (s(N), s(N)), (0, 0, 0, 0))
        canvas.alpha_composite(layer, (off, off))
        layer = canvas
    img.alpha_composite(layer)
    return img


def finish(img, size):
    return img.resize((size, size), Image.LANCZOS)


def rounded_mask(size, radius):
    m = Image.new("L", (size, size), 0)
    ImageDraw.Draw(m).rounded_rectangle([0, 0, size - 1, size - 1], radius=radius, fill=255)
    return m


def main(out):
    out = Path(out)
    out.mkdir(parents=True, exist_ok=True)
    big = s(N)

    # Full icon: gradient square with the mark (iOS / Expo icon, store listing).
    icon = draw_art(gradient(big))
    finish(icon, 1024).convert("RGB").save(out / "icon.png")

    # Android adaptive icon: background and a foreground kept inside the safe zone.
    finish(gradient(big), 1024).convert("RGB").save(out / "android-icon-background.png")
    fg = draw_art(Image.new("RGBA", (big, big), (0, 0, 0, 0)), scale=0.8)
    finish(fg, 1024).save(out / "android-icon-foreground.png")
    mono = draw_art(Image.new("RGBA", (big, big), (0, 0, 0, 0)), scale=0.8, mono=True)
    finish(mono, 1024).save(out / "android-icon-monochrome.png")

    # Splash: the mark alone on transparent, shown on the brand colour.
    splash = draw_art(Image.new("RGBA", (big, big), (0, 0, 0, 0)))
    finish(splash, 1024).save(out / "splash-icon.png")

    # Rounded logo for sharing, documents and the web.
    logo = finish(icon, 1024)
    logo.putalpha(rounded_mask(1024, 224))
    logo.save(out / "logo-1024.png")
    logo.resize((512, 512), Image.LANCZOS).save(out / "logo-512.png")
    logo.resize((192, 192), Image.LANCZOS).save(out / "logo-192.png")
    logo.resize((48, 48), Image.LANCZOS).save(out / "favicon.png")


if __name__ == "__main__":
    main(sys.argv[1])
