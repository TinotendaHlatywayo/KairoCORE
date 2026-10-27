# SEO Manual Steps for Kairo CORE (kairo.co.zw)

This document lists everything you must do manually. Follow in order.

## 1) Google Search Console
1. Go to [Google Search Console](https://search.google.com/search-console).
2. Add property: prefer Domain property for `kairo.co.zw` (requires DNS TXT verification). If DNS access is limited, use URL-prefix `https://kairo.co.zw/` and add the meta tag via env `GOOGLE_SITE_VERIFICATION`.
3. Submit sitemap: `https://kairo.co.zw/sitemap.xml` (Sitemaps section).
4. Use URL Inspection → Request Indexing for these priority URLs:
   - https://kairo.co.zw/
   - https://kairo.co.zw/features
   - https://kairo.co.zw/school-fees
   - https://kairo.co.zw/admissions
   - https://kairo.co.zw/students
   - https://kairo.co.zw/academics
   - https://kairo.co.zw/about
   - https://kairo.co.zw/contact
   - https://kairo.co.zw/blog
   - https://kairo.co.zw/docs
5. Monitor: Performance (queries/pages/countries), Coverage/Pages, Core Web Vitals weekly.

## 2) Bing Webmaster Tools
1. Go to [Bing Webmaster Tools](https://www.bing.com/webmasters).
2. Add/import `https://kairo.co.zw/`. Verify via DNS or the `BING_SITE_VERIFICATION` meta tag (added to layout when env set).
3. Submit sitemap: `https://kairo.co.zw/sitemap.xml`.
4. IndexNow: confirm key `5fa5d286335b4dd08128683307e2c0b0` is recognized (key file at `https://kairo.co.zw/5fa5d286335b4dd08128683307e2c0b0.txt` must return only the key as plain text).
5. URL Inspection → Request Indexing for the 10 priority URLs above.
6. "Discovered but not crawled": usually means low priority/new domain/little external linking. Fix by adding internal links from high-traffic pages, getting real backlinks, ensuring fast server response, no noindex, and submitting via IndexNow + sitemap.

### Quick IndexNow test (after key file is live)
```bash
curl -X POST "https://api.indexnow.org/IndexNow" \
  -H "Content-Type: application/json; charset=utf-8" \
  -d '{
    "host": "kairo.co.zw",
    "key": "5fa5d286335b4dd08128683307e2c0b0",
    "keyLocation": "https://kairo.co.zw/5fa5d286335b4dd08128683307e2c0b0.txt",
    "urlList": [
      "https://kairo.co.zw/",
      "https://kairo.co.zw/features",
      "https://kairo.co.zw/school-fees",
      "https://kairo.co.zw/admissions",
      "https://kairo.co.zw/security",
      "https://kairo.co.zw/about",
      "https://kairo.co.zw/blog",
      "https://kairo.co.zw/contact",
      "https://kairo.co.zw/docs",
      "https://kairo.co.zw/help/enroll-student",
      "https://kairo.co.zw/register"
    ]
  }'
```
Run via Laravel: `php artisan seo:indexnow`

## 3) kairocore.me
- Check WHOIS/DNS ownership. If it is yours, 301 redirect page-for-page to `https://kairo.co.zw/`. If not owned by you, do not imply ownership; focus on `kairo.co.zw` as the canonical brand site and ensure consistent "Kairo CORE" naming.

## 4) Brand profiles & AI Overview correction
Create/update with consistent info and link to `https://kairo.co.zw/`:
- LinkedIn Company Page
- Facebook Page
- X (Twitter)
- YouTube (demo videos)
- Instagram (bio link to kairo.co.zw)
- Crunchbase (if eligible)

Ensure descriptions use "Kairo CORE" and accurate facts. If Google's AI Overview cites an old Instagram post, update that post/profile to reference `kairo.co.zw` and use correct naming.

## 5) Directories & reviews
Submit with consistent NAP/description:
- G2, Capterra, SoftwareSuggest, SaaS.africa, AlternativeTo, Product Hunt
- Google Business Profile (if applicable)
- Bing Places

Suggested short description: "Kairo CORE is a multi-tenant cloud school management platform for schools in Zimbabwe, Southern Africa and worldwide — admissions, fees, attendance, academics, ZIMSEC & Cambridge-ready report cards, and a public website per school."

## 6) Backlinks & testimonials
- Reach out to Zimbabwean education/tech sites, school associations, and partner schools
- Collect real testimonials (only with permission). If you publish real reviews, schema can include aggregateRating legitimately
- Do NOT add forced "Powered by Kairo CORE" footer links to tenant sites without approval

## 7) Monitoring
- GSC weekly: queries, impressions/clicks, coverage
- Bing Webmaster Tools weekly
- Manual spot checks in incognito/VPN (personalized results vary)
- Track IndexNow responses (200/202 accepted)

## 8) Timeline
Expect initial crawl/indexing improvements over weeks; competitive terms take longer. Focus on technical correctness first (done), then content + links.
