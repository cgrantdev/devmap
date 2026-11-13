import { onBeforeUnmount, ref } from 'vue'

// Copy a string to the clipboard, with a textarea fallback for older Safari
// and non-HTTPS contexts. `copied` flips true for `resetMs` after a
// successful copy so buttons can show a "Copied" state.
export function useCopyText(resetMs = 1800) {
  const copied = ref(false)
  let timer = null

  async function copy(text) {
    const value = String(text || '').trim()
    if (!value) return false
    let ok = false
    try {
      await navigator.clipboard.writeText(value)
      ok = true
    } catch {
      try {
        const ta = document.createElement('textarea')
        ta.value = value
        ta.setAttribute('readonly', '')
        ta.style.position = 'fixed'
        ta.style.opacity = '0'
        document.body.appendChild(ta)
        ta.select()
        ok = document.execCommand('copy')
        document.body.removeChild(ta)
      } catch {
        ok = false
      }
    }
    if (ok) {
      copied.value = true
      clearTimeout(timer)
      timer = setTimeout(() => { copied.value = false }, resetMs)
    }
    return ok
  }

  onBeforeUnmount(() => clearTimeout(timer))

  return { copied, copy }
}
