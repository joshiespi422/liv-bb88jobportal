<script setup>
import { ref, computed, h, reactive, watch } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { longDate } from "../Composables/useDateFormatter";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import ListBox from "../Components/ListBox.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import {
  useRequestLeaveFormFields,
  useValidateLeaveFormFields,
} from "../Data/forms/leaveFormFields";

const props = defineProps({
  leaves: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  leaveTypes: {
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
// for notification click
const { onMountedHandleParameter } = useUrlParameter();

// State for modals for forms
const isRequestModalOpen = ref(false);
const isValidateModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);
// confirmation before request
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

// request form state
const categoriesList = ref([]);
const requestForm = useForm({
  user_id: authUser.value.id,
  leave_type_id: "",
  leave_category_id: "",
  request_date: "",
  reason: "",
  proof: null,
});
// validate form state
const validateForm = useForm({
  status: "",
  reject_reason: "",
  hard_copy: null,
});

// Details Modal state
const isDetailsModalOpen = ref(false);
const selectedDetails = ref(null);
const isDetailsLoading = ref(false);
const isDetailsError = ref(false);
// state for showing rejection reason
const showRejectReason = ref(false);

// for min attribute deadline
const today = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
});
// find the full object for the selected leave type using its ID.
const selectedLeaveType = computed(() => {
  if (!requestForm.leave_type_id) return "";
  const type = props.leaveTypes.find((t) => t.id == requestForm.leave_type_id);
  return type ? type.name : "";
});
// fetch leave categories for request leave
const fetchCategoriesList = async (leaveTypeId) => {
  if (!leaveTypeId) {
    categoriesList.value = [];
    return;
  }
  try {
    // Use the route() helper from Ziggy
    const response = await axios.get(
      route("leave.categories", {
        leaveTypeId: leaveTypeId,
      })
    );
    categoriesList.value = response.data;
  } catch (error) {
    console.error("Failed to fetch leave categories:", error);
    categoriesList.value = [];
  }
};
// Watch for changes in leave type to fetch new categories
watch(
  () => requestForm.leave_type_id,
  async (newLeaveType) => {
    if (newLeaveType) {
      requestForm.leave_category_id = "";
      requestForm.request_date = "";
      categoriesList.value = [];
      await fetchCategoriesList(newLeaveType);
    }
  }
);
// Watch for changes in leave category
watch(
  () => requestForm.leave_category_id,
  (newCategoryId) => {
    if (selectedLeaveType.value === "Special" && newCategoryId) {
      const category = categoriesList.value.find((c) => c.id == newCategoryId);
      if (category) {
        // Update the days display by triggering recomputation
        categoriesList.value = [...categoriesList.value];
      }
    }
  }
);
// Form field configuration for requesting leave
const requestFormFields = useRequestLeaveFormFields(
  requestForm,
  props,
  categoriesList,
  selectedLeaveType,
  today
);

// form field configuration for validating leave
const validateFormFields = useValidateLeaveFormFields(
  validateForm,
  selectedDetails
);

// handle validate leave
const handleValidateLeave = () => {
  if (!selectedDetails.value) return;
  isValidateModalOpen.value = true;
  isDetailsModalOpen.value = false;
};
// handle request leave
const handleRequestLeave = () => {
  isRequestModalOpen.value = true;
};
const closeAllModal = () => {
  isRequestModalOpen.value = false;
  isValidateModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// control validate back button visibility
const showBackButtonInValidate = computed(() => {
  return isValidateModalOpen.value && selectedDetails.value !== null;
});
// handle validate back navigation
const handleBackFromValidate = () => {
  isValidateModalOpen.value = false;
  isDetailsModalOpen.value = true;
};
// -- Request Leave Flow --
const handleRequestSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Request Leave",
    message: "Are you sure you want to request this leave?",
    confirmText: "Request",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-folder-open",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    requestForm.post(route("leave.store"), {
      preserveScroll: true,
      onSuccess: () => {
        closeAllModal();
        requestForm.reset();
      },
      onError: () => closeConfirmModal(),
      onFinish: () => {
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
};

// -- Validate Leave Flow --
const handleValidateSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Validate Leave",
    message: "Are you sure you want to validate this leave?",
    confirmText: "Validate",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-folder-open",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    validateForm.post(
      route("leave.validate", { leave: selectedDetails.value.id }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          validateForm.reset();
        },
        onError: () => closeConfirmModal(),
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

// -- Leave Details Logic --
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
// Function to toggle rejection reason
const toggleRejectReason = () => {
  showRejectReason.value = !showRejectReason.value;
};
// Handler for viewing leave details and details modal function
const handleViewDetails = (leaveId) => {
  showRejectReason.value = false;
  fetchLeaveDetails(leaveId);
};
const closeDetailsModal = () => {
  isDetailsModalOpen.value = false;
  showRejectReason.value = false;
};
const statusClassMap = {
  approved: "text-success",
  rejected: "text-error",
  pending: "text-accent",
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
    html: true,
  },
  {
    key: "hard_copy",
    label: "Hard Copy",
    formatter: attachFormatter,
    html: true,
  },
]);
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchLeaveDetails);

