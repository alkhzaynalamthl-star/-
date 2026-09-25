"""
Optimum Closets — procedural wardrobe renders (Blender / Cycles, bpy >= 4.2).

Every product image, material swatch and inspiration scene in the store is
generated from this single file so that lighting, materials, colours and
proportions stay consistent. Hotspot coordinates for the interactive
"inside the wardrobe" feature are projected from the real 3D objects and
written next to the renders, so the UI always points at the right thing.

Usage:
    python wardrobes.py --out ./out --only hinged-wood-oak-closed --samples 96
    python wardrobes.py --list
"""

import argparse
import json
import math
import os
import random
import sys

import bpy
from bpy_extras.object_utils import world_to_camera_view
from mathutils import Vector

# ---------------------------------------------------------------------------
# Scene / render setup
# ---------------------------------------------------------------------------


def reset_scene():
    bpy.ops.wm.read_factory_settings(use_empty=True)
    scene = bpy.context.scene
    scene.render.engine = "CYCLES"
    cy = scene.cycles
    cy.device = "CPU"
    cy.use_adaptive_sampling = True
    cy.adaptive_threshold = 0.03
    cy.use_denoising = True
    try:
        cy.denoiser = "OPENIMAGEDENOISE"
    except TypeError:
        pass
    cy.max_bounces = 7
    cy.diffuse_bounces = 3
    cy.glossy_bounces = 3
    cy.transmission_bounces = 6
    cy.transparent_max_bounces = 8
    cy.caustics_reflective = False
    cy.caustics_refractive = False
    cy.sample_clamp_indirect = 8
    cy.blur_glossy = 1.0
    scene.render.film_transparent = False
    scene.view_settings.view_transform = "AgX"
    for look in ("AgX - Medium High Contrast", "Medium High Contrast"):
        try:
            scene.view_settings.look = look
            break
        except TypeError:
            continue
    scene.view_settings.exposure = -1.7
    scene.render.image_settings.file_format = "PNG"
    scene.render.image_settings.color_depth = "8"
    return scene


def kelvin(k):
    """Approximate RGB for a colour temperature (Tanner Helland)."""
    t = k / 100.0
    if t <= 66:
        r = 255
        g = 99.4708025861 * math.log(t) - 161.1195681661
        b = 0 if t <= 19 else 138.5177312231 * math.log(t - 10) - 305.0447927307
    else:
        r = 329.698727446 * ((t - 60) ** -0.1332047592)
        g = 288.1221695283 * ((t - 60) ** -0.0755148492)
        b = 255
    c = [max(0, min(255, v)) / 255.0 for v in (r, g, b)]
    return (c[0] ** 2.2, c[1] ** 2.2, c[2] ** 2.2, 1.0)


def hex_rgba(h):
    h = h.lstrip("#")
    r, g, b = (int(h[i : i + 2], 16) / 255.0 for i in (0, 2, 4))
    lin = lambda c: c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4
    return (lin(r), lin(g), lin(b), 1.0)


# ---------------------------------------------------------------------------
# Materials
# ---------------------------------------------------------------------------

_MATS = {}


def _new_mat(name):
    m = bpy.data.materials.new(name)
    m.use_nodes = True
    nt = m.node_tree
    bsdf = nt.nodes.get("Principled BSDF")
    return m, nt, bsdf


def _set(bsdf, key, value):
    if key in bsdf.inputs:
        bsdf.inputs[key].default_value = value


def mat_wood(name, dark, light, rough=0.42, scale=1.0, coat=0.15):
    """Procedural straight-grain veneer. Grain runs along object Z by default."""
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    n, l = nt.nodes, nt.links
    tc = n.new("ShaderNodeTexCoord")
    mp = n.new("ShaderNodeMapping")
    mp.inputs["Scale"].default_value = (30 * scale, 30 * scale, 0.9 * scale)
    l.new(tc.outputs["Object"], mp.inputs["Vector"])
    # irregular fine streaks (flat-cut veneer) ...
    noise = n.new("ShaderNodeTexNoise")
    noise.inputs["Scale"].default_value = 3.0
    noise.inputs["Detail"].default_value = 10
    noise.inputs["Roughness"].default_value = 0.62
    l.new(mp.outputs["Vector"], noise.inputs["Vector"])
    # ... with a soft cathedral figure on top
    wave = n.new("ShaderNodeTexWave")
    wave.wave_type = "BANDS"
    wave.bands_direction = "X"
    wave.inputs["Scale"].default_value = 0.12
    wave.inputs["Distortion"].default_value = 9.0
    wave.inputs["Detail"].default_value = 3
    wave.inputs["Detail Scale"].default_value = 1.5
    l.new(mp.outputs["Vector"], wave.inputs["Vector"])
    fine = n.new("ShaderNodeTexNoise")
    fine.inputs["Scale"].default_value = 40
    fine.inputs["Detail"].default_value = 2
    l.new(mp.outputs["Vector"], fine.inputs["Vector"])
    mix = n.new("ShaderNodeMix")
    mix.data_type = "FLOAT"
    mix.inputs["Factor"].default_value = 0.3
    l.new(noise.outputs["Fac"], mix.inputs["A"])
    l.new(wave.outputs["Fac"], mix.inputs["B"])
    mix2 = n.new("ShaderNodeMix")
    mix2.data_type = "FLOAT"
    mix2.inputs["Factor"].default_value = 0.2
    l.new(mix.outputs["Result"], mix2.inputs["A"])
    l.new(fine.outputs["Fac"], mix2.inputs["B"])
    ramp = n.new("ShaderNodeValToRGB")
    ramp.color_ramp.elements[0].position = 0.36
    ramp.color_ramp.elements[0].color = hex_rgba(dark)
    ramp.color_ramp.elements[1].position = 0.66
    ramp.color_ramp.elements[1].color = hex_rgba(light)
    l.new(mix2.outputs["Result"], ramp.inputs["Fac"])
    l.new(ramp.outputs["Color"], bsdf.inputs["Base Color"])
    rr = n.new("ShaderNodeMapRange")
    rr.inputs["To Min"].default_value = rough - 0.08
    rr.inputs["To Max"].default_value = rough + 0.1
    l.new(mix2.outputs["Result"], rr.inputs["Value"])
    l.new(rr.outputs["Result"], bsdf.inputs["Roughness"])
    bump = n.new("ShaderNodeBump")
    bump.inputs["Strength"].default_value = 0.08
    bump.inputs["Distance"].default_value = 0.0015
    l.new(mix2.outputs["Result"], bump.inputs["Height"])
    l.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    _set(bsdf, "Coat Weight", coat)
    _set(bsdf, "Coat Roughness", 0.25)
    _MATS[name] = m
    return m


def mat_paint(name, color, rough=0.5, coat=0.0):
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    bsdf.inputs["Base Color"].default_value = hex_rgba(color)
    bsdf.inputs["Roughness"].default_value = rough
    _set(bsdf, "Coat Weight", coat)
    n, l = nt.nodes, nt.links
    noise = n.new("ShaderNodeTexNoise")
    noise.inputs["Scale"].default_value = 400
    bump = n.new("ShaderNodeBump")
    bump.inputs["Strength"].default_value = 0.02
    l.new(noise.outputs["Fac"], bump.inputs["Height"])
    l.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    _MATS[name] = m
    return m


