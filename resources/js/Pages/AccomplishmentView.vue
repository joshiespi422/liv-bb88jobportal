<script setup>
import { ref, computed, reactive } from "vue";
import { useForm, usePage, router, Link } from "@inertiajs/vue3";
import { formatDate } from "../Composables/useDateFormatter";
import { useUrlParameter } from "../Composables/useUrlParameter";
import { useExcelExporter } from "../Composables/useExcelExporter";
import { onClickOutside } from "@vueuse/core";
import { useToast } from "../Composables/useToast";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useEditAccomplishmentFormFields } from "../Data/forms/accomplishmentFormFields";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { accomplishDetailFields } from "../Data/detailFields";
import { useAccomplishmentColumns } from "../Data/tableColumns";
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

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

const { error } = useToast();
// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);
// for notification click
const { onMountedHandleParameter } = useUrlParameter();

// For editing an accomplishment
const isFormModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);
// confirmation before editing
const isConfirmModalOpen = ref(false);
const isConfirmLoading = ref(false);

// Holds the properties for the confirmation modal
const confirmModalProps = reactive({
  title: "",
  message: "",
  confirmText: "",
  confirmButtonBg: "",
  iconName: "",
});
// Closes the confirmation modal
const closeConfirmModal = () => {
  isConfirmModalOpen.value = false;
};
// Executes the action on confirmation
const executeConfirm = () => {
  if (pendingAction.value) {
    pendingAction.value();
  }
};

// edit form state
const editAccomplishForm = useForm({
  _method: "PATCH",
  description: "",
  link: "",
  attachment: null,
});

// -- Accomplishment Details Logic --
const {
  isOpen: isAccomplishModalOpen,
  isLoading: isAccomplishLoading,
  isError: isAccomplishError,
  data: selectedAccomplish,
  open: fetchAccomplishDetails,
  close: closeAccomplishModal,
} = useDetailsModal({ baseUrl: "/accomplishment" });
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchAccomplishDetails);

// Form field configuration for editing an accomplishment
const editFormFields = useEditAccomplishmentFormFields(selectedAccomplish);

// edit accomplishment modal state
const handleEditAccomplish = () => {
  if (!selectedAccomplish.value) return;
  isFormModalOpen.value = true;
  isAccomplishModalOpen.value = false;
};
const closeAllModal = () => {
  isFormModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// control edit back button visibility
const showBackButtonInEdit = computed(() => {
  return isFormModalOpen.value && selectedAccomplish.value !== null;
});
// handle edit back navigation
const handleBackFromEdit = () => {
  isFormModalOpen.value = false;
  isAccomplishModalOpen.value = true;
};

// -- Edit Accomplishment Flow --
const handleEditSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Edit Accomplishment",
    message: "Are you sure you want to edit this accomplishment?",
    confirmText: "Edit",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-chart-line",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    editAccomplishForm.post(
      route("accomplishment.update", selectedAccomplish.value.id),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          editAccomplishForm.reset();
        },
        onError: () => {
          closeConfirmModal();
        },
        onFinish: () => {
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      }
    );
  };

  isConfirmModalOpen.value = true;
};

// Handler for viewing accomplishment details and details modal function
const handleViewDetails = (accomplishment) => {
  fetchAccomplishDetails(accomplishment.id);
};

// tab handling navigation
const isRegularTab = computed(
  () =>
    authUser.value.userType === "employee" &&
    authUser.value.hierarchy !== "Leader"
);
const isLeaderTab = computed(
  () =>
    authUser.value.userType === "employee" &&
    authUser.value.hierarchy === "Leader" &&
    props.currentType === "employee"
);
const tabs = computed(() => {
  const items = [{ id: "all", label: "All Accomplish" }];

  if (
    isRegularTab.value ||
    isLeaderTab.value ||
    authUser.value.userType === "intern"
  ) {
    items.unshift({
      id: "own",
      label: "Your Accomplish",
    });
  }

  return items;
});

// table columns tanstack definition
const accomplishTableColumns = useAccomplishmentColumns(props, {
  handleViewDetails,
});
// for date picker
const showDateFilter = ref(false);
const dateRange = ref(null);
// toggle date filter
const handleDateFilter = () => {
  showDateFilter.value = !showDateFilter.value;
};

const capitalizedType = computed(() => {
  if (!props.currentType) return "";
  return props.currentType.charAt(0).toUpperCase() + props.currentType.slice(1);
});

const today = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
});
const showEditButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";

  // 1. Must not be super admin or leader
  if (isSuperAdmin) {
    return false;
  }
  // 2. Must have selected task details
  if (!selectedAccomplish.value) {
    return false;
  }
  // 3. Must be in "own" tab
  if (props.activeTab !== "own") {
    return false;
  }
  // 4. Must be his own accomplishment
  if (selectedAccomplish.value.user?.id !== authUser.value?.id) {
    return false;
  }
  // 5. Must be in the same date as today
  const formattedDate = formatDate(
    selectedAccomplish.value.created_at,
    "yyyy-MM-dd"
  );
  if (formattedDate !== today.value) {
    return false;
  }

  return true;
});

// for closing date filter when clicked outside
const dateFilterRef = ref(null);
const buttonFilterRef = ref(null);
onClickOutside(
  dateFilterRef,
  () => {
    showDateFilter.value = false;
  },
  { ignore: [buttonFilterRef] }
);

// excel export
const dataTableRef = ref(null);
const { exportToExcel } = useExcelExporter();

