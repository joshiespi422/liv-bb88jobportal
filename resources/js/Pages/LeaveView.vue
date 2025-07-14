<script setup>
import { ref, computed, h } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { longDate } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";
import ListBox from "../Components/ListBox.vue";
import DetailsModal from "../Components/DetailsModal.vue";

const props = defineProps({
  leaves: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  currentDepartmentId: {
    type: Number,
    default: null,
  },
  activeTab: String,
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Details Modal state
const isDetailsModalOpen = ref(false);
const selectedDetails = ref(null);
const isDetailsLoading = ref(false);
const isDetailsError = ref(false);

const attachFormatter = (attachment) => {
  if (!attachment) return "N/A";
  return `
    <div class="flex items-center gap-2">
      <i class="pi pi-paperclip text-sm"></i>
      <a href="${attachment.url}" 
         target="_blank" 
         class="text-blue-500 hover:underline truncate"
         download="${attachment.name}">
        ${attachment.name}
      </a>
    </div>`;
};
const requestDateFormatter = (date) => {
  if (props.activeTab === "special") return date;
  return longDate(date);
};
const leaveDetailFields = ref([
  { key: "name", label: "Employee" },
  { key: "dept_name", label: "Department" },
  {
    key: "leave_type",
    label: "Leave Type",
    formatter: (value) => `${value} Leave`,
  },
  {
    key: "category",
    label: "Category",
    formatter: (value) => `${value} Leave`,
  },
  { key: "created_at", label: "Submitted", formatter: longDate },
  { key: "request_date", label: "Leave Date", formatter: requestDateFormatter },
  { key: "reason", label: "Reason" },
  { key: "status", label: "Status" },
  {
    key: "proof",
    label: "Proof",
    formatter: attachFormatter,
  },
  {
    key: "hard_copy",
    label: "Hard Copy",
    formatter: attachFormatter,
  },
]);

// Function to fetch leave details
const fetchLeaveDetails = async (leaveId) => {
  isDetailsLoading.value = true;
  isDetailsModalOpen.value = true;
  selectedDetails.value = null;
  isDetailsError.value = false;

  try {
    const response = await axios.get(`/leave/${leaveId}`);
    selectedDetails.value = response.data;
  } catch (error) {
    console.error("Error fetching leave details:", error);
    selectedDetails.value = null;
    isDetailsError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isDetailsLoading.value = false;
  }
};
// Handler for viewing leave details and details modal function
const handleViewDetails = (leaveId) => {
  fetchLeaveDetails(leaveId);
};
const closeDetailsModal = () => {
  isDetailsModalOpen.value = false;
};

// core logic for the super_admin filter
const selectedDepartment = computed({
  // GET: This runs on initial load and whenever props change.
  get() {
    return props.currentDepartmentId;
  },
  // SET: This runs when the user selects a new item in the ListBox.
  set(newDeptId) {
    if (authUser.value.userType === "super_admin" && newDeptId) {
      router.get(
        route("leave"),
        { dept: newDeptId },
        {
          preserveState: true, // Keeps Vue component state
          preserveScroll: true, // Keeps scroll position
          replace: true, // Avoids polluting browser history
        }
      );
    }
  },
});
// format of departments for the ListBox component
const departmentOptions = computed(() => {
  return props.departments.map((d) => ({ value: d.id, label: d.dept_name }));
});

// tab handling navigation
const tabs = computed(() => {
  const items = [
    { id: "regular", label: "Regular" },
    { id: "special", label: "Special" },
  ];

  return items;
});
// handle tab navigation
function setTab(tabId) {
  if (tabId === props.activeTab) return;
  router.get(
    route("leave"),
    {
      ...route().params,
      tab: tabId,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
}

// Tanstack Table columns definition
const leaveTableColumns = [
  {
    id: "employee",
    header: "EMPLOYEE",
    accessorFn: (row) => row.user.name,
    cell: ({ cell }) => {
      const userPicture = cell.row.original.user.picture;
      return h(
        "span",
        {
          class: "flex items-center justify-center gap-2",
        },
        [
          h("img", {
            src: userPicture || "/profile-images/default.png",
            class: "avatar w-10 rounded-full",
          }),
          h(
            "span",
            {
              class: "truncate",
            },
            cell.getValue()
          ),
        ]
      );
    },
  },
  {
    header: "SUBMITTED DATE",
    accessorFn: (row) => longDate(row.created_at),
    id: "submitted-date",
    cell: ({ cell }) => {
      return h("span", {}, cell.getValue());
    },
  },
  {
    header: "STATUS",
    accessorKey: "status",
    cell: ({ row }) => {
      const status = row.original.status;
      const badgeClass = statusColor[status] || "badge-primary";
      return h(
        "span",
        {
          class: `badge badge-soft ${badgeClass} text-sm px-3.5 py-3.5`,
        },
        status
      );
    },
  },
  {
    id: "details",
    header: "DETAILS",
    cell: ({ row }) =>
      h(
        "button",
        {
          onClick: () => handleViewDetails(row.original.id),
          class:
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];

const statusColor = {
  pending: "badge-accent",
  approved: "badge-info",
  rejected: "badge-error",
};
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">Leave Management</h1>
      <div
        v-if="authUser?.userType === 'super_admin'"
        class="w-52 md:w-60 lg:w-72"
      >
        <ListBox
          v-model="selectedDepartment"
          :options="departmentOptions"
          placeholder="Select a department"
        />
      </div>
    </div>

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

    <!-- Task Table -->
    <DataTable :data="props.leaves" :columns="leaveTableColumns">
      <template #custom-actions>
        <!-- <button
          @click="handleNewTask"
          v-if="showNewButton"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          New Task
        </button> -->
      </template>
    </DataTable>
  </div>

  <!-- Leave Details Modal -->
  <DetailsModal
    :isOpen="isDetailsModalOpen"
    :item="selectedDetails"
    :loading="isDetailsLoading"
    :error="isDetailsError"
    title="LEAVE DETAILS"
    :fields="leaveDetailFields"
    @close="closeDetailsModal"
  />
</template>
