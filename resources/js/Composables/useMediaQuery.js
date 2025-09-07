import { ref, onMounted, onUnmounted } from "vue";

/**
 * A Vue composable that tracks whether a CSS media query matches.
 * @param {string} query - The media query string to watch (e.g., '(min-width: 1024px)').
 * @returns {ref<boolean>} A ref that is true when the media query matches, and false otherwise.
 */
export function useMediaQuery(query) {
  // state
  const isMatch = ref(false);
  // store the MediaQueryList object here.
  let mediaQuery;
  // handler to update state
  const updateMatch = (event) => {
    isMatch.value = event.matches;
  };

  onMounted(() => {
    // Ensure this only runs in the browser.
    if (typeof window !== "undefined") {
      // Create the MediaQueryList object.
      mediaQuery = window.matchMedia(query);
      // Set the initial value.
      isMatch.value = mediaQuery.matches;
      // Add the listener for changes.
      mediaQuery.addEventListener("change", updateMatch);
    }
  });

  onUnmounted(() => {
    // Clean up the listener to prevent memory leaks.
    if (mediaQuery) {
      mediaQuery.removeEventListener("change", updateMatch);
    }
  });

  // Return the reactive state for the component to use.
  return isMatch;
}
