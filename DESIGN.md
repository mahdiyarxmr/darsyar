# DarsYar (درسیار) — Exclusive Design System Specification

This document is the authoritative UI/UX engineering specification for the **DarsYar (درسیار)** School Management & Educational Platform. Inspired by **Refined Neo-Brutalism** principles (clear structural outlines, hard geometric offset shadows, tactile spring press physics, radical simplification, and zero redundant subtext), DarsYar implements its **own bespoke visual identity and color system**.

---

## 1. Design Philosophy (DarsYar Signature Edition)

- **Crafted Tactile Clarity**: Crisp `1.5px` structural borders, hard physical offset shadows (`2.5px 2.5px 0`), generous warm paper breathing room, and tactile button press physics (`hover: -1px,-1px`, `active: +1.5px,+1.5px`).
- **Radical Simplification (No Stuttering UI)**: Remove obvious helper prose, stacked inline creation forms above tables, and duplicate subtitles. Action buttons stay in the page header and open clean modals or smooth drawers.
- **Lively Motion Where It Matters**: Animated geometric canvas and smooth segmented Login <-> Register transitions on `/login` & `/register`, staggered card entrances, modal spring reveal, and subtle tab sliders — always respecting `prefers-reduced-motion`.
- **Strictly Bilingual**: Persian (`fa`, `dir="rtl"`, primary/default, Jalali calendar, localized Persian digits) and English (`en`, `dir="ltr"`, Gregorian calendar). Arabic is excluded everywhere.

---

## 2. Color System (CSS Custom Properties)

Defined exclusively via CSS variables on `:root` and `[data-theme="dark"]` in [`public/assets/css/app.css`](public/assets/css/app.css).

### Signature DarsYar Brand Palette
| Token | Role | Light Theme | Dark Theme |
|---|---|---|---|
| `--primary` | DarsYar Electric Cobalt | `#2554FF` | `#5B82FF` |
| `--primary-hover` | Primary Hover | `#1B42D8` | `#476EEF` |
| `--primary-active` | Primary Pressed | `#1535B0` | `#3B5ED9` |
| `--primary-soft` | Cobalt Tint Surface | `#EBF0FF` | `rgba(91, 130, 255, 0.16)` |
| `--primary-text` | Accessible Cobalt Text | `#1D4ED8` | `#93B4FF` |
| `--accent` | Persian Tile Teal (Educational Accent) | `#0EA5A4` | `#2DD4BF` |
| `--accent-soft` | Teal Soft Surface | `#E6F8F8` | `rgba(45, 212, 191, 0.14)` |
| `--warm` | Saffron Highlight Accent | `#EAB308` | `#FACC15` |
| `--secondary` | Graphite Slate | `#334155` | `#94A3B8` |

### Theme Surface, Structural Border & Shadow Ink Tokens
| Token | Light Theme | Dark Theme |
|---|---|---|
| `--bg` | `#FAF9F5` (Warm Cream Paper) | `#0B0F17` (Obsidian Slate) |
| `--surface` | `#FFFFFF` | `#141A24` |
| `--surface-2` | `#F2F0E9` (Warm Sunken Stone) | `#0F141E` |
| `--surface-raised` | `#FFFFFF` | `#1B2332` |
| `--text` | `#111827` | `#F8FAFC` |
| `--text-muted` | `#525866` | `#94A3B8` |
| `--border` | `#1E293B` (Crisp Structural Ink) | `#334155` |
| `--border-subtle` | `#E2DFD5` (Soft Internal Dividers) | `#232C3D` |
| `--shadow-ink` | `#1E293B` (Hard Offset Ink) | `#020617` |

### Semantic Status Tokens
| Token | Light | Dark | Soft Surface |
|---|---|---|---|
| `--success` | `#16A34A` | `#34D399` | `#ECFDF5` / `rgba(52, 211, 153, 0.16)` |
| `--warning` | `#D97706` | `#FBBF24` | `#FFFBEB` / `rgba(251, 191, 36, 0.16)` |
| `--danger` | `#DC2626` | `#F87171` | `#FEF2F2` / `rgba(248, 113, 113, 0.16)` |
| `--info` | `#0284C7` | `#60A5FA` | `#EFF6FF` / `rgba(96, 165, 250, 0.16)` |

---

## 3. Shape, Borders & Elevation

- **Border Width**: `1.5px solid var(--border)` on buttons, inputs, cards, modals, and auth containers; `1px solid var(--border-subtle)` inside table rows and horizontal dividers.
- **Border Radii**:
  - Buttons & Inputs: `12px` (`--radius-sm`)
  - Cards & Tables: `16px` (`--radius`)
  - Modals & Auth Showcase: `20px` (`--radius-lg`)
  - Badges & Pills: `9999px` (`--radius-pill`)
- **Shadows**:
  - Tactile Controls & Cards: Hard geometric offset (`--shadow-hard-xs: 2px 2px 0`, `--shadow-hard-sm: 2.5px 2.5px 0`, `--shadow-hard-md: 3.5px 3.5px 0`, `blur = 0`).
  - Floating Overlays Only (Dropdowns & Toasts): Soft diffused shadow (`--shadow-float`).

---

## 4. Typography & Iconography

- **Persian (`[lang="fa"]`)**: Locally hosted `'Vazirmatn', Tahoma, sans-serif` (`/assets/fonts/Vazirmatn-var.woff2`), line-height `1.7`, localized Persian digits via `DateHelper`.
- **English (`[lang="en"]`)**: Locally hosted `'Inter', Arial, sans-serif` (`/assets/fonts/Inter-var.woff2`), line-height `1.6`.
- **Strict Minimum Size Floor**: Never below `12px` (`0.8rem`) for captions, badges, or metadata.
- **Icons**: Clean inline outline Lucide SVGs (`App\Core\Icon`) with `stroke-width="2"` and RTL mirroring on directional icons.

---

## 5. Tactile Motion Physics

- **Button / Card Press**: `150ms cubic-bezier(0.16, 1, 0.3, 1)` (`hover`: lift `-1px, -1px`; `active`: press `+1.5px, +1.5px`).
- **Animated Auth Stage (`/login`, `/register`)**: Subtle floating geometric particles, sliding segmented mode pill (`ورود` / `ثبت‌نام`), smooth panel transition, role selector cards, password visibility reveal, and staggered entrance animation.
- **Accessibility**: Minimum `42px`–`44px` interactive tap targets on controls, visible focus rings, tabular numbers inside stats/timers, and `@media (prefers-reduced-motion: reduce)` fallbacks.
