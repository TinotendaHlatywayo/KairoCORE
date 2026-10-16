# Kairo CORE — SEO & AI Discoverability: Initial Audit

Date: 2026-10-16
Auditor scope: repository inspection (branch `feature/enhanced-contact-page-dark-mode`, HEAD `4e4d57e`) plus read-only inspection of the live site `https://kairocore.co.zw/`.
Nothing was deployed, registered, submitted or published externally.

---

## 1. Findings

### 1.1 Verified stack (correcting the brief)

| Assumption in brief | Actual (verified) |
|---|---|
| Laravel 12 | **Laravel 13** (`laravel/framework: ^13.8`, installed 13.x) |
| Filament v4 | **Filament v3.3** (`filament/filament: ^3.2`) |
| PHP 8.4 | PHP `^8.3` in composer.json; host runtime PHP 8.4 |
| Livewire | v3 (installed 3.8.x) |
| MySQL | Yes (`DB_CONNECTION` mysql) |

Additional: modular architecture under `Modules/` (CMS, Finance, Academics, Students, SaaS, Library, …), Vite + Tailwind 4 build, Alpine.js loaded from CDN on marketing pages, Livewire/Filament for the authenticated app. Tests: PHPUnit (`phpunit.xml`), one large `tests/Feature/` suite.

### 1.2 Public marketing surface (already exists)

Defined in `routes/web.php:44-304`, scoped by `Route::domain(parse_url(config('app.url'), PHP_URL_HOST))` — i.e. canonical host is derived from `APP_URL`, **not** from arbitrary request Host headers.

| Route | Name | View | Status |
|---|---|---|---|
| `/` | `marketing.home` | `marketing/home` | 200 live |
| `/features` | `marketing.features` | `marketing/features` | 200 live (with `?category=` filters) |
| `/about` | `marketing.about` | `marketing/about` | 200 live |
| `/contact` | `marketing.contact` | `marketing/contact` | 200 live (POST + email) |
| `/help` | `marketing.help` | `marketing/help` | **404 live — not yet deployed** |
| `/help/enroll-student` | `marketing.help.enroll` | `marketing/help-enroll-student` | **404 live** |
| `/help/register-school` | `marketing.help.register` | `marketing/help-register-school` | **404 live** |
| `/help/collect-fees` | `marketing.help.fees` | `marketing/help-collect-fees` | **404 live** |
| `/terms` | `platform.terms` | `terms/platform` | 200 live |
| `/register` | `register` | Livewire `RegistrationWizard` | 200 live (robots-disallowed) |
| `/robots.txt` | `marketing.robots` | closure | 200 live (older content) |
| `/sitemap.xml` | `marketing.sitemap` | closure | 200 live (5 URLs) |
| `/llms.txt` | `marketing.llms` | closure | **404 live — not yet deployed** |

Also public: `/contact` POST (mail), account activation pages, Google OAuth routes, `/terms/pdf`, `/register/success`, locale switchers (`/language/{locale}`, `/locale/{locale}` → 302 back), verification pages (`/verify-report/{hash}`, `/verify-finance/{hash}`, `/verify-card/{hash}` — hash-addressed, not linked from sitemap).

### 1.3 Authenticated / private boundary

- Platform panel: `/platform*` (Filament), workspace: `/workspace*`, student portal: `/student*`, tenant subdomain group `Route::domain('{tenant}.'.host)` (CMS-rendered school sites, `tenant` middleware → `ResolveTenant`).
- Central `robots.txt` disallows `/platform`, `/workspace`, `/student`, `/register`, `/activate-account` (registration is a funnel page — disallowed to keep it out of the index; see §3 for trade-off).
- Tenant CMS `robots.txt` (`CmsSeoService::generateRobotsTxt`) allows public school pages, disallows `/_cms-preview/` and `/cms-render/` — correct (school websites are intentionally public).
- Tenant sitemap (`CmsSeoService::generateSitemapXml`) includes only `is_published && !hide_from_sitemap && !noindex` pages — correct.
- HTML responses send `cache-control: must-revalidate, no-cache, no-store, private` (live-verified; `NoStoreHtml` middleware) — prevents cached personalised/tenant HTML leaks at the app layer.

