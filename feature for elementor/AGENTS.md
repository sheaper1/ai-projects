# Elementor page workflow

- When the user provides a Figma design for a page, identify the exact page frame before building.
- Treat Figma as the canonical section inventory; use an existing WordPress page only as a structural donor and restore any sections present in Figma but missing from WordPress.
- Reuse verified Elementor export patterns from this workspace and the local Claude `figma-to-elementor` specification.
- Download every design image, upload it to `https://staging.digirelation.dev/`, and reference only staging media URLs/IDs in the final JSON.
- Produce one Elementor page-import JSON file in this workspace.
- Exclude the site header and footer unless the user explicitly requests them.
- Keep changes limited to the requested page and verify JSON parsing, unique element IDs, media references, and Elementor root format.
