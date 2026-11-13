// Shared money formatting for price components. Prices stay in the
// vendor's own currency; symbols that end in a letter (CHF, lei, Kč)
// get a space so "CHF 12.00" never reads as "CHF12.00".

export function moneyPrefix(symbol) {
  const s = symbol || '$'
  return /[A-Za-zÀ-ɏЀ-ӿ]$/.test(s) ? s + ' ' : s
}

export function formatMoney(amount, symbol = '$', digits = 2) {
  const n = Number(amount)
  if (!Number.isFinite(n)) return null
  return moneyPrefix(symbol) + n.toLocaleString('en-US', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  })
}

// Price per mg: two decimals under 100, whole numbers above (HUF, JPY
// and KRW per-mg figures run into the hundreds or thousands).
export function formatPerMg(value, symbol = '$') {
  const n = Number(value)
  if (value === null || value === undefined || value === '' || !Number.isFinite(n) || n <= 0) return null
  return formatMoney(n, symbol, n >= 100 ? 0 : 2)
}

// "10mg", "5mg/5mg" and "500mcg" are stored with units; a bare legacy
// number is milligrams.
export function formatSizeLabel(size) {
  if (size === null || size === undefined) return null
  const s = String(size).trim()
  if (!s) return null
  if (/^[0-9.]+$/.test(s)) {
    const n = parseFloat(s)
    if (!Number.isFinite(n)) return s
    return n >= 1000 ? `${n / 1000}g` : `${n}mg`
  }
  return s
}

// Size grouping for filter chips and tabs. "10 mg", "10mg", "10MG" and a
// bare legacy "10" are one size; "500mcg" and "0.5mg" are one size.
// rank orders single amounts (by mg) before blends, then other units.
const SIZE_UNIT_MG = { mcg: 0.001, ug: 0.001, mg: 1, g: 1000 }
export function sizeInfo(raw) {
  const compact = String(raw ?? '').toLowerCase().replace(/\s+/g, '')
  const single = compact.match(/^([0-9]+(?:\.[0-9]+)?)(mcg|ug|mg|g)?$/)
  if (single) {
    const mg = parseFloat(single[1]) * SIZE_UNIT_MG[single[2] || 'mg']
    return { key: `mg:${Math.round(mg * 1000) / 1000}`, rank: 0, mg }
  }
  const lead = parseFloat(compact)
  return { key: `raw:${compact}`, rank: compact.includes('/') ? 1 : 2, mg: Number.isFinite(lead) ? lead : Infinity }
}

export function compareSizeInfo(a, b) {
  return a.rank - b.rank || a.mg - b.mg || String(a.key).localeCompare(String(b.key))
}

// Pack size from the vendor's own product name: "10 vials", "10 x 10mg",
// "10mg x 10", "5-pack", "60 ct", "Kit". Null for a single unit or when
// the name is unclear. "BPC-157 x 2" style names need a unit next to the
// multiplier so the compound number is never read as a count.
export function packLabel(rawName) {
  const name = String(rawName || '')
  if (!name) return null
  const count = (m) => (m && Number(m[1]) > 1 ? Number(m[1]) : null)

  let n = count(name.match(/\b(\d{1,3})\s*vials\b/i))
  if (n) return `${n} vials`
  n = count(name.match(/\b(\d{1,3})\s*[x×]\s*\d+(?:\.\d+)?\s*(?:mg|mcg|ug|g|ml|iu)\b/i))
    || count(name.match(/\d\s*(?:mg|mcg|ug|g|ml|iu)\s*[x×]\s*(\d{1,3})\b(?!\s*(?:\.\d|mg|mcg|ug|g|ml|iu))/i))
  if (n) return `${n}-pack`
  n = count(name.match(/\bpack\s+of\s+(\d{1,3})\b/i)) || count(name.match(/\b(\d{1,3})\s*-?\s*(?:pack|pk)\b/i))
  if (n) return `${n}-pack`
  n = count(name.match(/\b(\d{1,4})\s*(?:ct|count|caps|capsules|tabs|tablets|softgels)\b/i))
  if (n) return `${n} ct`
  if (/\bkit\b/i.test(name)) return 'Kit'
  return null
}
