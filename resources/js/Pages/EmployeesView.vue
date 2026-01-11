<script setup>
import { ref, computed } from "vue";
import { useForm, usePage, Link } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import Department from "../Components/Department.vue";
import {
  useEmployeeFormFields,
  useUpdateFormFields,
} from "../Data/forms/employeeFormFields";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { employeeDetailFields } from "../Data/detailFields";
import { useEmployeeColumns } from "../Data/tableColumns";

// Props received from Inertia
const props = defineProps({
  employees: {
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

// For adding new employee
const isFormModalOpen = ref(false);
// For updating employee
const isUpdateModalOpen = ref(false);
// confirmation before adding
const showConfirmModal = ref(false);
const isConfirmLoading = ref(false);

// Add form state
const addForm = useForm({
  email: "",
  name: "",
  qr_code: "",
  position: "",
  department_id: getDefaultDepartment(),
  hierarchy: "",
  password: "",
});

// Update form state
const editableFields = ref({});
const updateForm = useForm({
  status: "",
  terminate_reason: "",
  qr_code: "",
  position: "",
  current_salary: "",
});

// Form field configuration for adding new employee
const formFields = useEmployeeFormFields(authUser, props);

// Set the default department for the "Add" form based on user role
function getDefaultDepartment() {
  if (authUser.value.userType === "super_admin") {
    return "";
  }
  return authUser.value.department?.id || null;
}

// Add employee form modal state
const handleAddNewEmployee = () => {
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

// after confirmation of adding
const submitAddForm = () => {
  isConfirmLoading.value = true;
  addForm.post(route("team.employees.store"), {
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
  // Filter only editable fields to send to the backend
  const dataToSubmit = {};
  Object.keys(editableFields.value).forEach((key) => {
    if (editableFields.value[key]) {
      dataToSubmit[key] = updateForm[key];
    }
  });

  updateForm
    .transform(() => dataToSubmit)
    .patch(
      route("team.employees.update", { employee: selectedEmployee.value.id }),
      {
        onSuccess: () => {
          isUpdateModalOpen.value = false;
          editableFields.value = {};
          updateForm.reset();
        },
        onFinish: () => {
          closeConfirmModal();
          isConfirmLoading.value = false;
        },
      }
    );
};

// tab handling navigation
const tabs = computed(() => {
  const items = [
    { id: "active", label: "Active" },
    { id: "separated", label: "Separated" },
  ];

  return items;
});

// -- Employee Details Logic --
const {
  isOpen: isEmployeeModalOpen,
  isLoading: isEmployeeLoading,
  isError: isEmployeeError,
  data: selectedEmployee,
  open: fetchEmployeeDetails,
  close: closeEmployeeModal,
} = useDetailsModal({ baseUrl: "/team/employees" });
const showReasonForStatus = ref(null);

// Handler for viewing employee details and details modal function
const handleViewDetails = (employee) => {
  showReasonForStatus.value = null;
  fetchEmployeeDetails(employee.id);
};
// Show reason for status if available
const reasonConfig = {
  terminated: {
    reasonKey: "terminate_reason",
    label: "Reason for Terminating:",
  },
};
const currentReasonConfig = computed(() => {
  if (!showReasonForStatus.value) {
    return null;
  }
  return reasonConfig[showReasonForStatus.value];
});
const toggleReason = (status) => {
  if (reasonConfig[status]) {
    showReasonForStatus.value =
      showReasonForStatus.value === status ? null : status;
  }
};

// Form field configuration for updating employee
const updateFormFields = useUpdateFormFields(
  updateForm,
  selectedEmployee,
  editableFields
);
// toggle editable fields
const toggleEdit = (key) => {
  editableFields.value[key] = !editableFields.value[key];

  // If we just disabled the field, reset its value to the original
  if (!editableFields.value[key]) {
    updateForm[key] = selectedEmployee.value[key] || "";
    updateForm.clearErrors(key);
  }
};
// update employee modal state
const handleUpdateEmployee = () => {
  if (!selectedEmployee.value) return;
  // Pre-populate the form with current employee data
  updateForm.position = selectedEmployee.value.position || "";
  updateForm.qr_code = selectedEmployee.value.qr_code || "";
  updateForm.current_salary = selectedEmployee.value.current_salary || "";
  // Reset editable states whenever we open the modal
  editableFields.value = {};

  isUpdateModalOpen.value = true;
  isEmployeeModalOpen.value = false;
};
// control update back button visibility
const showBackButtonInUpdate = computed(() => {
  return isUpdateModalOpen.value && selectedEmployee.value !== null;
});
// handle update back navigation
const handleBackFromUpdate = () => {
  isUpdateModalOpen.value = false;
  isEmployeeModalOpen.value = true;
};
const handleUpdateSubmit = () => {
  showConfirmModal.value = true;
};
// disable submit button in update form
const isUpdateDisabled = computed(() => {
  return !Object.keys(editableFields.value).some(
    (key) => editableFields.value[key]
  );
});

// computed property for custom details field separation
const customDetails = computed(() => {
  const fields = employeeDetailFields;
  return {
    name: fields.find((f) => f.key === "name"),
    email: fields.find((f) => f.key === "email"),
    picture: fields.find((f) => f.key === "picture"),
    others: fields.filter((f) => !["name", "email", "picture"].includes(f.key)),
  };
});

// Tanstack Table columns definition
const employeeTableColumns = useEmployeeColumns({ handleViewDetails });

const showAddButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isLeader = authUser.value?.hierarchy === "Leader";

  // 1. Must be super admin
  if (!isSuperAdmin && !isLeader) {
    return false;
  }
  // 2. Must be in "active" tab
  if (props.activeTab !== "active") {
    return false;
  }

  return true;
});
const showUpdateButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";

  // 1. Must be super admin
  if (!isSuperAdmin) {
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
  <Head title="Employee" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Employee Management
      </h1>
      <div
        v-if="authUser?.userType === 'super_admin'"
        class="w-52 md:w-60 lg:w-72"
      >
        <Department
          :departments="props.departments"
          :current-department-id="props.currentDepartmentId"
          :auth-user="authUser"
          route-name="team.employees"
        />
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in tabs"
        :key="tab.id"
        :href="route('team.employees', { ...route().params, tab: tab.id })"
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

    <!-- Employee Table -->
    <DataTable
      :data="props.employees"
      :columns="employeeTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          v-if="showAddButton"
          @click="handleAddNewEmployee"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Employee
        </button>
      </template>
    </DataTable>

    <!-- Add Employee Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      title="ADD NEW EMPLOYEE"
      :form="addForm"
      :fields="formFields"
      submitText="Add"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    >
      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="showConfirmModal"
        title="Confirm Employee Creation"
        message="Are you sure you want to add an employee?"
        iconName="pi pi-user-plus"
        iconColor="text-blue-600"
        iconBgColor="bg-blue-100"
        confirmButtonBg="bg-blue-600 hover:bg-blue-700"
        confirmText="Yes, Add Employee"
        :loading="isConfirmLoading"
        @confirm="submitAddForm"
        @cancel="closeConfirmModal"
      />
    </FormModal>

    <!-- Update Employee Modal -->
    <FormModal
      :isOpen="isUpdateModalOpen"
      :showBackButton="showBackButtonInUpdate"
      title="UPDATE EMPLOYEE"
      :form="updateForm"
      :fields="updateFormFields"
      submitText="Submit"
      disabledButton
      :confirm-disabled="isUpdateDisabled"
      @close="isUpdateModalOpen = false"
      @back="handleBackFromUpdate"
      @submit="handleUpdateSubmit"
    >
      <template #custom-fields="{ fields, form }">
        <div v-for="field in fields" :key="field.key">
          <label :for="field.key" class="block text-sm font-bold ms-3">
            {{ field.label }}
          </label>
          <div class="flex items-center gap-2">
            <div class="w-full">
              <component
                :is="field.component"
                :id="field.key"
                v-bind="field.attrs"
                v-model="form[field.key]"
                :class="{
                  'ring-error': form.errors[field.key],
                  'focus:ring-indigo-600': !form.errors[field.key],
                  'w-full': true,
                }"
                @change="form.clearErrors(field.key)"
              />
            </div>
            <i
              v-if="
                field.key !== 'employee_name' &&
                field.key !== 'terminate_reason'
              "
              @click="toggleEdit(field.key)"
              :class="[
                'text-xl cursor-pointer ml-2 transition-colors',
                editableFields[field.key]
                  ? 'pi pi-times text-error'
                  : 'pi pi-pen-to-square text-blue-500',
              ]"
            ></i>
          </div>
          <p
            v-if="form.errors[field.key]"
            class="mt-1 text-sm font-semibold text- ms-3"
          >
            {{ form.errors[field.key] }}
          </p>
        </div>
      </template>

      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="showConfirmModal"
        title="Confirm Employee Update"
        message="Are you sure you want to update this employee?"
        iconName="pi pi-user-edit"
        iconColor="text-blue-600"
        iconBgColor="bg-blue-100"
        confirmButtonBg="bg-blue-600 hover:bg-blue-700"
        confirmText="Yes, Update Employee"
        :loading="isConfirmLoading"
        @confirm="submitUpdateForm"
        @cancel="closeConfirmModal"
      />
    </FormModal>

    <!-- Employee Details Modal -->
    <DetailsModal
      :isOpen="isEmployeeModalOpen"
      :item="selectedEmployee"
      :loading="isEmployeeLoading"
      :error="isEmployeeError"
      title="EMPLOYEE DETAILS"
      :fields="employeeDetailFields"
      :panel-class="'w-full max-w-xl'"
      @close="closeEmployeeModal(), (showReasonForStatus = false)"
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
              <div class="flex items-center justify-between">
                <p
                  class="font-semibold text-sm @md:text-base text-shadow-md truncate text-wrap"
                >
                  {{ getFieldValue(item, field) }}
                </p>
                <i
                  v-if="field.key === 'status' && reasonConfig[item.status]"
                  class="pi pi-info-circle text-xl text-error cursor-pointer ml-2"
                  @click="toggleReason(item.status)"
                ></i>
              </div>
              <!-- Revision reason row (appears below status) -->
              <transition
                enter-active-class="transition-all duration-300 ease-out"
                leave-active-class="transition-all duration-200 ease-in"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-20"
                leave-from-class="opacity-100 max-h-20"
                leave-to-class="opacity-0 max-h-0"
              >
                <div
                  v-if="
                    showReasonForStatus === item.status &&
                    currentReasonConfig &&
                    field.key === 'status'
                  "
                  class="grid grid-cols-1 -ms-[105%] items-center overflow-hidden"
                >
                  <div
                    class="text-sm bg-base-200 rounded-xl px-3 py-2 mt-2 space-y-2"
                  >
                    <label class="block font-bold">
                      {{ currentReasonConfig.label }}
                    </label>
                    <p class="truncate font-medium text-error">
                      {{
                        item[currentReasonConfig.reasonKey] ||
                        "No reason provided"
                      }}
                    </p>
                  </div>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </template>

      <template #custom-buttons>
        <button
          v-if="showUpdateButton"
          @click="handleUpdateEmployee"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          Update
        </button>
      </template>
    </DetailsModal>
  </div>
</template>