### 1.4 Existing metadata implementation

- Reusable partial `resources/views/partials/seo.blade.php`: title, meta description, canonical, robots, Open Graph (type/site_name/title/description/url/image/alt/secure_url), Twitter card, JSON-LD block (`$seoJsonLd` + `seo_jsonld` stack). Single source — no duplication between layout/components.
- Marketing layout `resources/views/marketing/layout.blade.php:6-34` resolves title/description in order: per-page `meta_title`/`title` section → DB override `marketing_content('meta_title')` → fallback.
- Every marketing view defines a unique `@section('title')` and `@section('meta_description')` **except the terms page** (inherits fallback title) — home/about/features/contact/help + 3 help articles are all unique. Exactly one `<h1>` per page (verified by grep).
- Canonical = `url()->current()` — on domain-scoped routes the host is APP_URL-controlled; query strings are stripped by `url()->current()`, so `/features?category=finance` self-canonicalises to `/features` (no duplicate-URL problem).
- Live homepage (verified): `<title>Kairo CORE - School Management System</title>`, unique description, canonical `https://kairocore.co.zw`, `index,follow`, OG + Twitter tags, `WebSite` + `Organization` JSON-LD, favicon + OG image.

### 1.5 Structured data (existing)

- `WebSite` + `Organization` on all marketing pages (layout-injected).
- `BreadcrumbList` on about, contact, help hub, and each help article (passed as `$seoJsonLd` from routes).
- `HowTo` + `HowToStep` on the three help articles (inline in the views).
- No `SoftwareApplication`, no `TechArticle`/`Article`, no `VideoObject` yet.

### 1.6 robots.txt / sitemap / llms.txt (repo version vs live)

Repo `routes/web.php:65-162` already implements:
- robots.txt: `Allow: /` + private-area disallows + explicit blocks for `GPTBot`, `ChatGPT-User`, `OAI-SearchBot`, `ClaudeBot`, `anthropic-ai`, `PerplexityBot`, `Google-Extended`, `CCBot` (all **Allow**) + Sitemap pointer.
- llms.txt: brand summary, public page list, "how to enroll" summary, notes for AI answers.
- sitemap.xml: 9 URLs (home, features, help hub, 3 help articles, about, contact, terms) with `lastmod` from blade `filemtime`.

Live site runs an **older build** (2026-10-15 lastmod): robots.txt lacks the AI-crawler sections, sitemap has only 5 URLs (no help pages), `/llms.txt` and `/help*` return 404, homepage uses `cdn.tailwindcss.com` + an older layout. **The repository is ahead of production; deployment is a manual external action.**

### 1.7 HTTP behaviour (live, verified with curl)

- `https://kairocore.co.zw/` → 200, no `X-Robots-Tag`, content-type HTML.
- `www.kairocore.co.zw` → **301 →** non-www (single hop).
- `http://` → **301 →** https (single hop). No loops/chains observed.
- Unknown path → genuine **404**; `/help` (undeployed) → 404; `/info.php` → 404; `/.env` → 404.
- App 404 view contains `<meta name="robots" content="noindex, nofollow">` (tested in `PlatformSeoTest`).

### 1.8 Production safety