// core logic for the super_admin filter
const selectedDepartment = computed({
  // GET: This runs on initial load and whenever props change.
  get() {
    return props.currentDepartmentId;
  },
  // SET: This runs when the user selects a new item in the ListBox.
  set(newDeptId) {
    if (
      (authUser.value.userType === "super_admin" ||
        authUser.value.department.name === "Admin") &&
      newDeptId
    ) {
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
    header: "SUBMITTED",
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
            "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
const statusColor = {
  pending: "badge-accent",
  approved: "badge-success",
  rejected: "badge-error",
};

const showRequestButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";

  // 1. Must not be super admin
  if (isSuperAdmin) {
    return false;
  }

  return true;
});

const showValidateButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isAdmin = authUser.value?.department?.name === "Admin";

  // 1. Must be super admin
  if (!isSuperAdmin && !isAdmin) {
    return false;
  }

  // 2. Must have selected task details
  if (!selectedDetails.value) {
    return false;
  }

  // 3. Must be in "pending" status
  if (selectedDetails.value.status !== "pending") {
    return false;
  }
  return true;
});
</script>

<template>
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Leave Management
      </h1>
      <div
        v-if="
          authUser?.userType === 'super_admin' ||
          authUser?.department?.name === 'Admin'
        "
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
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in tabs"
        :key="tab.id"
        :href="route('leave', { ...route().params, tab: tab.id })"
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

    <!-- Leave Table -->
    <DataTable
      :data="props.leaves"
      :columns="leaveTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleRequestLeave"
          v-if="showRequestButton"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Request Leave
        </button>
      </template>
    </DataTable>
  </div>

  <!-- Request Leave Modal -->
  <FormModal
    :isOpen="isRequestModalOpen"
    title="REQUEST LEAVE"
    :form="requestForm"
    :fields="requestFormFields"
    submitText="Request"
    @close="closeAllModal"
    @submit="handleRequestSubmit"
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

  <!-- Validate Leave Modal -->
  <FormModal
    :isOpen="isValidateModalOpen"
    :showBackButton="showBackButtonInValidate"
    title="VALIDATE LEAVE"
    :form="validateForm"
    :fields="validateFormFields"
    submitText="Submit"
    disabledButton
    @close="closeAllModal"
    @back="handleBackFromValidate"
    @submit="handleValidateSubmit"
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

  <!-- Leave Details Modal -->
  <DetailsModal
    :isOpen="isDetailsModalOpen"
    :item="selectedDetails"
    :loading="isDetailsLoading"
    :error="isDetailsError"
    title="LEAVE DETAILS"
    :fields="leaveDetailFields"
    @close="closeDetailsModal"
  >
    <!-- custom content slot  -->
    <template #content="{ item, getFieldValue }">
      <div class="space-y-4 my-5">
        <div
          v-for="field in leaveDetailFields"
          :key="field.key"
          class="grid grid-cols-1 @sm:grid-cols-[1fr_4fr] gap-1 @sm:gap-4"
        >
          <label
            class="block text-sm ps-2 @sm:ps-0 font-semibold @sm:font-bol mt-0 @sm:mt-2"
          >
            {{ field.label }}:
          </label>

          <!-- Status field with click handler -->
          <div v-if="field.key === 'status'">
            <div
              class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate flex justify-between"
            >
              <span :class="statusClassMap[item.status]">{{
                item.status || "N/A"
              }}</span>
              <i
                v-if="item.status === 'rejected'"
                class="pi pi-info-circle text-xl text-error cursor-pointer ml-2"
                @click="toggleRejectReason"
              ></i>
            </div>
            <!-- Rejection reason row (appears below status) -->
            <transition
              enter-active-class="transition-all duration-300 ease-out"
              leave-active-class="transition-all duration-200 ease-in"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-20"
              leave-from-class="opacity-100 max-h-20"
              leave-to-class="opacity-0 max-h-0"
            >
              <div
                v-if="item && item.status === 'rejected' && showRejectReason"
                class="grid grid-cols-1 gap-4 items-center overflow-hidden"
              >
                <div
                  class="text-sm bg-base-200 rounded-xl px-3 py-2 mt-2 overflow-hidden space-y-2"
                >
                  <label class="block text-sm font-bold">
                    Reason for Rejection:
                  </label>
                  <p class="truncate font-medium text-error">
                    {{ item.reject_reason || "No reason provided" }}
                  </p>
                </div>
              </div>
            </transition>
          </div>

          <!-- Other fields -->
          <div
            v-else-if="field.html"
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate"
            v-html="getFieldValue(item, field)"
          ></div>

          <p
            v-else
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate"
          >
            {{ getFieldValue(item, field) }}
          </p>
        </div>
      </div>
    </template>

    <template #custom-buttons>
      <button
        v-if="showValidateButton"
        @click="handleValidateLeave"
        class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
      >
        Validate
      </button>
    </template>
  </DetailsModal>
</template>