def mat_metal(name, color, rough=0.28, aniso=0.0):
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    bsdf.inputs["Base Color"].default_value = hex_rgba(color)
    bsdf.inputs["Metallic"].default_value = 1.0
    bsdf.inputs["Roughness"].default_value = rough
    _set(bsdf, "Anisotropic", aniso)
    _MATS[name] = m
    return m


def mat_glass(name, tint="#ffffff", rough=0.015, ior=1.52):
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    bsdf.inputs["Base Color"].default_value = hex_rgba(tint)
    bsdf.inputs["Roughness"].default_value = rough
    bsdf.inputs["IOR"].default_value = ior
    _set(bsdf, "Transmission Weight", 1.0)
    _MATS[name] = m
    return m


def mat_fabric(name, color, rough=0.92, sheen=0.6):
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    n, l = nt.nodes, nt.links
    bsdf.inputs["Roughness"].default_value = rough
    _set(bsdf, "Sheen Weight", sheen)
    _set(bsdf, "Sheen Roughness", 0.4)
    noise = n.new("ShaderNodeTexNoise")
    noise.inputs["Scale"].default_value = 60
    noise.inputs["Detail"].default_value = 8
    hsv = n.new("ShaderNodeMix")
    hsv.data_type = "RGBA"
    hsv.blend_type = "MULTIPLY"
    hsv.inputs["Factor"].default_value = 0.12
    hsv.inputs["A"].default_value = hex_rgba(color)
    l.new(noise.outputs["Color"], hsv.inputs["B"])
    l.new(hsv.outputs["Result"], bsdf.inputs["Base Color"])
    bump = n.new("ShaderNodeBump")
    bump.inputs["Strength"].default_value = 0.25
    l.new(noise.outputs["Fac"], bump.inputs["Height"])
    l.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    _MATS[name] = m
    return m


def mat_leather(name, color):
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    n, l = nt.nodes, nt.links
    bsdf.inputs["Base Color"].default_value = hex_rgba(color)
    bsdf.inputs["Roughness"].default_value = 0.38
    vor = n.new("ShaderNodeTexVoronoi")
    vor.inputs["Scale"].default_value = 260
    bump = n.new("ShaderNodeBump")
    bump.inputs["Strength"].default_value = 0.12
    l.new(vor.outputs["Distance"], bump.inputs["Height"])
    l.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    _MATS[name] = m
    return m


def mat_emit(name, k=3000, strength=12.0):
    if name in _MATS:
        return _MATS[name]
    m = bpy.data.materials.new(name)
    m.use_nodes = True
    nt = m.node_tree
    for node in list(nt.nodes):
        nt.nodes.remove(node)
    out = nt.nodes.new("ShaderNodeOutputMaterial")
    em = nt.nodes.new("ShaderNodeEmission")
    em.inputs["Color"].default_value = kelvin(k)
    em.inputs["Strength"].default_value = strength
    nt.links.new(em.outputs["Emission"], out.inputs["Surface"])
    _MATS[name] = m
    return m


def mat_floor(name, base="#e9e1d6", vein="#cfc3b3"):
    """Large-format porcelain tiles (60 x 120) with thin grout, typical of Saudi homes."""
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    n, l = nt.nodes, nt.links
    tc = n.new("ShaderNodeTexCoord")
    brick = n.new("ShaderNodeTexBrick")
    brick.offset = 0.5
    brick.inputs["Scale"].default_value = 1.0
    brick.inputs["Brick Width"].default_value = 1.2
    brick.inputs["Row Height"].default_value = 0.6
    brick.inputs["Mortar Size"].default_value = 0.0025
    brick.inputs["Mortar Smooth"].default_value = 0.2
    brick.inputs["Color1"].default_value = hex_rgba(base)
    brick.inputs["Color2"].default_value = hex_rgba(base)
    brick.inputs["Mortar"].default_value = hex_rgba("#b9ad9c")
    l.new(tc.outputs["Object"], brick.inputs["Vector"])
    noise = n.new("ShaderNodeTexNoise")
    noise.inputs["Scale"].default_value = 1.4
    noise.inputs["Detail"].default_value = 10
    noise.inputs["Distortion"].default_value = 2.5
    l.new(tc.outputs["Object"], noise.inputs["Vector"])
    ramp = n.new("ShaderNodeValToRGB")
    ramp.color_ramp.elements[0].position = 0.55
    ramp.color_ramp.elements[0].color = (1, 1, 1, 1)
    ramp.color_ramp.elements[1].position = 0.75
    ramp.color_ramp.elements[1].color = hex_rgba(vein)
    l.new(noise.outputs["Fac"], ramp.inputs["Fac"])
    mix = n.new("ShaderNodeMix")
    mix.data_type = "RGBA"
    mix.blend_type = "MULTIPLY"
    mix.inputs["Factor"].default_value = 1.0
    l.new(brick.outputs["Color"], mix.inputs["A"])
    l.new(ramp.outputs["Color"], mix.inputs["B"])
    l.new(mix.outputs["Result"], bsdf.inputs["Base Color"])
    bsdf.inputs["Roughness"].default_value = 0.18
    bump = n.new("ShaderNodeBump")
    bump.inputs["Strength"].default_value = 0.3
    bump.invert = True
    l.new(brick.outputs["Fac"], bump.inputs["Height"])
    l.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    _MATS[name] = m
    return m


def mat_plaster(name, color="#efe9e1"):
    if name in _MATS:
        return _MATS[name]
    m, nt, bsdf = _new_mat(name)
    n, l = nt.nodes, nt.links
    bsdf.inputs["Base Color"].default_value = hex_rgba(color)
    bsdf.inputs["Roughness"].default_value = 0.9
    noise = n.new("ShaderNodeTexNoise")
    noise.inputs["Scale"].default_value = 8
    noise.inputs["Detail"].default_value = 12
    bump = n.new("ShaderNodeBump")
    bump.inputs["Strength"].default_value = 0.05
    l.new(noise.outputs["Fac"], bump.inputs["Height"])
    l.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    _MATS[name] = m
    return m


# Finishes offered in the store. Keys are referenced by the WooCommerce demo data.
FINISHES = {
    "oak": lambda: mat_wood("Natural Oak", "#a3805b", "#cdb391", rough=0.46),
    "walnut": lambda: mat_wood("Smoked Walnut", "#3b261a", "#6e4a33", rough=0.4, coat=0.25),
    "cashmere": lambda: mat_paint("Cashmere Matt", "#e4dbcf", rough=0.55),
    "graphite": lambda: mat_paint("Graphite Matt", "#3a3836", rough=0.5),
}
FRAMES = {
    "bronze": lambda: mat_metal("Bronze Frame", "#6d5238", rough=0.32, aniso=0.4),
    "black": lambda: mat_metal("Black Frame", "#1b1b1c", rough=0.4),
    "champagne": lambda: mat_metal("Champagne Frame", "#b89c74", rough=0.28, aniso=0.4),
}
GLASSES = {
    "bronze": lambda: mat_glass("Bronze Glass", "#e2cdb0", rough=0.01),
    "smoked": lambda: mat_glass("Smoked Glass", "#b9b9bb", rough=0.01),
    "clear": lambda: mat_glass("Clear Glass", "#eef2f0", rough=0.005),
    "fluted": lambda: mat_glass("Satin Glass", "#e8e6e1", rough=0.22),
}

# ---------------------------------------------------------------------------
# Geometry helpers
# ---------------------------------------------------------------------------


