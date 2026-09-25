# Image generation prompts (for Codex / any image model)

**Status:** no image-generation tool (Codex or other) was connected to the environment this project was built in. I checked the available tools and found none. The shell also had no route to image services. So every generated image in this project is a **procedural 3D render**, made with `tools/renders/wardrobes.py` (Blender Cycles through the `bpy` package). The renders have consistent lighting, materials and perspective, and hotspot positions are projected from the real geometry.

## To connect Codex image generation later

1. Enable the Codex (OpenAI) image tool, or an MCP server that exposes image generation, in the Claude Code / claude.ai environment. Put its API key in the environment's secrets, never in the theme.
2. Allow its host in the environment's network policy.
3. Generate with the prompts below. Save the files as PNG under `tools/renders/out/<name>.png` using the **same names** as the renders. Then run `python tools/renders/optimize.py --src tools/renders/out --dest optimum-closets/assets/img/renders`. The theme, the demo importer and the concepts pick them up automatically.
4. Generated images must keep the "Illustrative render" label (`_oc_render = 1`). Use them only for inspiration scenes. **Never** use them as a completed Optimum Closets project, or in place of a real product photo on a product people buy.

## Shared style block (prepend to every prompt)

> Photorealistic interior photograph, Saudi contemporary bedroom, warm off-white plaster walls, large-format light beige porcelain floor with thin grout lines, soft daylight from the left plus warm 3000–3500 K recessed downlights and a ceiling cove light, 35 mm lens at eye level (1.3 m), vertical lines perfectly vertical (two-point perspective), calm and uncluttered, quiet luxury, true-to-life proportions, no text, no logos, no people.

## Prompts

| Name | Size | Prompt (after the style block) | Accuracy checks |
|---|---|---|---|
| `hero-glass-wide` | 2400×1200 | A 3 m floor-to-ceiling wardrobe with six hinged glass doors in slim 22 mm bronze aluminium frames, bronze-tinted glass, smoked walnut interior, warm LED strips under every shelf and a vertical LED line at each divider. The right-most door is open about 90°. Folded knitwear on the top shelves, handbags, hanging shirts and long white thobes and black abayas visible through the glass. A cream bouclé bench and a beige rug in the foreground. The wardrobe fills the centre 60 % of the frame, with room either side for text. | Door count, hinge side and frame width stay consistent. Glass shows reflections without hiding the interior. Shelves are level. |
| `hero-glass-mobile` | 1080×1440 | Same wardrobe and room, portrait framing, wardrobe centred, closer. | Same checks. |
| `hinged-{oak,walnut,cashmere}-closed` | 1200×1500 | A 2.4 m wardrobe with four flush hinged doors in {natural straight-grain oak veneer / smoked walnut veneer / matt cashmere lacquer}, long vertical brushed-brass bar handles at the two meeting stiles, recessed dark plinth, doors closed. | Four equal doors, handles at the correct stiles, grain vertical. |
| `hinged-*-open`, `-open-long`, `-open-drawers` | 1200×1500 | Same wardrobe, all four doors open about 90°, camera slightly right. Interior: {double hanging with shirts above and trousers below + shelves / long hanging for thobes and abayas + shelves / four interior drawers + shelves}, warm LED under the shelves. | Doors hinge on the outer edges of each pair. The interior matches the named layout exactly. |
| `glass-{bronze,black,champagne}-closed/open` | 1200×1500 | A 2.4 m wardrobe with four hinged glass doors, slim {bronze / black / champagne} aluminium frames, {bronze / smoked / clear} glass, {walnut / graphite / oak} interior with LED shelves, doors {closed / open}. | Frame colour and glass tint match the pairing. |
| `sliding-{oak,cashmere,walnut}-closed/open` | 1200×1500 | A 2.4 m sliding wardrobe with three panels on a top track: outer panels in {oak / cashmere / walnut} with thin vertical metal edge profiles and one horizontal reveal line, a centre panel of bronze glass. {Closed / the left panel slid over the centre showing hanging and shelves}. | Panels overlap on two tracks. The top track is visible and nothing floats. |
| `walkin-{oak,walnut,cashmere}-*` | varies | A U-shaped walk-in closet: a glass-fronted back wall with two doors open, open side runs with hanging, shoes and shelves, a central drawer island with a bronze glass top, a thin ring pendant light. | Symmetric layout. The island is centred and the pendant hangs straight. |
| `bespoke-wall(-wide)` | 1200×1500 / 2400×1200 | A made-to-measure wall of matt cashmere wardrobes with brass handles, a central oak vanity niche with a round brass-framed mirror, an LED shelf and a bouclé stool. | The mirror is round and centred, and the vanity sits at desk height (≈76 cm). |
| `detail-*` | 1200×1200 | Close-ups with shallow depth of field: brass handle on oak; bronze frame corner with glass; LED-lit shelves; walnut drawers with bronze pulls; sliding door edge profile and top track. | Materials match the products above. |
