import { ref } from "vue";

/**
 * A composable to manage the state and logic for a details modal.
 * @param {object} config - Configuration for the modal.
 * @param {string} [config.baseUrl] - A simple base URL for GET requests. Handles multiple args by joining with '/'.
 * @param {Function} [config.fetcher] - A custom async function to fetch data. Receives all arguments from `open()`.
 */
export function useDetailsModal({ baseUrl, fetcher }) {
  const isOpen = ref(false);
  const isLoading = ref(false);
  const isError = ref(false);
  const data = ref(null);

  // Define the actual fetch function based on provided config
  const fetchData = async (...args) => {
    if (fetcher) {
      return fetcher(...args);
    }
    if (baseUrl) {
      // A simple convention for baseUrl: join args with a slash.
      const endpoint = [baseUrl, ...args].join("/");
      return axios.get(endpoint);
    }
    throw new Error(
      "useDetailsModal requires a 'baseUrl' or a 'fetcher' function."
    );
  };

  // Accept multiple arguments using the rest parameter syntax
  const open = async (...args) => {
    // The last argument can optionally be a callback function.
    let onOpenCallback = null;
    if (typeof args[args.length - 1] === "function") {
      onOpenCallback = args.pop();
    }

    isLoading.value = true;
    isOpen.value = true;
    data.value = null;
    isError.value = false;

    if (onOpenCallback) {
      onOpenCallback();
    }

    try {
      // Pass all arguments along to the fetcher
      const response = await fetchData(...args);
      data.value = response.data;
    } catch (error) {
      console.error("Error fetching details:", error);
      isError.value = true;
    } finally {
      // await new Promise((resolve) => setTimeout(resolve, 500));
      isLoading.value = false;
    }
  };

  const close = () => {
    isOpen.value = false;
  };

  return {
    isOpen,
    isLoading,
    isError,
    data,
    open,
    close,
  };
}
