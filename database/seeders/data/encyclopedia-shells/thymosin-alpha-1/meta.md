# SEO Meta Package — Thymosin Alpha-1

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/thymosin-alpha-1.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/thymosin-alpha-1`  
**Page type:** single  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)  
**Intent:** immunomodulatory Tα1/thymalfasin encyclopedia; not FDA NDA; not TB-500; compounding concerns literacy

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/thymosin-alpha-1`

**Why:** Matches draft/cite lowercase hyphenation; `Thymosin-Alpha-1` also 200—canonical `thymosin-alpha-1`.

**Live check (2026-09-30):** **200** thin stub.

**Canonical:** `https://peptidemap.com/encyclopedia/thymosin-alpha-1`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Thymosin Alpha-1 (Thymalfasin)?`  
**Character count:** 39

**Why:** What-is + synthetic name; under 60; no efficacy promise.

**Runner-ups:**
1. `Thymosin Alpha-1: Research Peptide Overview` — **43 chars** — generic encyclopedia
2. `Thymosin Alpha-1 vs TB-500: Not the Same` — **40 chars** — disambiguation runner

---
## 3. Meta description (~155 chars)

**Recommended:** `Thymosin alpha-1 (thymalfasin) is an immunomodulatory peptide used abroad—not a broad U.S. FDA-approved NDA product and not the same as TB-500.`  
**Character count:** 143

**Why:** Identity + jurisdiction + TB-500 disambiguation; compounding caution belongs in body not hype meta.

**Runner-up (118):** `Educational Tα1 overview: structure/role, non-U.S. use context, orphan designation ≠ approval, and TB-500 distinction.`

---
## 4. H1

**Keep:** `Thymosin Alpha-1`

**Why:** Display name; slug remains hyphenated lowercase.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/thymosin-alpha-1`  
**og:site_name:** `Peptidemap`

**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/thymosin-alpha-1-featured.png`  
- Upload/rel path for CMS: `images/thymosin-alpha-1-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback if CMS requires absolute URL pre-upload: `https://peptidemap.com/images/og-default-v7.png`

**OG/Twitter title:** `What is Thymosin Alpha-1 (Thymalfasin)?` (39)

**OG/Twitter description:** `28-aa immunomodulatory peptide (thymalfasin). Not a broad U.S. NDA approval—and not TB-500. Educational only.` (109)

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- thymosin alpha-1
- thymalfasin
- what is thymosin alpha-1

**Secondary:**
- Tα1 peptide
- thymosin alpha-1 FDA
- thymosin alpha-1 vs TB-500

**Avoid:** dosing, prices, invented stats, absolute legal verdicts.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/TB-500` | **200** | Disambiguation |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | COA |
| `/vendors` | **200** | Directory |
| `/guides/peptide-legality-fda-ruo-compounding` | **200** | Compounding literacy |

**Soft / caveat:**
- `/blog/fda-peptide-reclassification-2026-what-researchers-need-to-know` — 200: CONFLICT caveat—Cat2≠Cat1≠Bulks; soft-link only

Hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors`, `/guides` (all **200** as of check).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/thymosin-alpha-1#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is thymosin alpha-1 FDA-approved in the U.S.?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Not as a broadly marketed NDA product; orphan designations are not approvals."
      }
    },
    {
      "@type": "Question",
      "name": "Is it the same as TB-500?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No."
      }
    },
    {
      "@type": "Question",
      "name": "Why do compounding rules matter?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "FDA has raised characterization/immunogenicity concerns for compounded peptide materials—verify current status."
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
  "@id": "https://peptidemap.com/encyclopedia/thymosin-alpha-1#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/thymosin-alpha-1"
  },
  "headline": "Thymosin Alpha-1",
  "name": "What is Thymosin Alpha-1 (Thymalfasin)?",
  "description": "Thymosin alpha-1 (thymalfasin) is an immunomodulatory peptide used abroad—not a broad U.S. FDA-approved NDA product and not the same as TB-500.",
  "url": "https://peptidemap.com/encyclopedia/thymosin-alpha-1",
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
    "thymosin alpha-1",
    "thymalfasin",
    "immunomodulatory peptide",
    "TB-500"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Thymosin alpha-1"
    },
    {
      "@type": "Thing",
      "name": "Thymalfasin"
    }
  ]
}
```

**Schema image note:** JSON-LD `image` uses site default until `images/thymosin-alpha-1-featured.png` is published; swap to the live absolute image URL at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *Thymosin Alpha-1*
**Cite URL:** `https://peptidemap.com/encyclopedia/thymosin-alpha-1`

**APA:** Peptidemap. (2026). *Thymosin Alpha-1*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/thymosin-alpha-1

**Chicago:** Peptidemap. "Thymosin Alpha-1." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/thymosin-alpha-1.

**Snippet accuracy:** Cite as educational overview; do not imply U.S. drug approval. Keep TB-500 distinction if snippet truncated.

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. Early TB-500 ≠ Tα1 callout.
2. Compounding: Category headlines ≠ permission—align with legality guide; caveat FDA reclassification blog.
3. Wire featured image from `images/thymosin-alpha-1-featured.png` after CMS upload.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin stub meta (`Comprehensive guide to …`).
- FDA blog framing conflict if heavily linked for compounding status.
- International approval claims must stay jurisdiction-specific.

**Shared:** No live-site edits by Meta Optimizer.