def box(name, size, center, mat=None, bevel=0.0015, parent=None):
    bpy.ops.mesh.primitive_cube_add(size=1, location=center)
    ob = bpy.context.active_object
    ob.name = name
    ob.scale = size
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    if bevel > 0:
        mod = ob.modifiers.new("bevel", "BEVEL")
        mod.width = bevel
        mod.segments = 2
        mod.limit_method = "ANGLE"
    if mat is not None:
        ob.data.materials.append(mat)
    if parent is not None:
        ob.parent = parent
        ob.matrix_parent_inverse = parent.matrix_world.inverted()
    return ob


def cylinder(name, radius, depth, center, rot=(0, 0, 0), mat=None, verts=24, parent=None):
    bpy.ops.mesh.primitive_cylinder_add(radius=radius, depth=depth, location=center, rotation=rot, vertices=verts)
    ob = bpy.context.active_object
    ob.name = name
    bpy.ops.object.shade_smooth()
    if mat is not None:
        ob.data.materials.append(mat)
    if parent is not None:
        ob.parent = parent
        ob.matrix_parent_inverse = parent.matrix_world.inverted()
    return ob


def empty(name, loc):
    ob = bpy.data.objects.new(name, None)
    ob.location = loc
    bpy.context.collection.objects.link(ob)
    return ob


def soft(ob, levels=2):
    m = ob.modifiers.new("sub", "SUBSURF")
    m.levels = levels
    m.render_levels = levels
    bpy.context.view_layer.objects.active = ob
    ob.select_set(True)
    bpy.ops.object.shade_smooth()
    return ob


def area_light(name, loc, rot, size, energy, k=4000, shape="RECTANGLE", size_y=None):
    data = bpy.data.lights.new(name, "AREA")
    data.shape = shape
    data.size = size
    if size_y is not None:
        data.size_y = size_y
    data.energy = energy
    data.color = kelvin(k)[:3]
    ob = bpy.data.objects.new(name, data)
    ob.location = loc
    ob.rotation_euler = rot
    ob.visible_glossy = False
    ob.visible_transmission = False
    ob.visible_camera = False
    bpy.context.collection.objects.link(ob)
    return ob


def spot(name, loc, energy, k=3000, angle=50, blend=0.6, radius=0.03):
    data = bpy.data.lights.new(name, "SPOT")
    data.energy = energy
    data.spot_size = math.radians(angle)
    data.spot_blend = blend
    data.shadow_soft_size = radius
    data.color = kelvin(k)[:3]
    ob = bpy.data.objects.new(name, data)
    ob.location = loc
    ob.rotation_euler = (0, 0, 0)
    bpy.context.collection.objects.link(ob)
    return ob


# ---------------------------------------------------------------------------
# Contents: garments, folded stacks, boxes, bags
# ---------------------------------------------------------------------------

GARMENT_TONES = ["#f4f1ea", "#f7f6f2", "#e9e1d3", "#c8b59a", "#a88a66", "#6f7461", "#2f3441", "#1e1d1f", "#8b6f63", "#d9d2c6"]
LONG_TONES = ["#141414", "#161616", "#f6f5f1", "#f3f1ea", "#1b1a1c", "#efece5"]  # abayas + thobes


def garment(x, y, top, length, tone, width=0.44, thick=0.06, idx=0, rail_mat=None):
    mat = mat_fabric(f"Fabric {tone}", tone)
    # Hanger: thin wooden bar + hook
    hanger_mat = mat_wood("Hanger Wood", "#8a6440", "#b18a5e", rough=0.4, scale=0.5)
    box(f"hanger_{idx}", (0.012, width * 0.92, 0.012), (x, y, top - 0.045), hanger_mat, bevel=0.004)
    cylinder(f"hook_{idx}", 0.0025, 0.04, (x, y, top - 0.02), mat=rail_mat or mat_metal("Chrome", "#c9c9c9", 0.2), verts=8)
    g = box(f"garment_{idx}", (thick, width, length), (x, y, top - 0.05 - length / 2), mat, bevel=0.018)
    # soften and flare the hem slightly for a believable hanging silhouette
    bpy.context.view_layer.objects.active = g
    bpy.ops.object.origin_set(type="ORIGIN_GEOMETRY")
    tp = g.modifiers.new("taper", "SIMPLE_DEFORM")
    tp.deform_method = "TAPER"
    tp.deform_axis = "Z"
    tp.factor = -0.18
    g.modifiers.new("sub", "SUBSURF").levels = 2
    bpy.ops.object.shade_smooth()
    g.rotation_euler = (random.uniform(-0.015, 0.015), 0, random.uniform(-0.28, 0.28))
    return g


def hanging_rail(x0, x1, y, z, rail_mat, tones, length_range, density=0.075, seed=0, start_idx=0):
    rnd = random.Random(seed)
    cylinder(f"rail_{seed}", 0.0125, (x1 - x0), ((x0 + x1) / 2, y, z), rot=(0, math.pi / 2, 0), mat=rail_mat)
    x = x0 + 0.06
    i = start_idx
    items = []
    while x < x1 - 0.05:
        tone = rnd.choice(tones)
        length = rnd.uniform(*length_range)
        items.append(garment(x, y, z + 0.015, length, tone, idx=i, rail_mat=rail_mat))
        x += density * rnd.uniform(0.75, 1.3)
        i += 1
    return items


def folded_stack(x, y, z, w=0.3, d=0.28, n=5, seed=0):
    rnd = random.Random(seed)
    tone = rnd.choice(GARMENT_TONES)
    zz = z
    for i in range(n):
        h = rnd.uniform(0.035, 0.055)
        t = tone if rnd.random() < 0.6 else rnd.choice(GARMENT_TONES)
        box(f"fold_{seed}_{i}", (w + rnd.uniform(-0.02, 0.01), d, h), (x + rnd.uniform(-0.008, 0.008), y, zz + h / 2), mat_fabric(f"Fabric {t}", t), bevel=0.012)
        zz += h
    return zz


def storage_box(x, y, z, w=0.34, d=0.3, h=0.2, tone="#d8cbb8", idx=0):
    mat = mat_fabric(f"Linen {tone}", tone, sheen=0.2)
    return box(f"sbox_{idx}", (w, d, h), (x, y, z + h / 2), mat, bevel=0.006)


def handbag(x, y, z, tone="#7a4b2e", idx=0, w=0.28, h=0.2):
    mat = mat_leather(f"Leather {tone}", tone)
    b = box(f"bag_{idx}", (w, 0.12, h), (x, y, z + h / 2), mat, bevel=0.02)
    bpy.ops.mesh.primitive_torus_add(major_radius=w * 0.28, minor_radius=0.006, location=(x, y, z + h), rotation=(math.pi / 2, 0, 0))
    t = bpy.context.active_object
    t.name = f"bagh_{idx}"
    t.scale = (1, 1.0, 1)
    t.data.materials.append(mat)
    bpy.ops.object.shade_smooth()
    return b


def shoe_pair(x, y, z, tone="#2a2522", idx=0):
    mat = mat_leather(f"Leather {tone}", tone)
    for k, dx in enumerate((-0.045, 0.045)):
        s = box(f"shoe_{idx}_{k}", (0.085, 0.26, 0.07), (x + dx, y, z + 0.035), mat, bevel=0.03)
        soft(s, 1)


# ---------------------------------------------------------------------------
# Wardrobe builder
# ---------------------------------------------------------------------------

T = 0.018  # carcass board thickness


