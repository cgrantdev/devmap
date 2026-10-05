# SEO Meta Package — GHRP-2

**Site:** https://peptidemap.com  
**Draft body:** `/workspace/drafts/encyclopedia/ghrp-2.md`  
**Live URL:** `https://peptidemap.com/encyclopedia/ghrp-2`  
**Page type:** single  
**Chemical class:** peptide (GHS-R1a / pralmorelin)  
**Status:** draft meta only — do not publish or edit live site  
**Prepared:** 2026-09-30 (Asia/Bangkok)  
**Intent:** GHRP-2/pralmorelin encyclopedia — Japan diagnostic use ≠ U.S. therapeutic approval; WADA S2; ≠ ipamorelin

---
## 1. Recommended slug

**Confirm:** `/encyclopedia/ghrp-2`

**Why:** Matches draft lowercase; `GHRP-2` also 200—prefer `ghrp-2` cite-us.

**Live check (2026-09-30):** **200** thin stub.

**Canonical:** `https://peptidemap.com/encyclopedia/ghrp-2`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is GHRP-2 (Pralmorelin)? GH Secretagogue`  
**Character count:** 45

**Why:** What-is + INN + class; under 60.

**Runner-ups:**
1. `GHRP-2 Peptide: Research & Sport Status` — **39 chars** — sport cue
2. `GHRP-2 vs Ipamorelin: Related, Not Same` — **39 chars** — disambiguation

---
## 3. Meta description (~155 chars)

**Recommended:** `GHRP-2 (pralmorelin) stimulates GH via the ghrelin receptor. Japan diagnostic use; not FDA-approved as a U.S. therapy. WADA S2 lists it.`  
**Character count:** 136

**Why:** Identity + jurisdiction + sport; no dosing.

**Runner-up (126):** `Educational GHRP-2 overview: GHS-R1a agonist peptide, Japanese diagnostic context, U.S. unapproved status, and WADA S2 naming.`

---
## 4. H1

**Keep:** `GHRP-2`

**Why:** Common token; pralmorelin in subtitle.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/ghrp-2`  
**og:site_name:** `Peptidemap`

**og:image / twitter:image (CMS wiring):**  
- Draft/frontmatter asset: `/workspace/drafts/encyclopedia/images/ghrp-2-featured.png`  
- Upload/rel path for CMS: `images/ghrp-2-featured.png`  
- **Do not invent** a live public CDN URL until uploaded; temporary fallback: `https://peptidemap.com/images/og-default-v7.png`

**OG/Twitter title:** `What is GHRP-2 (Pralmorelin)? GH Secretagogue` (45)

**OG/Twitter description:** `Synthetic growth hormone–releasing peptide (pralmorelin). Not an FDA-approved U.S. therapeutic; prohibited in sport (WADA S2).` (126)

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- GHRP-2
- pralmorelin
- GHRP-2 peptide

**Secondary:**
- GHRP-2 vs ipamorelin
- GHRP-2 WADA
- ghrelin receptor agonist

**Avoid:** dosing, prices, invented stats, absolute legal verdicts; never mislabel non-peptides as peptides.

---
## 7. Internal link suggestions (verified 2026-09-30)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/ghrp-6` | **200** | Related GHRP |
| `/encyclopedia/Ipamorelin` | **200** | Related GHS, different molecule |
| `/encyclopedia/CJC-1295-Ipamorelin` | **200** | Related GH-axis blend |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | COA |
| `/vendors` | **200** | Directory |

Hubs: `/encyclopedia`, `/compare`, `/testing-labs`, `/vendors`, `/guides` (**200**).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror draft FAQs only.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/ghrp-2#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is GHRP-2 FDA-approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Not as a U.S. therapeutic. Japan uses pralmorelin diagnostically."
      }
    },
    {
      "@type": "Question",
      "name": "Banned in sport?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes—WADA S2 names GHRP-2 (pralmorelin)."
      }
    },
    {
      "@type": "Question",
      "name": "Same as ipamorelin?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No—related class, different molecule."
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
  "@id": "https://peptidemap.com/encyclopedia/ghrp-2#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/ghrp-2"
  },
  "headline": "GHRP-2",
  "name": "What is GHRP-2 (Pralmorelin)? GH Secretagogue",
  "description": "GHRP-2 (pralmorelin) stimulates GH via the ghrelin receptor. Japan diagnostic use; not FDA-approved as a U.S. therapy. WADA S2 lists it.",
  "url": "https://peptidemap.com/encyclopedia/ghrp-2",
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
    "GHRP-2",
    "pralmorelin",
    "growth hormone secretagogue",
    "WADA"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "GHRP-2"
    },
    {
      "@type": "Thing",
      "name": "Pralmorelin"
    }
  ]
}
```

**Schema image note:** Swap Article `image` from site default to published absolute URL for `images/ghrp-2-featured.png` at go-live.

---
## 9. Cite-us awareness

**Suggested cite title:** *GHRP-2*
**Cite URL:** `https://peptidemap.com/encyclopedia/ghrp-2`

**APA:** Peptidemap. (2026). *GHRP-2*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/ghrp-2

**Chicago:** Peptidemap. "GHRP-2." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/ghrp-2.

**Snippet accuracy:** Prefer noting pralmorelin alias. Do not cite as U.S. approved therapy.

**Access note:** Educational research overview; not a clinical guideline. Verify primary sources before scholarly citation.

---
## 10. Notes for Page Author

1. CAS/sequence verify at polish (editor).
2. No diagnostic cutoff numbers as DIY instructions.
3. Wire featured image from `images/ghrp-2-featured.png` after CMS upload.

---
## 11. Publish blockers / Grok Bot flags

- Live page **200** but thin stub meta (`Comprehensive guide to … peptides.`).
- CAS confirm pending.

- **Chemical class reminder:** peptide (GHS-R1a / pralmorelin)

**Shared:** No live-site edits by Meta Optimizer.
