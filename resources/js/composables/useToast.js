import { useToast as useVueToastification } from 'vue-toastification'
import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'

/**
 * Composable for toast notifications
 * Automatically shows toasts from Laravel flash messages
 */
export function useToast() {
  const page = usePage()
  const toast = useVueToastification()

  // Watch for flash messages and show toasts
  watch(
    () => page.props.flash,
    (flash) => {
      if (flash?.success) {
        toast.success(flash.success, {
          timeout: 3000,
        })
      }
      if (flash?.error) {
        toast.error(flash.error, {
          timeout: 4000,
        })
      }
      if (flash?.info) {
        toast.info(flash.info, {
          timeout: 3000,
        })
      }
      if (flash?.warning) {
        toast.warning(flash.warning, {
          timeout: 3000,
        })
      }
    },
    { deep: true, immediate: true }
  )

  return {
    toast,
    success: (message, options = {}) => toast.success(message, { timeout: 3000, ...options }),
    error: (message, options = {}) => toast.error(message, { timeout: 4000, ...options }),
    info: (message, options = {}) => toast.info(message, { timeout: 3000, ...options }),
    warning: (message, options = {}) => toast.warning(message, { timeout: 3000, ...options }),
  }
}

