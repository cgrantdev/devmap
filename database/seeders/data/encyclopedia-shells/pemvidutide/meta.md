# SEO Meta Package: Pemvidutide

**Site:** https://peptidemap.com  
**Target URL:** `https://peptidemap.com/encyclopedia/pemvidutide` (new page)  
**Page type:** single  
**Prepared:** 2026-10-08 (Asia/Bangkok)

**Intent:** investigational GLP-1/glucagon dual agonist encyclopedia entry. Liver/MASH-first, with a trial timeline and status explainer. Not buying intent, not dosing.

---
## 1. Recommended slug

**Recommended:** `pemvidutide`

**Why:** INN is lowercase. The task specifies `pemvidutide`. Live check on 8 Oct 2026: `/encyclopedia/pemvidutide` **404**, `/encyclopedia/Pemvidutide` **404**, `/compare/pemvidutide` **404**. No existing row to match casing against.

**Canonical:** `https://peptidemap.com/encyclopedia/pemvidutide`

---
## 2. SEO title (~60 chars)

**Recommended:** `What is Pemvidutide? GLP-1/Glucagon Agonist (MASH)`  
**Character count:** 50

**Why:** Puts the "what is" query first, names the class, and signals the liver focus. No efficacy claim.

**Runner-ups:**
1. `Pemvidutide (ALT-801): Phase 3 MASH Trial Explained` (51 chars). Covers the code-name and PERFORMA query.
2. `Pemvidutide: Investigational GLP-1/Glucagon Agonist` (51 chars). Status-first.

---
## 3. Meta description (~155 chars)

**Recommended:** `Pemvidutide (ALT-801) is an investigational GLP-1/glucagon dual agonist in Phase 3 for MASH. Not approved. Trial timeline, evidence types and status, explained.`  
**Character count:** 160

**Why:** Status and "not approved" appear within the first 100 characters. No numbers, so nothing can go stale or be misread. Fits the 160-character limit exactly.

**Shorter alternative (146):** `Pemvidutide (ALT-801) is an investigational GLP-1/glucagon dual agonist in Phase 3 for MASH. Not approved. Trial timeline and evidence, explained.`

**Runner-up (142):** `Educational overview of pemvidutide: dual GLP-1/glucagon agonism, IMPACT and MOMENTUM data, and the PERFORMA Phase 3 MASH trial. Not approved.`

---
## 4. H1

**Recommended:** `Pemvidutide`
Subtitle GLP-1/glucagon dual receptor agonist · investigational, not approved

**Why:** Matches slug, INN, and cite-us title. The subtitle line carries status.

---
## 5. Open Graph + Twitter

**og:type:** `article`  
**og:url / twitter:url:** `https://peptidemap.com/encyclopedia/pemvidutide`  
**og:site_name:** `Peptidemap`  
**og:image / twitter:image (CMS wiring):**  
- File: `pemvidutide-featured.png` (1920×1080 PNG), uploaded to `/images/encyclopedia/pemvidutide-featured.png`  
- Absolute URL: `https://peptidemap.com/images/encyclopedia/pemvidutide-featured.png`

**OG/Twitter title:** `Pemvidutide: Investigational GLP-1/Glucagon Agonist` (51)

**OG/Twitter description:** `Altimmune's GLP-1/glucagon dual agonist, in Phase 3 (PERFORMA) for MASH. Not approved. Evidence labeled peer-reviewed vs topline.`  
**Character count:** 129

**Twitter card:** `summary_large_image`

---
## 6. Keywords

**Primary:**
- pemvidutide
- ALT-801
- pemvidutide MASH

**Secondary:**
- PERFORMA trial
- IMPACT phase 2b pemvidutide
- MOMENTUM pemvidutide obesity
- GLP-1 glucagon dual agonist

**Avoid in meta:** dosing, dose amounts, "buy", "for sale", price, "approved", weight-loss percentages, "better than".

---
## 7. Internal link suggestions (verified live 8 Oct 2026; link only these)

| Path | Status | Role |
| --- | --- | --- |
| `/encyclopedia/Survodutide` | **200** | GLP-1/glucagon dual agonist sibling (investigational) |
| `/encyclopedia/Mazdutide` | **200** | GLP-1/glucagon dual agonist sibling (China-approved; not FDA) |
| `/encyclopedia/retatrutide` | **200** | Triple-agonist class contrast (investigational; Q1 2027 BLA = filing plan) |
| `/encyclopedia/semaglutide` | **200** | GLP-1 RA class literacy |
| `/encyclopedia/tirzepatide` | **200** | GIP/GLP-1 class literacy |
| `/guides/peptide-legality-fda-ruo-compounding` | **200** | Regulatory literacy |
| `/encyclopedia` | **200** | Hub |
| `/testing-labs` | **200** | Quality context (optional) |

Do not link `/compare` or shop pages from this entry.
Not live (do not link): `/compare/pemvidutide` (**404**), `/blog/peptide-legality-fda-ruo-compounding` (**404**; the guide lives under `/guides/`).
Side note: `/encyclopedia/survodutide` and `/encyclopedia/Survodutide` both return 200, each with a **self-referencing canonical** (same for mazdutide/Mazdutide). That is a duplicate-canonical issue outside this page's scope.

---
## 8. JSON-LD (FAQPage + Article)

Claims mirror the page FAQs only. No dosing, prices, or efficacy numbers.

