const fs = require('fs')
const path = require('path')
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel,
  AlignmentType, PageOrientation, LevelFormat, BorderStyle,
  Table, TableRow, TableCell, WidthType, ShadingType, PageBreak,
} = require('docx')

// ----- Helpers --------------------------------------------------------------

const black = '0A0B0E'
const accent = '4338CA'
const muted = '6B7280'
const greenBg = 'ECFDF5'
const greenInk = '065F46'

const para = (text, opts = {}) => new Paragraph({
  spacing: { after: 100, ...opts.spacing },
  alignment: opts.alignment,
  ...opts,
  children: opts.children || [new TextRun({ text, ...(opts.run || {}) })],
})

const bullet = (children) => new Paragraph({
  numbering: { reference: 'bullets', level: 0 },
  spacing: { after: 80 },
  children: Array.isArray(children) ? children : [new TextRun(children)],
})

const numbered = (children) => new Paragraph({
  numbering: { reference: 'numbers', level: 0 },
  spacing: { after: 80 },
  children: Array.isArray(children) ? children : [new TextRun(children)],
})

const h1 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_1,
  spacing: { before: 360, after: 180 },
  children: [new TextRun({ text, bold: true, color: black })],
})

const h2 = (text) => new Paragraph({
  heading: HeadingLevel.HEADING_2,
  spacing: { before: 280, after: 120 },
  children: [new TextRun({ text, bold: true, color: black })],
})

const label = (text, value) => new Paragraph({
  spacing: { after: 60 },
  children: [
    new TextRun({ text: text + ' ', bold: true, color: black }),
    new TextRun({ text: value, color: muted }),
  ],
})

// Renders a "quoted message" block: thin left border, slight indent, monospace-ish feel.
const messageBlock = (lines) => {
  return lines.map((line, i) => {
    if (line === '') {
      return new Paragraph({
        spacing: { after: 60 },
        indent: { left: 360 },
        border: { left: { style: BorderStyle.SINGLE, size: 12, color: accent, space: 12 } },
        children: [new TextRun({ text: ' ' })],
      })
    }
    // Detect bold prefix like **Steps:** at the start
    const boldMatch = line.match(/^\*\*(.+?)\*\*(.*)$/)
    if (boldMatch) {
      return new Paragraph({
        spacing: { after: 60 },
        indent: { left: 360 },
        border: { left: { style: BorderStyle.SINGLE, size: 12, color: accent, space: 12 } },
        children: [
          new TextRun({ text: boldMatch[1], bold: true, color: black }),
          new TextRun({ text: boldMatch[2], color: black }),
        ],
      })
    }
    return new Paragraph({
      spacing: { after: 60 },
      indent: { left: 360 },
      border: { left: { style: BorderStyle.SINGLE, size: 12, color: accent, space: 12 } },
      children: [new TextRun({ text: line, color: black })],
    })
  })
}

// "What you do" pill — green tinted single-cell table
const youDoBox = (paragraphs) => new Table({
  width: { size: 9360, type: WidthType.DXA },
  columnWidths: [9360],
  rows: [
    new TableRow({
      children: [
        new TableCell({
          width: { size: 9360, type: WidthType.DXA },
          shading: { fill: greenBg, type: ShadingType.CLEAR },
          margins: { top: 140, bottom: 140, left: 200, right: 200 },
          borders: {
            top: { style: BorderStyle.SINGLE, size: 1, color: 'A7F3D0' },
            bottom: { style: BorderStyle.SINGLE, size: 1, color: 'A7F3D0' },
            left: { style: BorderStyle.SINGLE, size: 12, color: '10B981' },
            right: { style: BorderStyle.SINGLE, size: 1, color: 'A7F3D0' },
          },
          children: paragraphs,
        }),
      ],
    }),
  ],
})

// ----- Vendor data ---------------------------------------------------------

