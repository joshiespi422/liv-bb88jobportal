<script setup>
import { useSidebarStore } from "../Stores/sidebarStore.js";
import { useThemeStore } from "../Stores/themeStore.js";

const sidebarStore = useSidebarStore();
const themeStore = useThemeStore();
</script>

<template>
  <div
    class="h-18 flex items-center transition-all duration-300 ease-in-out relative header"
  >
    <div class="w-2/3">
      <i
        :class="[
          'pi p-2 text-3xl ml-5 cursor-pointer relative inline-block transition-all duration-300 ease-in-out hover:scale-125 gradient-text',
          {
            'pi-angle-double-right': !sidebarStore.isCollapsed,
            'pi-angle-double-left': sidebarStore.isCollapsed,
          },
        ]"
        @click="sidebarStore.toggleSidebar"
      ></i>
    </div>
    <div class="w-1/3 flex justify-end items-center">
      <button
        @click="themeStore.toggleTheme"
        class="mr-4 rounded-full bg-gradient-to-tr from-green-secondary to-green-primary-1 hover:scale-110 transition-all duration-300 ease-in-out cursor-pointer"
        aria-label="Toggle theme"
      >
        <i
          :class="[
            'pi p-2 text-xl text-white-primary',
            themeStore.theme.value === 'nord' ? 'pi-moon' : 'pi-sun',
          ]"
        ></i>
      </button>
      <i
        class="pi pi-bell p-2 text-xl mr-5 cursor-pointer text-white-primary bg-gradient-to-tr from-green-secondary to-green-primary-1 rounded-full hover:scale-110 transition-all duration-300 ease-in-out"
      >
      </i>
    </div>
  </div>
</template>

<style scoped>
.header::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background-image: linear-gradient(
    to right,
    var(--color-green-primary-1) 0%,
    var(--color-green-secondary) 70%
  );
}

.gradient-text {
  background-image: linear-gradient(
    to bottom,
    var(--color-green-primary-1) 50%,
    var(--color-green-secondary) 70%
  );
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
</style>
