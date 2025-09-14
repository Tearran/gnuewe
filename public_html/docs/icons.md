---
title: Armbian
tags: 
  - images
  - icone
  - site-tools
---


# SVG Icon Sprite (`icons.svg`) 
## Usage Guide:

Version: 0.1 (tentative)  
Status: Draft (adjust sections as needed for your project)

---

## 1. Overview

This repository provides a reusable inline SVG symbol sprite (`icons.svg`) containing a set of 24×24 UI icons.  
You include the sprite **once** in the document (inline or injected) and reference each icon with `<use href="#icon-id">`.

Advantages:
- Single DOM definition, many lightweight `<use>` references.
- Inherits `currentColor` for easy theming.
- No network waterfall if inlined.
- Easy to extend with additional symbols.

---

## 2. Quick Start

### 2.1 Inline (recommended for first paint + theming)
Paste the entire sprite near the start of `<body>` (or via server-side include):

```html
<body>
  <!-- Icon sprite (load once) -->
  <svg xmlns="http://www.w3.org/2000/svg" style="display:none;" aria-hidden="true">
    <!-- (full symbols from icons.svg inserted here) -->
  </svg>

  <!-- Use an icon -->
  <button class="btn">
    <svg class="icon" width="20" height="20" aria-hidden="true">
      <use href="#i-refresh"></use>
    </svg>
    Refresh
  </button>
</body>
```

### 2.2 External file (cached)
```html
<!-- Inject once (hidden) -->
<object type="image/svg+xml" data="/assets/icons.svg" hidden></object>

<!-- Later: -->
<svg class="icon" aria-hidden="true" width="20" height="20">
  <use href="#i-search"></use>
</svg>
```

Alternatives: `<iframe hidden>`, fetch + DOM injection, or build-time bundling.

---

## 3. Referencing Icons

Basic:
```html
<svg class="icon" width="24" height="24" aria-hidden="true">
  <use href="#i-settings"></use>
</svg>
```

Legacy compatibility (older Safari / IE11):
```html
<use xlink:href="#i-settings"></use>
```

Do not duplicate the symbol sprite; only one copy of the `<svg ... style="display:none">` wrapper should exist per document.

---

## 4. Accessibility

| Use Case | Pattern |
|----------|---------|
| Purely decorative | `<svg aria-hidden="true">` |
| Has semantic meaning | Add `<title>` (and optionally `role="img"`) |
| Dynamic label | Manage `<title id>` and `aria-labelledby` |

Example (semantic):
```html
<svg class="icon" role="img" aria-labelledby="ttl-refresh">
  <title id="ttl-refresh">Refresh data</title>
  <use href="#i-refresh"></use>
</svg>
```

If the adjacent text already conveys identical meaning (e.g., button text “Delete”), treat the icon as decorative (`aria-hidden="true"`).

---

## 5. Theming & Styling

All icons rely on `currentColor` unless explicitly overridden.

```css
.icon {
  width: 1em;
  height: 1em;
  display: inline-block;
  vertical-align: middle;
  fill: currentColor; /* Ensure fill inheritance */
}
.icon.muted { color: #777; }
.icon.danger { color: #d63636; }
```

Per-icon override:
```css
.icon[name="warning"] { color: #e6a100; }
```

Stroke-based icons: if you add strokes later, ensure consistent `stroke-linecap` / `stroke-linejoin`.

---

## 6. Icon Inventory (Current Set)

Ids and indicative semantics:

```
i-book            Documentation / library
i-palette         Theme / appearance / design
i-home            Home / dashboard
i-settings        Settings / preferences
i-pin             Location / pin
i-search          Search
i-filter          Filter
i-sort-v          Sort vertical (two axes)
i-refresh         Refresh / sync
i-plus            Add / create
i-minus           Remove / collapse
i-edit            Edit / modify
i-trash           Delete
i-download        Download (generic)
i-download-box    Download into container
i-download-archive Download archive/package
i-download-file   Download file
i-upload          Upload
i-clipboard       Clipboard / copy
i-external        External link
i-info            Informational
i-warning         Warning / caution
i-error           Error / failure
i-check           Success / confirmation
i-close           Close / dismiss
i-user            User / profile
i-lock            Locked / secure
i-lock-open       Unlocked
i-tag             Tag / label
i-star            Favorite / rating
i-eye             Visible / preview
i-eye-off         Hidden / conceal
i-bell            Notifications
i-terminal        Console / shell
i-database        Database / storage
i-link            Link / connect
i-unlink          Unlink / disconnect
i-list            List view
i-grid            Grid view
i-arrow-right     Navigate forward
i-chevron-down    Expand / dropdown
i-folder          Folder
i-file            File
i-menu            Hamburger / menu
i-color           Color mode / palette (overlaps with i-palette)
```

