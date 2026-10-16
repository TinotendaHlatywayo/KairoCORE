# Kairo CORE — Keyword & Page Map

Date: 2026-10-16 · Branch: `feature/enhanced-contact-page-dark-mode`
Rules applied: existing pages first; no doorway/thin pages; no invented features, pricing, claims; one page per distinct intent (no cannibalisation); country-specific standalone pages deliberately avoided (see decisions).

## Decisions

- **No `/pricing` page yet.** The product publishes no prices and no trial terms beyond the visible CTA "Start free registration" (`marketing_content('hero_primary_label')` default) and "Book a personal demo". A pricing page without verified numbers would require invention → logged in `CONTENT_VERIFICATION_REQUIRED.md`. Until then, pricing intent maps to `/contact` and `/register`.
- **No dedicated "for primary schools" / "for secondary schools" pages.** Near-identical audience pages would be doorway-style duplication; both intents are addressed inside the homepage, `/academics`, `/school-fees` copy and FAQs.
- **No country-specific clones** (e.g. "school management software Kenya" vs "…Zimbabwe"). Zimbabwe is the founding market and named where truthful (About: "Harare, Zimbabwe"); other markets are served as the same canonical English pages.
- **Finance vs fees = one page.** The product module is "Finance & Fees"; splitting fees and finance into two pages would cannibalise. One substantive page covers both intents.
- **Session-based locales** (`/language/{locale}` 302) mean translated copy shares one URL. Crawlers see default English only → no `hreflang` possible/needed now (see international plan).
- **Status vocabulary:** ✅ exists & verified · 🔨 built in this effort · ⏸ blocked on verified content (see `CONTENT_VERIFICATION_REQUIRED.md`) · ❌ decided against (with reason).

## Keyword → page map

