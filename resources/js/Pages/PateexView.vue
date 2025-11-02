<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import { formatTime } from "../Composables/useDateFormatter";
import LocationMap from "../Components/LocationMap.vue";
import Combobox from "../Components/fields/ComboBox.vue";
import { registrantLogsColumns } from "../Data/tableColumns";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const props = defineProps({
  registrantLogs: Array,
  registrantsMapFilter: Array,
  registrantLocations: Array,
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

// State for the user selection filter
const getInitialSelectedUser = () => {
  const urlParams = new URLSearchParams(window.location.search);
  const userIdFromUrl = urlParams.get("user");

  // Case 1: A user is present in the URL
  if (userIdFromUrl) {
    //  find the user in the list and parse the ID as a number
    const targetUserId = !isNaN(parseInt(userIdFromUrl))
      ? parseInt(userIdFromUrl)
      : userIdFromUrl;
    const userInList = props.registrantsMapFilter.find(
      (user) => user.id === targetUserId
    );

    // To be valid, the user must exist in our filter list AND have location data
    if (userInList && props.registrantLocations.length > 0) {
      return userInList; // Success! Set this user as the active one.
    }

    // If user isn't in the list or has no locations, select nothing
    return null;
  }

  // Case 2: No user in the URL (initial page load), default to "all"
  return props.registrantsMapFilter.find((user) => user.id === "all") || null;
};
// State for the user selection filter, initialized with our new logic
const selectedUser = ref(getInitialSelectedUser());
// this computed property decides IF the map should fit its bounds
const shouldFitBounds = computed(() => {
  // Only fit bounds if a specific user is selected
  return selectedUser.value && selectedUser.value.id !== "all";
});
// The data-fetching watcher logic for user selection
watch(selectedUser, (newUser) => {
  if (newUser) {
    router.get(
      route("pateex"),
      { user: newUser.id },
      {
        preserveState: true,
        preserveScroll: true,
        only: ["registrantLocations"],
      }
    );
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

    <div class="p-4 mt-7 rounded-2xl shadow-md border-4 border-green-primary-1">
      <div class="flex flex-col @lg:flex-row justify-between items-center mb-4">
        <h1
          class="text-lg @xl:text-2xl @4xl:text-3xl font-bold mx-2 mb-1.5 @lg:mb-0"
        >
          Map Coordinates
        </h1>
        <div class="w-60 @3xl:w-xs -py-2">
          <Combobox
            v-model="selectedUser"
            :options="props.registrantsMapFilter"
            placeholder="Filter by user..."
          />
        </div>
      </div>

      <LocationMap
        :locations="props.registrantLocations"
        :fit-bounds="shouldFitBounds"
      >
        <template #popup="{ location }">
          <div class="font-sans space-y-2">
            <div class="font-bold">{{ location.name }}</div>
            <hr />
            <div class="mt-3">
              <strong>Time In:</strong> {{ formatTime(location.time_in) }}
              <div><strong>Gate:</strong> {{ location.gate }}</div>
            </div>
          </div>
        </template>
      </LocationMap>
    </div>
  </div>
</template>