const vendors = [
  {
    num: 1,
    name: 'Nura Systems LLC',
    status: 'Char-limit blocker fixed. Need them to retry + clarify the import error.',
    youDo: 'Send the message below.',
    message: [
      'Hi — just deployed a fix on our end for the "2000 characters" error you were hitting. Please try saving your profile again, it should go through now.',
      '',
      'For the separate "server error during product import" — could you let us know where you were importing from (a URL, file, or copy/paste), and the exact wording of the error? Once we have that we can fix it right away.',
    ],
  },
  {
    num: 2,
    name: 'Peptiva (Medusa.js)',
    status: 'Integration code is built. Need their backend URL.',
    youDo: 'Send the message below.',
    message: [
      'Hi — good news, we built out Medusa.js support on our end. To finish your integration, we just need two quick details:',
      '',
      '**1.** Your Medusa backend URL — the API host (e.g. https://api.peptiva.com)',
      '**2.** Your default USD region ID (optional) — Medusa often requires a region ID to return prices. If you only have one region, no need to send anything.',
      '',
      'No API keys needed. Once we have the URL, we’ll wire up syncing same day.',
    ],
  },
  {
    num: 3,
    name: 'Research Chem (Medusa.js)',
    status: 'Same as Peptiva. Also — they don’t appear to have completed signup yet on our side.',
    youDo: 'Send the message below.',
    message: [
      'Hi — we built Medusa.js support and we’re ready to integrate Research Chem. Two things:',
      '',
      '**1.** Could you confirm you’ve completed the vendor onboarding form at https://join.peptidemap.com? We don’t see a Research Chem account on our side yet — let us know if you hit any issues.',
      '**2.** Once onboarding is done, please send your Medusa backend URL (e.g. https://api.researchchem.com) and your default USD region ID if applicable.',
      '',
      'No API keys needed — Medusa’s public Store API gives us everything.',
    ],
  },
  {
    num: 4,
    name: 'Mr. Peps Labs LTD',
    status: 'DONE — 21 products ingested and sitting in staging.',
    youDo: '1) Open /admin/staged-products. 2) Filter by vendor "Mr. Peps Labs LTD". 3) Approve the 21 staged products. Then send the message below.',
    message: [
      'Hi — your products are ingesting cleanly. We’ve imported 21 products from your catalog and they’re going live now.',
      '',
      'Going forward we’ll auto-refresh from your endpoint daily so any price or stock changes on your side update automatically on PeptideMap. Nothing more needed from you — welcome aboard!',
    ],
  },
  {
    num: 5,
    name: 'Bioinfinity Lab',
    status: 'Declined API. We have a fallback. Need to confirm interest + their storefront URL.',
    youDo: 'Send the message below.',
    message: [
      'Hi — totally understand on the API concerns. We have a fallback that doesn’t require any API access on your end at all:',
      '',
      'We crawl your public storefront on a schedule (the same pages your customers visit) and pull product names, prices, and images automatically. Nothing private is touched, no credentials needed, and you can turn it off at any time.',
      '',
      'If that sounds good, please:',
      '**1.** Complete the onboarding form at https://join.peptidemap.com (skip the API keys section)',
      '**2.** Reply with the URL of your products page (e.g. https://yourstore.com/products or /shop)',
      '',
      'We’ll handle the rest.',
    ],
  },
  {
    num: 6,
    name: 'Solution Peptides',
    status: 'Not technical. Needs reassurance + a link.',
    youDo: 'Send the message below.',
    message: [
      'Hi — happy to clarify how the platform works before you finish onboarding:',
      '',
      '**Live demo:** https://demo.peptidemap.com — fully populated with fictional vendors so you can see exactly what your listing will look like.',
      '**Support:** we currently handle everything by email at support@peptidemap.com — fast turnaround on any technical or onboarding questions.',
      '**Integration options once you’re in:** WooCommerce REST API, public JSON feed, Medusa.js direct, BigCommerce direct, or a public-pages scraper fallback.',
      '',
      'Let me know if anything else would help before you finish the form at https://join.peptidemap.com.',
    ],
  },
]

const limitlessMessage = [
  'Hi,',
  '',
  'To integrate Limitless Life with PeptideMap, we just need one quick set of credentials from your BigCommerce admin (takes about 3 minutes — read-only access only):',
  '',
  '**Steps:**',
  '**1.** Log into your BigCommerce control panel',
  '**2.** Go to Settings → API → Store-level API accounts',
  '**3.** Click Create API account',
  '**4.** Token type: V2/V3 API token',
  '**5.** Name: PeptideMap',
  '**6.** Under OAuth scopes, set Products → read-only (that’s the only permission needed)',
  '**7.** Click Save',
  '',
  'BigCommerce will display the Access Token, Client ID, and API Path on screen one time only — please copy all three and reply with them here.',
  '',
  'Once we have those, we’ll have your catalog syncing within a few hours.',
  '',
  'Thanks!',
]

