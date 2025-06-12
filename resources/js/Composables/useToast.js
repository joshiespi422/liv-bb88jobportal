import { ref } from "vue";

const toasts = ref([]);

export function useToast() {
  const removeToast = (id) => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
  };

  const showToast = (message, type = "success", duration = 7000) => {
    const id = Date.now();
    const toast = { id, message, type, duration };
    toasts.value.push(toast);

    setTimeout(() => removeToast(id), duration);
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
