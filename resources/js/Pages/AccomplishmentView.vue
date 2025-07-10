<script setup>
import { ref, h, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { longDate, longDateTime } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";
import ListBox from "../Components/ListBox.vue";
import DetailsModal from "../Components/DetailsModal.vue";

const props = defineProps({
  accomplishments: {
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
  currentType: {
    type: String,
    default: "employee",
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

// the fields to be displayed in the details modal for an accomplishment
const accomplishDetailFields = ref([
  { key: "task_title", label: "Task" },
  { key: "user_name", label: "From" },
  { key: "title", label: "Title" },
  { key: "description", label: "Description" },
  {
    key: "link",
    label: "Link",
    formatter: (value) =>
      value
        ? `<a href="${value}" target="_blank" class="text-blue-500 hover:underline">${value}</a>`
        : "N/A",
    html: true,
  },
  {
    key: "attachment",
    label: "Attachment",
    formatter: (attachment) => {
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
        </div>
      `;
    },
    html: true,
  },
  { key: "created_at", label: "Submitted", formatter: longDateTime },
]);

// // Function to fetch accomplishment details
const fetchAccomplishDetails = async (accomplishmentId) => {
  isDetailsLoading.value = true;
  isDetailsModalOpen.value = true;
  selectedDetails.value = null;
  isDetailsError.value = false;

  try {
    const response = await axios.get(`/accomplishment/${accomplishmentId}`);
    selectedDetails.value = response.data;
  } catch (error) {
    console.error("Error fetching accomplishment details:", error);
    isDetailsError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isDetailsLoading.value = false;
  }
};

// // Handler for viewing accomplishment details and details modal function
const handleViewDetails = (accomplishment) => {
  fetchAccomplishDetails(accomplishment.id);
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
        route("accomplishment"),
        { dept: newDeptId, type: props.currentType },
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
  const items = [{ id: "all_accomplishments", label: "All Accomplishments" }];

  if (authUser.value.userType !== "super_admin") {
    items.unshift({
      id: "your_accomplishments",
      label: "Your Accomplishments",
    });
  }

  return items;
});
// handle tab navigation
function setTab(tabId) {
  if (tabId === props.activeTab) return;
  router.get(
    route("accomplishment"),
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

const accomplishTableColumns = computed(() => {
  const columns = [];

  // Conditionally add "FROM" column for All Accomplishments tab
  if (props.activeTab === "all_accomplishments") {
    columns.push({
      accessorKey: "user_name",
      header: "FROM",
    });
  }

  // Common columns
  columns.push(
    {
      accessorKey: "task_title",
      header: "TASK",
    },
    {
      accessorKey: "title",
      header: "TITLE",
    },
    {
      header: "SUBMITTED",
      accessorFn: (row) => longDate(row.created_at),
      id: "started-date",
      cell: ({ cell }) => h("span", {}, cell.getValue()),
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => handleViewDetails(row.original),
            class:
              "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details"
        ),
      enableSorting: false,
    }
  );

  return columns;
});

const capitalizedType = computed(() => {
  if (!props.currentType) return "";
  return props.currentType.charAt(0).toUpperCase() + props.currentType.slice(1);
});
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">
        {{ capitalizedType }} Accomplishments
      </h1>
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

    <!-- Accomplishment Table -->
    <DataTable
      :data="props.accomplishments"
      :columns="accomplishTableColumns"
      enable-tooltips
    />

    <!-- Accomplishment Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      :error="isDetailsError"
      title="ACCOMPLISHMENT DETAILS"
      :fields="accomplishDetailFields"
      custom-content
      @close="closeDetailsModal"
    />
  </div>
</template>
