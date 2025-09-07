<script setup>
import Sidebar from "../Components/Sidebar.vue";
import Header from "../Components/Header.vue";
import ToastContainer from "../Components/toast/ToastContainer.vue";
import { useSidebarStore } from "../Stores/sidebarStore.js";
import { useNotificationStore } from "../Stores/notificationStore.js";
import { useToast } from "../Composables/useToast";
import { useMediaQuery } from "../Composables/useMediaQuery";
import { usePage } from "@inertiajs/vue3";
import { watch, computed, onMounted } from "vue";

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

const notificationStore = useNotificationStore();
onMounted(() => {
  notificationStore.fetchNotifications();
});

// --- Logic for Overlay ---
const isLgScreen = useMediaQuery("(min-width: 1024px)");
// computed property to decide when to show the overlay
const showOverlay = computed(() => {
  return !sidebarStore.isCollapsed && !isLgScreen.value;
});
</script>

<template>
  <div class="relative min-h-screen">
    <div
      v-if="showOverlay"
      @click="sidebarStore.toggleSidebar"
      class="fixed inset-0 z-30 bg-black/50 transition-opacity duration-300 lg:hidden"
    ></div>
    <Sidebar />

    <div
      class="grid h-screen grid-rows-[auto_1fr] overflow-hidden transition-all duration-300 ease-in-out"
    >
      <Header
        :class="{
          'ml-[250px] md:ml-[288px]': !sidebarStore.isCollapsed,
          'ml-0 sm:ml-[80px]': sidebarStore.isCollapsed,
        }"
      />
      <main
        class="pl-0 sm:pl-[80px] overflow-auto transition-all duration-300 ease-in-out"
        :class="{
          'ml-0 lg:ml-[210px]': !sidebarStore.isCollapsed,
          'ml-0': sidebarStore.isCollapsed,
        }"
      >
        <slot />
        <ToastContainer />
      </main>
    </div>
  </div>
</template>
