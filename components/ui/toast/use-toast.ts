import { ref, computed } from 'vue'

export interface Toast {
  id: string
  title?: string
  description?: string
  variant?: 'default' | 'destructive'
  duration?: number
}

const toasts = ref<Toast[]>([])

let count = 0

export function useToast() {
  function toast(options: Omit<Toast, 'id'>) {
    const id = String(++count)
    const newToast: Toast = {
      id,
      duration: 5000,
      variant: 'default',
      ...options,
    }
    toasts.value.push(newToast)

    setTimeout(() => {
      dismiss(id)
    }, newToast.duration)

    return { id, dismiss: () => dismiss(id) }
  }

  function dismiss(id: string) {
    const idx = toasts.value.findIndex((t) => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  return {
    toasts: computed(() => toasts.value),
    toast,
    dismiss,
  }
}
