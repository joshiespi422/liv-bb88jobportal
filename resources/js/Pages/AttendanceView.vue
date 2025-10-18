<script setup>
import { computed, ref } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import {
  formatDate,
  longDate,
  formatTime,
} from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import LocationMap from "../Components/LocationMap.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import {
  useTodayListColumns,
  useDeptAttendanceColumns,
} from "../Data/tableColumns";

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
    { id: "all", label: "All Logs" },
  ];
});

// --- Individual Log Details ---
const {
  isOpen: isLogModalOpen,
  isLoading: isLogLoading,
  isError: isLogError,
  data: selectedLog,
  open: openLogModal,
  close: closeLogModal,
} = useDetailsModal({
  // Define the fetcher with the exact parameters it needs
  fetcher: (userId, date) =>
    axios.get(route("attendance.show", { id: userId, date })),
});
// tanstack table columns definition for today list
const todayListColumns = useTodayListColumns({ openLogModal });
const showMap = ref(false);
const visibleIps = ref({});
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

// --- Department Log Details ---
const {
  isOpen: isDeptLogModalOpen,
  isLoading: isDeptLogLoading,
  isError: isDeptLogError,
  data: selectedDeptLog,
  open: openDeptLogModal,
  close: closeDeptLogModal,
} = useDetailsModal({
  // This fetcher takes different parameters, and the composable handles it perfectly
  fetcher: (deptId, date) =>
    axios.get(route("attendance.show.dept", { deptId, date })),
});
// tanstack table columns definition for all department attendance
const deptAttendanceColumns = useDeptAttendanceColumns({
  openDeptLogModal,
});
const hideCloseBtn = computed(() => {
  return props.activeTab !== "today";
});

const attendanceTitle = computed(() => {
  return props.activeTab === "today" ? "Attendance Today" : "Time Logs";
});
</script>

<template>
  <Head title="Attendance" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <h1
      class="text-lg @sm:text-2xl @4xl:text-3xl font-bold text-center @2xl:text-start mb-3 @2xl:mb-5"
    >
      {{ attendanceTitle }}
    </h1>
    <!-- Tabs -->
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in tabs"
        :key="tab.id"
        :href="route('attendance', { ...route().params, tab: tab.id })"
        :class="[
          'tab',
          activeTab === tab.id
            ? 'tab-active font-bold pointer-events-none'
            : 'hover:bg-base-300',
        ]"
        preserve-state
        preserve-scroll
        replace
      >
        {{ tab.label }}
      </Link>
    </div>

    <!-- Today Table -->
    <DataTable
      v-if="activeTab === 'today'"
      :data="props.todayList"
      :columns="todayListColumns"
      :enable-view-toggle="true"
    />

    <!-- All Table -->
    <DataTable
      v-if="activeTab === 'all'"
      :data="props.deptAttendance"
      :columns="deptAttendanceColumns"
      :enable-view-toggle="true"
    />

    <!-- Log Details Modal -->
    <DetailsModal
      :isOpen="isLogModalOpen"
      :item="selectedLog"
      :loading="isLogLoading"
      :error="isLogError"
      :hide-close-btn="hideCloseBtn"
      title="ATTENDANCE DETAILS"
      @close="closeLogModal(), (showMap = false), (visibleIps = {})"
      panelClass="w-full max-w-2xl"
    >
      <!-- Custom Skeleton Loader -->
      <template #skeleton>
        <div class="my-2">
          <div class="grid grid-cols-1 @md:grid-cols-2 gap-3 rounded-lg p-3">
            <div v-for="n in 4" :key="n">
              <div class="skeleton h-6 w-1/2 mb-1" />
              <div class="skeleton h-7 @md:h-8 w-full" />
            </div>
          </div>

          <div class="rounded-lg p-3 space-y-3">
            <div v-for="n in 3" :key="n">
              <div class="skeleton h-7 @md:h-8 w-full" />
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item }">
        <div class="my-5 space-y-4">
          <div
            class="grid grid-cols-1 @md:grid-cols-2 gap-2 @md:gap-4 rounded-lg bg-base-200 p-3 @sm:p-4"
          >
            <div>
              <p class="text-slate-500 text-sm">Name:</p>
              <p class="font-semibold text-sm @sm:text-base">
                {{ item.user.name }}
              </p>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Date:</p>
              <p class="font-semibold text-sm @sm:text-base">
                {{ formatDate(item.date) }}
              </p>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Position:</p>
              <p class="font-semibold text-sm @sm:text-base">
                {{ item.user.position }}
              </p>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Department:</p>
              <p class="font-semibold text-sm @sm:text-base">
                {{ item.user.department }}
              </p>
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
              class="rounded-lg bg-base-200 p-2 @sm:p-3"
            >
              <div class="flex items-center justify-between">
                <p>
                  <span class="text-slate-500 text-sm">Time In: </span>
                  <span class="font-mono font-semibold text-sm @sm:text-base">{{
                    formatTime(log.time_in)
                  }}</span>
                  <span class="mx-2">-</span>
                  <span class="text-slate-500 text-sm">Time Out: </span>
                  <span class="font-mono font-semibold text-sm @sm:text-base">{{
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
                    <span
                      class="font-mono font-semibold text-blue-500 text-sm @sm:text-base"
                      >{{ log.ip_address ?? "N/A" }}</span
                    >
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
          class="btn btn-sm @sm:btn-md btn-soft rounded-full me-2"
          @click="handleBackFromLog"
        >
          <i class="pi pi-arrow-left me-1" /> Back
        </button>

        <button
          v-if="mapLocations.length > 0"
          @click="showMap = !showMap"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
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
                  openLogModal(user.id, item.date);
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
