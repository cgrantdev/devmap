// Strip the trailing " — Peptidemap" / " - Peptidemap" / " | Peptidemap"
// suffix from a title. Mirrors the server-side logic in app.blade.php so
// the browser tab, OG title, and <Head> block stay consistent after
// Vue hydrates and overwrites the SSR <title>.
//
// Jake (Advantage Collective, 2026-11-06): the " - Peptidemap" tail ate
// SERP real estate without adding search value, and agency-authored
// titles already carry whatever brand reference they want. The homepage
// is the one exception where we keep the bare site name.
export function stripSiteSuffix(title, siteName = 'Peptidemap') {
  if (!title) return title
  const str = String(title)
  if (str === siteName) return str
  const escaped = siteName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const re = new RegExp('\\s*[—\\-|]\\s*' + escaped + '\\s*$', 'u')
  const stripped = str.replace(re, '').trim()
  return stripped === '' ? str : stripped
}
