import { ref } from "vue";

const toasts = ref([]);

export function useToast() {
  const showToast = (message, type = "success", duration = 5000) => {
    const id = Date.now();
    toasts.value.push({ id, message, type, duration });

    // Auto-remove after animation completes
    setTimeout(() => {
      toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }, duration + 500);
  };

  return {
    toasts,
    showToast,
    success: (message, duration) => showToast(message, "success", duration),
    error: (message, duration) => showToast(message, "error", duration),
    info: (message, duration) => showToast(message, "info", duration),
    warning: (message, duration) => showToast(message, "warning", duration),
  };
}
