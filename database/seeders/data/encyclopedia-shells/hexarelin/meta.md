# SEO Meta Package: Hexarelin

**Site:** https://peptidemap.com  
**Target URL:** `https://peptidemap.com/encyclopedia/hexarelin` (new page)  
**Page type:** single  
**Prepared:** 2026-10-08 (Asia/Bangkok)

**Intent:** "what is hexarelin" encyclopedia entry for a 1990s GH-releasing peptide: identity (INN examorelin), ghrelin-receptor and CD36 pharmacology, human evidence labeled by study type and size, and regulatory and anti-doping status. Not buying intent, not dosing.

---
## 1. Recommended slug

**Recommended:** `hexarelin`

**Why:** The existing ProductCategory slug shown on live product pages is lowercase `hexarelin`. Live check on 8 Oct 2026: `/encyclopedia/hexarelin` **404**, `/encyclopedia/Hexarelin` **404**, `/compare/hexarelin` **404**. No live encyclopedia page exists to match casing against, so the slug follows the stored category slug.

**Canonical:** `https://peptidemap.com/encyclopedia/hexarelin`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Hexarelin (Examorelin)? GHRP Research Overview`  
**Character count:** 54

**Why:** Puts the "what is" query first and carries both the common name and the INN. No efficacy claim.

**Runner-ups:**
1. `Hexarelin (Examorelin): GH-Releasing Peptide Explained` (54 chars).
2. `Hexarelin: GHRP-6 Analog, CD36 Binding and WADA Status` (54 chars).

---
## 3. Meta description (~155 chars)

**Recommended:** `Hexarelin (examorelin) is a synthetic GHRP-6 analog acting on the ghrelin receptor and CD36. Not approved; WADA-prohibited. Human evidence explained.`  
**Character count:** 149

**Why:** Identity, class, and "not approved" appear in the first 120 characters. No numbers, so nothing can go stale or be misread.

**Runner-up (153):** `Hexarelin (examorelin) is a GHRP-6 analog studied 1994-2004. Ghrelin-receptor and CD36 pharmacology, human evidence, and regulatory status. Not approved.`

---
## 4. H1

**Recommended:** `Hexarelin`
Subtitle Synthetic GH-releasing hexapeptide (INN examorelin) · not approved · WADA S2

**Why:** Matches the slug, the common name, and the cite-us title. The subtitle line carries the INN and status.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/hexarelin`  
**og:site_name:** `Peptidemap`  
**og:image / twitter:image (CMS wiring):**  
- File: `hexarelin-featured.png` (1920×1080 PNG), uploaded to `/images/encyclopedia/hexarelin-featured.png`  
- Absolute URL: `https://peptidemap.com/images/encyclopedia/hexarelin-featured.png`

**OG/Twitter title:** `Hexarelin (Examorelin): GH-Releasing Peptide Explained` (54)

**OG/Twitter description:** `A 1990s GH-releasing peptide that also binds cardiac CD36. Not approved; WADA-prohibited. Human studies labeled by type and size.`  
**Character count:** 129

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- hexarelin
- examorelin
- hexarelin GHRP-6 analog

**Secondary:**
- hexarelin CD36
- hexarelin desensitization
- GH-releasing peptide WADA
- ghrelin receptor agonist peptide

**Avoid in meta:** dosing, dose amounts, "buy", "for sale", price, "approved", "results", "better than", bodybuilding framing.

