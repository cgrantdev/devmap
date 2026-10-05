# SEO Meta Package — Elamipretide

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/Elamipretide.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/Elamipretide`  
**Page type:** single  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)  
**Intent:** mitochondria-targeted tetrapeptide; distinguish approved Forzinity (Barth syndrome, accelerated approval) from research-chemical/wellness vials

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/Elamipretide`

**Why:** Matches draft cite-us and live URL casing; `elamipretide` also 200—prefer draft/cite canonical `Elamipretide`.

**Live check (2026-09-30):** **200** thin stub.

**Canonical:** `https://peptidemap.com/encyclopedia/Elamipretide`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Elamipretide? SS-31 & Forzinity Basics`  
**Character count:** 46

**Why:** What-is + research alias + approved brand cue without equating grey-market vials to Forzinity.

**Runner-ups:**
1. `Elamipretide (SS-31): Research vs Forzinity` — **43 chars** — alias + pathway split
2. `Elamipretide: Mitochondrial Peptide Overview` — **44 chars** — softer brand mention

---
## 3. Meta description (~155 chars)

**Recommended:** `Elamipretide (SS-31) is a mitochondria-targeted peptide. Forzinity has accelerated FDA approval for Barth syndrome—research vials are not that product.`  
**Character count:** 151

**Why:** Separates approved NDA product from catalog RUO; no dosing/prices; accurate to Sept 2025 accelerated approval framing in draft.

**Runner-up (141):** `Educational elamipretide overview: cardiolipin research context, Forzinity accelerated approval limits, and why vendor vials ≠ approved drug.`

---
## 4. H1

**Keep:** `Elamipretide`

**Why:** INN/name matches cite-us; aliases in subtitle/title.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/Elamipretide`  
**og:site_name:** `Peptidemap`

**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/elamipretide-featured.png`  
- Upload/rel path for CMS: `images/elamipretide-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback if CMS requires absolute URL pre-upload: `https://peptidemap.com/images/og-default-v7.png`

**OG/Twitter title:** `What is Elamipretide? SS-31 & Forzinity Basics` (46)

**OG/Twitter description:** `Mitochondria-targeted tetrapeptide (SS-31). Forzinity accelerated approval is for Barth syndrome—not research-chemical or wellness vials.` (137)

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- elamipretide
- elamipretide SS-31
- Forzinity elamipretide

**Secondary:**
- MTP-131
- Barth syndrome peptide
- mitochondria targeted peptide

**Avoid:** dosing, prices, invented stats, absolute legal verdicts.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | COA |
| `/vendors` | **200** | Directory |
| `/guides/peptide-legality-fda-ruo-compounding` | **200** | Pathway literacy |
| `/guides/beginners-guide-to-research-peptides` | **200** | Beginner literacy |

Hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors`, `/guides` (all **200** as of check).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/Elamipretide#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is elamipretide FDA-approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Forzinity (elamipretide) has accelerated approval for Barth syndrome in patients ≥30 kg. That does not approve research-chemical vials or wellness uses."
      }
    },
    {
      "@type": "Question",
      "name": "Is accelerated approval “full” approval?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "It allows earlier access based on an intermediate endpoint reasonably likely to predict benefit; confirmatory trials are required."
      }
    },
    {
      "@type": "Question",
      "name": "Are Peptidemap vendor vials Forzinity?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. Catalog research products are not the approved NDA product."
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
  "@id": "https://peptidemap.com/encyclopedia/Elamipretide#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/Elamipretide"
  },
  "headline": "Elamipretide",
  "name": "What is Elamipretide? SS-31 & Forzinity Basics",
  "description": "Elamipretide (SS-31) is a mitochondria-targeted peptide. Forzinity has accelerated FDA approval for Barth syndrome—research vials are not that product.",
  "url": "https://peptidemap.com/encyclopedia/Elamipretide",
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
    "elamipretide",
    "SS-31",
    "Forzinity",
    "Barth syndrome",
    "mitochondria"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Elamipretide"
    },
    {
      "@type": "Thing",
      "name": "Mitochondria"
    }
  ]
}
```

**Schema image note:** JSON-LD `image` uses site default until `images/elamipretide-featured.png` is published; swap to the live absolute image URL at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *Elamipretide*
**Cite URL:** `https://peptidemap.com/encyclopedia/Elamipretide`

**APA:** Peptidemap. (2026). *Elamipretide*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/Elamipretide

**Chicago:** Peptidemap. "Elamipretide." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/Elamipretide.

**Snippet accuracy:** Do not cite as blanket FDA approval for all elamipretide products—specify Forzinity/Barth accelerated approval when relevant.

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. Lead with Forzinity vs research-vial distinction—meta promises it.
2. Avoid implying Peptidemap sells Forzinity.
3. Keep accelerated-approval literacy (intermediate endpoint / confirmatory trials).
4. Wire featured image from `images/elamipretide-featured.png` after CMS upload.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin stub meta (`Comprehensive guide to …`).
- Cannibalization risk if any future Forzinity/pharmacy pages—keep this encyclopedia educational/research-vs-NDA.
- Guides now **200**—prefer those for pathway literacy.

**Shared:** No live-site edits by Meta Optimizer.