class Hotspots:
    def __init__(self):
        self.points = []

    def add(self, key, loc):
        self.points.append((key, Vector(loc)))


def build_bay(x0, x1, y0, y1, z0, z1, layout, inner, rail_mat, led_mat, hs, seed):
    """Fill one interior bay between x0..x1 (inside faces) and z0..z1."""
    rnd = random.Random(seed)
    cx = (x0 + x1) / 2
    yd = (y0 + y1) / 2
    depth = y1 - y0
    w = x1 - x0

    def shelf(z, key=None):
        box(f"shelf_{seed}_{z:.2f}", (w, depth - 0.02, T), (cx, yd + 0.01, z), inner)
        if led_mat is not None:
            box(f"led_{seed}_{z:.2f}", (w - 0.02, 0.008, 0.004), (cx, y0 + 0.03, z - T / 2 - 0.002), led_mat, bevel=0)
        if key:
            hs.add(key, (cx, y0 + 0.05, z))

    if layout == "hang_long":
        top_shelf = z1 - 0.34
        shelf(top_shelf)
        for i in range(2):
            storage_box(cx + (i - 0.5) * (w / 2.1), yd, top_shelf + T / 2, w=min(0.36, w / 2.3), idx=seed * 10 + i, tone=["#d8cbb8", "#cbbba4"][i])
        rail_z = top_shelf - 0.07
        hanging_rail(x0, x1, yd, rail_z, rail_mat, LONG_TONES, (1.25, 1.5), density=0.07, seed=seed)
        hs.add("rail_long", (cx, y0 + 0.02, rail_z))
    elif layout == "hang_double":
        top_shelf = z1 - 0.34
        shelf(top_shelf)
        folded_stack(cx - w / 4, yd, top_shelf + T / 2, w=min(0.3, w / 2.4), n=4, seed=seed + 1)
        folded_stack(cx + w / 4, yd, top_shelf + T / 2, w=min(0.3, w / 2.4), n=5, seed=seed + 2)
        r1 = top_shelf - 0.07
        hanging_rail(x0, x1, yd, r1, rail_mat, GARMENT_TONES, (0.62, 0.8), density=0.08, seed=seed)
        mid = z0 + (r1 - z0) * 0.5 + 0.04
        shelf(mid - 0.02)
        r2 = mid - 0.09
        hanging_rail(x0, x1, yd, r2, rail_mat, GARMENT_TONES, (0.5, 0.62), density=0.09, seed=seed + 7, start_idx=200)
        hs.add("rail_double", (cx, y0 + 0.02, r2))
    elif layout == "shelves":
        n = 6
        span = (z1 - z0)
        step = span / n
        for i in range(1, n):
            z = z0 + i * step
            shelf(z, "shelf_led" if i == 3 else None)
            if i % 2 == 1:
                folded_stack(cx - w * 0.22, yd, z + T / 2, w=min(0.3, w / 2.4), n=rnd.randint(3, 5), seed=seed * 7 + i)
                folded_stack(cx + w * 0.22, yd, z + T / 2, w=min(0.3, w / 2.4), n=rnd.randint(3, 5), seed=seed * 9 + i)
            else:
                handbag(cx - w * 0.2, yd, z + T / 2, tone=rnd.choice(["#7a4b2e", "#1f1c1a", "#c9b08f", "#8b2f2f"]), idx=seed * 10 + i)
                handbag(cx + w * 0.2, yd, z + T / 2, tone=rnd.choice(["#d9cbb5", "#2f3441", "#6b4a35"]), idx=seed * 10 + i + 50, w=0.24, h=0.17)
    elif layout == "drawers":
        dh = 0.2
        for i in range(4):
            z = z0 + 0.01 + i * (dh + 0.004)
            box(f"drawer_{seed}_{i}", (w - 0.006, 0.02, dh), (cx, y0 + 0.02, z + dh / 2), inner)
            box(f"pull_{seed}_{i}", (w * 0.4, 0.012, 0.012), (cx, y0 + 0.006, z + dh - 0.03), rail_mat, bevel=0.004)
        hs.add("drawers", (cx, y0 + 0.01, z0 + 2 * dh))
        top = z0 + 4 * (dh + 0.004) + 0.02
        shelf(top)
        span = z1 - top
        for i in range(1, 4):
            z = top + i * span / 4
            shelf(z)
            folded_stack(cx, yd, z + T / 2, w=min(0.32, w / 1.8), n=rnd.randint(3, 6), seed=seed * 5 + i)
        folded_stack(cx, yd, top + T / 2, w=min(0.32, w / 1.8), n=4, seed=seed * 3)
    elif layout == "shoes":
        n = 7
        step = (z1 - z0) / n
        for i in range(0, n):
            z = z0 + i * step
            if i:
                shelf(z)
            for k in range(max(1, int(w / 0.24))):
                shoe_pair(x0 + 0.12 + k * 0.24, yd, z + T / 2, tone=rnd.choice(["#2a2522", "#7a4b2e", "#d8cbb8", "#8b2f2f", "#1e2a3a"]), idx=seed * 100 + i * 10 + k)
        hs.add("shoes", (cx, y0 + 0.05, z0 + 3 * step))