### FAQPage
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://peptidemap.com/encyclopedia/pemvidutide#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is pemvidutide approved?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. As of 8 Oct 2026 pemvidutide is investigational: in Phase 3 (PERFORMA) for MASH and in Phase 2 for alcohol use disorder and alcohol-associated liver disease. FDA Fast Track and Breakthrough Therapy designations are not approvals."
      }
    },
    {
      "@type": "Question",
      "name": "What is the PERFORMA trial?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "PERFORMA (NCT07795164) is Altimmune's registrational Phase 3, placebo-controlled trial in non-cirrhotic F2–F3 MASH. Altimmune announced enrollment had begun on 3 Aug 2026, and ClinicalTrials.gov lists a start date of 30 Jul 2026. It combines 52-week liver-biopsy endpoints with clinical outcomes at about 60 months. The company anticipates the 52-week readout in 2029."
      }
    },
    {
      "@type": "Question",
      "name": "How does pemvidutide differ from survodutide, mazdutide, and retatrutide?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Pemvidutide, survodutide, and mazdutide are all GLP-1/glucagon dual agonists, but they are different molecules from different sponsors with different development and regulatory status. Mazdutide is approved in China; pemvidutide and survodutide are investigational. Retatrutide is a GIP/GLP-1/glucagon triple agonist and is also investigational. Their trials enrolled different populations with different durations and endpoints, so cross-trial numbers do not show that one is better than another."
      }
    },
    {
      "@type": "Question",
      "name": "Is pemvidutide the same as the \"ALT-801\" in older cancer trials?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No. Altor BioScience used the code ALT-801 for an unrelated fusion-protein immunotherapy in oncology trials. Pemvidutide's ALT-801 is Altimmune's GLP-1/glucagon peptide."
      }
    },
    {
      "@type": "Question",
      "name": "Are the liver effects just a result of weight loss?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "This is not settled. Trial authors propose a direct liver effect through the glucagon receptor and report large liver-fat reductions alongside modest weight change in early studies. Published trials were not designed to fully separate the two effects."
      }
    },
    {
      "@type": "Question",
      "name": "Can I buy pemvidutide?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Peptidemap is a publisher, not a supplier, pharmacy, or clinic, and makes no supply, availability, vendor, or price claims about pemvidutide. It is not approved. Before any approval, the legitimate route to the investigational drug is a registered clinical trial. Any product sold online under this name is not Altimmune trial supply."
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
  "@id": "https://peptidemap.com/encyclopedia/pemvidutide#article",
  "mainEntityOfPage": { "@type": "WebPage", "@id": "https://peptidemap.com/encyclopedia/pemvidutide" },
  "headline": "Pemvidutide",
  "name": "What is Pemvidutide? GLP-1/Glucagon Agonist (MASH)",
  "description": "Pemvidutide (ALT-801) is an investigational GLP-1/glucagon dual agonist in Phase 3 for MASH. Not approved. Trial timeline, evidence types and status, explained.",
  "url": "https://peptidemap.com/encyclopedia/pemvidutide",
  "isPartOf": { "@type": "WebSite", "name": "Peptidemap", "url": "https://peptidemap.com" },
  "author": { "@type": "Organization", "name": "Peptidemap", "url": "https://peptidemap.com" },
  "publisher": { "@type": "Organization", "name": "Peptidemap", "url": "https://peptidemap.com", "logo": { "@type": "ImageObject", "url": "https://peptidemap.com/images/logo.png" } },
  "image": ["https://peptidemap.com/images/encyclopedia/pemvidutide-featured.png"],
  "datePublished": "TBD at publish",
  "dateModified": "TBD at publish",
  "inLanguage": "en-US",
  "keywords": ["pemvidutide", "ALT-801", "MASH", "GLP-1/glucagon dual agonist", "PERFORMA"],
  "about": [
    { "@type": "Thing", "name": "Pemvidutide" },
    { "@type": "Thing", "name": "Metabolic dysfunction-associated steatohepatitis" }
  ]
}
```
**Date placeholders:** `datePublished` and `dateModified` are intentionally left as placeholders. Fill both with the actual publish timestamp (ISO 8601) at publish.

Optional BreadcrumbList: Home → Encyclopedia → Pemvidutide.

**Schema notes:** Use Article + FAQPage only. Do **not** use `Drug`/`MedicalEntity` with `legalStatus` or `prescriptionStatus`, because these could imply an approved product. Do not add `Product`/`Offer` schema (no supply claims). `image` uses the featured PNG's absolute URL. The file must be uploaded to `/images/encyclopedia/` before publish.

---
## 9. Cite-us awareness

**Suggested cite title:** *Pemvidutide*  
**Cite URL:** `https://peptidemap.com/encyclopedia/pemvidutide`  
**APA (suggested):** Peptidemap. (2026). *Pemvidutide*. Peptidemap Encyclopedia. https://peptidemap.com/encyclopedia/pemvidutide  
**Chicago (suggested):** Peptidemap. "Pemvidutide." *Peptidemap Encyclopedia*. 2026. https://peptidemap.com/encyclopedia/pemvidutide.

**Snippet accuracy:** Keep "investigational / not approved" in any short blurb. Never put weight-loss percentages in a title or meta description.

---
## 10. Notes for Page Author

1. Every result in the body is labeled peer-reviewed or topline. Keep those labels if the text is edited.
2. Arms are described as lower or higher dose with no dose amounts, by design.
3. Comparator mentions are for class literacy only. No ranking and no "better than".
