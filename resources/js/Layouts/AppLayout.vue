<script setup>
import Sidebar from "../Components/Sidebar.vue";
import Header from "../Components/Header.vue";
import ToastContainer from "../Components/toast/ToastContainer.vue";
import { useSidebarStore } from "../Stores/sidebarStore.js";
import { useToast } from "../Composables/useToast";
import { usePage } from "@inertiajs/vue3";
import { watch } from "vue";

// for sidebar state
const sidebarStore = useSidebarStore();

// for toast messages
const page = usePage();
const toast = useToast();
watch(
  () => page.props.flash,
  (flash) => {
    if (flash.success) toast.success(flash.success);
  },
  { immediate: true }
);
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
