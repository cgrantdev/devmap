import {
    ref
} from 'vue'

// Global shared refs so all admin pages share the same loading state
const isLoading = ref(false)
const loadingMessage = ref('Processing, please wait...')

export function useAdminLoading() {
    const setLoading = (value, message = 'Processing, please wait...') => {
        isLoading.value = value
        loadingMessage.value = message
    }

    return {
        isLoading,
        loadingMessage,
        setLoading,
    }
}

// Kept for compatibility; now simply returns the shared refs
export function provideAdminLoading() {
    return useAdminLoading()
}