- `.env.railway` / `.env.infinityfree` templates: `APP_ENV=production`, `APP_DEBUG=false` ✓; local `.env` is `APP_ENV=local`, `APP_DEBUG=true` (dev only).
- nginx (`docker/nginx/conf.d/app.conf`) denies hidden files (`.env` etc.).
- CSRF enabled with a single documented webhook exception (`saas/paynow/webhook`); session cookie scoping middleware (`ForceSessionCookieScope`); trusted proxies configurable (`TRUSTED_PROXIES`, default `*` — behind Nginx Proxy Manager).
- `public/storage` symlink — publishes only `storage/app/public` (tenant-safe by Laravel convention); no private tenant routes were found reachable without auth in route audit (all `/documents/*`, `/platform/*`, `/workspace/*` routes sit behind auth middleware groups — see §6 caveats).

### 1.9 Analytics, docs, performance

- **No analytics** of any kind found (no GA4/GTM/gtag/umami/plausible/matomo in views, config or assets). Live site carries only a Cloudflare Insights beacon (`static.cloudflareinsights.com`, token present in page) — traffic metrics only, not event tracking.
- **No documentation platform** beyond 3 static help blades (70–150 lines each). No `/docs/`, no last-reviewed metadata, no screenshots, no troubleshooting articles.
- Images: marketing images carry `alt`, `width`/`height` on logos; feature/hero images are remote Pexels URLs (third-party dependency) or admin-uploaded WebP (recently improved by the media pipeline work).
- Fonts: `fonts.bunny.net` with `preconnect` + `display=swap` ✓; marketing CSS is Vite-built (live still on Tailwind CDN — repo already fixed).
- Live homepage HTML: no security headers (HSTS/CSP/X-Frame-Options) visible at the application response — likely/possibly terminated at Cloudflare/NPM (cannot be verified from the repository).

### 1.10 Tests / conventions

- `tests/Feature/PlatformSeoTest.php` covers: homepage canonical/OG/JSON-LD, about/contact breadcrumbs, sitemap content, robots sitemap+disallows, 404 noindex. All passing (baseline run: 19/19 with `NavigablePagesHelpTest` + `InaccessiblePageResponseTest`).
- `NavigablePagesHelpTest` guards help-centre navigation.
- Conventions: helper-driven SEO (`app/Helpers/helpers.php`), `__()` translatable copy, PlatformSettings-backed `marketing_content()` overrides, routes-as-closures for the central marketing surface.

---

## 2. Existing strengths

1. Canonical host architecture already correct: routes bound to `config('app.url')` host; canonical/OG/sitemap URLs generated from config, not Host headers.
2. Single reusable metadata partial with per-page overrides — no duplicate tags.
3. Unique titles/descriptions on all key pages; single H1; images have alt text; breadcrumb + WebSite + Organization + HowTo JSON-LD already implemented.
4. robots.txt correctly disallows private areas, references the sitemap, and explicitly allows `OAI-SearchBot` (requirement already met in repo).
5. Sitemap is hand-curated to genuinely public pages only; tenant sitemap respects publish/hide/noindex flags; no private URLs anywhere in central sitemap.
6. Genuine 404s with `noindex` error view; www/http → https 301s already in place at the edge; no redirect loops found.
7. `no-store` on HTML prevents CDN/browser caching of personalised or tenant-specific responses.
8. llms.txt scaffold already exists with product facts and task summaries.
9. Translation infrastructure (6 locales, `lang/`) with session-based language switching — content is translatable without URL duplication.
10. Test baseline green; SEO regression tests already exist.

---

## 3. Problems

### CRITICAL

| # | Problem | Evidence |
|---|---|---|
| C1 | **Tracked secrets in a PUBLIC GitHub repository.** `.env.infinityfree` (real-looking `APP_KEY` len 50, `DB_PASSWORD` len 16) and `.env.railway` (`DB_PASSWORD` len 14) are committed (since `34e7af1`) even though `.gitignore` now ignores `.env.*`. Repo visibility check: `https://github.com/TinotendaHlatywayo/KairoCORE` returns HTTP 200 → public. Git history retains the values; anyone can read them today. | `git ls-files`, `curl` on repo URL, grep of env files (values not reproduced here) |

### HIGH

