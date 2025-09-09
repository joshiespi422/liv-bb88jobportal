<script setup>
import { ref, h, computed } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { formatDate } from "../Composables/useDateFormatter";
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
});
// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// For adding new employee
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
  return [
    {
      key: "email",
      label: "Email Address",
      component: TextInput,
      attrs: {
        type: "email",
        required: true,
        placeholder: "h1D2y@example.com",
      },
    },
    {
      key: "name",
      label: "Name",
      component: TextInput,
      attrs: { required: true, placeholder: "John Doe" },
    },
    {
      key: "qr_code",
      label: "QR Code",
      component: TextInput,
      attrs: {
        placeholder: "02-E0001-1925",
        pattern: "^[A-Z0-9]{2}-[A-Z0-9]{5}-[A-Z0-9]{4}$",
      },
    },
    {
      key: "position",
      label: "Position",
      component: TextInput,
      attrs: { required: true, placeholder: "Software Engineer" },
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
              placeholder: "Select a department",
            }
          : {
              readonly: true,
              value: authUser.value.department?.name || "N/A",
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
        placeholder: "Select a hierarchy",
      },
    },
    {
      key: "password",
      label: "Password",
      component: PasswordInput,
      attrs: { required: true, placeholder: "Enter password" },
    },
  ];
});

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

// after confirmation
const submitAddForm = () => {
  showConfirmModal.value = false;

  addForm.post(route("team.employees.store"), {
    onSuccess: () => {
      isFormModalOpen.value = false;
      addForm.reset();
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
        route("team.employees"),
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

// Details Modal state
const isDetailsModalOpen = ref(false);
const selectedDetails = ref(null);
const isDetailsLoading = ref(false);
const isDetailsError = ref(false);

// the fields to be displayed in the details modal for an employee
const employeeDetailFields = ref([
  { key: "name", label: "Full Name" },
  { key: "email", label: "Email" },
  { key: "picture", label: "Picture" },
  { key: "position", label: "Position" },
  { key: "deptName", label: "Department" },
  { key: "hierarchy", label: "Hierarchy" },
  { key: "address", label: "Address" },
  { key: "gender", label: "Gender" },
  { key: "bday", label: "Birthday", formatter: formatDate },
]);

// Function to fetch employee details
const fetchEmployeeDetails = async (employeeId) => {
  isDetailsLoading.value = true;
  isDetailsModalOpen.value = true;
  selectedDetails.value = null;
  isDetailsError.value = false;

  try {
    const response = await axios.get(`/team/employees/${employeeId}`);
    selectedDetails.value = response.data;
  } catch (error) {
    console.error("Error fetching employee details:", error);
    selectedDetails.value = null;
    isDetailsError.value = true;
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

// computed property for custom details field separation
const customDetails = computed(() => {
  const fields = employeeDetailFields.value;
  return {
    name: fields.find((f) => f.key === "name"),
    email: fields.find((f) => f.key === "email"),
    picture: fields.find((f) => f.key === "picture"),
    others: fields.filter((f) => !["name", "email", "picture"].includes(f.key)),
  };
});

// Tanstack Table columns definition
const employeeTableColumns = [
  {
    accessorKey: "name",
    header: "NAME",
    size: 200,
  },
  {
    accessorKey: "deptName",
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
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
</script>

<template>
  <div class="p-4 md:p-8 lg:p-10 xl:p-12">
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
        <ListBox
          v-model="selectedDepartment"
          :options="departmentOptions"
          placeholder="Select a department"
        />
      </div>
    </div>

    <!-- Employee Table -->
    <DataTable
      :data="props.employees"
      :columns="employeeTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleAddNewEmployee"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Employee
        </button>
      </template>
    </DataTable>

    <!-- Add Employee Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      :inert="showConfirmModal"
      title="ADD NEW EMPLOYEE"
      :form="addForm"
      :fields="formFields"
      submitText="Add"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Confirm Employee Creation"
      message="`Are you sure you want to add an employee?`"
      iconName="pi pi-user-plus"
      iconColor="text-blue-600"
      iconBgColor="bg-blue-100"
      confirmButtonBg="bg-blue-600 hover:bg-blue-700"
      confirmText="Yes, Add Employee"
      @confirm="submitAddForm"
      @cancel="closeConfirmModal"
    />

    <!-- Employee Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      :error="isDetailsError"
      title="EMPLOYEE DETAILS"
      :fields="employeeDetailFields"
      :panel-class="'w-full max-w-xl'"
      @close="closeDetailsModal"
    >
      <!-- Custom Skeleton -->
      <template #skeleton="{ skeletonFieldCount }">
        <div class="space-y-6 mx-4 my-8">
          <div class="flex items-center gap-4">
            <div class="skeleton w-28 h-28 rounded-full"></div>
            <div class="flex-1 space-y-2">
              <div class="skeleton h-6 w-3/4"></div>
              <div class="skeleton h-4 w-full"></div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="i in skeletonFieldCount - 3"
              :key="`custom-skel-${i}`"
              class="space-y-2"
            >
              <div class="skeleton h-4 w-1/3"></div>
              <div class="skeleton h-6 w-full"></div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item, getFieldValue }">
        <div class="space-y-6 mx-4 my-8">
          <div class="flex items-center gap-4">
            <img
              :src="
                getFieldValue(item, customDetails.picture) ||
                '/profile-images/default.png'
              "
              class="w-28 h-28 rounded-full object-cover shadow-xl/20"
            />
            <div class="truncate">
              <h3 class="text-2xl font-bold truncate">
                {{ getFieldValue(item, customDetails.name) }}
              </h3>
              <p class="text-gray-500 font-semibold truncate">
                {{ getFieldValue(item, customDetails.email) }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mx-6">
            <div v-for="field in customDetails.others" :key="field.key">
              <label class="block text-sm text-neutral-500 opacity-50">
                {{ field.label }}
              </label>
              <p class="font-semibold text-shadow-md truncate text-wrap">
                {{ getFieldValue(item, field) }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </DetailsModal>
  </div>
</template>
