import { ref } from 'vue'

// True while the homepage's own search box is on screen (not hidden under
// the sticky header). The header hides its duplicate search box only then,
// so search stays one click away once the page scrolls. Starts true so the
// homepage's first paint doesn't flash the header box before the observer
// reports; WelcomeV2 resets it when it unmounts.
export const homeSearchInView = ref(true)
