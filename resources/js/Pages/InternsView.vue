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
      key: "school",
      label: "School",
      component: TextInput,
      attrs: { required: true, placeholder: "Example School" },
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
              placeholder: "Select a department",
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
      attrs: { required: true, placeholder: "Enter password" },
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

// after confirmation
const submitAddForm = () => {
  showConfirmModal.value = false;

  addForm.post(route("team.interns.store"), {
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
const isDetailsError = ref(false);

// the fields to be displayed in the details modal for an intern
const internDetailFields = ref([
  { key: "name", label: "Full Name" },
  { key: "email", label: "Email" },
  { key: "picture", label: "Picture" },
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
  isDetailsError.value = false;

  try {
    const response = await axios.get(`/team/interns/${internId}`);
    selectedDetails.value = response.data;
  } catch (error) {
    console.error("Error fetching intern details:", error);
    selectedDetails.value = null;
    isDetailsError.value = true;
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
  isDetailsError.value = false;
};

// computed property for custom details field separation
const customDetails = computed(() => {
  const fields = internDetailFields.value;
  return {
    name: fields.find((f) => f.key === "name"),
    email: fields.find((f) => f.key === "email"),
    picture: fields.find((f) => f.key === "picture"),
    others: fields.filter((f) => !["name", "email", "picture"].includes(f.key)),
  };
});

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
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">Intern Management</h1>
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

    <!-- Intern Table -->
    <DataTable :data="props.interns" :columns="internTableColumns">
      <template #custom-actions>
        <button
          @click="handleAddNewIntern"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Intern
        </button>
      </template>
    </DataTable>

    <!-- Add Intern Modal -->
    <FormModal
      :isOpen="isFormModalOpen"
      :inert="showConfirmModal"
      title="ADD NEW INTERN"
      :form="addForm"
      :fields="formFields"
      submitText="Add"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

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
      @confirm="submitAddForm"
      @cancel="closeConfirmModal"
    />

    <!-- Intern Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      :error="isDetailsError"
      title="INTERN DETAILS"
      :fields="internDetailFields"
      :panel-class="'w-full max-w-lg'"
      custom-skeleton
      custom-content
      @close="closeDetailsModal"
      @after-leave="afterDetailsClose"
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
            <div>
              <h3 class="text-2xl font-bold">
                {{ getFieldValue(item, customDetails.name) }}
              </h3>
              <p class="text-gray-500 font-semibold">
                {{ getFieldValue(item, customDetails.email) }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mx-6">
            <div v-for="field in customDetails.others" :key="field.key">
              <label class="block text-sm text-neutral-500 opacity-50">
                {{ field.label }}
              </label>
              <p class="font-semibold text-shadow-md">
                {{ getFieldValue(item, field) }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </DetailsModal>
  </div>
</template>
