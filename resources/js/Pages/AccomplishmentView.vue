<script setup>
import { ref, h, computed, reactive } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { formatDate, longDateTime } from "../Composables/useDateFormatter";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import ListBox from "../Components/ListBox.vue";
import DetailsModal from "../Components/DetailsModal.vue";
import FormModal from "../Components/FormModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import TextInput from "../Components/forms/TextInput.vue";
import FileInput from "../Components/forms/FileInput.vue";

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
// for notification click
const { onMountedHandleParameter } = useUrlParameter();

// For editing an accomplishment
const isFormModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);
// confirmation before editing
const isConfirmModalOpen = ref(false);

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

// Form field configuration for editing an accomplishment
const editFormFields = computed(() => {
  return [
    {
      key: "task_title",
      label: "Task Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.task_title || "N/A",
      },
    },
    {
      key: "user_name",
      label: "From",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.user_name || "N/A",
      },
    },
    {
      key: "title",
      label: "Accomplish Name",
      component: TextInput,
      attrs: { disabled: true, value: selectedDetails.value?.title || "N/A" },
    },
    {
      key: "description",
      label: "Description",
      component: TextInput,
      attrs: {
        required: true,
        placeholder: "Example Description",
      },
    },
    {
      key: "link",
      label: "Reference Link (optional)",
      component: TextInput,
      attrs: { placeholder: "https://example.com" },
    },
    {
      key: "attachment",
      label: "Attachment (optional)",
      component: FileInput,
      attrs: {
        accept: ".pdf,.doc,.docx,.jpg,.jpeg,.png",
      },
    },
  ];
});

// edit accomplishment modal state
const handleEditAccomplish = () => {
  if (!selectedDetails.value) return;
  isFormModalOpen.value = true;
  isDetailsModalOpen.value = false;
};
const closeAllModal = () => {
  isFormModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// control edit back button visibility
const showBackButtonInEdit = computed(() => {
  return isFormModalOpen.value && selectedDetails.value !== null;
});
// handle edit back navigation
const handleBackFromEdit = () => {
  isFormModalOpen.value = false;
  isDetailsModalOpen.value = true;
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

  pendingAction.value = () =>
    editAccomplishForm.post(
      route("accomplishment.update", selectedDetails.value.id),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          editAccomplishForm.reset();
        },
        onError: () => {
          closeConfirmModal();
        },
      }
    );

  isConfirmModalOpen.value = true;
};

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

// Function to fetch accomplishment details
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
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchAccomplishDetails);

// Handler for viewing accomplishment details and details modal function
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
  const items = [{ id: "all_accomplishments", label: "All Accomplishments" }];

  if (
    isRegularTab.value ||
    isLeaderTab.value ||
    authUser.value.userType === "intern"
  ) {
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
      accessorFn: (row) => longDateTime(row.created_at),
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
  if (!selectedDetails.value) {
    return false;
  }

  // 3. Must be in "your_accomplishments" tab
  if (props.activeTab !== "your_accomplishments") {
    return false;
  }

  // 4. Must be his own accomplishment
  if (selectedDetails.value.user?.id !== authUser.value?.id) {
    return false;
  }

  // 5. Must be in the same date as today
  const formattedDate = formatDate(
    selectedDetails.value.created_at,
    "yyyy-MM-dd"
  );
  if (formattedDate !== today.value) {
    return false;
  }

  return true;
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
    />

    <!-- Edit Accomplishment Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      :inert="isConfirmModalOpen"
      :showBackButton="showBackButtonInEdit"
      title="EDIT ACCOMPLISHMENT"
      :form="editAccomplishForm"
      :fields="editFormFields"
      submitText="Submit"
      disabledButton
      @close="closeAllModal"
      @back="handleBackFromEdit"
      @submit="handleEditSubmit"
    />

    <!-- Accomplishment Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      :error="isDetailsError"
      title="ACCOMPLISHMENT DETAILS"
      :fields="accomplishDetailFields"
      @close="closeDetailsModal"
    >
      <template #custom-buttons>
        <button
          v-if="showEditButton"
          @click="handleEditAccomplish"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          Edit
        </button>
      </template>
    </DetailsModal>

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="isConfirmModalOpen"
      v-bind="confirmModalProps"
      @cancel="closeConfirmModal"
      @confirm="executeConfirm"
    />
  </div>
</template>
