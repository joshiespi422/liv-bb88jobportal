<script setup>
import { computed, h, onMounted, onUnmounted, ref, watch, reactive } from "vue";
import { usePage, router, useForm } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import {
  formatDate,
  formatTime,
  shortDate,
} from "../Composables/useDateFormatter";
import LocationMap from "../Components/LocationMap.vue";
import Combobox from "../Components/forms/ComboBox.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import { useToast } from "../Composables/useToast";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const { error } = useToast();

const props = defineProps({
  totalCounts: {
    type: Object,
  },
  userDetails: {
    type: Object,
  },
  attendanceList: {
    type: Array,
    default: () => [],
  },
  onlineUsers: {
    type: Array,
    default: () => [],
  },
  timeLogLocations: {
    type: Array,
    default: () => [],
  },
  usersForMapFilter: {
    type: Array,
    default: () => [],
  },
});

// time in form state
const timeInform = useForm({
  latitude: null,
  longitude: null,
});
const isTimeLoading = ref(false);

// The time-in function that gets location and sends data
const handleTimeIn = () => {
  if (authUser.value.userType === "super_admin") return;
  if ("geolocation" in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        isTimeLoading.value = true;
        timeInform.latitude = position.coords.latitude;
        timeInform.longitude = position.coords.longitude;
        // Now send the form data to the backend
        timeInform.post(route("timein"), {
          preserveScroll: true,
          onError: (errors) => {
            // The onError handler receives errors from the backend
            const firstError = Object.values(errors)[0];
            if (firstError) {
              error(firstError);
            }
          },
          onFinish: () => {
            timeInform.reset();
            isTimeLoading.value = false;
          },
        });
      },
      (geolocationError) => {
        if (geolocationError.code === geolocationError.PERMISSION_DENIED) {
          error("Location access is required for time in");
        } else {
          error("Error getting location: " + geolocationError.message);
        }
      }
    );
  } else {
    error("Geolocation is not supported by your browser");
  }
};

// State for the confirmation modal
const isConfirmModalOpen = ref(false);
const isConfirmLoading = ref(false);
const pendingAction = ref(null);
// Holds the properties for the confirmation modal
const confirmModalProps = reactive({
  title: "",
  message: "",
  confirmText: "",
});
// Executes the action on confirmation
const executeConfirm = () => {
  if (pendingAction.value) {
    pendingAction.value();
  }
};
const closeConfirmModal = () => {
  isConfirmModalOpen.value = false;
  isTimeLoading.value = false;
};

// first request to check time-out validation
const handleTimeOut = () => {
  isTimeLoading.value = true;
  axios
    .post(route("timeout.check"))
    .then((response) => {
      // Backend determined that confirmation is needed
      if (response.data.needsConfirmation) {
        Object.assign(confirmModalProps, {
          title: "Time Out",
          message: response.data.message,
          confirmText: "Time Out",
        });
        pendingAction.value = () => {
          confirmTimeOut(response.data.timeLogId);
        };
        isConfirmModalOpen.value = true;
      }
    })
    .catch((err) => {
      isTimeLoading.value = false;
      // Handle standard validation errors (like "no time in")
      if (err.response && err.response.status === 422) {
        const firstError = Object.values(err.response.data.errors)[0][0];
        error(firstError);
      } else {
        error("An unexpected error occurred");
      }
    });
};
// second request to confirm time-out
const confirmTimeOut = (timeLogId) => {
  isConfirmLoading.value = true;
  router.patch(
    route("timeout.update", { timeLog: timeLogId }),
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isConfirmModalOpen.value = false;
        isTimeLoading.value = false;
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    }
  );
};

// Tanstack Table columns definition for attendance
const attendanceColumns = [
  {
    header: "DATE",
    accessorFn: (row) => shortDate(row.date),
  },
  {
    header: "FIRST IN",
    accessorFn: (row) =>
      row.firstIn !== "N/A" ? formatTime(row.firstIn) : "N/A",
  },
  {
    header: "1ST BREAK",
    accessorFn: (row) =>
      row.secondIn !== "N/A" ? formatTime(row.secondIn) : "N/A",
  },
  {
    header: "LUNCH",
    accessorFn: (row) =>
      row.thirdIn !== "N/A" ? formatTime(row.thirdIn) : "N/A",
  },
  {
    header: "2ND BREAK",
    accessorFn: (row) =>
      row.fourthIn !== "N/A" ? formatTime(row.fourthIn) : "N/A",
  },
  {
    header: "LAST OUT",
    accessorFn: (row) =>
      row.lastOut !== "N/A" ? formatTime(row.lastOut) : "N/A",
  },
];

