# PressQueue Design System (`DESIGN.md`)

> **Visual World:** Pressroom Slate & Cobalt — Flat, High-Contrast Light-Mode First with Midnight Slate Dark Mode
> **Primary Mode:** `Operate` (operational dashboards, queues, inventory ledgers, checkout, settings) paired with `Persuade` (`welcome.blade.php`) and `Read` (Official Receipts & Standardized Reports).

---

## 1. Core Surface, Brand & Elevation Palette

PressQueue uses a crisp, flat architectural slate-and-cobalt palette anchored directly in the geometric PressQueue logo mark (`#0F172A` Ink + `#2563EB` Cobalt). Zero gradients, zero ambient glow blobs, and clear 3-tier surface separation keep high-density operational screens scannable and solid for a decade of daily print-shop use.

| Token | Role | Light Mode (`Bond Paper & Ink`) | Dark Mode (`html.dark` — `Midnight Blueprint Slate`) |
| :--- | :--- | :--- | :--- |
| `--pq-canvas` | Page background canvas | `#f1f5f9` (`slate-100`) | `#0b1120` (deep cool midnight slate) |
| `--pq-surface` | Primary cards, modals, sticky topbars | `#ffffff` (`white`) | `#0f172a` (`slate-900`) |
| `--pq-well` | Inset wells, table heads, secondary tiles | `#f8fafc` (`slate-50`) | `#1e293b` (`slate-800/70`) |
| `--pq-border` | Hairline container & divider strokes | `#e2e8f0` (`slate-200`) | `#1e293b` (`slate-800`) |
| `--pq-border-strong` | Input fields & active outline contrast | `#cbd5e1` (`slate-300`) | `#334155` (`slate-700`) |
| `--pq-ink` | Primary headings & tabular metrics | `#0f172a` (`slate-900`) | `#f8fafc` (`slate-50`) |
| `--pq-muted` | Supporting labels, captions, metadata | `#475569` (`slate-600`) | `#94a3b8` (`slate-400`) |
| `--pq-accent` | Primary CTAs, active tabs & focus highlight | `#2563eb` (`blue-600`) | `#2563eb` / `#3b82f6` (`blue-600` / `blue-500`) |
| `--pq-accent-strong` | Link & text-level accent (`>= 4.5:1` WCAG AA) | `#1d4ed8` (`blue-700`) | `#60a5fa` (`blue-400`) |

### Brand Logo Discipline
- **Global Mark (`public/pressqueue-logo-v1.svg` & `<x-app-logo-icon />`):** Two-tone geometric `P` (`#0F172A` light / `#F8FAFC` dark) paired with twin cobalt queue bars (`#2563EB` light / `#3B82F6` dark). Never apply `fill-current` over `<x-app-logo-icon />` or wrap it in colored brand boxes—seat it cleanly inside a neutral framed tile (`border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800`).
- **Elevation & Border Discipline:** Declare elevation **once** per container: crisp `1px` hairline border (`border-slate-200 dark:border-slate-800`) plus at most a subtle `shadow-xs` on elevated cards or modals.
- **Card & Control Radii:** `rounded-2xl` (`16px`) or `rounded-3xl` (`24px`) for top-level panels; `rounded-xl` (`12px`) for controls, buttons, and inputs; `rounded-full` / `rounded-md` only for compact status pills.

---

## 2. Typography & Tabular Precision

- **Primary Font:** `'Instrument Sans'` loaded via `@bunny/fonts`, fallback `ui-sans-serif, system-ui, sans-serif`.
- **Hierarchy:**
  - Page Heading (`h1`): `text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100`
  - Section Heading (`h2`): `text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100`
  - Card / Table Label: `text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400`
  - Numeric Discipline: Apply `tabular-nums` to **every** price (`₱`), page count, copy count, sheet balance, and order/tracking number (`PQ-*`, `TRK-*`, `OR-*`).

---

## 3. WCAG-AA Semantic Color Pairs (Light + Dark Required)

Never use dark-mode-only pastel text classes (`text-rose-300`, `text-sky-300`, `text-indigo-300`, `text-emerald-300`, `text-cyan-400`) on light surfaces. Every status badge and alert MUST specify explicit Light + `dark:` pairs (or use `.pq-badge-*` component classes in `resources/css/app.css`). **Amber is strictly reserved for operational warnings and pending states—never for primary CTAs, logos, or navigation.**

- **Primary CTA:** `bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold dark:bg-blue-600 dark:hover:bg-blue-500`
- **Brand / Role / Module Highlight (`.pq-badge-brand`):** `bg-blue-50 text-blue-800 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800`
- **Success / Normal / Verified / Ready (`.pq-badge-success`):** `bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800`
- **Warning / Busy / Pending / Maintenance / Low Stock (`.pq-badge-warning`):** `bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800`
- **Danger / At Capacity / Rejected / Aging / Refund (`.pq-badge-danger`):** `bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800`
- **Info / Specs Locked / Wide Format / DP Verified (`.pq-badge-info`):** `bg-sky-50 text-sky-800 border border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800`
- **Neutral / Paused / Draft / Unassigned (`.pq-badge-neutral`):** `bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700`

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

1. **Zero gradients & zero ambient `blur-3xl` / `blur-[140px]` radial glow blobs** behind auth views, dashboards, cards, or settings.
2. **Zero UI emojis** (`📚`, `📄`, `⚡`, `📦`, `📕`, `📘`, `🧾`, `🖨️`, `✅`) inside buttons, filter chips, badges, or section titles—use crisp monochrome Flux / Heroicon SVGs.
3. **Zero noisy promotional micro-pills or eyebrows** (`• LIVE CASHFLOW`, `• SHOP FLOOR LIVE`, `CORE`, `Single Sign-On Print Operations Facility`, eyebrow kickers above page `h1`s).
4. **Zero CSS theme-inversion hacks:** Every component uses native Light-Mode-first Tailwind classes + explicit `dark:` variants.