Note: `i-palette` vs `i-color` are semantically close. Decide whether both are required.

---

## 7. Adding New Icons

1. Maintain `viewBox="0 0 24 24"` for consistency.
2. Use a unique id naming pattern: `i-{kebab-case}`.
3. Optimize path data:
   - Merge contiguous shapes when possible.
   - Remove redundant decimals (`.5` not `0.5`).
   - Round coordinates to at most 2–3 decimal places.
4. Keep related shapes grouped logically (for potential animation ordering).
5. Test at 16px, 20px, 24px for legibility.

Template:
```xml
<symbol id="i-new-icon" viewBox="0 0 24 24">
  <path d="..."/>
</symbol>
```

---

## 8. Optimization Workflow (Optional)

Example using `svgo`:

`svgo.config.json` (subset):
```json
{
  "multipass": true,
  "plugins": [
    "cleanupAttrs",
    "removeComments",
    "removeMetadata",
    { "name": "convertPathData", "params": { "straightCurves": true } }
  ]
}
```

Process step:
```bash
svgo --config=svgo.config.json -i icons.svg -o icons.min.svg
```

If generating a subset for a specific bundle:
- Parse HTML/JSX templates for `#i-...` usage.
- Extract only matching `<symbol>` nodes.
- Emit a reduced sprite for that page (build-time only; not required for most cases).

---

## 9. Integration Examples

### 9.1 React (static import then inject)
```jsx
// SpriteInjector.jsx
import sprite from './icons.svg?raw';

export function SpriteInjector() {
  return <div dangerouslySetInnerHTML={{ __html: sprite }} />;
}

// Usage in root layout
// <SpriteInjector />
// <svg className="icon" aria-hidden="true"><use href="#i-user" /></svg>
```

### 9.2 Vue
```vue
<!-- In App.vue -->
<template>
  <div v-html="sprite" style="display:none" aria-hidden="true"></div>
  <button>
    <svg class="icon" aria-hidden="true"><use href="#i-plus" /></svg>
    Add
  </button>
</template>

<script setup>
import sprite from './icons.svg?raw';
</script>
```

### 9.3 Svelte
```svelte
<!-- +layout.svelte -->
{@html sprite}
<svg class="icon" aria-hidden="true"><use href="#i-terminal" /></svg>

<script>
  import sprite from './icons.svg?raw';
</script>
```

### 9.4 Server-Side (Express / Node)
Embed at render time:
```js
const fs = require('fs');
const sprite = fs.readFileSync('public/icons.svg', 'utf8');
res.send(`<!doctype html><html><body>${sprite} ... </body></html>`);
```

---

## 10. Caching & Versioning

Strategy:
- Inline copy: No additional HTTP request; update invalidates instantly on deploy.
- External file: Use filename hashing (`icons.v0.1.svg` → `icons.v0.2.svg`) or query param `icons.svg?v=0.2`.

Increment version whenever geometry changes; CSS-driven color/stroke changes do not require a version bump unless appearance contract changes.

---

## 11. Performance Considerations

| Topic | Notes |
|-------|-------|
| First Paint | Inline avoids an extra network round-trip. |
| DOM Weight | Symbol definitions are inert; `<use>` clones are lightweight. |
| Subsetting | Only necessary at very large icon counts (>200 symbols) or strict budgets. |
| HTTP Cache | External sprite benefits from long cache (immutable hashed filename). |
| FOUC / Flash | Avoid external injection that depends on JS executing late. |

---

## 12. Animations (Optional)

You can target internal paths with `:where()` or descendant selectors when instanced:

```css
.icon.spin use[href="#i-refresh"] {
  animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
```

If advanced per-path animation needed, wrap with an inline `<svg>` referencing `<symbol>` content duplicated explicitly (rare case).

---

## 13. Troubleshooting

