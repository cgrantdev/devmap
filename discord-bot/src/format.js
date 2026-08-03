// Shared formatting helpers. Discord embed limits — keep in mind:
//   title ≤256, description ≤4096, field.name ≤256, field.value ≤1024,
//   ≤25 fields per embed, ≤6000 chars per embed total.

export const BRAND_COLOR = 0x4338CA // matches site's primary indigo

export function money(n) {
  if (n === null || n === undefined) return '—'
  const num = Number(n)
  if (!Number.isFinite(num) || num <= 0) return '—'
  return '$' + num.toFixed(2)
}

export function truncate(s, max) {
  if (!s) return ''
  return s.length <= max ? s : s.slice(0, max - 1) + '…'
}

// Discord's message content also caps at 2000 chars — used for command
// responses that go over as plain text instead of embeds.
export function truncMsg(s) {
  return truncate(s, 1990)
}

export function priceLine(p) {
  if (p.discount_pct && p.discount_pct > 0) {
    return `~~${money(p.price)}~~ **${money(p.effective_price)}** (\`-${p.discount_pct}%\`)`
  }
  return `**${money(p.effective_price)}**`
}
