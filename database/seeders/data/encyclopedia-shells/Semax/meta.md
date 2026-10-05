# SEO Meta Package — Semax

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/Semax.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/Semax`  
**Page type:** single  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)  
**Intent:** ACTH(4-7) analog nootropic/neuro peptide; Russia registration ≠ FDA approval; Adamax ≠ Semax

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/Semax`

**Why:** Matches draft cite-us capital S; `semax` also 200—prefer `Semax`.

**Live check (2026-09-30):** **200** thin stub.

**Canonical:** `https://peptidemap.com/encyclopedia/Semax`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Semax? ACTH Analog Research Peptide`  
**Character count:** 43

**Why:** What-is + class cue; avoids unproven cognitive claims in title.

**Runner-ups:**
1. `Semax Peptide: Evidence & Regulatory Basics` — **43 chars** — evidence/reg
2. `Semax vs Adamax: Not the Same Peptide` — **37 chars** — disambiguation

---
## 3. Meta description (~155 chars)

**Recommended:** `Semax is a synthetic ACTH(4-7) analog registered in Russia for some neurologic uses—it is not FDA-approved. Adamax is a different marketplace analog.`  
**Character count:** 149

**Why:** Jurisdiction honesty + Adamax split; no cognitive efficacy claims.

**Runner-up (128):** `Educational Semax overview: sequence/class, Russian registration vs U.S. status, and why Adamax is not interchangeable evidence.`

---
## 4. H1

**Keep:** `Semax`

**Why:** Primary name/cite-us.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/Semax`  
**og:site_name:** `Peptidemap`

**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/semax-featured.png`  
- Upload/rel path for CMS: `images/semax-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback if CMS requires absolute URL pre-upload: `https://peptidemap.com/images/og-default-v7.png`

**OG/Twitter title:** `What is Semax? ACTH Analog Research Peptide` (43)

**OG/Twitter description:** `Synthetic heptapeptide ACTH(4-7) analog. Russian registration does not equal FDA approval. Educational only.` (108)

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- Semax
- Semax peptide
- what is Semax

**Secondary:**
- ACTH 4-7 analog
- Semax vs Adamax
- Semax FDA

**Avoid:** dosing, prices, invented stats, absolute legal verdicts.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/adamax` | **200** | Analog disambiguation |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | COA |
| `/vendors` | **200** | Directory |
| `/guides/beginners-guide-to-research-peptides` | **200** | Beginner |

Hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors`, `/guides` (all **200** as of check).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/Semax#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is Semax FDA-approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No."
      }
    },
    {
      "@type": "Question",
      "name": "Does Russian approval count in the U.S.?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No—approvals are jurisdiction-specific."
      }
    },
    {
      "@type": "Question",
      "name": "Is Adamax the same?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No; Adamax is a marketed analog with far less peer-reviewed identity-specific evidence."
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
  "@id": "https://peptidemap.com/encyclopedia/Semax#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/Semax"
  },
  "headline": "Semax",
  "name": "What is Semax? ACTH Analog Research Peptide",
  "description": "Semax is a synthetic ACTH(4-7) analog registered in Russia for some neurologic uses—it is not FDA-approved. Adamax is a different marketplace analog.",
  "url": "https://peptidemap.com/encyclopedia/Semax",
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
    "Semax",
    "ACTH analog",
    "research peptide",
    "Adamax"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Semax"
    },
    {
      "@type": "Thing",
      "name": "ACTH fragments"
    }
  ]
}
```

**Schema image note:** JSON-LD `image` uses site default until `images/semax-featured.png` is published; swap to the live absolute image URL at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *Semax*
**Cite URL:** `https://peptidemap.com/encyclopedia/Semax`

**APA:** Peptidemap. (2026). *Semax*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/Semax

**Chicago:** Peptidemap. "Semax." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/Semax.

**Snippet accuracy:** Do not cite Russian indication as U.S. approval. Keep Adamax separate.

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. Soft WADA speculation per editor—don’t invent named ban in meta/schema.
2. Cross-link Adamax for disambiguation only.
3. Wire featured image from `images/semax-featured.png` after CMS upload.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin stub meta (`Comprehensive guide to …`).
- Nootropic SERP temptation—keep non-claimy meta.

**Shared:** No live-site edits by Meta Optimizer.
