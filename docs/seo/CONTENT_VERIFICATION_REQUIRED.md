# Content Verification Required

This file lists content that **must not be written or published until a human with
authority at Kairo CORE verifies it**. Nothing here is blocked as code — it is
blocked as *claim*. For each item: what is needed, who should confirm, and what
ships in the meantime.

## 1. Pricing, plans and free trials — `/pricing`

**Why it is blocked:** There is no pricing page, no plan/pricing table, no trial
term and no published rate anywhere in the repository, the marketing content
tables, or any verified source. Inventing prices for a live B2B product is a
trust and legal risk.

**Needed to unblock:** Verified plan names, what each includes, prices (with
currency and whether they are per-school / per-term / per-year), trial length and
conditions, and what happens after trial.

**Interim SEO behaviour (implemented):**
- No `/pricing` route exists; crawlers hitting it receive a genuine 404
  (verified by test) rather than a thin or misleading page.
- Internal links use `/register` (start) and `/contact` (ask) as CTAs.
- No page anywhere states a price, plan limit or trial term, because none has
  been verified.

**Owner:** Kairo CORE leadership / finance.

## 2. Testimonials, named schools and case studies

**Why it is blocked:** No verified customer quotes, school names, logos,
enrolment numbers or outcome statistics exist in the repo.

**Needed:** Written permission from each named school; the exact quote and
metrics as approved by that school.

**Interim:** Pages describe capabilities only; no customer claims.

**Owner:** Kairo CORE, customer success.

## 3. Certifications, compliance and "bank-level security" claims

**Why it is blocked:** The security page deliberately states what the code does
(tenant isolation, RBAC, CSRF, bcrypt, auth-gated documents, backups). It makes
**no** certification, ISO, GDPR, PoPIA or "bank-grade" claims because none are
evidenced in the repository.

**Needed:** Certificates or legal/compliance sign-off before any such wording
appears anywhere — site, help articles, decks or AI descriptions.

**Interim:** `/security` uses the honest "What we will never claim" section.

**Owner:** Kairo CORE legal / technical leadership.

## 4. Uptime, support hours and SLA numbers

**Why it is blocked:** No SLA, uptime figure, support window or response-time
commitment is defined in the repo.

**Needed:** The actual commitment, including time zone and coverage.

**Interim:** Contact page exists; no numeric promises anywhere.

**Owner:** Kairo CORE operations.

## 5. Country / regional availability beyond Zimbabwe

**Why the keyword map blocks this:** The site targets Zimbabwe (verified
currency integrations: EcoCash, Innbucks, ZIPIT, RTGS context). Doorway pages
for other countries were explicitly ruled out in `KEYWORD_AND_PAGE_MAP.md`.
`hreflang` / `llms.txt` language variants exist as a *platform capability*
(sessions carry a locale), not as live country sites.

**Needed:** Confirmation of which countries are actually served, with locally
relevant proof per country, before any `/en-XX/` or regional landing page.

**Owner:** Kairo CORE leadership.

## 6. Integrations beyond the verified set

**Verified and usable in copy today:** Paynow, PaynowRTGS, NPS, PayPal
(gateway code), EcoCash, Innbucks, ZIPIT (payment recording), ZIMSEC result
import (test-covered).

**Blocked until verified:** Any other gateway, SMS provider, WhatsApp, or
government system integration claim.

**Owner:** Kairo CORE engineering.

## 7. Privacy policy — page missing entirely

There is **no `/privacy` route and no privacy policy view** in the repository
(only a mention of privacy inside the terms view). This is both a content
verification item and an open gap: a public privacy policy is expected by
search engines, AI assistants and any school doing due diligence.

**Needed to unblock:** Reviewed policy text covering data collected, purpose,
sub-processors, retention, and international transfers (deployments differ:
InfinityFree legacy, Railway/Azure VM), plus a decision on the route.

**Interim:** The gap is logged; do not generate a policy from thin air —
generate a draft only from facts supplied by Kairo CORE, then have legal approve.

**Owner:** Kairo CORE legal.

---

### Rule of thumb for future copy

> If the claim is not in the repository (code, tests, config, verified content
> tables) or in a document handed to us by Kairo CORE, it does not ship.
> Add it here instead, with the "needed to unblock" line filled in.