| # | Problem | Evidence |
|---|---|---|
| H1 | **Production is behind the repository.** `/help*`, `/llms.txt` return 404 live; live robots.txt lacks AI-crawler sections; live sitemap missing help pages; live homepage still loads `cdn.tailwindcss.com` (render-blocking JIT compiler — hurts LCP/INP/CLS) and an older design. Every repo SEO improvement is invisible until the user deploys. | curl status checks; live HTML source |
| H2 | **No analytics/conversion measurement at all** (Phase 7 blocked on a manual GA4 property + measurement ID). | repo grep |
| H3 | **Documentation depth far below the AI-discoverability objective.** Only 3 how-to articles exist; the brief requires verified workflows (enroll, invoice, payment, report card, …). AI assistants currently cannot retrieve verified answers for most product tasks. | `resources/views/marketing/help*` |
| H4 | **Global `meta_title`/`meta_description` DB override can collapse every page to one identical title/description.** `layout.blade.php:10-19`: if an admin saves a global `meta_title`, it overwrites each page's unique `@section('title')`. Currently empty (defaults `''`), so latent — but a one-field save away from sitewide duplicate titles. | layout logic |

### MEDIUM

| # | Problem | Evidence |
|---|---|---|
| M1 | Home title is functional but generic (`Kairo CORE - School Management System`) — no Zimbabwe/school-ERP intent differentiation vs. the brief's keyword priorities (title exists, keyword mapping not done). | live `<title>` |
| M2 | `/features` (the primary product page) lacks `BreadcrumbList` JSON-LD; no page passes `SoftwareApplication` schema for the product. | `routes/web.php:190-192` |
| M3 | Terms page has no own title/description section (inherits platform fallback). | grep of `@section` |
| M4 | `public/info.php` (`<?php phpinfo();`) and `public/debug-marker.txt` committed; `info.php` returns 404 on live (not deployed/exposed) but would leak full PHP/environment configuration if ever deployed. | `public/info.php`, live curl |
| M5 | No `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, HSTS observed on app responses; unknown whether Cloudflare/NPM supply them (cannot verify from repo). | curl -I |
| M6 | Sitemap `lastmod` uses blade `filemtime` — fine for static blades, but will not update when only PlatformSettings-driven content changes; also fine today because content lives mostly in blades. | `routes/web.php:126-131` |
| M7 | Help articles lack: last-reviewed date, role/permissions, prerequisites, expected results, common errors, related links — required structure from the brief not yet present. | help blades |
| M8 | Two locale-switcher routes (`/language/{locale}` and `/locale/{locale}`) duplicate functionality; both are 302-back session setters (index-safe, but inconsistent). | `routes/web.php:49, 314` |
| M9 | No performance budget/tests; marketing pages depend on third-party Pexels/Unsplash image CDNs and `fonts.bunny.net`/`cdn.jsdelivr.net` (Alpine) availability. | layout + helpers |

### LOW

| # | Problem | Evidence |
|---|---|---|
| L1 | Homepage canonical is `https://kairocore.co.zw` (no trailing slash). Google normalises, but consistency with `Sitemap` entry (same form) is already fine — cosmetic only. | live head |
| L2 | Footer "Resources" link points to home instead of Help Center (repo version footer already fixed — live still old). | live footer |
| L3 | `robots.txt` disallows `/register` — deliberate (keeps the funnel out of the index) but conflicts with the "trial registration completion" analytics goal if organic discovery of registration is desired later; document the trade-off. | `routes/web.php:74` |
| L4 | GPTBot (training crawler) is explicitly **allowed** in both central and tenant robots.txt. Per brief this is a policy decision to document, not silently change — see §7. | `routes/web.php:78`, `CmsSeoService:143` |
| L5 | Repository root contains deployment leftovers (`System documentation.docx`, `import-template-corrected.xlsx` untracked, etc.) — clutter, not SEO-affecting; pem files are correctly untracked. | `ls`, `git ls-files` |

