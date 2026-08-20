// Small helper to stamp the current page path onto outbound /go/ links.
//
// Our Buy buttons use rel="noopener noreferrer nofollow sponsored" for
// privacy + SEO hygiene; the noreferrer bit strips the Referer header,
// so /go/{product} can't tell which internal page emitted the click.
// This composable attaches ?src={pathname}, which the controller reads
// and stores in place of the missing header. Analytics can then answer
// "which internal pages actually drive affiliate clicks?"
//
// Falls back to the raw URL server-side / SSR where window is absent.

export function useOutbound() {
  return { withSrc }
}

export function withSrc(url) {
  if (!url || typeof window === 'undefined') return url
  try {
    const src = window.location.pathname || '/'
    const sep = url.includes('?') ? '&' : '?'
    return `${url}${sep}src=${encodeURIComponent(src)}`
  } catch {
    return url
  }
}
