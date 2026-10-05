# SEO Meta Package — Glutathione

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/glutathione.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/glutathione`  
**Page type:** single  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)
**Light refresh (2026-09-30):** Wired featured/OG image from draft frontmatter; title/meta/H1 re-checked against revised body — **no title/meta/H1 change required** (still accurate). `/guides` + beginner/legality guides now **200**.
  
**Intent:** endogenous antioxidant tripeptide literacy; injectable wellness claims ≠ FDA approval; quality/safety warnings educational

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/glutathione`

**Why:** Live lowercase canonical; `Glutathione` also 200—cite-us uses lowercase.

**Live check (2026-09-30):** **200** thin stub (misleadingly under ‘peptides’ comprehensive-guide template).

**Canonical:** `https://peptidemap.com/encyclopedia/glutathione`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Glutathione (GSH)? Redox Biology Overview`  
**Character count:** 49

**Why:** What-is + common abbreviation; biology-first not ‘research peptide hype’.
**Runner-ups:**
1. `Glutathione: Endogenous Tripeptide Antioxidant Guide` — **52 chars** — endogenous cue
2. `Glutathione (GSH): Research & Regulatory Basics` — **47 chars** — reg/regulatory lite

---
## 3. Meta description (~155 chars)

**Recommended:** `Glutathione (GSH) is an endogenous antioxidant tripeptide. Injectable wellness/skin claims aren’t FDA-approved drug uses—quality literacy matters.`  
**Character count:** 146

**Why:** Separates biochemistry from marketplace/compounding hype; no dosing; safety literacy without invented stats.

**Runner-up (133):** `Educational glutathione overview: redox role, forms, why IV wellness marketing isn’t drug approval, and documentation quality issues.`

---
## 4. H1

**Keep:** `Glutathione`

**Why:** Standard INN-style name; subtitle carries chemistry.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/glutathione`  
**og:site_name:** `Peptidemap`  
**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/glutathione-featured.png`  
- Upload/rel path for CMS: `images/glutathione-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback if CMS requires absolute URL pre-upload: `https://peptidemap.com/images/og-default-v7.png`
**OG/Twitter title:** `What is Glutathione (GSH)? Redox Biology Overview` (49)

**OG/Twitter description:** `Endogenous Glu–Cys–Gly tripeptide central to redox biology—not an FDA-approved injectable wellness drug. Educational only.`  
**Character count:** 122

**Twitter card:** `summary_large_image` (reuse OG).

---
## 6. Keywords
**Primary:**
- glutathione
- what is glutathione
- GSH tripeptide

**Secondary:**
- glutathione antioxidant
- injectable glutathione FDA
- reduced glutathione GSH

**Avoid in meta:** dosing, prices, invented efficacy stats, absolute legal verdicts.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/NAD` | **200** | Related redox/NAD+ encyclopedia (slug NAD, title NAD+) |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | Quality/COA adjacent |
| `/vendors` | **200** | Directory |

**Soft / blocked:**
- `/guides/peptide-legality-fda-ruo-compounding` — 200: Compounding pathway literacy

Also useful hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors` (all **200**).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only. No dosing/prices/invented stats.
### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/glutathione#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is glutathione a “research peptide” like BPC-157?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Biochemically it is an endogenous tripeptide; commercially it appears in overlapping catalogs, but evidence and regulatory stories differ."
      }
    },
    {
      "@type": "Question",
      "name": "Is IV glutathione FDA-approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No approved product for general wellness/skin lightening."
      }
    },
    {
      "@type": "Question",
      "name": "Why do quality warnings matter?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Injectable compounding with non-sterile/supplement-grade inputs has been linked to serious adverse events."
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
  "@id": "https://peptidemap.com/encyclopedia/glutathione#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/glutathione"
  },
  "headline": "Glutathione",
  "name": "What is Glutathione (GSH)? Redox Biology Overview",
  "description": "Glutathione (GSH) is an endogenous antioxidant tripeptide. Injectable wellness/skin claims aren’t FDA-approved drug uses—quality literacy matters.",
  "url": "https://peptidemap.com/encyclopedia/glutathione",
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
    "glutathione",
    "GSH",
    "antioxidant",
    "tripeptide",
    "redox"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Glutathione"
    },
    {
      "@type": "Thing",
      "name": "Antioxidants"
    }
  ]
}
```
Optional BreadcrumbList: Home → Encyclopedia → this name.

**Schema image note:** Swap Article `image` from site default to published absolute URL for `images/glutathione-featured.png` at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *Glutathione*
**Cite URL:** `https://peptidemap.com/encyclopedia/glutathione`
**APA (suggested):**
Peptidemap. (2026). *Glutathione*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/glutathione
**Chicago (suggested):**
Peptidemap. "Glutathione." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/glutathione.

**Snippet accuracy:** Cite as biochemistry overview, not clinical guideline for IV use. Keep endogenous-molecule framing in snippets.

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. Don’t force ‘research peptide’ framing that equates GSH with BPC-class RUO marketing.
2. Verify current compounding-list language before assertive Category statements (editor note).
3. Legality guide is live: `/guides/peptide-legality-fda-ruo-compounding`.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin meta (`Comprehensive guide to … peptides.`).
- Regulatory compounding status may move—avoid hardcoding Category claims in meta.
- `/guides` + beginner & legality guides now **200** (verified 2026-09-30).
- `/encyclopedia/NAD+` **404**; use `/encyclopedia/NAD`.

**Shared:** No live-site edits performed by Meta Optimizer; CMS paste only after Colin approval.
