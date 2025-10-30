<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import { formatDate, formatTime } from "../Composables/useDateFormatter";
import LocationMap from "../Components/LocationMap.vue";
import { registrantLogsColumns } from "../Data/tableColumns";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const props = defineProps({
  registrantLogs: Array,
});

// Auto-refresh logic
let refreshInterval = null;
let activityTimeout = null;
const userIsActive = ref(true);

// Marks user as active and resets the inactivity timer
const handleUserActivity = () => {
  userIsActive.value = true;
  clearTimeout(activityTimeout);
  activityTimeout = setTimeout(() => {
    userIsActive.value = false; // set false after 3 minutes of inactivity
  }, 3 * 60 * 1000);
};

// Fetches fresh data using an efficient partial reload
const refreshRegistrantLogs = () => {
  if (authUser.value?.userType === "super_admin" && userIsActive.value) {
    router.reload({
      only: ["registrantLogs"], // IMPORTANT: Only fetch the 'registrantLogs' prop
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => console.log("Refreshed online users."),
    });
  }
};

// Set up listeners and intervals when the component is mounted
onMounted(() => {
  if (authUser.value?.userType === "super_admin") {
    window.addEventListener("mousemove", handleUserActivity);
    window.addEventListener("keydown", handleUserActivity);
    handleUserActivity(); // Initial call
    refreshInterval = setInterval(refreshRegistrantLogs, 2 * 60 * 1000); // Refresh every 2 minutes
  }
});

// Clean up listeners and intervals to prevent memory leaks
onUnmounted(() => {
  if (authUser.value?.userType === "super_admin") {
    clearInterval(refreshInterval);
    clearTimeout(activityTimeout);
    window.removeEventListener("mousemove", handleUserActivity);
    window.removeEventListener("keydown", handleUserActivity);
  }
});
</script>

<template>
  <Head title="Pateex" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <h1
      class="text-lg @sm:text-2xl @4xl:text-3xl font-bold text-center @2xl:text-start mb-3 @2xl:mb-5"
    >
      Pateex Logs
    </h1>

    <DataTable
      :data="props.registrantLogs"
      :columns="registrantLogsColumns"
      :enable-view-toggle="true"
    />
  </div>
</template>