def build_wardrobe(
    x0,
    width,
    height=2.55,
    depth=0.6,
    door="hinged",
    finish="oak",
    frame="bronze",
    glass="bronze",
    handle="brass",
    open_doors=(),
    slide_open=0.0,
    layouts=None,
    led=True,
    hs=None,
    seed=1,
    plinth=0.07,
):
    """Build a wardrobe against the wall at y=+depth. Front face at y=0."""
    hs = hs or Hotspots()
    fin = FINISHES[finish]()
    inner = FINISHES["cashmere"]() if finish in ("cashmere", "graphite") else fin
    if door == "glass":
        inner = fin
    frame_mat = FRAMES[frame]()
    glass_mat = GLASSES[glass]()
    handle_mat = {
        "brass": lambda: mat_metal("Brushed Brass", "#b8925a", rough=0.3, aniso=0.5),
        "black": FRAMES["black"],
        "bronze": FRAMES["bronze"],
    }[handle]()
    rail_mat = FRAMES[frame]() if door == "glass" else mat_metal("Brushed Brass", "#b8925a", rough=0.3, aniso=0.5)
    led_mat = mat_emit("LED 3500K", 3500, 45.0 if door == "glass" else 18.0) if led else None

    x1 = x0 + width
    z0 = plinth
    z1 = height
    # Carcass
    box("side_l", (T, depth, height - plinth), (x0 + T / 2, depth / 2, plinth + (height - plinth) / 2), fin)
    box("side_r", (T, depth, height - plinth), (x1 - T / 2, depth / 2, plinth + (height - plinth) / 2), fin)
    box("top", (width, depth, T), (x0 + width / 2, depth / 2, height - T / 2), fin)
    box("bottom", (width - 2 * T, depth, T), (x0 + width / 2, depth / 2, plinth + T / 2), inner)
    box("back", (width - 2 * T, 0.008, height - plinth), (x0 + width / 2, depth - 0.004, plinth + (height - plinth) / 2), inner, bevel=0)
    box("plinth", (width, 0.02, plinth), (x0 + width / 2, 0.04, plinth / 2), mat_paint("Plinth", "#2a2724", 0.6), bevel=0)

    if door == "sliding":
        n_bays = 2 if width < 2.0 else 3
    else:
        n_doors = max(2, round(width / 0.6))
        if n_doors % 2:
            n_doors += 1
        n_bays = n_doors // 2
    layouts = layouts or ["hang_double", "shelves", "hang_long", "drawers"][:n_bays]
    bay_w = (width - 2 * T - (n_bays - 1) * T) / n_bays
    inner_y0 = 0.02 if door != "sliding" else 0.0
    for b in range(n_bays):
        bx0 = x0 + T + b * (bay_w + T)
        bx1 = bx0 + bay_w
        if b > 0:
            box(f"divider_{b}", (T, depth - 0.02, height - plinth - 2 * T), (bx0 - T / 2, depth / 2 + 0.01, plinth + (height - plinth) / 2), inner)
        if led and door in ("glass", "sliding"):
            # soft interior light: the glow real glass wardrobes get from their LED profiles
            area_light(f"bay_light_{b}", ((bx0 + bx1) / 2, depth * 0.45, height - 0.08), (0, 0, 0), bay_w * 0.9, 18 if door == "glass" else 6, k=3000, size_y=depth * 0.6)
        if led and door == "glass":
            # vertical LED profile on the divider face, typical of glass wardrobes
            box(f"vled_{b}", (0.006, 0.006, height - plinth - 0.2), (bx0 + 0.006, inner_y0 + 0.03, plinth + (height - plinth) / 2), led_mat, bevel=0)
        build_bay(bx0, bx1, inner_y0 + 0.01, depth - 0.01, z0 + T, z1 - T, layouts[b % len(layouts)], inner, rail_mat, led_mat, hs, seed * 31 + b)

    door_objs = []
    if door in ("hinged", "glass"):
        dw = (width - 0.004 * (n_doors - 1)) / n_doors
        dh = height - plinth - 0.006
        for i in range(n_doors):
            left_hinge = i % 2 == 0
            dx0 = x0 + i * (dw + 0.004)
            hinge_x = dx0 if left_hinge else dx0 + dw
            pivot = empty(f"pivot_{i}", (hinge_x, -0.002, plinth + 0.003))
            dz = dh / 2
            cxd = dw / 2 if left_hinge else -dw / 2
            if door == "hinged":
                p = box(f"door_{i}", (dw, 0.019, dh), (hinge_x + cxd, -0.0115, plinth + 0.003 + dz), fin, bevel=0.0012)
                p.parent = pivot
                p.matrix_parent_inverse = pivot.matrix_world.inverted()
                # vertical bar handle near the meeting edge
                hx = hinge_x + (dw - 0.06 if left_hinge else -(dw - 0.06))
                hz = plinth + 1.05
                for zz in (hz - 0.35, hz + 0.35):
                    cylinder(f"hstand_{i}_{zz:.2f}", 0.005, 0.035, (hx, -0.035, zz), rot=(math.pi / 2, 0, 0), mat=handle_mat, parent=pivot)
                cylinder(f"handle_{i}", 0.0075, 0.78, (hx, -0.052, hz), mat=handle_mat, parent=pivot)
            else:
                fw = 0.022
                fd = 0.024
                fy = -0.012
                parts = [
                    ((fw, fd, dh), (hinge_x + (fw / 2 if left_hinge else -fw / 2), fy, plinth + 0.003 + dz)),
                    ((fw, fd, dh), (hinge_x + (dw - fw / 2 if left_hinge else -(dw - fw / 2)), fy, plinth + 0.003 + dz)),
                    ((dw, fd, fw), (hinge_x + cxd, fy, plinth + 0.003 + fw / 2)),
                    ((dw, fd, fw), (hinge_x + cxd, fy, plinth + 0.003 + dh - fw / 2)),
                ]
                for k, (sz, c) in enumerate(parts):
                    box(f"frame_{i}_{k}", sz, c, frame_mat, bevel=0.001, parent=pivot)
                box(f"glass_{i}", (dw - 2 * fw + 0.004, 0.005, dh - 2 * fw + 0.004), (hinge_x + cxd, fy, plinth + 0.003 + dz), glass_mat, bevel=0, parent=pivot)
                # slim full-height pull integrated into the meeting stile
                hx = hinge_x + (dw - fw / 2 if left_hinge else -(dw - fw / 2))
                box(f"pull_{i}", (0.01, 0.03, 0.9), (hx, -0.036, plinth + 1.05), frame_mat, bevel=0.002, parent=pivot)
            if i in open_doors:
                ang = math.radians(92)
                pivot.rotation_euler = (0, 0, -ang if left_hinge else ang)
            door_objs.append(pivot)
    elif door == "sliding":
        n_pan = 3 if width >= 2.2 else 2
        overlap = 0.03
        pw = (width + overlap * (n_pan - 1)) / n_pan
        dh = height - plinth - 0.06
        box("track_top", (width, 0.09, 0.05), (x0 + width / 2, -0.045, height - 0.025), frame_mat, bevel=0.002)
        box("track_bot", (width, 0.09, 0.012), (x0 + width / 2, -0.045, plinth + 0.006), frame_mat, bevel=0.001)
        for i in range(n_pan):
            front = i % 2 == 1
            y = -0.07 if front else -0.03
            px0 = x0 + i * (pw - overlap)
            if i == 0 and slide_open > 0:
                px0 += slide_open * (pw - overlap)
            if i == n_pan - 1 and slide_open > 0 and n_pan == 2:
                px0 -= 0
            cx = px0 + pw / 2
            cz = plinth + 0.012 + dh / 2
            # wood panel with slim aluminium edge profiles (acts as the handle)
            fw = 0.018
            box(f"slide_edge_l_{i}", (fw, 0.03, dh), (px0 + fw / 2, y, cz), frame_mat, bevel=0.002)
            box(f"slide_edge_r_{i}", (fw, 0.03, dh), (px0 + pw - fw / 2, y, cz), frame_mat, bevel=0.002)
            if glass and i == 1 and n_pan == 3:
                inset = GLASSES[glass]()
                box(f"slide_frame_t_{i}", (pw - 2 * fw, 0.024, fw), (cx, y, plinth + 0.012 + dh - fw / 2), frame_mat, bevel=0.001)
                box(f"slide_frame_b_{i}", (pw - 2 * fw, 0.024, fw), (cx, y, plinth + 0.012 + fw / 2), frame_mat, bevel=0.001)
                box(f"slide_glass_{i}", (pw - 2 * fw, 0.006, dh - 2 * fw), (cx, y, cz), inset, bevel=0)
            else:
                box(f"slide_panel_{i}", (pw - 2 * fw, 0.02, dh), (cx, y, cz), fin, bevel=0.001)
                # horizontal reveal line for a crafted look
                box(f"slide_reveal_{i}", (pw - 2 * fw, 0.022, 0.004), (cx, y, plinth + 1.0), frame_mat, bevel=0)
    return hs, door_objs


# ---------------------------------------------------------------------------
# Room
# ---------------------------------------------------------------------------