// Tanstack Table columns definition for online users
const onlineUsersColumns = [
  {
    accessorKey: "name",
    header: "NAME",
  },
  {
    accessorKey: "department",
    header: "DEPARTMENT",
  },
  {
    accessorKey: "position",
    header: "POSITION",
  },
  {
    accessorKey: "status",
    header: "STATUS",
    // Custom cell render to apply green text style
    cell: ({ getValue }) =>
      h("span", { class: "font-bold text-green-600" }, getValue()),
  },
];

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
const refreshOnlineUsers = () => {
  if (authUser.value?.userType === "super_admin" && userIsActive.value) {
    router.reload({
      only: ["onlineUsers"], // IMPORTANT: Only fetch the 'onlineUsers' prop
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
    refreshInterval = setInterval(refreshOnlineUsers, 2 * 60 * 1000); // Refresh every 2 minutes
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
    const userInList = props.usersForMapFilter.find(
      (user) => user.id === targetUserId
    );

    // To be valid, the user must exist in our filter list AND have location data
    if (userInList && props.timeLogLocations.length > 0) {
      return userInList; // Success! Set this user as the active one.
    }

    // If user isn't in the list or has no locations, select nothing
    return null;
  }

  // Case 2: No user in the URL (initial page load), default to "all"
  return props.usersForMapFilter.find((user) => user.id === "all") || null;
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
      route("dashboard"),
      { user: newUser.id },
      {
        preserveState: true,
        preserveScroll: true,
        only: ["timeLogLocations"],
      }
    );
  }
});
</script>

