# SEO Meta Package — Melanotan-II

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/Melanotan-II.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/Melanotan-II`  
**Page type:** single  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)
**Light refresh (2026-09-30):** Wired featured/OG image from draft frontmatter; title/meta/H1 re-checked against revised body — **no title/meta/H1 change required** (still accurate). `/guides` + beginner/legality guides now **200**.
  
**Intent:** unapproved α-MSH analog encyclopedia — risk-forward, regulator warnings; not how-to-tan

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/Melanotan-II`

**Why:** Matches live capitalized slug and cite-us; do not invent `melanotan-ii` without redirect.

**Live check (2026-09-30):** **200** thin stub.

**Canonical:** `https://peptidemap.com/encyclopedia/Melanotan-II`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Melanotan-II? Unapproved α-MSH Analog`  
**Character count:** 45

**Why:** What-is + unapproved framing upfront; reduces medical-claim SERP mismatch.
**Runner-ups:**
1. `Melanotan II (MT-II): Research & Safety Overview` — **48 chars** — alias coverage
2. `Melanotan-II: Melanocortin Agonist Explained` — **44 chars** — mechanism cue

---
## 3. Meta description (~155 chars)

**Recommended:** `Melanotan II is a synthetic nonselective α-MSH analog—not FDA-approved. Regulators warn about unapproved tanning products; educational overview only.`  
**Character count:** 149

**Why:** Risk/regulatory honesty first; no tanning how-to; no dosing.

**Runner-up (139):** `Educational Melanotan-II overview: melanocortin pharmacology, historical research interest, and why it is not an approved tanning medicine.`

---
## 4. H1

**Keep (site slug/name). Optional display ‘Melanotan II’ in prose:** `Melanotan-II`

**Why:** Consistent with URL/cite-us; hyphenated product token used on site.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/Melanotan-II`  
**og:site_name:** `Peptidemap`  
**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/melanotan-ii-featured.png`  
- Upload/rel path for CMS: `images/melanotan-ii-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback if CMS requires absolute URL pre-upload: `https://peptidemap.com/images/og-default-v7.png`
**OG/Twitter title:** `What is Melanotan-II? Unapproved α-MSH Analog` (45)

**OG/Twitter description:** `Synthetic cyclic α-MSH analog studied historically for pigmentation pharmacology—not FDA-approved; consumer warnings apply. Educational only.`  
**Character count:** 141

**Twitter card:** `summary_large_image` (reuse OG).

---
## 6. Keywords
**Primary:**
- Melanotan II
- Melanotan-II
- what is Melanotan II

**Secondary:**
- MT-II peptide
- α-MSH analog
- unapproved tanning peptide

**Avoid in meta:** dosing, prices, invented efficacy stats, absolute legal verdicts.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/Melanotan-I` | **200** | Sibling (may be thin) |
| `/encyclopedia/kpv` | **200** | Different α-MSH fragment focus |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | Quality context |
| `/vendors` | **200** | Directory |

Also useful hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors` (all **200**).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only. No dosing/prices/invented stats.
### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/Melanotan-II#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is Melanotan II legal/approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Not FDA-approved; sold in research grey markets; regulators warn consumers."
      }
    },
    {
      "@type": "Question",
      "name": "Is it the same as an approved tanning implant/drug?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No—do not confuse with regulated photomedicine products."
      }
    },
    {
      "@type": "Question",
      "name": "Why so many side-effect stories?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Nonselective receptor activity + unregulated product quality."
      }
    }
  ]
}
```
### Article
```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "@id": "https://peptidemap.com/encyclopedia/Melanotan-II#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/Melanotan-II"
  },
  "headline": "Melanotan-II",
  "name": "What is Melanotan-II? Unapproved α-MSH Analog",
  "description": "Melanotan II is a synthetic nonselective α-MSH analog—not FDA-approved. Regulators warn about unapproved tanning products; educational overview only.",
  "url": "https://peptidemap.com/encyclopedia/Melanotan-II",
  "isPartOf": {
    "@type": "WebSite",
    "name": "Peptidemap",
    "url": "https://peptidemap.com"
  },
  "author": {
    "@type": "Organization",
    "name": "Peptidemap",
    "url": "https://peptidemap.com"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Peptidemap",
    "url": "https://peptidemap.com",
    "logo": {
      "@type": "ImageObject",
      "url": "https://peptidemap.com/images/logo.png"
    }
  },
  "image": [
    "https://peptidemap.com/images/og-default-v7.png"
  ],
  "datePublished": "2026-09-30",
  "dateModified": "2026-09-30",
  "inLanguage": "en-US",
  "keywords": [
    "Melanotan-II",
    "Melanotan II",
    "α-MSH",
    "melanocortin",
    "unapproved"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Melanotan II"
    },
    {
      "@type": "Thing",
      "name": "Melanocortin receptors"
    }
  ]
}
```
Optional BreadcrumbList: Home → Encyclopedia → this name.

**Schema image note:** Swap Article `image` from site default to published absolute URL for `images/melanotan-ii-featured.png` at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *Melanotan-II*
**Cite URL:** `https://peptidemap.com/encyclopedia/Melanotan-II`
**APA (suggested):**
Peptidemap. (2026). *Melanotan-II*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/Melanotan-II
**Chicago (suggested):**
Peptidemap. "Melanotan-II." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/Melanotan-II.

**Snippet accuracy:** Do not cite as tanning guideline. Keep ‘not FDA-approved’ in any short blurb. Avoid absolute criminal-law ‘illegal everywhere’ wording in schema (draft uses approval/warning framing).

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. Keep risk-forward tone; no how-to-tan H2s that could attract unsafe intent.
2. FAQ #1 schema uses draft’s approval/warning framing—not absolute legality.
3. Melanotan-I sibling may still be thin—link carefully.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin meta (`Comprehensive guide to … peptides.`).
- High misuse-intent SERP risk — meta/H1 must stay educational/risk-forward.
- Avoid copying any dosing FAQs if present on other sites’ schema.

**Shared:** No live-site edits performed by Meta Optimizer; CMS paste only after Colin approval.