| Symptom | Cause | Resolution |
|---------|-------|-----------|
| Icon not visible | Missing sprite or wrong id | Confirm sprite loaded and `href="#i-..."` matches symbol id |
| Wrong color | CSS not applying `fill: currentColor` | Add `.icon { fill: currentColor; }` |
| Broken in older browser | `href` unsupported | Use `xlink:href` fallback |
| Duplicate symbols error | Sprite injected multiple times | Ensure injection occurs once (guard at app root) |
| Layout shift | Dimensions missing | Set explicit `width`/`height` or CSS sizing |

---

## 14. Migration Guidelines

If moving from embedded `<img>` or font icons:
1. Introduce sprite inline.
2. Replace `<img src=".../trash.svg">` with `<svg aria-hidden="true"><use href="#i-trash"></use></svg>`.
3. Remove legacy icon font CSS after coverage reaches 100%.
4. Audit for accessibility differences (fonts often relied on `aria-hidden="true"` already).

---

## 15. Consistency Rules (Design)

| Rule | Rationale |
|------|-----------|
| 24×24 grid, 1–2px stroke equivalent visual weight | Cohesive look |
| Avoid excessive detail below 16px | Legibility |
| Align key geometry on 0.5 or integer coordinates | Crisp rendering |
| Prefer path merges to many disjoint paths | Smaller file |
| Maintain optical centering (not exact bounding box) | Balanced appearance |

---

## 16. Validation Checklist (Before Commit)

- [ ] New symbol id unique and prefixed with `i-`.
- [ ] `viewBox="0 0 24 24"` preserved.
- [ ] No inline `style` attributes unless necessary.
- [ ] Path data optimized (no redundant decimals).
- [ ] Works at 16px + 24px render sizes.
- [ ] Accessibility: Provide guidance if meaning differs from name.

---

## 17. Security Notes

- Do not accept untrusted runtime user input into the sprite; treat it as a static asset.
- Avoid inline event handlers or script tags inside `<symbol>` (none included now).

---

## 18. License (Placeholder)

Insert actual license (e.g., MIT, Apache-2.0, CC-BY-4.0).  
If combining third-party paths, ensure their licenses allow redistribution and include attribution if required.

---

## 19. Example Minimal Build Extraction (Optional)

If you want to auto-generate a subset sprite per page:

```js
// build/extract-icons.js (example sketch)
import fs from 'node:fs';

const source = fs.readFileSync('icons.svg', 'utf8');
const usedIds = new Set();

// Naive scan: look for href="#i-..."
const html = fs.readFileSync('dist/index.html', 'utf8');
[...html.matchAll(/href="#(i-[a-z0-9-]+)"/g)].forEach(m => usedIds.add(m[1]));

const symbols = [...source.matchAll(/<symbol[^>]*id="(i-[^"]+)"[\s\S]*?<\/symbol>/g)]
  .filter(m => usedIds.has(m[1]))
  .map(m => m[0])
  .join('\n');

const subset = `<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">\n${symbols}\n</svg>\n`;
fs.writeFileSync('dist/icons-subset.svg', subset);
```

(Tentative; refine with a real XML parser for robustness.)

---

## 20. Roadmap (Optional Section)

| Item | Status |
|------|--------|
| Remove semantic overlap (palette vs color) | (planned) |
| Add dark-mode specific variant icons | (planned) |
| Add animation utilities | (tentative) |
| Provide TypeScript mapping enum | (optional) |

---

## 21. FAQ

Q: Can `<use>` re-color individual segments?  
A: Only via CSS selectors targeting internal shapes if they have class names. Current symbols have no per-part classes; add minimally if needed.

Q: Can I inline only a single symbol?  
A: Yes, but you lose the benefit of a shared sprite; simpler for one-off icons.

Q: Do I need `role="img"`?  
A: Only when the icon alone conveys essential meaning (no adjacent textual label).

---

## 22. Change Log (Start Here When Versioning)

- 0.1: Initial symbol set, baseline documentation.

---

## 23. Contributing

1. Open an issue describing new icon purpose (ensure non-duplication).
2. Provide monochrome SVG (24×24).
3. Follow optimization + checklist.
4. Add entry to inventory list (Section 6).
5. Update version if paths changed (Section 22).

---

## 24. Minimal Copy-Paste Snippet

```html
<!-- Include once -->
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
  <!-- symbols -->
</svg>

<!-- Use -->
<svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>
```

---

(End of README)