# SEO & AI Discoverability — Implementation Report

Scope: 12-phase brief for `https://kairocore.co.zw/` (see `INITIAL_AUDIT.md` for
the baseline). All work landed in this repository; **nothing was deployed or
externally registered** — those steps are listed in §7.

## 1. What shipped, by phase

| Phase | Deliverable | Status | Evidence |
|---|---|---|---|
| 1 | Audit | ✅ | `docs/seo/INITIAL_AUDIT.md` (stack corrections, C1–L5 problem table) |
| 2 | Technical foundation | ✅ | secrets untracked, `info.php` removed, JSON-LD dedup + `platform_software_schema()`, unique titles/descriptions, robots AI sections + `/language/` `/locale/` disallow, layout-driven SEO partial, `SeoFoundationTest` |
| 3 | Keyword/page architecture | ✅ | `docs/seo/KEYWORD_AND_PAGE_MAP.md` + 5 new pages: `/school-fees`, `/students`, `/admissions`, `/academics`, `/security` (breadcrumbs, sitemap, llms.txt, footer links) |
| 4 | Help centre | ✅ | 5 guides (enroll, register, fees, attendance, report cards) each with What/Why/Steps/Tips/FAQ/Related + HowTo & FAQPage schema; hub page; `docs/seo/CONTENT_VERIFICATION_REQUIRED.md` |
| 5 | Structured data doc | ✅ | `docs/seo/STRUCTURED_DATA.md` (node inventory, omissions policy, test enforcement) |
| 6 | Analytics plan | ✅ | `docs/seo/ANALYTICS_SETUP.md` — no ID invented; measurement plan + implementation checklist |
| 7 | Search engine setup | ✅ | `docs/seo/SEARCH_ENGINE_SETUP.md` — GSC/Bing steps, deploy prerequisite, monitoring cadence, curl pack |
| 8 | This report | ✅ | `docs/seo/IMPLEMENTATION_REPORT.md` |
| 9 | Tests | ✅ | §5 |
| 10 | Performance/accessibility | ✅ (in-scope) | no Tailwind CDN (Vite build, test-enforced); single stylesheet; semantic headings; `alt` on OG; verified no regressions. CWV measurement needs field data → external. |
| 11 | Monitoring | ✅ | `SEARCH_ENGINE_SETUP.md` §6–7 (cadence + curl pack) |
| 12 | International plan | ✅ (decision) | KEYWORD_AND_PAGE_MAP: no country doorway pages; session-locale `/language/{locale}` exists as capability; `hreflang` deferred until real localized sites exist (robots disallows `/language/` and `/locale/` from indexing loops) |

Commits (main): `25d6cef` 500-fix · `cdfbee5` Phase 1–2 · `b523029` help views ·
`1fca1b5` Phase 3 · `1b26e73` zip ignore · `e7cd520` Phase 4 · `e09360a` Phase 5–7
· `<this>` Phase 8–9. (Originally authored on
`feature/enhanced-contact-page-dark-mode`, rebased onto `main` by the
maintainer.)

## 2. Decisions worth recording

1. **GPTBot remains explicitly allowed** in robots (central + tenant). The brief
   says document, never silently change. Revisit is a product decision.
2. **No `/pricing` page** — zero verified pricing exists; crawlers get a genuine
   404; CTAs point to `/register` and `/contact`. See
   `CONTENT_VERIFICATION_REQUIRED.md` §1.
3. **Global `meta_title`/`meta_description` DB override keeps precedence over
   `@section('title')`** (H4). The admin "Website Content → SEO" field remains
   the strongest override by design — it is how an operator is expected to fix
   titles without a deploy. Latent risk documented below (§3).
4. **`SoftwareApplication` schema omits offers/ratings/reviews** — schema must
   mirror only visible, verifiable content.
5. **Solution pages are capability pages**, not doorway pages: one page per
   verified intent, honest scope, internal links both ways (map §linking rules).
6. **Security page states mechanism, not certifications** — includes an explicit
   "What we will never claim" section.

## 3. Known gaps & caveats

