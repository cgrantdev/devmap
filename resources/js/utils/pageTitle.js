// Strip a trailing " - Peptidemap" / " — Peptidemap" / " | Peptidemap"
// from a page title. Mirrors the server-side strip in app.blade.php so the
// browser tab stays clean after Vue hydrates and rewrites the title.
// Loops so doubled tails like "Foo | Peptidemap - Peptidemap" fully clear.
const SEPARATORS = '\\-\\u2013\\u2014|:'

export function stripSiteSuffix(title, siteName) {
  if (!title) return title
  const name = siteName || 'Peptidemap'
  const original = String(title)
  if (original.trim().toLowerCase() === name.toLowerCase()) return original
  const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const re = new RegExp('\\s*[' + SEPARATORS + ']\\s*' + escaped + '\\s*$', 'i')
  let out = original
  for (let i = 0; i < 3; i++) {
    const next = out.replace(re, '').trim()
    if (next === out) break
    out = next
  }
  return out === '' ? original : out
}
