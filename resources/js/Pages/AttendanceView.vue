<script setup>
import { computed, h, ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import {
  formatDate,
  longDate,
  formatTime,
} from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/DetailsModal.vue";
import LocationMap from "../Components/LocationMap.vue";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const props = defineProps({
  todayList: {
    type: Array,
    default: () => [],
  },
  deptAttendance: {
    type: Array,
    default: () => [],
  },
  activeTab: {
    type: String,
    default: "today",
  },
});

// tab handling navigation
const tabs = computed(() => {
  return [
    { id: "today", label: "Today" },
    { id: "all", label: "All" },
  ];
});
// handle tab navigation
function setTab(tabId) {
  if (tabId === props.activeTab) return;
  router.get(
    route("attendance"),
    {
      tab: tabId,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
}

// tanstack table columns definition for today list
const todayListColumns = [
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
    header: "DATE",
    accessorFn: (row) => formatDate(row.date),
  },
  {
    id: "details",
    header: "DETAILS",
    cell: ({ row }) =>
      h(
        "button",
        {
          onClick: () => handleViewToday(row.original.id, row.original.date),
          class:
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
// individual log details and map state
const isLogModalOpen = ref(false);
const selectedLog = ref(null);
const isLogLoading = ref(false);
const isLogError = ref(false);
const showMap = ref(false);
const visibleIps = ref({});
// Handler for viewing log details and details modal function
const handleViewToday = (userId, date) => {
  fetchLogDetails(userId, date);
};
const fetchLogDetails = async (id, date) => {
  isLogLoading.value = true;
  isLogModalOpen.value = true;
  selectedLog.value = null;
  isLogError.value = false;

  try {
    const response = await axios.get(route("attendance.show", { id, date }));
    selectedLog.value = response.data;
  } catch (error) {
    console.error("Error fetching log details:", error);
    selectedLog.value = null;
    isLogError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isLogLoading.value = false;
  }
};
const closeLogModal = () => {
  isLogModalOpen.value = false;
  showMap.value = false;
  visibleIps.value = {};
};
const toggleIpVisibility = (logIndex) => {
  visibleIps.value[logIndex] = !visibleIps.value[logIndex];
};
// Computed property to format locations for the map component
const mapLocations = computed(() => {
  if (!selectedLog.value?.logs) return [];

  return selectedLog.value.logs
    .filter((log) => log.latitude && log.longitude) // Only include logs with coordinates
    .map((log) => ({
      ...log,
      // Add user details to each location for the popup
      name: selectedLog.value.user.name,
      position: selectedLog.value.user.position,
      department: selectedLog.value.user.department,
    }));
});
// control log back button visibility
const showBackButtonInLog = computed(() => {
  return (
    isLogModalOpen.value &&
    selectedLog.value !== null &&
    props.activeTab !== "today"
  );
});
// handle log back navigation
const handleBackFromLog = () => {
  isLogModalOpen.value = false;
  isDeptLogModalOpen.value = true;
};

// tanstack table columns definition for all department attendance
const deptAttendanceColumns = [
  {
    accessorKey: "department",
    header: "DEPARTMENT",
  },
  {
    header: "DATE",
    accessorFn: (row) => longDate(row.date),
  },
  {
    id: "details",
    header: "DETAILS",
    cell: ({ row }) =>
      h(
        "button",
        {
          onClick: () => handleViewAllDept(row.original.id, row.original.date),
          class:
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
// department log Details state
const isDeptLogModalOpen = ref(false);
const selectedDeptLog = ref(null);
const isDeptLogLoading = ref(false);
const isDeptLogError = ref(false);
// Handler for viewing log details and details modal function
const handleViewAllDept = (deptId, date) => {
  fetchDeptLogDetails(deptId, date);
};
const fetchDeptLogDetails = async (deptId, date) => {
  isDeptLogLoading.value = true;
  isDeptLogModalOpen.value = true;
  selectedDeptLog.value = null;
  isDeptLogError.value = false;

  try {
    const response = await axios.get(
      route("attendance.show.dept", { deptId, date })
    );
    selectedDeptLog.value = response.data;
  } catch (error) {
    console.error("Error fetching log details:", error);
    selectedDeptLog.value = null;
    isDeptLogError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isDeptLogLoading.value = false;
  }
};
const closeDeptLogModal = () => {
  isDeptLogModalOpen.value = false;
};

const hideCloseBtn = computed(() => {
  return props.activeTab !== "today";
});

const attendanceTitle = computed(() => {
  return props.activeTab === "today" ? "Attendance Today" : "Time Logs";
});
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <h1 class="text-2xl lg:text-3xl font-bold mb-5">
      {{ attendanceTitle }}
    </h1>
    <!-- Tabs -->
    <div class="tabs tabs-box my-3">
      <a
        v-for="tab in tabs"
        :key="tab.id"
        @click.prevent="setTab(tab.id)"
        :class="[
          'tab',
          activeTab === tab.id ? 'tab-active font-bold' : 'hover:bg-base-300',
        ]"
      >
        {{ tab.label }}
      </a>
    </div>

    <!-- Today Table -->
    <DataTable
      v-if="activeTab === 'today'"
      :data="props.todayList"
      :columns="todayListColumns"
    />

    <!-- All Table -->
    <DataTable
      v-if="activeTab === 'all'"
      :data="props.deptAttendance"
      :columns="deptAttendanceColumns"
    />

    <!-- Log Details Modal -->
    <DetailsModal
      :isOpen="isLogModalOpen"
      :item="selectedLog"
      :loading="isLogLoading"
      :error="isLogError"
      :hide-close-btn="hideCloseBtn"
      title="ATTENDANCE DETAILS"
      @close="closeLogModal"
      panelClass="w-full max-w-2xl"
    >
      <!-- Custom Skeleton Loader -->
      <template #skeleton>
        <div class="my-2">
          <div class="grid grid-cols-2 gap-3 rounded-lg p-3">
            <div v-for="n in 4" :key="n">
              <div class="skeleton h-6 w-1/2 mb-1" />
              <div class="skeleton h-8 w-full" />
            </div>
          </div>

          <div class="rounded-lg p-3 space-y-3">
            <div v-for="n in 3" :key="n">
              <div class="skeleton h-8 w-full" />
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item }">
        <div class="my-5 space-y-4">
          <div class="grid grid-cols-2 gap-4 rounded-lg bg-base-200 p-4">
            <div>
              <p class="text-slate-500 text-sm">Name:</p>
              <p class="font-semibold">{{ item.user.name }}</p>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Date:</p>
              <p class="font-semibold">{{ formatDate(item.date) }}</p>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Position:</p>
              <p class="font-semibold">{{ item.user.position }}</p>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Department:</p>
              <p class="font-semibold">{{ item.user.department }}</p>
            </div>
          </div>

          <div v-if="showMap" class="mt-4">
            <LocationMap :locations="mapLocations" :fitBounds="true">
              <template #popup="{ location }">
                <div class="font-sans space-y-2">
                  <div class="font-bold">{{ location.name }}</div>
                  <hr />
                  <div class="mt-3">
                    <strong>Time In:</strong> {{ formatTime(location.time_in) }}
                  </div>
                  <div>
                    <strong>Time Out:</strong>
                    {{ formatTime(location.time_out) }}
                  </div>
                  <div><strong>Position:</strong> {{ location.position }}</div>
                  <div>
                    <strong>Department:</strong> {{ location.department }}
                  </div>
                </div>
              </template>
            </LocationMap>
          </div>

          <div class="space-y-3">
            <div
              v-for="(log, index) in item.logs"
              :key="index"
              class="rounded-lg bg-base-200 p-3"
            >
              <div class="flex items-center justify-between">
                <p>
                  <span class="text-slate-500 text-sm">Time In: </span>
                  <span class="font-mono font-semibold">{{
                    formatTime(log.time_in)
                  }}</span>
                  <span class="mx-2">-</span>
                  <span class="text-slate-500 text-sm">Time Out: </span>
                  <span class="font-mono font-semibold">{{
                    formatTime(log.time_out) ?? "N/A"
                  }}</span>
                </p>
                <button
                  @click="toggleIpVisibility(index)"
                  class="btn btn-ghost btn-circle btn-sm"
                  title="Show/Hide IP Address"
                >
                  <i class="pi pi-info-circle text-lg text-blue-500"></i>
                </button>
              </div>
              <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
              >
                <div
                  v-if="visibleIps[index]"
                  class="mt-2 border-t border-base-300 pt-2"
                >
                  <p class="truncate">
                    <span class="text-slate-500 text-sm">IP Address: </span>
                    <span class="font-mono font-semibold text-blue-500">{{
                      log.ip_address ?? "N/A"
                    }}</span>
                  </p>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </template>

      <template #custom-buttons>
        <button
          v-if="showBackButtonInLog"
          class="btn btn-soft rounded-full me-2"
          @click="handleBackFromLog"
        >
          <i class="pi pi-arrow-left me-1" /> Back
        </button>

        <button
          v-if="mapLocations.length > 0"
          @click="showMap = !showMap"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          <i
            :class="showMap ? 'pi pi-map-marker' : 'pi pi-map'"
            class="mr-2"
          ></i>
          {{ showMap ? "Hide Map" : "Show Map" }}
        </button>
      </template>
    </DetailsModal>

    <!-- Department Log Modal -->
    <DetailsModal
      :isOpen="isDeptLogModalOpen"
      :item="selectedDeptLog"
      :loading="isDeptLogLoading"
      :error="isDeptLogError"
      title="DEPARTMENT LOG"
      panelClass="w-full max-w-lg"
      @close="closeDeptLogModal"
    >
      <!-- Custom Skeleton Layout -->
      <template #skeleton>
        <div class="my-5">
          <div v-for="n in 6" :key="n">
            <div class="grid grid-cols-[2fr_0.5fr] items-center gap-2 mb-3">
              <div class="skeleton h-8 w-full" />
              <div class="skeleton h-8 w-full" />
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item }">
        <div class="my-3">
          <div class="flex items-center justify-start mt-3 px-3">
            <p class="font-semibold text-slate-500">
              {{ item.department }} - {{ longDate(item.date) }}
            </p>
          </div>
          <div v-for="(user, index) in item.users" :key="index" class="p-2">
            <div class="grid grid-cols-[1fr_auto] items-center gap-2">
              <p class="font-semibold bg-base-200 py-2 px-4 rounded-md">
                {{ user.name }}
              </p>
              <button
                @click="
                  fetchLogDetails(user.id, item.date);
                  closeDeptLogModal();
                "
                class="btn bg-green-primary-1 hover:bg-green-primary-3 rounded-full"
              >
                <i class="pi pi-caret-right text-xl text-white-primary"></i>
              </button>
            </div>
          </div>
        </div>
      </template>
    </DetailsModal>
  </div>
</template>
