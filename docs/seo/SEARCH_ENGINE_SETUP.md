# Search Engine Setup — Instructions & Status

External registrations are **manual** (this project never registers accounts or
deploys for you). Status legend: ✅ done in repo · 🔶 code ready, external
action pending · ❌ not started.

## 0. Prerequisite: deploy the current branch 🔶

The live site at `https://kairocore.co.zw/` currently runs **older code** than
this repository (verified by curl during the audit):

| Check | Live (audit) | After deploy |
|---|---|---|
| `/help` | 404 | 200 (help centre) |
| `/llms.txt` | 404 | 200 |
| `robots.txt` | old version (no AI-crawler sections, no `/language/` disallow) | new central + tenant robots |
| `/sitemap.xml` | 5 URLs | full URL set incl. solution pages + guides |
| Tailwind CDN on homepage | yes (`cdn.tailwindcss.com`) | Vite build (CSP/perf) |

**Nothing below should be submitted to search engines until the deploy is live,
otherwise you submit URLs that 404.**

## 1. Google Search Console 🔶

1. Create account (manual) → Add property → **URL prefix**:
   `https://kairocore.co.zw/` (canonical; also verify `https://www.kairocore.co.zw`
   only if you keep serving it — currently www 301s to apex, so URL-prefix of
   the apex is enough).
2. Verify via HTML-tag (drop into `marketing/layout.blade.php` head when you
   paste the tag) or DNS TXT (preferred, invisible to visitors).
3. Submit sitemap: `https://kairocore.co.zw/sitemap.xml`.
4. Request indexing for `/` once rich results validate.
5. Monitor: Pages (indexed vs excluded), Performance (queries — map against
   `KEYWORD_AND_PAGE_MAP.md`), Mobile usability, Core Web Vitals.

## 2. Bing Webmaster Tools 🔶

1. Register (manual) → add site → verify (meta tag or DNS).
2. Submit `sitemap.xml`. Bing also serves DuckDuckGo — one submission covers both.
3. Optional: IndexNow key file (only if we decide to ping on publish; not
   implemented, needs a product decision).

## 3. Robots & crawler policy ✅ (code) / 🔶 (deploy)

Central (`routes/web.php`) and tenant (`CmsSeoService::generateRobotsTxt`)
robots.txt both include:

- `Disallow: /platform`, `/workspace`, `/student`, `/register`,
  `/activate-account`, `/language/`, `/locale/` (private/SEO-loops)
- AI crawlers: `GPTBot` **Allow** (current documented decision — see
  `INITIAL_AUDIT.md` §7), `OAI-SearchBot`, `ClaudeBot`, `PerplexityBot` Allow;
  `Claude-Web`/`CCBot`/`Semrush`/`Ahrefs`/`Bytespider`/`GPTBot-User` etc. per
  central file.
- `Sitemap: {absolute}` line present.

Decision log: changing the GPTBot line is a **product decision**, never a
silent edit.

## 4. Directories & citations (manual, low priority) 🔶

- Google Business Profile for Kairo CORE (if you want local visibility).
- Zimbabwe-relevant directories/startup lists — only listings you can maintain;
  no bulk directory spam.

## 5. Hosting / DNS hygiene ✅ verified in audit

- apex → apex 301s (www single-hop) ✅ · http → https ✅ · missing host → genuine 404 ✅
- `/.env`, `/info.php` → 404 ✅ (info.php removed from repo)
- No `X-Robots-Tag` issues; HTML `cache-control: no-store` (fine for HTML;
  ensure static assets get long max-age — CDN/host config, external).

## 6. Ongoing monitoring cadence

| Cadence | Where | What to look at |
|---|---|---|
| Weekly | Search Console Performance | impressions/clicks vs keywords in KEYWORD_AND_PAGE_MAP |
| Weekly | Search Console Coverage | new 404s, "Crawled – currently not indexed" spikes |
| Monthly | Pages report | which solution pages gain impressions → expand content |
| Monthly | `docs/seo/*` review | stale claims? new features needing pages? |
| After each deploy | curl checks | `/help` 200, `/sitemap.xml` fresh, robots includes AI sections |
| After each deploy | Rich Results Test | SoftwareApplication/Breadcrumb/HowTo/FAQ no errors |

## 7. Quick curl verification pack (run post-deploy)

```sh
for p in / /robots.txt /sitemap.xml /llms.txt /help /school-fees /students \
         /admissions /academics /security; do
  printf '%-20s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' https://kairocore.co.zw$p)"
done
curl -s https://kairocore.co.zw/robots.txt | head -30
```
