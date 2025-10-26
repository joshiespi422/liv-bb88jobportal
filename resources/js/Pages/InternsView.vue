<script setup>
import { ref, computed } from "vue";
import { useForm, usePage, router, Link } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import Department from "../Components/Department.vue";
import {
  useInternFormFields,
  useUpdateFormFields,
} from "../Data/forms/internFormFields";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { internDetailFields } from "../Data/detailFields";
import { useInternColumns } from "../Data/tableColumns";

// Props received from Inertia
const props = defineProps({
  interns: {
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

// For adding new intern and toast
const isFormModalOpen = ref(false);
// For updating intern
const isUpdateModalOpen = ref(false);
// confirmation before adding
const showConfirmModal = ref(false);
const isConfirmLoading = ref(false);

// Add form state
const addForm = useForm({
  email: "",
  name: "",
  position: "Intern",
  department_id: getDefaultDepartment(),
  school: "",
  password: "",
});

// Update form state
const updateForm = useForm({
  status: "",
  qr_code: "",
  position: "",
  hierarchy: "",
});

// Set the default department for the "Add" form based on user role
function getDefaultDepartment() {
  if (authUser.value.userType === "super_admin") {
    return "";
  }
  return authUser.value.department?.id || null;
}

// Form field configuration for adding new intern
const formFields = useInternFormFields(authUser, props);

// Add intern form modal state
const handleAddNewIntern = () => {
  isFormModalOpen.value = true;
};
const closeFormModal = () => {
  isFormModalOpen.value = false;
  showConfirmModal.value = false;
};
const handleFormSubmit = () => {
  showConfirmModal.value = true;
};
const closeConfirmModal = () => {
  showConfirmModal.value = false;
};

// after confirmation
const submitAddForm = () => {
  isConfirmLoading.value = true;
  addForm.post(route("team.interns.store"), {
    onSuccess: () => {
      isFormModalOpen.value = false;
      addForm.reset();
    },
    onFinish: () => {
      closeConfirmModal();
      setTimeout(() => {
        isConfirmLoading.value = false;
      }, 500);
    },
  });
};

// after confirmation for updating
const submitUpdateForm = () => {
  isConfirmLoading.value = true;
  updateForm.patch(
    route("team.interns.update", { intern: selectedIntern.value.id }),
    {
      onSuccess: () => {
        isUpdateModalOpen.value = false;
        updateForm.reset();
      },
      onFinish: () => {
        closeConfirmModal();
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    }
  );
};

// tab handling navigation
const tabs = computed(() => {
  const items = [
    { id: "ongoing", label: "Ongoing" },
    { id: "completed", label: "Completed" },
  ];

  return items;
});

// -- Intern Details Logic --
const {
  isOpen: isInternModalOpen,
  isLoading: isInternLoading,
  isError: isInternError,
  data: selectedIntern,
  open: fetchInternDetails,
  close: closeInternModal,
} = useDetailsModal({ baseUrl: "/team/interns" });

// Handler for viewing intern details and details modal function
const handleViewDetails = (intern) => {
  fetchInternDetails(intern.id);
};

// Form field configuration for updating intern
const updateFormFields = useUpdateFormFields(updateForm, selectedIntern);
// update intern modal state
const handleUpdateIntern = () => {
  if (!selectedIntern.value) return;
  isUpdateModalOpen.value = true;
  isInternModalOpen.value = false;
};
// control update back button visibility
const showBackButtonInUpdate = computed(() => {
  return isUpdateModalOpen.value && selectedIntern.value !== null;
});
// handle update back navigation
const handleBackFromUpdate = () => {
  isUpdateModalOpen.value = false;
  isInternModalOpen.value = true;
};
const handleUpdateSubmit = () => {
  showConfirmModal.value = true;
};

// computed property for custom details field separation
const customDetails = computed(() => {
  const fields = internDetailFields;
  return {
    name: fields.find((f) => f.key === "name"),
    email: fields.find((f) => f.key === "email"),
    picture: fields.find((f) => f.key === "picture"),
    others: fields.filter((f) => !["name", "email", "picture"].includes(f.key)),
  };
});

// Tanstack Table columns definition
const internTableColumns = useInternColumns({ handleViewDetails });

const showAddButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isLeader = authUser.value?.hierarchy === "Leader";

  // 1. Must be super admin
  if (!isSuperAdmin && !isLeader) {
    return false;
  }
  // 2. Must be in "ongoing" tab
  if (props.activeTab !== "ongoing") {
    return false;
  }

  return true;
});
const showUpdateButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isLeader = authUser.value?.hierarchy === "Leader";

  // 1. Must be super admin or leader
  if (!isSuperAdmin && !isLeader) {
    return false;
  }
  // 2. Must have selected employee
  if (!selectedEmployee.value) {
    return false;
  }

  return true;
});
</script>