| # | Gap | Detail | Owner |
|---|---|---|---|
| G1 | **H4 still latent** | If an admin saves a *global* `meta_title` in Website Content, all 15 marketing pages collapse to that one title (`layout.blade.php` L10–13 override path; no page defines `@section('meta_title')`, which would win). Today the DB has **no** override rows → tests assert uniqueness and pass. | Decision §2.3; optional future patch: apply override only when the page yields no title |
| G2 | **Live = older build** | `/help`, `/llms.txt`, new robots/sitemap, Vite assets are **not live until you deploy**. Submitting to GSC before deploy submits 404s. | §7.1 |
| G3 | **Secrets remain in git history** | `.env.infinityfree` / `.env.railway` untracked since `cdfbee5`, but the **public** GitHub repo's history still contains APP_KEY/DB passwords. | §7.2 |
| G4 | **No analytics running** | Plan ready (`ANALYTICS_SETUP.md`); blocked on GA4/GTM IDs. | §7.3 |
| G5 | **No privacy policy page** | No `/privacy` route exists at all; logged as content gap + legal task. | `CONTENT_VERIFICATION_REQUIRED.md` §7 |
| G6 | **No field CWV data** | Real-user performance needs Search Console CWV after deploy. | external |
| G7 | **DB-dependent test** | `NavigablePagesHelpTest` now prefers an **active** school (it broke when the seeded `rujeko` school disappeared and the fallback hit a `suspended` tenant → 403s). Requires local MySQL `schoolcore`. | test self-heals |

## 4. Problem closure vs INITIAL_AUDIT §3

| ID | Was | Now |
|---|---|---|
| C1 public-repo secrets | tracked env files in public repo | untracked + ignored; rotation/history purge = §7.2 (unavoidably manual) |
| M4 `info.php` | present | deleted |
| H1 prod behind repo | — | code now contains everything; deploy = §7.1 |
| H2 no analytics | — | plan ready; IDs = §7.3 |
| H3 docs depth | 3 thin articles | 5 full guides (What/Why/Steps/Tips/FAQ/Related + schema), hub with 5 cards |
| H4 global override | undocumented | documented (§2.3, G1) + uniqueness test guard |
| M1–M9 | metadata/JSON-LD duplication, robots gaps, CDN, missing breadcrumb/schema | fixed in Phase 2/3 (see `STRUCTURED_DATA.md`) |
| L1–L5 | minor | fixed or documented (zip ignore `1b26e73`) |

## 5. Tests

Run: `./vendor/bin/phpunit tests/Feature/SeoFoundationTest.php tests/Feature/PlatformSeoTest.php tests/Feature/NavigablePagesHelpTest.php tests/Feature/InaccessiblePageResponseTest.php`

Latest run: **27/27 passing, 1292 assertions.**

- `SeoFoundationTest` — unique titles/descriptions across 21 public routes,
  canonical query-stripping, SoftwareApplication validity (no fabricated
  offers/ratings), JSON-LD top-level uniqueness, sitemap origin + privacy
  (first-path-segment excludes `/platform|workspace|student|register`), robots
  directives (incl. AI sections), no Tailwind CDN in HTML.
- `PlatformSeoTest` — pre-existing SEO invariants.
- `NavigablePagesHelpTest` — every navigation page renders + has page-specific help.
- `InaccessiblePageResponseTest` — private routes stay auth-gated.

## 6. Content safety rules (standing)

1. If a claim is not in the repo (code/tests/config/verified content) or a
   Kairo CORE-provided document, it does not ship — log it in
   `CONTENT_VERIFICATION_REQUIRED.md` instead.
2. No invented prices, certifications, uptime, customer counts, ratings.
3. Robots AI-crawler changes are product decisions, never silent edits.
4. Schema mirrors visible content only.

## 7. Manual actions for Kairo CORE (in order)

1. **Deploy** `main` to `kairocore.co.zw` (or your pipeline), then run the curl
   pack in `SEARCH_ENGINE_SETUP.md` §7 — confirm `/help` 200, robots has AI
   sections, sitemap lists all URLs.
2. **Rotate credentials** referenced by the historically-pushed env files:
   Laravel `APP_KEY` (re-encrypt; rotating invalidates encrypted cookies/sessions),
   database passwords for every DB ever in `.env.infinityfree` / `.env.railway`,
   and consider a GitHub history purge (`git filter-repo`) or repo-visibility
   change. Rotation is the essential step; purge is defense-in-depth.
3. **Create GA4 + GTM** and hand the IDs over → implement per
   `ANALYTICS_SETUP.md` §5.
4. **Search Console + Bing** registration and sitemap submission per
   `SEARCH_ENGINE_SETUP.md` §1–2 (after deploy!).
5. **Product decisions:** GPTBot allow/deny; privacy policy content + `/privacy`
   route; pricing page when real numbers exist; testimonial/case-study sourcing.
6. **Optional:** fix H4 precedence if operators keep global meta fields —
   one-line guard in `layout.blade.php` (see G1).