| # | Priority search topic | Target page | URL | Status | Title (as shipped) | Meta description (as shipped) | Intent / audience | Conversion goal |
|---|---|---|---|---|---|---|---|---|
| 1 | School management system in Zimbabwe | Homepage | `/` | ✅ 🔨 | `Kairo CORE - School Management System in Zimbabwe` | Modern school management system for admissions, academics, fees, attendance, HR, communications and more. | School leaders/owners evaluating systems (Zimbabwe) | Book demo → `/register` |
| 2 | School management software in Zimbabwe | Homepage | `/` (same page, same intent) | ✅ | (as above) | (as above) | Same intent as #1 — deliberately one page | (as above) |
| 3 | School ERP software | Product overview = Features | `/features` | ✅ | `Kairo CORE - Features` (existing section) | Explore all features of the school management system… | Comparators searching "ERP"-language | Demo CTA on page |
| 4 | School fees management software | Fees & Finance | `/school-fees` | 🔨 | `School Fees & Finance Management Software - Kairo CORE` | Manage school fees end to end: fee structures, bulk invoicing, multi-currency payments, receipts, statements and financial reports on Kairo CORE. | Bursars/finance officers | Demo CTA + link to fees help article |
| 5 | Student information system | Students | `/students` | 🔨 | `Student Information System for Schools - Kairo CORE` | Central student records: profiles, guardians, enrolment history, attendance, fees and results in one student information system for schools. | Admins/registries | Demo CTA + enroll help article |
| 6 | School admissions software | Admissions & Enrolment | `/admissions` | 🔨 | `School Admissions & Enrolment Software - Kairo CORE` | Run admissions end to end: online applications, document uploads, review, placement and enrolment that creates the student record on Kairo CORE. | Deans of admissions/admin | Demo CTA + enroll/apply help article |
| 7 | School finance management software | Fees & Finance (same as #4) | `/school-fees` | 🔨 | (as #4) | (as #4) | Same module, overlapping intent — one authoritative page | (as #4) |
| 8 | School examination & report card software | Academics & Reporting | `/academics` | 🔨 | `Academic Management, Exams & Report Cards - Kairo CORE` | Manage classes, timetables, assessments, examinations, grading and report cards — academic management built for how schools run. | Academic heads/deputies | Demo CTA + report-card help (Phase 4) |
| 9 | School management software for primary schools | Homepage + `/academics` + `/school-fees` copy/FAQ | `/` | ✅ (in-copy) | — | — | Primary school buyers researching fit | Same-page CTAs |
| 10 | School management software for secondary schools | Homepage + `/academics` + `/school-fees` copy/FAQ | `/` | ✅ (in-copy) | — | — | Secondary school buyers | Same-page CTAs |
| 11 | Kairo CORE features | Features | `/features` | ✅ | `Kairo CORE - Features` | Explore all features… | Branded research | Demo CTA |
| 12 | Kairo CORE pricing | — | — | ⏸ | *(needs verified pricing/trial terms)* | — | Price checkers | Route: `/contact`, `/register` interim |
| 13 | Kairo CORE security and data protection | Security & Data Protection | `/security` | 🔨 | `Security & Data Protection - Kairo CORE` | How Kairo CORE protects school data: tenant isolation, role-based access, encrypted sessions, backups and least-privilege administration. | IT/decision-makers, data-protection questions | Trust → demo |
| 14 | How to use Kairo CORE | Help Center | `/help` | ✅ | `Kairo CORE Help Center — How to use Kairo CORE` (existing) | Step-by-step guides… | New users | Deeper docs engagement |
| 15 | Kairo CORE tutorials & troubleshooting | Help Center + guides (Phase 4) | `/help/*` | ✅ 🔨 (expanding) | per-article titles (existing pattern) | per-article (existing pattern) | Stuck users, AI assistants | Task completion |
| — | Product facts (AI/brand) | About + `llms.txt` | `/about`, `/llms.txt` | ✅ | existing unique titles | existing | Assistants & humans seeking official facts | Trust → contact |

## New pages — content requirements (Phase 3 build)

### `/school-fees` — Fees & Finance
- **Audience:** bursars, finance officers, proprietors.
- **Must cover (verified module capabilities only):** fee structures per class/term, bulk invoicing, payments (cash/bank/mobile/online — Paynow sandbox integration exists in repo; word as "payment gateway integration" unless verified live), multi-currency (USD/ZiG named in existing marketing copy), receipts, statements/financial history, expense/procurement links, financial reports.
- **Internal links:** `/features?category=finance`, help `/help/collect-fees`, `/students`, `/admissions`, `/academics`.
- **Unique elements:** fee-cycle FAQ; role note (bursar/finance role); not a copy of the features card — includes workflow narrative.

### `/students` — Student Information System
- **Audience:** registrars, admins.
- **Must cover:** student profile fields (guardians, medical notes, transport, attendance, fee history, results — from verified feature copy), directory/search, ID cards (print routes exist), enrolment history, guardians/parents, document storage (private, auth-gated — describe capability, not URLs).
- **Internal links:** `/admissions`, `/academics`, `/school-fees`, help enroll article.
- **FAQ:** data import? (only if verified — else omit), who can see student data (roles/tenant isolation).

### `/admissions` — Admissions & Enrolment
- **Audience:** admissions offices, school owners.
- **Must cover:** applications from school public website (Apply/Apply Online — verified in help article + CMS routes), document upload, review workflow in workspace, admission → student record → invoice chain (verified in llms.txt/help copy), sibling/transport notes only if verified (features copy mentions sibling links — it's shipped marketing copy, acceptable as product claim source but flag if unsure).
- **Internal links:** help enroll + register-school articles, `/students`, `/school-fees`.

### `/academics` — Academics, Exams & Report Cards
- **Audience:** deputies academic, HODs, exam officers.
- **Must cover:** classes/streams/subjects, timetables, assessments & grading schemes, examinations & scheduling, result publication, report cards/generation (help article planned Phase 4), ZIMSEC import exists (test: `AssessmentAllTermsAndZimsecImportTest`) — mention "exam result import" carefully (verify UI label before tutorial; page-level mention OK from tests).
- **Internal links:** `/features?category=academics`, `/students`, help articles.

### `/security` — Security & Data Protection
- **Audience:** IT-aware buyers, data-protection questions.
- **Verified claims allowed (from code/tests):** multi-tenant isolation per school, role-based access control with capability catalogue, CSRF protection, session encryption option, hashed passwords, auth-gated document endpoints, backups (platform backup feature exists — verify cadence claims: do NOT claim schedule unless configured), no private data in sitemap/robots, public storage only for intentionally public files.
- **Forbidden:** certifications (ISO/POPIA/PDPA compliance claims), uptime guarantees, "bank-level encryption" marketing fluff, penalties.
- **Internal links:** `/terms`, `/contact`, `/about`.

## Internal linking rules

- Every new page links up via `BreadcrumbList` (Home → page) and to at least two existing pages (features, help, related module pages).
- Footer "Product" column: add Fees/Admissions links (layout edit) so new pages receive sitewide links.
- `/help` hub links to matching module pages; module pages link back to relevant help articles.
- No links to private areas (`/platform`, `/workspace`) from public content except the existing "Platform Login" nav item (intentional).

## Implementation status log

| Page | Route added | View | Breadcrumb | In sitemap | Internal links | Tests |
|---|---|---|---|---|---|---|
| `/school-fees` | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 (SeoFoundation route list) |
| `/students` | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 |
| `/admissions` | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 |
| `/academics` | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 |
| `/security` | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 | 🔨 |
| `/pricing` | ⏸ blocked on verified pricing — do not build | — | — | — | — | — |

(🔨 = implemented in this effort; updated in `IMPLEMENTATION_REPORT.md`.)
