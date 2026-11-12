// Strip the trailing " - Peptidemap" / " | Peptidemap" suffix from a
// title. Mirrors the server-side logic in app.blade.php so the browser
// tab stays consistent after Vue hydrates and overwrites the SSR title.
export function stripSiteSuffix(title, siteName) {
  if (!title) return title
  siteName = siteName || 'Peptidemap'
  const str = String(title)
  if (str === siteName) return str
  const escaped = siteName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const re = new RegExp('\\s*[\\-|]\\s*' + escaped + '\\s*$')
  const stripped = str.replace(re, '').trim()
  return stripped === '' ? str : stripped
}
