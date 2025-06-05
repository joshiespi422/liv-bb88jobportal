<script setup>
import { ref, h, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "../Composables/useToast";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/DetailsModal.vue";
import FormModal from "../Components/FormModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import TextInput from "../Components/forms/TextInput.vue";
import SelectInput from "../Components/forms/SelectInput.vue";
import PasswordInput from "../Components/forms/PasswordInput.vue";

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
});

// For adding new employee and toast
const page = usePage();
const isFormModalOpen = ref(false);
// confirmation before adding
const showConfirmModal = ref(false);

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

// Form field configuration for adding new employee
const formFields = computed(() => {
  const isSuperAdmin = page.props.auth.user?.user_type === "super_admin";

  return [
    {
      key: "email",
      label: "Email Address",
      component: TextInput,
      attrs: { type: "email", required: true },
    },
    {
      key: "name",
      label: "Name",
      component: TextInput,
      attrs: { required: true },
    },
    {
      key: "qr_code",
      label: "QR Code",
      component: TextInput,
      attrs: {
        placeholder: "00-00000-0000",
        pattern: "^[A-Z0-9]{2}-[A-Z0-9]{5}-[A-Z0-9]{4}$",
      },
    },
    {
      key: "position",
      label: "Position",
      component: TextInput,
      attrs: { required: true },
    },
    {
      key: "department_id",
      label: "Department",
      component: isSuperAdmin ? SelectInput : TextInput,
      attrs: isSuperAdmin
        ? {
            options: props.departments.map((d) => ({
              value: d.id,
              label: d.dept_name,
            })),
          }
        : {
            readonly: true,
            value: page.props.auth.user?.department?.name || "N/A",
          },
    },
    {
      key: "hierarchy",
      label: "Hierarchy",
      component: SelectInput,
      attrs: {
        required: true,
        options: [
          { value: "Leader", label: "Leader" },
          { value: "Member", label: "Member" },
        ],
      },
    },
    {
      key: "password",
      label: "Password",
      component: PasswordInput,
      attrs: { required: true },
    },
  ];
});

// Set default department based on user role
function getDefaultDepartment() {
  if (page.props.auth.user?.user_type === "super_admin") {
    return "";
  }
  return page.props.auth.user?.department?.id || null;
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

// for toast messages
const toast = useToast();
// Watch for flash messages
watch(
  () => page.props.flash,
  (flash) => {
    if (flash.success) {
      toast.success(flash.success);
    }
    if (flash.error) {
      toast.error(flash.error);
    }
  },
  { deep: true, immediate: true }
);

// after confirmation
const submitAddForm = () => {
  showConfirmModal.value = false;

  addForm.post(route("team.employees.store"), {
    onSuccess: () => {
      isFormModalOpen.value = false;
      addForm.reset();
    },
    onError: (errors) => {
      // Handle validation errors
      if (Object.keys(errors).length > 0) {
        // Validation errors are already handled in form component
        return;
      }

      // Handle general errors from the server
      const errorMessage = errors.message || "An unexpected error occurred";
      toast.error(errorMessage);
    },
  });
};

// Details Modal state
const isDetailsModalOpen = ref(false);
const selectedDetails = ref(null);
const isDetailsLoading = ref(false);

// Formatting function for date fields
const formatDate = (dateString) => {
  if (!dateString) return "N/A";
  return new Date(dateString).toLocaleDateString("en-US", {
    // Example formatting
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

// the fields to be displayed in the details modal for an employee
const employeeDetailFields = ref([
  { key: "name", label: "Full Name" },
  { key: "position", label: "Position" },
  { key: "dept_name", label: "Department" },
  { key: "hierarchy", label: "Hierarchy Level" },
  { key: "address", label: "Address" },
  { key: "gender", label: "Gender" },
  { key: "bday", label: "Birthday", formatter: formatDate },
]);

// Function to fetch employee details
const fetchEmployeeDetails = async (employeeId) => {
  isDetailsLoading.value = true;
  isDetailsModalOpen.value = true;
  selectedDetails.value = null;

  try {
    const response = await axios.get(`/teams/employees/${employeeId}`);
    selectedDetails.value = response.data;
  } catch (error) {
    console.error("Error fetching employee details:", error);
    selectedDetails.value = null;
    // Consider adding user-friendly error feedback
    // alert("Failed to fetch employee details. Please try again.");
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isDetailsLoading.value = false;
  }
};

// Handler for viewing employee details and details modal function
const handleViewDetails = (employee) => {
  fetchEmployeeDetails(employee.id);
};
const closeDetailsModal = () => {
  isDetailsModalOpen.value = false;
};
const afterDetailsClose = () => {
  selectedDetails.value = null;
};

// Tanstack Table columns definition
const employeeTableColumns = [
  {
    accessorKey: "name",
    header: "NAME",
  },
  {
    accessorKey: "dept_name",
    header: "DEPARTMENT",
  },
  {
    accessorKey: "hierarchy",
    header: "HIERARCHY",
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
            "px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
</script>

<template>
  <div class="p-4 md:p-8 lg:p-20">
    <h1 class="text-2xl font-semibold mb-6">Employees</h1>

    <DataTable :data="props.employees" :columns="employeeTableColumns">
      <template #custom-actions>
        <button @click="handleAddNewEmployee" class="btn">Add New</button>
      </template>
    </DataTable>

    <!-- Add Employee Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      :inert="showConfirmModal"
      title="Add New Employee"
      :form="addForm"
      :fields="formFields"
      submitText="Add Employee"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Confirm Employee Creation"
      :message="`Are you sure you want to add an employee?`"
      confirmText="Yes, Add Employee"
      @confirm="submitAddForm"
      @cancel="closeConfirmModal"
    />

    <!-- Employee Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      title="Employee Details"
      :fields="employeeDetailFields"
      @close="closeDetailsModal"
      @after-leave="afterDetailsClose"
    />
  </div>
</template>
