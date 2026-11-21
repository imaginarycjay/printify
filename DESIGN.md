# PressQueue Design System (`DESIGN.md`)

> **Visual World:** Editorial Print-House Light-Mode First (anchored in `resources/views/welcome.blade.php` and `resources/views/pages/⚡shops.blade.php`)
> **Primary Mode:** `Operate` (operational dashboards, queues, inventory ledgers, checkout, settings) paired with `Persuade` (`welcome.blade.php`) and `Read` (Official Receipts & Standardized Reports).

---

## 1. Core Surface & Elevation Palette

PressQueue uses a warm, tactile paper-and-ink stone palette built for high contrast in sunlit print shops, laptops, and printed academic figures, with clean explicit `dark:` counterparts.

| Token | Role | Light Mode | Dark Mode (`html.dark`) |
| :--- | :--- | :--- | :--- |
| `--pq-canvas` | Page background canvas | `#f5f5f4` (`stone-100`) | `#0c0a09` (`stone-950`) |
| `--pq-surface` | Primary cards, modals, sticky topbars | `#ffffff` (`white`) | `#1c1917` (`stone-900`) |
| `--pq-well` | Inset wells, table heads, secondary tiles | `#fafaf9` (`stone-50`) | `#292524` (`stone-800/60`) |
| `--pq-border` | Hairline container & divider strokes | `#e7e5e4` (`stone-200`) | `#292524` (`stone-800`) |
| `--pq-border-strong` | Input fields & active outline contrast | `#d6d3d1` (`stone-300`) | `#44403c` (`stone-700`) |
| `--pq-ink` | Primary headings & tabular metrics | `#1c1917` (`stone-900`) | `#f5f5f4` (`stone-100`) |
| `--pq-muted` | Supporting labels, captions, metadata | `#57534e` (`stone-600`) | `#a8a29e` (`stone-400`) |
| `--pq-accent` | Brand primary action & focus highlight | `#f59e0b` (`amber-500`) | `#f59e0b` (`amber-500`) |
| `--pq-accent-strong` | Link & text-level accent (`>= 4.5:1` on white) | `#b45309` (`amber-700`) | `#fbbf24` (`amber-400`) |

### Elevation & Border Discipline
- Declare elevation **once** per container: crisp `1px` hairline border (`border-stone-200 dark:border-stone-800`) plus at most a subtle `shadow-xs` (`0 1px 2px rgba(28, 25, 23, 0.05)`) on elevated cards or modals.
- Card border radius: `rounded-2xl` (`16px`) or `rounded-3xl` (`24px`) for top-level panels; `rounded-xl` (`12px`) for controls/buttons/inputs; `rounded-full` / `rounded-md` only for compact status pills.

---

## 2. Typography & Tabular Precision

- **Primary Font:** `'Instrument Sans'` loaded via `@bunny/fonts`, fallback `ui-sans-serif, system-ui, sans-serif`.
- **Hierarchy:**
  - Page Heading (`h1`): `text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100`
  - Section Heading (`h2`): `text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100`
  - Card / Table Label: `text-xs font-semibold uppercase tracking-wider text-stone-500 dark:text-stone-400`
  - Numeric Discipline: Apply `tabular-nums` to **every** price (`₱`), page count, copy count, sheet balance, and order/tracking number (`PQ-*`, `TRK-*`, `OR-*`).

---

## 3. WCAG-AA Semantic Color Pairs (Light + Dark Required)

Never use dark-mode-only pastel text classes (`text-rose-300`, `text-sky-300`, `text-indigo-300`, `text-emerald-300`, `text-cyan-400`) on light surfaces. Every status badge and alert MUST specify explicit Light + `dark:` pairs (or use `.pq-badge-*` component classes in `resources/css/app.css`):

- **Primary CTA:** `bg-amber-500 hover:bg-amber-400 text-stone-950 font-semibold` (or high-contrast ink button `bg-stone-900 hover:bg-stone-800 text-white dark:bg-amber-500 dark:text-stone-950`).
- **Success / Normal / Verified / Ready:** `bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800`
- **Warning / Busy / Pending / Maintenance:** `bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800`
- **Danger / At Capacity / Rejected / Aging / Refund:** `bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800`
- **Info / Specs Locked / Wide Format / DP Verified:** `bg-sky-50 text-sky-800 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800`
- **Neutral / Paused / Draft / Unassigned:** `bg-stone-100 text-stone-700 border border-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:border-stone-700`

---

## 4. Universal Contextual Back Navigation Standard

- **Never** rely on raw browser `window.history.back()` or `url()->previous()`, which break after Livewire POSTs, redirects, or direct deep links.
- Resolve parent routes deterministically via `App\Support\Navigation::backTarget()` and `App\Support\Navigation::workspaceHome()` with strict role-scoped `?from=` allow-listing.
- Render `<x-app-back-link>` (inside `<x-platform-header>` or Settings/Report toolbars) in the **consistent top-left header slot** on every non-root view:
  - **Root Portals (`owner.dashboard`, `staff.dashboard`, `customer.dashboard`, `admin.shops`, `welcome`):** Show the PressQueue brand mark—never a stray or dead-end back arrow.
  - **Owner Sub-Apps (`owner.platform`, `owner.analytics-hub`, `owner.inventory-hub`, `owner.document-printing`, `owner.thesis-binding`, `owner.wizard`):** Show `← App Launcher` (or `?from=` service return when navigated from Document Printing / Thesis Binding).
  - **Owner Print View (`owner.reports.show`):** Show `← Standardized Reports` linking to `route('owner.analytics-hub', ['tab' => 'reports'])` (`print:hidden`).
  - **Customer Flow (`shops.index` authenticated → `← My Orders`; `shops.show` → `← Find Shops`; Checkout & Order Wizards → `← [Shop Name]`):** Clear, linear navigation back to the exact parent context.
  - **Staff Modals/Drawers (`staff.dashboard`):** Ticket, Direct Browser Print Preview, and Spoilage overlays provide explicit `← Back to Queue` / `Close` actions.
  - **Fortify Settings (`profile.edit`, `security.edit`, `appearance.edit`):** Always render `← Back to [Role Workspace]` (`App Launcher`, `Production Queue`, `My Orders`, or `Platform Admin`) via `Navigation::workspaceHome()`.

---

## 5. Strict Anti-Slop Bans

1. **Zero ambient `blur-3xl` / `blur-[140px]` radial glow blobs** behind dashboards, cards, or settings.
2. **Zero UI emojis** (`📚`, `📄`, `⚡`, `📦`, `📕`, `📘`, `🧾`, `🖨️`, `✅`) inside buttons, filter chips, badges, or section titles—use crisp monochrome Flux / Heroicon SVGs.
3. **Zero noisy promotional micro-pills or eyebrows** (`• LIVE CASHFLOW`, `• SHOP FLOOR LIVE`, `CORE`, `Single Sign-On Print Operations Facility`, eyebrow kickers above page `h1`s).
4. **Zero CSS theme-inversion hacks:** Every new or refactored component uses native Light-Mode-first Tailwind classes + explicit `dark:` variants (the legacy `html:not(.dark)` inversion block in `resources/css/app.css` is scheduled for complete removal once `CR-2` Wave 2D finishes migrating all views).
