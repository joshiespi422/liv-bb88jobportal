<script setup>
import Sidebar from "../Components/Sidebar.vue";
import Header from "../Components/Header.vue";
import ToastContainer from "../Components/toast/ToastContainer.vue";

import { useSidebarStore } from "../Stores/sidebarStore.js";

const sidebarStore = useSidebarStore();
</script>

<template>
  <div
    class="grid h-screen transition-all duration-300 ease-in-out"
    :style="{
      gridTemplateColumns: sidebarStore.isCollapsed ? '80px 1fr' : '288px 1fr',
    }"
  >
    <Sidebar />
    <div class="grid grid-rows-[auto_1fr] overflow-hidden">
      <Header />
      <!-- Dynamic content area -->
      <main class="overflow-auto">
        <slot />
        <ToastContainer />
      </main>
    </div>
  </div>
</template>
