import { ref, onMounted } from "vue";

export const useThemeStore = () => {
  const theme = ref(localStorage.getItem("theme") || "nord");

  const setTheme = (newTheme) => {
    theme.value = newTheme;
    localStorage.setItem("theme", newTheme);
    document.documentElement.setAttribute("data-theme", newTheme);
  };

  const toggleTheme = () => {
    const newTheme = theme.value === "nord" ? "dracula" : "nord";
    setTheme(newTheme);
  };

  // Initialize theme on component mount
  onMounted(() => {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
      setTheme(savedTheme);
    } else {
      // Check for prefers-color-scheme if no theme is saved
      const prefersDark =
        window.matchMedia &&
        window.matchMedia("(prefers-color-scheme: dark)").matches;
      if (prefersDark) {
        setTheme("dracula");
      } else {
        setTheme("nord");
      }
    }
  });

  return {
    theme,
    toggleTheme,
  };
};