const handleExport = async () => {
  // Access the tanstack table instance from the child component
  const table = dataTableRef.value?.table;
  if (!table) {
    console.error("DataTable instance not found.");
    error("No table available to export");
    return;
  }
  // Get the rows that are currently visible after all filters (search, date, etc.)
  const filteredRows = table.getFilteredRowModel().rows;
  if (filteredRows.length === 0) {
    error("No filtered data available to export");
    return;
  }
  // Extract the original IDs from the visible rows
  const accomplishmentIds = filteredRows.map((row) => row.original.id);
  try {
    // 6. Fetch the full data for ONLY the visible accomplishments
    const response = await axios.post(route("accomplishment.export"), {
      ids: accomplishmentIds,
    });
    const fullDataToExport = response.data;

    // 7. Define the columns and their properties for the Excel file
    const exportColumns = [
      { header: "User", key: "user_name", width: 25 },
      { header: "Project", key: "project_name", width: 25 },
      { header: "Accomplish Title", key: "accomplish_title", width: 30 },
      { header: "Date", key: "date_report", width: 25 },
      { header: "Description", key: "description", width: 45 },
      { header: "Link", key: "link", width: 40 },
      { header: "Attachment", key: "attachment_url", width: 40 },
    ];

    // 8. Call the reusable exporter function
    await exportToExcel(
      fullDataToExport,
      exportColumns,
      "accomplishment_reports",
      "Accomplishment Reports"
    );
  } catch (error) {
    console.error("Failed to export data:", error);
  }
};
</script>

<template>
  <Head title="Accomplishment" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        {{ capitalizedType }} Accomplishments
      </h1>
      <div
        v-if="authUser?.userType === 'super_admin'"
        class="w-52 md:w-60 lg:w-72"
      >
        <Department
          :departments="props.departments"
          :current-department-id="props.currentDepartmentId"
          :auth-user="authUser"
          route-name="accomplishment"
          :other-params="{ type: props.currentType }"
        />
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in tabs"
        :key="tab.id"
        :href="route('accomplishment', { ...route().params, tab: tab.id })"
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

    <!-- Accomplishment Table -->
    <DataTable
      ref="dataTableRef"
      :data="props.accomplishments"
      :columns="accomplishTableColumns"
      :date-filter="dateRange"
      :filter-key="'created_at'"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <!-- date picker -->
        <div v-if="showDateFilter" ref="dateFilterRef">
          <VueDatePicker
            v-model="dateRange"
            range
            :auto-position="'bottom'"
            :ui="{
              input:
                ' !w-40 @md:!w-52 @sm:!py-2.5 !py-2 !bg-base-100 !border-2 !rounded-xl !text-xs !text-base-content !border-base-content !shadow-md hover:!border-green-primary-1',
              menu: '!p-0 !bg-base-100 !border-2 !border-base-content !rounded-xl !text-xs @sm:!text-sm !shadow-md hover:!border-green-primary-1',
            }"
            :enable-time-picker="false"
            placeholder="Select date range"
          />
        </div>
        <button
          @click="handleDateFilter"
          ref="buttonFilterRef"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          <i :class="showDateFilter ? 'pi pi-times' : 'pi pi-calendar-clock'" />
          Date
        </button>

        <button
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          @click="handleExport"
        >
          <i class="pi pi-download"></i>
          Export
        </button>
      </template>
    </DataTable>

    <!-- Edit Accomplishment Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      :showBackButton="showBackButtonInEdit"
      title="EDIT ACCOMPLISHMENT"
      :form="editAccomplishForm"
      :fields="editFormFields"
      submitText="Submit"
      disabledButton
      @close="closeAllModal"
      @back="handleBackFromEdit"
      @submit="handleEditSubmit"
    >
      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="isConfirmModalOpen"
        v-bind="confirmModalProps"
        :loading="isConfirmLoading"
        @cancel="closeConfirmModal"
        @confirm="executeConfirm"
      />
    </FormModal>

    <!-- Accomplishment Details Modal -->
    <DetailsModal
      :isOpen="isAccomplishModalOpen"
      :item="selectedAccomplish"
      :loading="isAccomplishLoading"
      :error="isAccomplishError"
      title="ACCOMPLISH DETAILS"
      :fields="accomplishDetailFields"
      @close="closeAccomplishModal"
    >
      <template #custom-buttons>
        <button
          v-if="showEditButton"
          @click="handleEditAccomplish"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          Edit
        </button>
      </template>
    </DetailsModal>
  </div>
</template>

<!-- <style scoped>
.dp__theme_light {
  --dp-background-color: var(--color-base-100);
  --dp-border-color: var(--color-base-content);
  --dp-text-color: var(--color-base-content);
  --dp-icon-color: var(--color-white-primary);
}
</style> -->

<style scoped>
:deep(.dp__cell_inner),
:deep(.dp__calendar_header_item),
:deep(.dp__month_year_select) {
  color: var(--color-base-content);
}
:deep(.dp__cell_inner):hover,
:deep(.dp__action_cancel):hover,
:deep(.dp__month_year_select):hover {
  background-color: var(--color-base-300);
}
:deep(.dp__cell_offset),
:deep(.dp__selection_preview) {
  color: var(--color-slate-500);
}
:deep(.dp__range_between) {
  background-color: var(--color-green-primary-1);
  color: var(--color-white);
  border: 0;
}
:deep(.dp__range_start),
:deep(.dp__range_end) {
  color: var(--color-white);
}
:deep(.dp__today) {
  background-color: var(--color-indigo-500);
  color: var(--color-white);
  border: 0;
}
:deep(.dp__action_cancel) {
  color: var(--color-base-content);
  border: 0;
}
:deep(.dp__action_select) {
  background-color: var(--color-indigo-500);
}
:deep(.dp__action_select):hover {
  background-color: var(--color-indigo-600);
}
:deep(.dp__overlay) {
  border-radius: 12px;
}
</style>