<template>
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      v-if="authUser?.userType !== 'super_admin'"
      class="p-4 rounded-2xl shadow-md bg-base-200 border-4 border-green-primary-1 space-y-5"
    >
      <div class="flex flex-col @2xl:flex-row items-center justify-between">
        <h1
          class="text-lg @sm:text-2xl @4xl:text-3xl font-bold flex-none text-center"
        >
          Attendance Today
          <span class="block @md:inline">
            ({{ formatDate(props.userDetails?.date) }})</span
          >
        </h1>
        <div class="mt-3 @2xl:mt-0">
          <button
            @click="handleTimeIn"
            :disabled="isTimeLoading"
            class="btn btn-sm @sm:btn-md bg-emerald-600 text-white border-2 border-white rounded-full hover:scale-105 transition-all duration-200 ease-in-out me-3"
          >
            Time In
          </button>
          <button
            @click="handleTimeOut"
            :disabled="isTimeLoading"
            class="btn btn-sm @sm:btn-md bg-rose-600 text-white border-2 border-white rounded-full hover:scale-105 transition-all duration-200 ease-in-out"
          >
            Time Out
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 @2xl:grid-cols-2 gap-4">
        <div class="flex items-center gap-5 p-4 rounded-3xl bg-base-100">
          <div class="avatar">
            <div
              class="w-18 @xl:w-20 @4xl:w-24 ring-indigo-500 ring-offset-base-300 rounded-full ring-3 ring-offset-2"
            >
              <img
                :src="
                  props.userDetails?.picture || '/profile-images/default.png'
                "
              />
            </div>
          </div>
          <div class="overflow-hidden">
            <h3 class="text-base @3xl:text-lg font-bold truncate">
              {{ props.userDetails?.name }}
            </h3>
            <p
              class="text-sm @3xl:text-base text-slate-500 font-semibold truncate"
            >
              {{ props.userDetails?.department }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="avatar">
            <div class="w-18 @xl:w-20 @4xl:w-24">
              <img
                v-if="props.userDetails?.status === 'Online'"
                src="../../assets/img/status-icon-on.png"
              />
              <img v-else src="../../assets/img/status-icon-off.png" />
            </div>
          </div>
          <div>
            <h3 class="text-base @3xl:text-lg font-bold">STATUS</h3>
            <p
              :class="[
                'text-sm @3xl:text-base font-semibold',
                {
                  'text-red-600': props.userDetails?.status === 'Offline',
                  'text-green-600': props.userDetails?.status === 'Online',
                },
              ]"
            >
              {{ props.userDetails?.status }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="w-18 @xl:w-20 @4xl:w-24 flex-none">
            <img src="../../assets/img/timein-icon.png" />
          </div>

          <div>
            <h3 class="text-base @3xl:text-lg font-bold">TIME IN</h3>
            <div
              v-if="props.userDetails?.time_in?.length"
              class="text-sm @3xl:text-base text-slate-500 font-semibold leading-5"
            >
              <div
                v-for="(time, index) in props.userDetails.time_in"
                :key="`in-${index}`"
              >
                {{ formatTime(time) }}
              </div>
            </div>
            <p v-else class="text-slate-500 font-semibold">N/A</p>
          </div>
        </div>

        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="w-18 @xl:w-20 @4xl:w-24 flex-none">
            <img src="../../assets/img/timeout-icon.png" />
          </div>

          <div>
            <h3 class="text-base @3xl:text-lg font-bold">TIME OUT</h3>
            <div
              class="text-sm @3xl:text-base text-slate-500 font-semibold leading-5"
            >
              <div v-if="props.userDetails?.time_out?.length">
                <div
                  v-for="(time, index) in props.userDetails.time_out"
                  :key="`out-${index}`"
                >
                  {{ formatTime(time) }}
                </div>

                <!-- Add N/A if time_in is longer than time_out -->
                <div
                  v-for="n in Math.max(
                    props.userDetails.time_in.length -
                      props.userDetails.time_out.length,
                    0
                  )"
                  :key="`na-${n}`"
                >
                  N/A
                </div>
              </div>
              <p v-else>N/A</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="authUser?.userType === 'super_admin'"
      class="grid grid-cols-1 @4xl:grid-cols-3 p-4 rounded-2xl shadow-md bg-base-200 gap-4"
    >
      <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
        <div class="w-20 @2xl:w-24 flex-none">
          <img src="../../assets/img/total-employee-icon.png" />
        </div>

        <div class="flex flex-col space-y-2 truncate">
          <h3 class="text-2xl @2xl:text-3xl font-bold">
            {{ props.totalCounts?.users }}
          </h3>
          <p class="text-sm @2xl:text-base text-slate-500 font-semibold">
            Total Users
          </p>
        </div>
      </div>
      <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
        <div class="w-20 @2xl:w-24 flex-none">
          <img src="../../assets/img/total-task-icon.png" />
        </div>

        <div class="flex flex-col space-y-2 truncate">
          <h3 class="text-2xl @2xl:text-3xl font-bold">
            {{ props.totalCounts?.tasks }}
          </h3>
          <p class="text-sm @2xl:text-base text-slate-500 font-semibold">
            Total Tasks
          </p>
        </div>
      </div>
      <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
        <div class="w-20 @2xl:w-24 flex-none">
          <img src="../../assets/img/total-accomplish-icon.png" />
        </div>

        <div class="flex flex-col space-y-2 truncate">
          <h3 class="text-2xl @2xl:text-3xl font-bold">
            {{ props.totalCounts?.accomplishments }}
          </h3>
          <p class="text-sm @2xl:text-base text-slate-500 font-semibold">
            Total Accomplishments
          </p>
        </div>
      </div>
    </div>

    <div v-if="authUser?.userType === 'super_admin'" class="mt-7">
      <h1
        class="text-lg @sm:text-2xl @4xl:text-3xl font-bold text-center mx-4 mb-3 @lg:text-start"
      >
        Online Users
      </h1>
      <DataTable
        :data="props.onlineUsers"
        :columns="onlineUsersColumns"
        :enable-view-toggle="true"
      />
      <div
        class="p-4 mt-7 rounded-2xl shadow-md border-4 border-green-primary-1"
      >
        <div
          class="flex flex-col @lg:flex-row justify-between items-center mb-4"
        >
          <h1
            class="text-lg @xl:text-2xl @4xl:text-3xl font-bold mx-2 mb-1.5 @lg:mb-0"
          >
            Map Coordinates
          </h1>
          <div class="w-60 @3xl:w-xs -py-2">
            <Combobox
              v-model="selectedUser"
              :options="props.usersForMapFilter"
              placeholder="Filter by user..."
            />
          </div>
        </div>

        <LocationMap
          :locations="props.timeLogLocations"
          :fit-bounds="shouldFitBounds"
        >
          <template #popup="{ location }">
            <div class="font-sans space-y-2">
              <div class="font-bold">{{ location.name }}</div>
              <hr />
              <div class="mt-3">
                <strong>Time In:</strong> {{ formatTime(location.time_in) }}
              </div>
              <div><strong>Position:</strong> {{ location.position }}</div>
              <div><strong>Department:</strong> {{ location.department }}</div>
            </div>
          </template>
        </LocationMap>
      </div>
    </div>

    <!-- Attendance Table -->
    <DataTable
      v-if="authUser?.userType !== 'super_admin'"
      :data="props.attendanceList"
      :columns="attendanceColumns"
      class="mt-7"
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="isConfirmModalOpen"
      v-bind="confirmModalProps"
      :loading="isConfirmLoading"
      @cancel="closeConfirmModal"
      @confirm="executeConfirm"
    />
  </div>
</template>
