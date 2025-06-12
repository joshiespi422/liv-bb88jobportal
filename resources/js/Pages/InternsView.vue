<script setup>
import { ref, h, computed, watch } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { useToast } from "../Composables/useToast";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/DetailsModal.vue";
import FormModal from "../Components/FormModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import TextInput from "../Components/forms/TextInput.vue";
import SelectInput from "../Components/forms/SelectInput.vue";
import PasswordInput from "../Components/forms/PasswordInput.vue";
import ListBox from "../Components/ListBox.vue";

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
});
// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// For adding new intern and toast
const isFormModalOpen = ref(false);
// confirmation before adding
const showConfirmModal = ref(false);

// Add form state
const addForm = useForm({
  email: "",
  name: "",
  position: "Intern",
  department_id: getDefaultDepartment(),
  school: "",
  password: "",
});

// Set the default department for the "Add" form based on user role
function getDefaultDepartment() {
  if (authUser.value.userType === "super_admin") {
    return "";
  }
  return authUser.value.department?.id || null;
}

// Form field configuration for adding new intern
const formFields = computed(() => {
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
      key: "school",
      label: "School",
      component: TextInput,
      attrs: { required: true },
    },
    {
      key: "position",
      label: "Position",
      component: TextInput,
      attrs: { readonly: true, value: "Intern" },
    },
    {
      key: "department_id",
      label: "Department",
      component:
        authUser.value.userType === "super_admin" ? SelectInput : TextInput,
      attrs:
        authUser.value.userType === "super_admin"
          ? {
              options: props.departments.map((d) => ({
                value: d.id,
                label: d.dept_name,
              })),
            }
          : {
              readonly: true,
              value: authUser.value.department?.name || "N/A",
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

// for toast messages
const toast = useToast();

// after confirmation
const submitAddForm = () => {
  showConfirmModal.value = false;

  addForm.post(route("team.interns.store"), {
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
        route("team.interns"),
        { department_id: newDeptId },
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

// the fields to be displayed in the details modal for an intern
const internDetailFields = ref([
  { key: "name", label: "Full Name" },
  { key: "position", label: "Position" },
  { key: "deptName", label: "Department" },
  { key: "school", label: "School" },
  { key: "address", label: "Address" },
  { key: "gender", label: "Gender" },
  { key: "bday", label: "Birthday", formatter: formatDate },
]);

// Function to fetch intern details
const fetchInternDetails = async (internId) => {
  isDetailsLoading.value = true;
  isDetailsModalOpen.value = true;
  selectedDetails.value = null;

  try {
    const response = await axios.get(`/teams/interns/${internId}`);
    selectedDetails.value = response.data;
  } catch (error) {
    console.error("Error fetching intern details:", error);
    selectedDetails.value = null;
    // Consider adding user-friendly error feedback
    // alert("Failed to fetch intern details. Please try again.");
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isDetailsLoading.value = false;
  }
};

// Handler for viewing intern details and details modal function
const handleViewDetails = (intern) => {
  fetchInternDetails(intern.id);
};
const closeDetailsModal = () => {
  isDetailsModalOpen.value = false;
};
const afterDetailsClose = () => {
  selectedDetails.value = null;
};

// Tanstack Table columns definition
const internTableColumns = [
  {
    accessorKey: "name",
    header: "NAME",
  },
  {
    accessorKey: "deptName",
    header: "DEPARTMENT",
  },
  {
    accessorKey: "school",
    header: "SCHOOL",
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
    <div class="flex justify-between">
      <h1 class="text-2xl font-semibold mb-6">Intern Management</h1>
      <div
        v-if="authUser?.userType === 'super_admin'"
        class="w-full md:w-72 mt-4 md:mt-0"
      >
        <ListBox
          v-model="selectedDepartment"
          :options="departmentOptions"
          placeholder="Select a department"
        />
      </div>
    </div>

    <!-- Intern Table -->
    <DataTable :data="props.interns" :columns="internTableColumns">
      <template #custom-actions>
        <button @click="handleAddNewIntern" class="btn">Add New</button>
      </template>
    </DataTable>

    <!-- Add Intern Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      :inert="showConfirmModal"
      title="Add New Intern"
      :form="addForm"
      :fields="formFields"
      submitText="Add Intern"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Confirm Intern Creation"
      :message="`Are you sure you want to add an intern?`"
      confirmText="Yes, Add Intern"
      @confirm="submitAddForm"
      @cancel="closeConfirmModal"
    />

    <!-- Intern Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      title="Intern Details"
      :fields="internDetailFields"
      @close="closeDetailsModal"
      @after-leave="afterDetailsClose"
    />
  </div>
</template>