<template>
  <Head title="Intern" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Intern Management
      </h1>
      <div
        v-if="authUser?.userType === 'super_admin'"
        class="w-52 md:w-60 lg:w-72"
      >
        <Department
          :departments="props.departments"
          :current-department-id="props.currentDepartmentId"
          :auth-user="authUser"
          route-name="team.interns"
        />
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in tabs"
        :key="tab.id"
        :href="route('team.interns', { ...route().params, tab: tab.id })"
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

    <!-- Intern Table -->
    <DataTable
      :data="props.interns"
      :columns="internTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          v-if="showAddButton"
          @click="handleAddNewIntern"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Intern
        </button>
      </template>
    </DataTable>

    <!-- Add Intern Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      title="ADD NEW INTERN"
      :form="addForm"
      :fields="formFields"
      submitText="Add"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    >
      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="showConfirmModal"
        title="Confirm Intern Creation"
        message="`Are you sure you want to add an intern?`"
        iconName="pi pi-user-plus"
        iconColor="text-blue-600"
        iconBgColor="bg-blue-100"
        confirmButtonBg="bg-blue-600 hover:bg-blue-700"
        confirmText="Yes, Add Intern"
        :loading="addForm.processing"
        @confirm="submitAddForm"
        @cancel="closeConfirmModal"
      />
    </FormModal>

    <!-- Update Intern Modal -->
    <FormModal
      :isOpen="isUpdateModalOpen"
      :showBackButton="showBackButtonInUpdate"
      title="UPDATE INTERN"
      :form="updateForm"
      :fields="updateFormFields"
      submitText="Submit"
      disabledButton
      @close="isUpdateModalOpen = false"
      @back="handleBackFromUpdate"
      @submit="handleUpdateSubmit"
    >
      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="showConfirmModal"
        title="Confirm Intern Update"
        message="Are you sure you want to update this intern?"
        iconName="pi pi-user-edit"
        iconColor="text-blue-600"
        iconBgColor="bg-blue-100"
        confirmButtonBg="bg-blue-600 hover:bg-blue-700"
        confirmText="Yes, Update Intern"
        :loading="isConfirmLoading"
        @confirm="submitUpdateForm"
        @cancel="closeConfirmModal"
      />
    </FormModal>

    <!-- Intern Details Modal -->
    <DetailsModal
      :isOpen="isInternModalOpen"
      :item="selectedIntern"
      :loading="isInternLoading"
      :error="isInternError"
      title="INTERN DETAILS"
      :fields="internDetailFields"
      :panel-class="'w-full max-w-xl'"
      @close="closeInternModal"
    >
      <!-- Custom Skeleton -->
      <template #skeleton="{ skeletonFieldCount }">
        <div class="space-y-6 mx-4 my-8">
          <div class="flex flex-col @md:flex-row items-center gap-4">
            <div class="flex-none skeleton w-28 h-28 rounded-full"></div>
            <div class="w-full md:flex-1 space-y-2">
              <div class="skeleton h-6 w-3/4 mx-auto @md:mx-0"></div>
              <div class="skeleton h-4 w-11/12 mx-auto @md:mx-0"></div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="i in skeletonFieldCount - 3"
              :key="`custom-skel-${i}`"
              class="space-y-2"
            >
              <div class="skeleton h-4 w-1/2"></div>
              <div class="skeleton h-6 w-full"></div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item, getFieldValue }">
        <div class="space-y-6 mx-4 my-8">
          <div
            class="flex flex-col @md:flex-row text-center @md:text-start items-center gap-4"
          >
            <img
              :src="
                getFieldValue(item, customDetails.picture) ||
                '/profile-images/default.png'
              "
              class="w-24 h-24 @md:w-28 @md:h-28 rounded-full object-cover shadow-xl/20"
            />
            <div class="truncate">
              <h3 class="text-xl @md:text-2xl font-bold truncate">
                {{ getFieldValue(item, customDetails.name) }}
              </h3>
              <p
                class="text-sm @md:text-base text-gray-500 font-semibold truncate"
              >
                {{ getFieldValue(item, customDetails.email) }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mx-0 @md:mx-6">
            <div v-for="field in customDetails.others" :key="field.key">
              <label class="block text-sm text-neutral-500 opacity-50">
                {{ field.label }}
              </label>
              <p
                class="font-semibold text-sm @md:text-base text-shadow-md truncate text-wrap"
              >
                {{ getFieldValue(item, field) }}
              </p>
            </div>
          </div>
        </div>
      </template>

      <template #custom-buttons>
        <button
          v-if="showUpdateButton"
          @click="handleUpdateIntern"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          Update
        </button>
      </template>
    </DetailsModal>
  </div>
</template>