def build_room(x_min=-3.0, x_max=3.5, back_y=0.6, ceiling=2.9, floor="porcelain", wall="#efe9e1", side_wall_x=None, rug=True, bench=True, bench_x=0.0, window=True):
    fm = mat_floor("Porcelain") if floor == "porcelain" else mat_wood("Floor Oak", "#8d6a48", "#b8966f", rough=0.35, scale=0.6)
    bpy.ops.mesh.primitive_plane_add(size=1, location=((x_min + x_max) / 2, back_y - 4, 0))
    f = bpy.context.active_object
    f.name = "floor"
    f.scale = (x_max - x_min + 4, 10, 1)
    f.data.materials.append(fm)
    wm = mat_plaster("Wall", wall)
    box("wall_back", (x_max - x_min + 4, 0.1, ceiling), ((x_min + x_max) / 2, back_y + 0.05, ceiling / 2), wm, bevel=0)
    box("ceiling", (x_max - x_min + 4, 10, 0.1), ((x_min + x_max) / 2, back_y - 4, ceiling + 0.05), mat_plaster("Ceiling", "#f4f1ec"), bevel=0)
    if side_wall_x is not None:
        box("wall_side", (0.1, 10, ceiling), (side_wall_x - 0.05, back_y - 4.9, ceiling / 2), wm, bevel=0)
    # skirting
    box("skirting", (x_max - x_min + 4, 0.012, 0.08), ((x_min + x_max) / 2, back_y - 0.006, 0.04), mat_paint("Skirting", "#e6dfd5", 0.5), bevel=0)
    # ceiling cove light line along the wall
    box("cove", (x_max - x_min + 4, 0.03, 0.01), ((x_min + x_max) / 2, back_y - 0.35, ceiling - 0.005), mat_emit("Cove 3200K", 3200, 6.0), bevel=0)
    if rug:
        bpy.ops.mesh.primitive_plane_add(size=1, location=(bench_x, -1.3, 0.004))
        r = bpy.context.active_object
        r.name = "rug"
        r.scale = (2.6, 1.8, 1)
        r.data.materials.append(mat_fabric("Rug", "#d9ccb8", sheen=0.8))
        m = r.modifiers.new("solid", "SOLIDIFY")
        m.thickness = 0.008
    if bench:
        bm = mat_fabric("Bench Boucle", "#efe7da", sheen=0.9)
        seat = box("bench_seat", (1.1, 0.42, 0.14), (bench_x, -1.25, 0.37), bm, bevel=0.035)
        soft(seat, 1)
        legmat = FRAMES["bronze"]()
        for sx in (-0.48, 0.48):
            for sy in (-0.16, 0.16):
                cylinder(f"bleg_{sx}_{sy}", 0.012, 0.3, (bench_x + sx, -1.25 + sy, 0.15), mat=legmat)
    if window:
        # daylight from the left through an unseen window
        area_light("window", (x_min - 0.2, -1.6, 1.6), (0, math.radians(-90), 0), 2.2, 700, k=5600, size_y=1.8)
    world = bpy.data.worlds.new("World")
    bpy.context.scene.world = world
    world.use_nodes = True
    bg = world.node_tree.nodes["Background"]
    bg.inputs["Color"].default_value = (0.9, 0.86, 0.8, 1)
    bg.inputs["Strength"].default_value = 0.35


def downlights(xs, y, ceiling=2.9, energy=160, k=3300):
    for i, x in enumerate(xs):
        s = spot(f"down_{i}", (x, y, ceiling - 0.02), energy, k=k, angle=70, blend=0.8, radius=0.04)
        s.rotation_euler = (math.radians(12), 0, 0)
        cylinder(f"downrim_{i}", 0.04, 0.005, (x, y, ceiling - 0.001), mat=mat_emit("Downlight", k, 20))


# ---------------------------------------------------------------------------
# Camera
# ---------------------------------------------------------------------------


def camera(loc, target, lens=35, shift_x=0.0, shift_y=0.0, res=(1200, 1500), dof=None):
    scene = bpy.context.scene
    data = bpy.data.cameras.new("cam")
    data.lens = lens
    data.sensor_width = 36
    data.shift_x = shift_x
    data.shift_y = shift_y
    data.clip_start = 0.05
    cam = bpy.data.objects.new("cam", data)
    bpy.context.collection.objects.link(cam)
    cam.location = loc
    d = Vector(target) - Vector(loc)
    # Keep verticals true: only yaw (and optional small pitch if target z differs a lot)
    yaw = math.atan2(d.x, d.y)
    horiz = math.hypot(d.x, d.y)
    pitch = math.atan2(d.z, horiz)
    cam.rotation_euler = (math.pi / 2 + pitch, 0, -yaw)
    if dof:
        data.dof.use_dof = True
        data.dof.focus_distance = dof[0]
        data.dof.aperture_fstop = dof[1]
    scene.camera = cam
    scene.render.resolution_x, scene.render.resolution_y = res
    scene.render.resolution_percentage = 100
    return cam


def project_hotspots(hs, cam):
    scene = bpy.context.scene
    bpy.context.view_layer.update()
    out = {}
    for key, p in hs.points:
        co = world_to_camera_view(scene, cam, p)
        if 0 <= co.x <= 1 and 0 <= co.y <= 1 and co.z > 0:
            out.setdefault(key, [round(co.x * 100, 1), round((1 - co.y) * 100, 1)])
    return out


# ---------------------------------------------------------------------------
# Shots
# ---------------------------------------------------------------------------

SHOTS = {}


def shot(name):
    def deco(fn):
        SHOTS[name] = fn
        return fn

    return deco


def product_scene(door, finish, frame="bronze", glass="bronze", handle="brass", open_doors=(), slide_open=0.0, layouts=None, width=2.4, cam="front", res=(1200, 1500), floor="porcelain"):
    build_room(floor=floor, bench=False, bench_x=0.2)
    hs, _ = build_wardrobe(-width / 2, width, door=door, finish=finish, frame=frame, glass=glass, handle=handle, open_doors=open_doors, slide_open=slide_open, layouts=layouts)
    downlights([-0.8, 0.8], -0.6)
    area_light("fill", (1.8, -3.2, 1.8), (math.radians(70), 0, math.radians(35)), 3.0, 140, k=4500)
    if cam == "front":
        c = camera((0.55, -3.55, 1.28), (0.0, 0.3, 1.28), lens=30, shift_y=0.015, res=res)
    elif cam == "open":
        c = camera((0.75, -3.9, 1.3), (-0.1, 0.3, 1.3), lens=30, shift_y=0.01, res=res)
    else:
        c = camera((0.35, -2.2, 1.2), (0.0, 0.3, 1.2), lens=35, res=res)
    return hs, c


def _register_products():
    for fin in ("oak", "walnut", "cashmere"):
        handle = "brass"
        SHOTS[f"hinged-{fin}-closed"] = (lambda f=fin, h=handle: product_scene("hinged", f, handle=h))
        SHOTS[f"hinged-{fin}-open"] = (lambda f=fin, h=handle: product_scene("hinged", f, handle=h, open_doors=(0, 1, 2, 3), cam="open"))
    for frame, glass, fin in (("bronze", "bronze", "walnut"), ("black", "smoked", "graphite"), ("champagne", "clear", "oak")):
        key = f"glass-{frame}"
        SHOTS[f"{key}-closed"] = (lambda fr=frame, g=glass, f=fin: product_scene("glass", f, frame=fr, glass=g))
        SHOTS[f"{key}-open"] = (lambda fr=frame, g=glass, f=fin: product_scene("glass", f, frame=fr, glass=g, open_doors=(0, 1, 2, 3), cam="open"))
    for fin, frame in (("oak", "bronze"), ("cashmere", "champagne"), ("walnut", "black")):
        SHOTS[f"sliding-{fin}-closed"] = (lambda f=fin, fr=frame: product_scene("sliding", f, frame=fr, glass="bronze"))
        SHOTS[f"sliding-{fin}-open"] = (lambda f=fin, fr=frame: product_scene("sliding", f, frame=fr, glass="bronze", slide_open=1.0, cam="open"))


