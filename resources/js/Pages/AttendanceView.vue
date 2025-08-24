<script setup>
import { computed, h } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { formatDate, longDate } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";

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
          onClick: () => handleViewToday(row.id),
          class:
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];

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
          onClick: () => handleViewAllDept(row.id),
          class:
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <h1 class="text-2xl lg:text-3xl font-bold mb-5">
      {{ props.activeTab === "today" ? "Attendance Today" : "Time Logs" }}
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
  </div>
</template>
