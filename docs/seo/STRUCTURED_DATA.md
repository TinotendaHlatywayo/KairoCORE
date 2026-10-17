# Structured Data (Schema.org) — Reference

Status of every JSON-LD node the platform emits, where it is produced, and what
is deliberately **not** emitted. Tests in `tests/Feature/SeoFoundationTest.php`
enforce the core invariants described here.

## Architecture (single source of truth)

| Layer | File | Responsibility |
|---|---|---|
| Partial | `resources/views/partials/seo.blade.php` | Renders `<title>`, meta description, canonical, robots, Open Graph, Twitter cards, and **every** JSON-LD node as `<script type="application/ld+json">` |
| Layout | `resources/views/marketing/layout.blade.php` (L20–34) | Always injects `platform_website_schema()` + `platform_organization_schema()`, merges any route-provided `$seoJsonLd`, passes the merged array to the partial, then `@stack('seo_jsonld')` for article-level pushes |
| Helpers | `app/Helpers/helpers.php` | `platform_website_schema()` (L648), `platform_organization_schema()` (L663), `platform_software_schema()` (L683), `platform_breadcrumb_list()` (L709) |
| Routes | `routes/web.php` | Each marketing route passes page-specific nodes via `'seoJsonLd' => [...]` |
| Articles | help views | `@push('seo_jsonld')` with HowTo + FAQPage (rendered by the layout's `@stack`) |

Rules:

1. **WebSite + Organization are never re-passed by routes.** The layout injects
   them once; routes add only what is page-specific. (Historic duplication was
   removed in SEO Phase 2 — `SeoFoundationTest` counts unique top-level nodes
   and fails on duplicates.)
2. **Canonical metadata is computed once** in the partial; `seoCanonical` is
   `url()->current()` (query strings stripped — also test-enforced).
3. **`seoRobots` is `index, follow`** for all marketing pages (hardcoded in the
   layout). Private/authenticated areas are outside this layout entirely.
4. Schema mirrors visible, verifiable content only (see
   `CONTENT_VERIFICATION_REQUIRED.md`).

## Node inventory

### Every marketing page

| Node | Source | Notes |
|---|---|---|
| `WebSite` | layout | name, url |
| `Organization` | layout | name, url, logo |

### Page-specific (route-provided `$seoJsonLd`)

| Node | Pages | Count |
|---|---|---|
| `SoftwareApplication` | `/` (home), `/features` | 2 |
| `BreadcrumbList` | about, contact, features, school-fees, students, admissions, academics, security, help, help/enroll, help/register-school, help/collect-fees, help/record-attendance, help/report-cards | 14 |

`SoftwareApplication` fields: name, url, applicationCategory
(`EducationalApplication`), operatingSystem, description, image, publisher.

### Help articles (`@push('seo_jsonld')`)

| Node | Pages | Count |
|---|---|---|
| `HowTo` (with numbered `HowToStep`s) | all 5 guides | 5 |
| `FAQPage` (with `Question`/`Answer`) | all 5 guides | 5 |

Guides: enroll student, register school, collect fees, record attendance,
produce report cards.

Home page: no breadcrumb (it is the root). No `FAQPage` on product pages —
FAQs are published where they are genuinely answerable (help guides).

## Deliberately omitted (policy)

| Node / field | Why |
|---|---|
| `AggregateRating`, `Review`, `ratingValue` | No verified customer ratings exist |
| `Offer`, `price`, `priceCurrency` | No verified pricing exists (see `CONTENT_VERIFICATION_REQUIRED.md` §1) |
| `customer count` / `numberOfUsers` social proof | Unverified |
| `FAQPage` on product pages | FAQ content lives on help guides only; avoids FAQ spam patterns |
| `Event`, `JobPosting`, `VideoObject` (platform-level) | No verifiable platform-level entities yet |
| `SearchAction` sitelinks searchbox | Requires a site search endpoint that does not exist |

Rule: **schema must mirror only visible, verifiable content.** A node that
claims something no visitor can read on the page is a violation.

## Enforcement (automated)

`tests/Feature/SeoFoundationTest.php`:

- unique non-empty `<title>` + meta description across all public routes
- canonical present and query-stripped
- `SoftwareApplication` validity + no fabricated `offers`/`aggregateRating`
- JSON-LD nodes counted from **decoded top-level array** → no duplicated nodes
- sitemap origin/privacy checks; robots directives; no Tailwind CDN

Run: `./vendor/bin/phpunit tests/Feature/SeoFoundationTest.php tests/Feature/PlatformSeoTest.php`

## Manual validation (external, do when deploying)

1. Google Rich Results Test on `/`, `/features`, one help guide — expect no
   errors for `SoftwareApplication`, `BreadcrumbList`, `HowTo`, `FAQPage`.
2. schema.org validator — confirm no duplicate-node warnings.
3. Re-check after any route change that adds `seoJsonLd`.