// ----- Document ------------------------------------------------------------

const sections = [
  // ---- Cover header ----
  new Paragraph({
    spacing: { after: 80 },
    children: [new TextRun({
      text: 'PEPTIDEMAP',
      bold: true,
      color: accent,
      size: 18,
      characterSpacing: 80,
    })],
  }),
  new Paragraph({
    spacing: { after: 60 },
    children: [new TextRun({ text: 'Vendor Outreach — Action List', bold: true, size: 40, color: black })],
  }),
  new Paragraph({
    spacing: { after: 360 },
    children: [new TextRun({
      text: 'Generated for Julia · Six pending vendors + Limitless Life (BigCommerce)',
      color: muted,
      size: 22,
      italics: true,
    })],
  }),

  // ---- Summary table of contents ----
  h2('Quick summary'),
  para('Each vendor below has three pieces: current status, the action you take, and (where applicable) a ready-to-send message in a green box. Engineering work for all six is already complete on our side — everything from here is messages and waiting on replies.', { run: { color: black } }),
]

// Vendor sections
vendors.forEach((v) => {
  sections.push(h2(`${v.num}. ${v.name}`))
  sections.push(label('Status:', v.status))
  sections.push(label('What you do:', v.youDo))
  sections.push(new Paragraph({ spacing: { before: 80, after: 60 }, children: [new TextRun({ text: 'Message to send:', bold: true, color: black })] }))
  // Message block
  messageBlock(v.message).forEach((p) => sections.push(p))
  // small spacer
  sections.push(new Paragraph({ spacing: { after: 200 }, children: [new TextRun(' ')] }))
})

// Limitless Life (BigCommerce) section
sections.push(h2('7. Limitless Life Nootropics (BigCommerce)'))
sections.push(label('Status:', 'Confirmed on BigCommerce (store hash: abfevmkahe). Need a read-only V3 API token from them.'))
sections.push(label('What you do:', 'Send the message below.'))
sections.push(new Paragraph({ spacing: { before: 80, after: 60 }, children: [new TextRun({ text: 'Message to send:', bold: true, color: black })] }))
messageBlock(limitlessMessage).forEach((p) => sections.push(p))

// Final action checklist
sections.push(h2('Your checklist'))
sections.push(numbered('Send the messages above to all 7 vendors (~5 minutes total)'))
sections.push(numbered('Log into /admin/staged-products and approve Mr. Peps Labs LTD’s 21 staged products — they go live immediately'))
sections.push(numbered('Wait for replies. As Medusa URLs and the BigCommerce token come in, paste them into Slack and engineering can wire each up same day'))

// Build the doc
const doc = new Document({
  creator: 'PeptideMap',
  title: 'Vendor Outreach Action List',
  styles: {
    default: { document: { run: { font: 'Arial', size: 22 } } },
    paragraphStyles: [
      {
        id: 'Heading1',
        name: 'Heading 1',
        basedOn: 'Normal',
        next: 'Normal',
        quickFormat: true,
        run: { size: 36, bold: true, font: 'Arial', color: black },
        paragraph: { spacing: { before: 360, after: 180 }, outlineLevel: 0 },
      },
      {
        id: 'Heading2',
        name: 'Heading 2',
        basedOn: 'Normal',
        next: 'Normal',
        quickFormat: true,
        run: { size: 28, bold: true, font: 'Arial', color: black },
        paragraph: { spacing: { before: 280, after: 120 }, outlineLevel: 1 },
      },
    ],
  },
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [{
          level: 0,
          format: LevelFormat.BULLET,
          text: '•',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } },
        }],
      },
      {
        reference: 'numbers',
        levels: [{
          level: 0,
          format: LevelFormat.DECIMAL,
          text: '%1.',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } },
        }],
      },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1080, right: 1080, bottom: 1080, left: 1080 },
      },
    },
    children: sections,
  }],
})

Packer.toBuffer(doc).then((buf) => {
  const outPath = path.resolve(process.argv[2] || 'vendor_outreach.docx')
  fs.writeFileSync(outPath, buf)
  console.log('Wrote:', outPath, '(' + buf.length + ' bytes)')
})
