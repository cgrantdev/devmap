# SEO Meta Package — Mazdutide

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/Mazdutide.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/Mazdutide`  
**Page type:** single  
**Chemical class:** peptide (GCG/GLP-1 dual agonist)  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)  
**Intent:** dual agonist peptide literacy — China NMPA Xinermei ≠ FDA approval; research vials ≠ branded drug

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/Mazdutide`

**Why:** Matches draft/cite-us capital M; `mazdutide` also 200—prefer `Mazdutide`.

**Live check (2026-09-30):** **200** thin stub (wrongly generic ‘peptides’ template).

**Canonical:** `https://peptidemap.com/encyclopedia/Mazdutide`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Mazdutide? Dual GLP-1/Glucagon Agonist`  
**Character count:** 46

**Why:** Draft seo_title; what-is + dual-agonist class; under 60.

**Runner-ups:**
1. `Mazdutide: China-Approved Dual Agonist Overview` — **47 chars** — jurisdiction cue
2. `Mazdutide (Xinermei) vs Research Vials` — **38 chars** — product-boundary

---
## 3. Meta description (~155 chars)

**Recommended:** `Mazdutide is a GCG/GLP-1 dual agonist approved in China (NMPA)—not FDA-approved. Research vials are not Xinermei.`  
**Character count:** 113

**Why:** NMPA vs FDA + vial≠brand; shortened to ≤155; no % claims.

**Runner-up (133):** `Educational mazdutide overview: once-weekly GCG/GLP-1 dual agonist, China approvals, and why U.S. research listings are not Xinermei.`

---
## 4. H1

**Keep:** `Mazdutide`

**Why:** Matches draft H1/name.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/Mazdutide`  
**og:site_name:** `Peptidemap`

**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/Mazdutide-featured.png`  
- Upload/rel path for CMS: `images/Mazdutide-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback: `https://peptidemap.com/images/og-default-v7.png`

**OG/Twitter title:** `What is Mazdutide? Dual GLP-1/Glucagon Agonist` (46)

**OG/Twitter description:** `Once-weekly GCG/GLP-1 dual agonist. NMPA-approved in China (Xinermei)—not FDA-approved; research vials are not that medicine.` (125)

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- mazdutide
- what is mazdutide
- mazdutide GLP-1 glucagon

**Secondary:**
- Xinermei
- IBI362
- dual agonist peptide
- mazdutide FDA

**Avoid:** dosing, prices, invented stats, absolute legal verdicts; never mislabel non-peptides as peptides.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/Survodutide` | **200** | Related dual agonist (investigational) |
| `/encyclopedia/orforglipron` | **200** | Oral GLP-1 contrast (nonpeptide) |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | COA |
| `/vendors` | **200** | Directory |
| `/guides/beginners-guide-to-research-peptides` | **200** | Beginner |

Hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors`, `/guides` (**200**).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/Mazdutide#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is mazdutide FDA-approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No—not as of this draft’s sources."
      }
    },
    {
      "@type": "Question",
      "name": "Is it approved anywhere?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes—China NMPA approvals for weight management and adult T2D (2025), per Innovent."
      }
    },
    {
      "@type": "Question",
      "name": "Are research vials Xinermei?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No."
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
  "@id": "https://peptidemap.com/encyclopedia/Mazdutide#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/Mazdutide"
  },
  "headline": "Mazdutide",
  "name": "What is Mazdutide? Dual GLP-1/Glucagon Agonist",
  "description": "Mazdutide is a GCG/GLP-1 dual agonist approved in China (NMPA)—not FDA-approved. Research vials are not Xinermei.",
  "url": "https://peptidemap.com/encyclopedia/Mazdutide",
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
    "mazdutide",
    "GLP-1",
    "glucagon",
    "dual agonist",
    "Xinermei"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Mazdutide"
    },
    {
      "@type": "Thing",
      "name": "GLP-1 receptor agonists"
    }
  ]
}
```

**Schema image note:** Swap Article `image` from site default to published absolute URL for `images/Mazdutide-featured.png` at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *Mazdutide*
**Cite URL:** `https://peptidemap.com/encyclopedia/Mazdutide`

**APA:** Peptidemap. (2026). *Mazdutide*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/Mazdutide

**Chicago:** Peptidemap. "Mazdutide." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/Mazdutide.

**Snippet accuracy:** Do not cite as FDA-approved. Keep NMPA/Xinermei vs research-vial distinction in snippets.

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. Lead with dual-agonist + China vs FDA.
2. Do not invent GLORY-1 % figures in meta/body (editor).
3. Cross-link Survodutide carefully—different molecule/status.
4. Wire featured image from `images/Mazdutide-featured.png` after CMS upload.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin stub meta (`Comprehensive guide to … peptides.`).
- Re-check U.S. Phase 3/filing at publish (editor).
- Obesity SERP temptation—no invented efficacy %.

- **Chemical class reminder:** peptide (GCG/GLP-1 dual agonist)

**Shared:** No live-site edits by Meta Optimizer.