---

## 4. Recommended fixes (staged implementation plan)

Already implemented in this session where marked ✅; to be implemented next:

**Stage A — security & foundation (this session):**
1. ✅ C1: untrack `.env.infinityfree` / `.env.railway` (files remain on disk), confirm `.gitignore` covers them; document rotation + history purge as manual steps.
2. ✅ M4: remove `public/info.php` (phpinfo) from the repository.
3. Phase 2B: extend the metadata partial per page where missing (features breadcrumb; terms title/description; `SoftwareApplication` JSON-LD on home/about/features).
4. Phase 2C: keep curated sitemap; add any new public pages as they land; switch `lastmod` to stable per-page values (view filemtime retained as fallback).
5. Phase 2D: robots.txt already correct in repo — add `Disallow: /locale/` + `/language/` noise reduction only if proven safe (they 302); document GPTBot policy (L4) without changing it.
6. H4: make page-specific `title`/`meta_description` sections win over the global DB override (DB override remains the explicit escape hatch only when the page defines no section — decision documented in code).

**Stage B — page architecture & docs (Phases 3–4):**
7. `KEYWORD_AND_PAGE_MAP.md` mapping the 15 priority intents to existing pages first; new pages only where genuinely needed (candidates: school fees/finance, admissions, student information, academic/report cards — each built from verified module capabilities).
8. Expand `/help` into a maintained help centre (reuse the existing static-blade pattern; avoid a CMS): add verified tutorials (enroll ✓ exists, register ✓, fees ✓, plus invoicing, payments, balances, report cards, announcements, academic years…) with the required article structure; log anything unverifiable in `CONTENT_VERIFICATION_REQUIRED.md`.
9. Product facts page: strengthen `/about` + `llms.txt` with only verified claims (no invented pricing/customers/certifications — pricing is NOT present in the product marketing copy today; do not invent it).

**Stage C — structured data, performance, AI (Phases 5, 6, 8):**
10. `SoftwareApplication` (category: EducationalApplication), breadcrumbs everywhere they apply, `TechArticle`/`HowTo` on docs; document in `STRUCTURED_DATA.md`.
11. Performance: rely on repo's Vite CSS (already replaces live Tailwind CDN on deploy), audit image dimensions/`fetchpriority` on hero, keep Bunny fonts + swap; document CWV targets vs lab testing.
12. AI: keep `OAI-SearchBot` allow; GPTBot documented as currently allowed (user decision); `llms.txt` kept in sync with new docs; official product facts surfaced.

**Stage D — measurement, monitoring, tests (Phases 7, 9, 11):**
13. GA4 integration scaffold behind a config flag with no fabricated measurement ID (`ANALYTICS_SETUP.md` with manual steps).
14. `SEARCH_ENGINE_SETUP.md` (GSC/Bing/OpenAI manual steps), `STRUCTURED_DATA.md`, `KEYWORD_AND_PAGE_MAP.md`, `CONTENT_VERIFICATION_REQUIRED.md`, `IMPLEMENTATION_REPORT.md`.
15. New tests: unique titles/descriptions per route, sitemap eligibility (no private URLs, all 200), robots contents incl. `OAI-SearchBot`, structured-data JSON validity, `/docs` 200s, private routes still auth-gated, noindex on 404 only.

---

## 5. Relevant source files