_register_products()

# Interior layout variants for the hinged wardrobe, so the "see inside" image always
# matches the layout the customer picks (classic = the default "open" render).
for _fin in ("oak", "walnut", "cashmere"):
    SHOTS[f"hinged-{_fin}-open-long"] = (lambda f=_fin: product_scene("hinged", f, open_doors=(0, 1, 2, 3), cam="open", layouts=["hang_long", "shelves"]))
    SHOTS[f"hinged-{_fin}-open-drawers"] = (lambda f=_fin: product_scene("hinged", f, open_doors=(0, 1, 2, 3), cam="open", layouts=["drawers", "shelves"]))


def walkin_scene(finish="oak", frame="bronze", cam="wide", res=(2400, 1200)):
    """U-shaped walk-in closet with a central island. Inspiration scene."""
    ceiling = 2.9
    build_room(x_min=-2.6, x_max=2.6, back_y=2.4, ceiling=ceiling, bench=False, rug=False, window=False)
    fin = FINISHES[finish]()
    hs = Hotspots()
    # Back run: glass-fronted wardrobe
    bpy.ops.object.select_all(action="DESELECT")
    _, _ = build_wardrobe(-1.8, 3.6, depth=0.6, door="glass", finish=finish, frame=frame, glass="bronze", open_doors=(2, 3), layouts=["hang_long", "shelves", "hang_double"], hs=hs, seed=5)
    # move everything built so far to the back wall
    for ob in bpy.context.scene.objects:
        if ob.name.startswith(("floor", "wall", "ceiling", "skirting", "cove", "window")) or ob.type in ("CAMERA",):
            continue
        if ob.parent is None:
            ob.location.y += 1.8
    for key_i, (key, p) in enumerate(hs.points):
        hs.points[key_i] = (key, p + Vector((0, 1.8, 0)))
    # Side runs: open shelving (left) and shoes (right), rotated 90 degrees
    for side, lay, sx in (("L", ["hang_double", "shoes"], -2.45), ("R", ["shelves", "drawers"], 2.45)):
        before = set(bpy.context.scene.objects)
        build_wardrobe(-1.0, 2.0, depth=0.55, door="none", finish=finish, frame=frame, layouts=lay, hs=Hotspots(), seed=11 if side == "L" else 17)
        new = [o for o in bpy.context.scene.objects if o not in before and o.parent is None]
        pivot = empty(f"side_{side}", (0, 0, 0))
        for o in new:
            o.parent = pivot
        pivot.rotation_euler = (0, 0, math.radians(90 if side == "L" else -90))
        pivot.location = (sx, 0.9, 0)
    # Island: drawers + glass top display
    isl = box("island", (1.4, 0.7, 0.86), (0, 0.6, 0.43), fin, bevel=0.004)
    for i in range(3):
        box(f"isl_drawer_{i}", (1.36, 0.01, 0.24), (0, 0.245, 0.14 + i * 0.27), fin, bevel=0.002)
        box(f"isl_pull_{i}", (0.5, 0.012, 0.01), (0, 0.235, 0.25 + i * 0.27), FRAMES[frame](), bevel=0.003)
    box("isl_top", (1.44, 0.74, 0.012), (0, 0.6, 0.866), GLASSES["bronze"](), bevel=0.002)
    box("isl_velvet", (1.3, 0.6, 0.01), (0, 0.6, 0.845), mat_fabric("Velvet", "#4a3a2e", sheen=1.0), bevel=0)
    hs.add("island", (0, 0.3, 0.86))
    # Pendant light
    bpy.ops.mesh.primitive_torus_add(major_radius=0.45, minor_radius=0.012, location=(0, 0.6, 2.2))
    ring = bpy.context.active_object
    ring.data.materials.append(mat_emit("Pendant", 2800, 25))
    for k in range(3):
        a = k * 2 * math.pi / 3
        cylinder(f"wire_{k}", 0.0015, 0.7, (0.45 * math.cos(a), 0.6 + 0.45 * math.sin(a), 2.55), mat=FRAMES["black"]())
    area_light("pendant_bounce", (0, 0.6, 2.15), (0, 0, 0), 0.9, 90, k=2800, shape="DISK")
    area_light("pendant_up", (0, 0.6, 2.25), (math.pi, 0, 0), 0.9, 30, k=2800, shape="DISK")
    downlights([-1.2, 1.2], 1.6, ceiling=ceiling, energy=120)
    area_light("fill", (0, -3.5, 1.9), (math.radians(80), 0, 0), 3.0, 180, k=4200)
    if cam == "wide":
        c = camera((0.0, -3.3, 1.4), (0.0, 2.0, 1.35), lens=24, shift_y=0.02, res=res)
    elif cam == "mobile":
        c = camera((0.35, -2.4, 1.3), (0.0, 2.0, 1.35), lens=24, shift_y=0.03, res=res)
    else:
        c = camera((1.2, -2.6, 1.35), (-0.3, 2.0, 1.2), lens=28, res=res)
    return hs, c


SHOTS["walkin-oak-wide"] = lambda: walkin_scene("oak", "bronze", "wide", (2400, 1200))
SHOTS["walkin-oak-mobile"] = lambda: walkin_scene("oak", "bronze", "mobile", (1080, 1440))
SHOTS["walkin-walnut-angle"] = lambda: walkin_scene("walnut", "black", "angle", (1200, 1500))
SHOTS["walkin-cashmere-angle"] = lambda: walkin_scene("cashmere", "champagne", "angle", (1200, 1500))


def bespoke_scene(res=(1200, 1500)):
    """Made-to-measure wall system with an integrated dressing table."""
    build_room(bench=False, bench_x=0.2)
    hs = Hotspots()
    build_wardrobe(-2.1, 1.4, door="hinged", finish="cashmere", handle="brass", layouts=["hang_double"], hs=hs, seed=3)
    build_wardrobe(0.9, 1.4, door="hinged", finish="cashmere", handle="brass", open_doors=(0,), layouts=["shelves"], hs=hs, seed=4)
    fin = FINISHES["oak"]()
    # Vanity niche between the two tall units
    box("vanity_top", (1.6, 0.5, 0.04), (0.1, 0.35, 0.76), fin, bevel=0.003)
    box("vanity_drawer", (1.6, 0.46, 0.14), (0.1, 0.37, 0.67), fin, bevel=0.003)
    box("niche_shelf", (1.6, 0.3, 0.03), (0.1, 0.45, 1.95), fin, bevel=0.003)
    box("niche_top", (1.6, 0.6, 0.6), (0.1, 0.3, 2.25), FINISHES["cashmere"](), bevel=0.002)
    bpy.ops.mesh.primitive_cylinder_add(radius=0.38, depth=0.01, location=(0.1, 0.57, 1.38), rotation=(math.pi / 2, 0, 0), vertices=96)
    mirror = bpy.context.active_object
    mirror.data.materials.append(mat_metal("Mirror", "#e8e8e8", rough=0.02))
    bpy.ops.mesh.primitive_torus_add(major_radius=0.385, minor_radius=0.008, location=(0.1, 0.565, 1.38), rotation=(math.pi / 2, 0, 0))
    bpy.context.active_object.data.materials.append(mat_metal("Brushed Brass", "#b8925a", 0.3, 0.5))
    box("niche_led", (1.56, 0.008, 0.004), (0.1, 0.2, 1.93), mat_emit("LED 3000K", 3000, 14.0), bevel=0)
    # stool
    st = box("stool", (0.45, 0.38, 0.1), (0.1, -0.25, 0.45), mat_fabric("Bench Boucle", "#efe7da", sheen=0.9), bevel=0.03)
    soft(st, 1)
    cylinder("stool_leg", 0.02, 0.4, (0.1, -0.25, 0.2), mat=FRAMES["bronze"]())
    downlights([-1.4, 0.1, 1.6], -0.6)
    area_light("fill", (1.8, -3.4, 1.8), (math.radians(70), 0, math.radians(35)), 3.0, 150, k=4500)
    c = camera((0.5, -3.8, 1.3), (0.0, 0.3, 1.3), lens=26, shift_y=0.01, res=res)
    return hs, c


