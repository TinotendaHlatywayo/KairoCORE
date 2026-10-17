# Analytics Setup — Plan & Instructions

**Status: no analytics exist anywhere in the codebase** (verified: no GA4, GTM,
gtag, fb-pixel or analytics.js references in `resources/`, `routes/` or
`app/`). Nothing below has been implemented — measurements IDs do not exist yet
and this document deliberately contains **no invented ID**. Once you obtain the
IDs, implementation is a small, reviewable change (see §5).

## 1. What to create (manual, external — you do this)

1. **Google Analytics 4 property** for `kairocore.co.zw`
   - admin.google.com → Create property → timezone/currency of Zimbabwe (CAT, USD/ZWL as you prefer).
   - Data stream → Web → `https://kairocore.co.zw`.
   - Copy the **Measurement ID** (`G-XXXXXXXXXX`).
2. **Google Tag Manager container** (recommended) → copy container ID
   (`GTM-XXXXXXX`). GTM lets you manage tags without further code changes.
3. **Google Search Console** property (see `SEARCH_ENGINE_SETUP.md`).

Do not commit real IDs into public docs if you prefer to keep them private —
the implementation below reads them from config/env.

## 2. Recommended measurement plan

### Page views (automatic)
- All marketing pages, help guides, solution pages.
- Authenticated app pages: **recommended off by default** — see §4 privacy.

### Conversion / key events
| Event | Trigger | Why |
|---|---|---|
| `demo_request` | Click on any "Book a personal demo" / `route('register')` CTA | Primary conversion (start of funnel) |
| `contact_submit` | Successful submit of `POST /contact` | Second conversion path |
| `help_article_view` | View of any `/help/*` guide | Measures documentation demand (drives Phase 4 backlog) |
| `solution_page_view` | `/school-fees`, `/students`, `/admissions`, `/academics`, `/security` | Keyword→interest mapping from KEYWORD_AND_PAGE_MAP |

### Outbound / off-site
- Email clicks (`mailto:`), WhatsApp if added later → `outbound_link` event.

## 3. Where the tags go (when implemented)

Single injection point exists: `resources/views/marketing/layout.blade.php`
(`<head>` around L34–38, or body-end). Because **all** public pages extend this
layout, one include covers the whole marketing site. Authenticated Filament
layouts (`filament/app/*`, `filament/student/*`) are intentionally separate —
do not tag them in phase 1.

Config keys to add (proposal): `config/analytics.php` → `id`, `gtm_id`,
`enabled` — so production can switch it on without touching views.

## 4. Privacy & compliance (decide before enabling)

- GA4 by default sets cookies / persists identifiers.
- The site currently sets **no** tracking cookies; enabling GA4 changes that.
  Consider: Google Consent Mode, IP anonymisation defaults, and a line in the
  (missing) privacy policy — see `CONTENT_VERIFICATION_REQUIRED.md` §7.
- Do **not** enable Google Signals / ad personalisation without a decision.

## 5. Implementation checklist (when IDs arrive)

- [ ] `config/analytics.php` + env keys (`ANALYTICS_ID`, `GTM_ID`, `ANALYTICS_ENABLED`).
- [ ] Layout include: GTM `<script>`/`<noscript>` (or gtag.js if not using GTM).
- [ ] `demo_request` click tracking (Alpine on CTA buttons or GTM click triggers).
- [ ] `contact_submit` success hook (Livewire dispatch after submit).
- [ ] DebugView verification on a staging domain.
- [ ] Mark `demo_request` as a key event in GA4 (conversions list).
- [ ] Link GA4 ↔ Search Console (admin → product links).

## 6. Verification (after implementation)

1. GA4 Admin → DebugView shows page_view on `/`.
2. Search Console → Pages report starts receiving data (72h typical lag).
3. No tag fires on `/login`, `/workspace/*` if phase-1 privacy decision excludes app.

## 7. Out of scope / non-goals

- No third-party heatmaps/session recorders without a privacy decision.
- No Facebook/LinkedIn pixel until ad spend decision exists.
- No invented dashboards — GA4 + Search Console cover the SEO brief's needs.