---
## 7. Internal link suggestions (verified live 8 Oct 2026; link only rows not marked "do not link yet")

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/Ipamorelin` | **200** (self-canonical; lowercase `/encyclopedia/ipamorelin` 301s) | Sibling peptide GHS-R1a agonist |
| `/encyclopedia/ghrp-6` | **200**, do not link yet (empty body); restore when bodies ship | Parent hexapeptide |
| `/encyclopedia/ghrp-2` | **200**, do not link yet (empty body); restore when bodies ship | Sibling GHRP |
| `/encyclopedia/Ibutamoren` | **200** | Oral non-peptide ghrelin-receptor agonist |
| `/encyclopedia/Sermorelin` | **200** | GHRH-receptor route (contrast) |
| `/encyclopedia/Tesamorelin` | **200** | GHRH-receptor route (contrast) |
| `/encyclopedia/CJC-1295` | **200** | GHRH-receptor route (contrast) |
| `/guides/peptide-legality-fda-ruo-compounding` | **200** | Regulatory literacy |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | Quality context (optional) |

Do not link /compare or shop pages from this entry.
Not live (do not link): `/compare/hexarelin` (**404**).

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror the page FAQs word for word. No dosing, prices, or efficacy numbers.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/hexarelin#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is hexarelin the same as GHRP-6?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. Hexarelin is a GHRP-6 analog that differs by one methyl group on the second residue (2-methyl-D-tryptophan in place of D-tryptophan). It has its own INN (examorelin), CAS number, and literature, so data for GHRP-6 do not automatically apply to hexarelin."
      }
    },
    {
      "@type": "Question",
      "name": "Is hexarelin approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. As of 8 Oct 2026 no FDA or EU approval was found, and ClinicalTrials.gov lists no registered hexarelin studies. The published human studies from 1994 to 2004 are research, not approval."
      }
    },
    {
      "@type": "Question",
      "name": "Is hexarelin banned in sport?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. The WADA 2026 and 2027 Prohibited Lists name examorelin (hexarelin) among GH-releasing peptides under section S2.2.4, which covers substances prohibited at all times."
      }
    },
    {
      "@type": "Question",
      "name": "Does hexarelin only raise growth hormone?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. Human studies found that it also raises prolactin, ACTH, and cortisol, and in healthy adults its ACTH and cortisol effect was similar in size to that of corticotropin-releasing hormone. It also binds CD36, a receptor linked to cardiac effects seen in animal studies and small single-dose human studies."
      }
    },
    {
      "@type": "Question",
      "name": "Does the response to hexarelin fade with repeated use?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Partly. In a 16-week study reported in two 1998 papers, the GH response fell and then recovered about 4 weeks after stopping. Short intermittent courses did not reduce the response. Cell studies show the receptor can desensitize within minutes."
      }
    },
    {
      "@type": "Question",
      "name": "Can I buy hexarelin?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Peptidemap is a publisher, not a supplier, pharmacy, or clinic, and makes no supply, availability, vendor, or price claims about hexarelin. It is not approved for any use. Any product sold online under this name is not regulated pharmaceutical supply, and its identity, purity, and sterility are unverified."
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
  "@id": "https://peptidemap.com/encyclopedia/hexarelin#article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://peptidemap.com/encyclopedia/hexarelin"
  },
  "headline": "Hexarelin",
  "name": "What is Hexarelin (Examorelin)? GHRP Research Overview",
  "description": "Hexarelin (examorelin) is a synthetic GHRP-6 analog acting on the ghrelin receptor and CD36. Not approved; WADA-prohibited. Human evidence explained.",
  "url": "https://peptidemap.com/encyclopedia/hexarelin",
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
    "https://peptidemap.com/images/encyclopedia/hexarelin-featured.png"
  ],
  "datePublished": "SET-AT-PUBLISH",
  "dateModified": "SET-AT-PUBLISH",
  "inLanguage": "en-US",
  "keywords": [
    "hexarelin",
    "examorelin",
    "GHRP",
    "ghrelin receptor",
    "CD36"
  ],
  "about": [
    {
      "@type": "Thing",
      "name": "Hexarelin"
    },
    {
      "@type": "Thing",
      "name": "Growth hormone secretagogue"
    }
  ]
}
```
**Date fields:** `datePublished` and `dateModified` hold the literal `SET-AT-PUBLISH`. Replace both with the actual publish timestamp (ISO 8601) when the page goes live.

Optional BreadcrumbList: Home → Encyclopedia → Hexarelin.

**Schema notes:** Use Article + FAQPage only. Do **not** use `Drug`/`MedicalEntity` with `legalStatus` or `prescriptionStatus`, because these could imply an approved product. Do not add `Product`/`Offer` schema (no supply claims). `image` is the featured PNG's absolute URL, so the file must be uploaded to `/images/encyclopedia/` before publish.

---
## 9. Cite-us awareness

**Suggested cite title:** *Hexarelin*  
**Cite URL:** `https://peptidemap.com/encyclopedia/hexarelin`  
**APA (suggested):** Peptidemap. (2026). *Hexarelin*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/hexarelin  
**Chicago (suggested):** Peptidemap. "Hexarelin." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/hexarelin.

**Snippet accuracy:** Keep "not approved" and the INN (examorelin) in any short blurb. Never put hormone or ejection-fraction figures in a title or meta description.

---
## 10. Notes for Page Author

1. Every human result in the body names its journal, PMID, and study size. Keep those labels if the text is edited.
2. No dose amounts or schedules appear in the body, by design. The cited abstracts contain them.
3. GHRP-6, GHRP-2, ipamorelin, and ibutamoren mentions are for class literacy only. No ranking and no "better than".
