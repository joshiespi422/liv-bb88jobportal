import { ref } from "vue";
import { defineStore } from "pinia";

export const useSidebarStore = defineStore("sidebar", () => {
  // Use local storage to persist the state across page refreshes
  const storedState = localStorage.getItem("sidebarCollapsed");

  // Initialize with stored value or default to false (expanded)
  const isCollapsed = ref(storedState ? JSON.parse(storedState) : false);

  function toggleSidebar() {
    isCollapsed.value = !isCollapsed.value;
    // Save to localStorage to persist across page refreshes
    localStorage.setItem("sidebarCollapsed", JSON.stringify(isCollapsed.value));
  }

  return { isCollapsed, toggleSidebar };
});
