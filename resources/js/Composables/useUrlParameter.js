import { onMounted } from "vue";

export function useUrlParameter() {
  // Get URL parameter value
  const getUrlParameter = (name) => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
  };

  // Remove URL parameter
  const removeUrlParameter = (name) => {
    const url = new URL(window.location);
    url.searchParams.delete(name);
    window.history.replaceState({}, "", url.pathname + url.search);
  };

  // Handle parameter with automatic removal
  const handleParameter = async (name, callback) => {
    const value = getUrlParameter(name);
    if (value) {
      // Remove immediately for normal cases
      removeUrlParameter(name);

      try {
        await callback(value);
      } finally {
        // Check if parameter still exists (manual reload case)
        const paramStillExists = getUrlParameter(name);
        if (paramStillExists) {
          removeUrlParameter(name);
        }
      }
    }
  };

  // Auto-trigger handler on component mount
  const onMountedHandleParameter = (name, callback) => {
    onMounted(() => handleParameter(name, callback));
  };

  return {
    getUrlParameter,
    removeUrlParameter,
    handleParameter,
    onMountedHandleParameter,
  };
}