SHOTS["bespoke-wall"] = bespoke_scene
SHOTS["bespoke-wall-wide"] = lambda: bespoke_scene(res=(2400, 1200))


# --- Hero ---------------------------------------------------------------------

def hero_wide(res=(2400, 1200)):
    """Wide hero: the wardrobe sits off-centre with room context and space for type."""
    build_room(bench=True, bench_x=-0.2, rug=True)
    hs, _ = build_wardrobe(-1.5, 3.0, door="glass", finish="walnut", frame="bronze", glass="bronze", open_doors=(3,))
    downlights([-1.0, 1.0], -0.6)
    area_light("fill", (2.4, -4.2, 1.8), (math.radians(70), 0, math.radians(35)), 3.0, 170, k=4500)
    c = camera((0.0, -5.0, 1.3), (0.0, 0.3, 1.3), lens=30, shift_y=0.05, res=res)
    return hs, c


SHOTS["hero-glass-wide"] = hero_wide
SHOTS["hero-glass-mobile"] = lambda: product_scene("glass", "walnut", frame="bronze", glass="bronze", open_doors=(3,), width=3.0, cam="front", res=(1080, 1440))


# --- Details ------------------------------------------------------------------


def detail_scene(kind, res=(1200, 1200)):
    build_room(bench=False, rug=False, window=True)
    hs = Hotspots()
    if kind == "handle":
        build_wardrobe(-1.2, 2.4, door="hinged", finish="oak", handle="brass", layouts=["hang_double", "shelves"])
        # Door 2 is hinged on the left, so its bar handle sits near x = +0.54 m.
        c = camera((0.86, -0.62, 1.28), (0.55, 0.0, 1.1), lens=65, res=res, dof=(0.7, 3.2))
    elif kind == "glass-frame":
        build_wardrobe(-1.2, 2.4, door="glass", finish="walnut", frame="bronze", glass="bronze", layouts=["hang_long", "shelves"])
        c = camera((-0.3, -0.7, 1.05), (0.0, 0.0, 0.95), lens=60, res=res, dof=(0.78, 3.2))
        bpy.context.scene.view_settings.exposure = -0.7
        area_light("frame_key", (-0.9, -1.2, 1.4), (math.radians(65), 0, math.radians(-35)), 1.2, 90, k=4000)
    elif kind == "led-shelf":
        build_wardrobe(-1.2, 2.4, door="glass", finish="walnut", frame="bronze", glass="bronze", open_doors=(0, 1, 2, 3), layouts=["shelves", "shelves"])
        c = camera((-0.45, -0.75, 1.55), (-0.6, 0.3, 1.3), lens=55, res=res, dof=(1.0, 2.8))
    elif kind == "drawer":
        build_wardrobe(-1.2, 2.4, door="hinged", finish="walnut", handle="bronze", open_doors=(0, 1, 2, 3), layouts=["drawers", "drawers"])
        c = camera((-0.6, -0.7, 1.35), (-0.6, 0.3, 0.6), lens=45, res=res, dof=(1.1, 3.5))
    elif kind == "sliding-profile":
        build_wardrobe(-1.2, 2.4, door="sliding", finish="oak", frame="bronze", glass="bronze")
        c = camera((-0.1, -0.75, 1.45), (-0.4, 0.0, 1.2), lens=55, res=res, dof=(0.8, 2.8))
    downlights([-0.6, 0.6], -0.6)
    area_light("fill", (1.5, -2.5, 1.8), (math.radians(70), 0, math.radians(35)), 2.0, 120, k=4500)
    return hs, c


for _k in ("handle", "glass-frame", "led-shelf", "drawer", "sliding-profile"):
    SHOTS[f"detail-{_k}"] = (lambda k=_k: detail_scene(k))


def swatch_scene(key, res=(600, 600)):
    """Flat material swatch for the finish picker (lit like the room)."""
    world = bpy.data.worlds.new("World")
    bpy.context.scene.world = world
    world.use_nodes = True
    world.node_tree.nodes["Background"].inputs["Strength"].default_value = 0.6
    if key in FINISHES:
        mat = FINISHES[key]()
    elif key.startswith("frame-"):
        mat = FRAMES[key[6:]]()
    else:
        mat = GLASSES[key[6:]]()
    if key.startswith("glass-"):
        box("back", (0.5, 0.01, 0.5), (0, 0.06, 0), mat_wood("Smoked Walnut", "#3b261a", "#6e4a33"), bevel=0)
    box("swatch", (0.4, 0.02, 0.4), (0, 0, 0), mat, bevel=0.004)
    area_light("key", (-0.6, -0.8, 0.6), (math.radians(60), 0, math.radians(-35)), 1.2, 60, k=4500)
    area_light("rim", (0.6, -0.4, -0.3), (math.radians(110), 0, math.radians(55)), 0.8, 20, k=3200)
    c = camera((0, -0.5, 0), (0, 0, 0), lens=85, res=res)
    return Hotspots(), c


for _k in list(FINISHES) + [f"frame-{k}" for k in FRAMES] + [f"glass-{k}" for k in GLASSES]:
    SHOTS[f"swatch-{_k}"] = (lambda k=_k: swatch_scene(k))


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------


def main():
    argv = sys.argv[sys.argv.index("--") + 1 :] if "--" in sys.argv else sys.argv[1:]
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", default="out")
    ap.add_argument("--only", nargs="*")
    ap.add_argument("--samples", type=int, default=128)
    ap.add_argument("--scale", type=int, default=100, help="resolution percentage (for quick tests)")
    ap.add_argument("--list", action="store_true")
    ap.add_argument("--skip-existing", action="store_true")
    a = ap.parse_args(argv)
    if a.list:
        print("\n".join(SHOTS))
        return
    os.makedirs(a.out, exist_ok=True)
    names = a.only or list(SHOTS)
    meta_path = os.path.join(a.out, "hotspots.json")
    meta = json.load(open(meta_path)) if os.path.exists(meta_path) else {}
    for name in names:
        path = os.path.join(a.out, f"{name}.png")
        if a.skip_existing and os.path.exists(path):
            continue
        random.seed(hash(name) & 0xFFFF)
        _MATS.clear()
        scene = reset_scene()
        scene.cycles.samples = a.samples if not name.startswith("swatch") else min(a.samples, 64)
        hs, cam = SHOTS[name]()
        scene.render.resolution_percentage = a.scale
        spots = project_hotspots(hs, cam)
        if spots:
            meta[name] = spots
        scene.render.filepath = path
        bpy.ops.render.render(write_still=True)
        print(f"rendered {name}", flush=True)
        json.dump(meta, open(meta_path, "w"), indent=1)


if __name__ == "__main__":
    main()