| Area | Files |
|---|---|
| Routes (public + tenant) | `routes/web.php` (marketing group L44–304; tenant group L327+; catch-all `/{slug}` L606) |
| Metadata | `resources/views/partials/seo.blade.php`, `resources/views/marketing/layout.blade.php` |
| SEO helpers / schemas | `app/Helpers/helpers.php` (`platform_name`, `platform_seo_description`, `platform_og_image_url`, `platform_website_schema`, `platform_organization_schema`, `platform_breadcrumb_list`, `marketing_content*`) |
| Marketing views | `resources/views/marketing/{home,features,about,contact,help,help-enroll-student,help-register-school,help-collect-fees}.blade.php` |
| Tenant CMS SEO | `Modules/CMS/Services/CmsSeoService.php`, `Modules/CMS/Http/Controllers/CmsRenderController.php` |
| Middleware / safety | `bootstrap/app.php`, `app/Http/Middleware/{NoStoreHtml,ForceSessionCookieScope,ResolveTenant,SetUserLocale}.php` |
| Tests | `tests/Feature/{PlatformSeoTest,NavigablePagesHelpTest,InaccessiblePageResponseTest}.php` |
| Server config | `docker/nginx/conf.d/app.conf`, `docker/php/uploads.ini`, `deploy/` |
| Env templates (secret issue) | `.env.railway`, `.env.infinityfree`, `.env.example` |
| Leftovers | `public/info.php`, `public/debug-marker.txt` |

## 6. Security and regression risks

1. **C1 remediation risk:** untracking env files stops future leakage but does NOT invalidate already-exposed values; DB/APP_KEY/Google/Mail credentials must be rotated (manual, external). History still contains them until a history rewrite (optional, disruptive — coordinate).
2. Changing robots.txt GPTBot policy affects training-crawler access (policy decision, currently allow — see §7).
3. Adding pages/sitemap entries must never include tenant (`{tenant}.` subdomains), `/platform*`, `/workspace*`, `/student*`, `/register*`, `/_cms-preview/*`, verification hashes, or query-string tokens — tests will enforce.
4. `TRUSTED_PROXIES=*` default trusts any client-supplied forwarding headers if the app is ever exposed without the proxy — flag only, do not change (infra-dependent).
5. Metadata changes are display-only; risk of regression limited to `<head>` — covered by existing `PlatformSeoTest` plus new assertions.
6. Making page titles win over the global DB override changes behaviour for admins who set `meta_title` deliberately — implemented as documented precedence, reversible in one place (`layout.blade.php`).
7. Third-party CDN reliance (Pexels images, Bunny fonts, jsDelivr Alpine) — availability/privacy consideration; Bunny Fonts is privacy-oriented; jsDelivr pinned `3.x.x` is not version-pinned (minor integrity risk — document, don't change without user approval).
8. Live site runs old code: verify which branch/tag production deploys from before assuming repo changes are live (user's deployment pipeline, external).

## 7. External actions requiring the user's manual involvement

1. **Rotate credentials** exposed in the public repo history (DB password, `APP_KEY` for the InfinityFree env, any Gmail app-password in `.env.*`), and decide whether to purge git history (e.g. git-filter-repo) — disruptive, requires coordination.
2. **Deploy** the current branch to `https://kairocore.co.zw` (help centre, llms.txt, AI-aware robots.txt, new layout/Vite assets, sitemap with help URLs are all repo-only today).
3. **GPTBot policy decision:** default in code today = *allow* (explicit `Allow: /` for `GPTBot`, `ChatGPT-User`, `ClaudeBot`, `PerplexityBot`, `CCBot`, `Google-Extended`, `OAI-SearchBot`). Allowing = site content may be used for model training by those crawlers (search bots like `OAI-SearchBot`/`ChatGPT-User` are retrieval, not training). If you want training opt-out, say so and I will flip only the training-class bots and document it.
4. **Google Search Console / Bing Webmaster Tools / OpenAI publisher verification** — DNS TXT records and sitemap submission (instructions will be produced in `SEARCH_ENGINE_SETUP.md`).
5. **GA4 property + measurement ID** — required before analytics can be wired (no ID will be invented).
6. **Confirm public facts** for copy: pricing/trial terms (none exist in current marketing copy), payment-provider names beyond the in-repo Paynow sandbox code, compliance claims — nothing unverified will be written.
7. Verify edge-level security headers (HSTS/CSP) at Cloudflare/NPM if not present.
