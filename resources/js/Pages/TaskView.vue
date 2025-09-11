<script setup>
import { ref, h, computed, reactive, watch } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import {
  shortDateTime,
  longDate,
  longDateTime,
} from "../Composables/useDateFormatter";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import ListBox from "../Components/ListBox.vue";
import DetailsModal from "../Components/DetailsModal.vue";
import FormModal from "../Components/FormModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import TextInput from "../Components/forms/TextInput.vue";
import SelectInput from "../Components/forms/SelectInput.vue";
import FileInput from "../Components/forms/FileInput.vue";
import ComboBox from "../Components/forms/ComboBox.vue";
import TextArea from "../Components/forms/TextArea.vue";

const props = defineProps({
  tasks: {
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

// State for modals for forms
const isUpdateModalOpen = ref(false);
const isValidateModalOpen = ref(false);
const isNewTaskModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);
// confirmation before updating
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

// update form state
const updateTaskForm = useForm({
  title: "",
  description: "",
  link: "",
  attachment: null,
  status: "",
});
// validate form state
const validateTaskForm = useForm({
  status: "",
  revise_reason: "",
});
// new task form state
const assigneesList = ref([]);
const projectsList = ref([]);
const newTaskForm = useForm({
  title: "",
  description: "",
  collateral: "",
  department_id: getDefaultDepartment(),
  project: "",
  assignees: [],
  deadline: "",
  priority: "",
  type: props.currentType,
});
// add comment form state
const commentForm = useForm({
  message: "",
  commentable_id: null,
  commentable_type: "App\\Models\\Task",
});

// Form field configuration for adding new task
const newTaskFormFields = computed(() => {
  return [
    {
      key: "title",
      label: "Task Name",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Task" },
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
              required: true,
            }
          : {
              readonly: true,
              value: authUser.value.department?.name || "N/A",
            },
    },
    {
      key: "collateral",
      label: "Collateral",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Collateral" },
    },
    {
      key: "assignees",
      label: "Assignees",
      component: ComboBox,
      attrs: {
        multiple: true,
        options: assigneesList.value,
        placeholder: "Select Assignees",
      },
    },
    {
      key: "project",
      label: "Project (optional)",
      component: ComboBox,
      attrs: {
        options: projectsList.value,
        placeholder: "Select a project",
      },
    },
    {
      key: "deadline",
      label: "Deadline",
      component: TextInput,
      attrs: { type: "date", required: true, min: today.value },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },

    {
      key: "priority",
      label: "Priority",
      component: SelectInput,
      attrs: {
        required: true,
        placeholder: "Select Priority",
        options: [
          { value: "high", label: "High" },
          { value: "medium", label: "Medium" },
          { value: "low", label: "Low" },
        ],
      },
    },
  ];
});
// Set the default department for the "New Task" form based on user role
function getDefaultDepartment() {
  if (authUser.value.userType === "super_admin") {
    return "";
  }
  return authUser.value.department?.id || null;
}
// for min attribute deadline
const today = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
});
// fetch assignable users for new task
const fetchAssigneesList = async (departmentId, type) => {
  if (!departmentId) {
    assigneesList.value = [];
    return;
  }
  try {
    // Use the route() helper from Ziggy
    const response = await axios.get(
      route("task.assignees", {
        department: departmentId,
        type: type,
      })
    );
    assigneesList.value = response.data;
  } catch (error) {
    console.error("Failed to fetch assignable users:", error);
    assigneesList.value = [];
  }
};
const fetchProjectsList = async (departmentId) => {
  if (!departmentId) {
    projectsList.value = [];
    return;
  }
  try {
    // Use the route() helper from Ziggy
    const response = await axios.get(
      route("task.projects", {
        department: departmentId,
      })
    );
    projectsList.value = response.data;
  } catch (error) {
    console.error("Failed to fetch projects:", error);
    projectsList.value = [];
  }
};
// Watch for changes in department_id to fetch new assignees and projects
watch(
  () => newTaskForm.department_id,
  async (newDeptId) => {
    if (authUser.value.userType === "super_admin" && newDeptId) {
      newTaskForm.assignees = [];
      await fetchAssigneesList(newDeptId, props.currentType);
      await fetchProjectsList(newDeptId);
    }
  }
);
// Add immediate watch for non-super_admin
watch(
  () => authUser.value.department?.id,
  async (departmentId) => {
    if (authUser.value.userType !== "super_admin" && departmentId) {
      await fetchAssigneesList(departmentId, props.currentType);
      await fetchProjectsList(departmentId);
    }
  },
  { immediate: true }
);

// Form field configuration for updating task
const updateFormFields = computed(() => {
  return [
    {
      key: "task_title",
      label: "Task Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.title || "N/A",
      },
    },
    {
      key: "title",
      label: "Accomplish Name",
      component: TextInput,
      attrs: {
        required: true,
        placeholder: "Example Accomplishment",
      },
    },
    {
      key: "status",
      label: "Status",
      component: SelectInput,
      attrs: {
        required: true,
        placeholder: "Select a status",
        options: statusOptions.value,
      },
    },
    {
      key: "description",
      label: "Description",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Description" },
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
// dynamic status options
const statusOptions = computed(() => {
  if (!selectedDetails.value) return [];

  const currentStatus = selectedDetails.value.status;

  if (currentStatus === "in progress") {
    return [
      { value: "in progress", label: "Still In Progress" },
      { value: "for approval", label: "For Approval" },
    ];
  } else if (currentStatus === "for approval") {
    return [{ value: "for approval", label: "Still For Approval" }];
  } else if (currentStatus === "revision") {
    return [
      { value: "revision", label: "Still For Revision" },
      { value: "for approval", label: "For Approval" },
    ];
  }
  return [];
});

// Form field configuration for validating task
const validateFormFields = computed(() => {
  const fields = [
    {
      key: "task_title",
      label: "Task Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.title || "N/A",
      },
    },
    {
      key: "status",
      label: "Status",
      component: SelectInput,
      attrs: {
        required: true,
        placeholder: "Select a status",
        options: [
          { value: "done", label: "Mark as Done" },
          { value: "revision", label: "For Revision" },
        ],
      },
    },
  ];

  // If the selected status is 'revision', add the reason text input
  if (validateTaskForm.status === "revision") {
    fields.push({
      key: "revise_reason",
      label: "Reason for Revision",
      component: TextInput,
      attrs: {
        required: true,
        placeholder: "Please provide a reason",
      },
    });
  }

  return fields;
});

// update task modal state
const handleUpdateTask = () => {
  if (!selectedDetails.value) return;
  isUpdateModalOpen.value = true;
  isDetailsModalOpen.value = false;
};
// validate task modal state
const handleValidateTask = () => {
  if (!selectedDetails.value) return;
  isValidateModalOpen.value = true;
  isDetailsModalOpen.value = false;
};
// handle new task modal state
const handleNewTask = () => {
  isNewTaskModalOpen.value = true;
};
const closeAllModal = () => {
  isUpdateModalOpen.value = false;
  isValidateModalOpen.value = false;
  isNewTaskModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// control update back button visibility
const showBackButtonInUpdate = computed(() => {
  return isUpdateModalOpen.value && selectedDetails.value !== null;
});
// handle update back navigation
const handleBackFromUpdate = () => {
  isUpdateModalOpen.value = false;
  isDetailsModalOpen.value = true;
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

// -- Update Task Flow --
const handleUpdateSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Update Task",
    message: "Are you sure you want to update this task?",
    confirmText: "Update",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-check-square",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    updateTaskForm.post(
      route("task.update", { task: selectedDetails.value.id }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          updateTaskForm.reset();
        },
        onError: () => closeConfirmModal(),
      }
    );

  isConfirmModalOpen.value = true;
};
// -- Validate Task Flow --
const handleValidateSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Validate Task",
    message: "Are you sure you want to change the status of this task?",
    confirmText: "Validate",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-check-square",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    validateTaskForm.post(
      route("task.validate", { task: selectedDetails.value.id }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          validateTaskForm.reset();
        },
        onError: () => closeConfirmModal(),
      }
    );

  isConfirmModalOpen.value = true;
};
// -- New Task Flow --
const handleNewTaskSubmit = () => {
  const transformedData = {
    ...newTaskForm.data(),
    project: newTaskForm.project?.id || null,
    assignees: newTaskForm.assignees.map((a) => a.id),
  };
  Object.assign(confirmModalProps, {
    title: "Create New Task",
    message: "Are you sure you want to create a new task?",
    confirmText: "Create",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-check-square",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    newTaskForm
      .transform(() => transformedData)
      .post(route("task.store"), {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          newTaskForm.reset();
        },
        onError: () => closeConfirmModal(),
      });

  isConfirmModalOpen.value = true;
};
// -- Comment Flow --
const handleCommentSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Add Comment",
    message: "Are you sure you want to add this comment?",
    confirmText: "Comment",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-comment",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    commentForm.post(route("comment.store"), {
      preserveScroll: true,
      onSuccess: () => {
        commentForm.reset();
        if (selectedDetails.value) {
          fetchTaskDetails(selectedDetails.value.id);
        }
        closeConfirmModal();
      },
      onError: () => closeConfirmModal(),
    });

  isConfirmModalOpen.value = true;
};
// Handle enter key press
const handleEnterKey = (event) => {
  if (event.key === "Enter" && !event.shiftKey) {
    event.preventDefault();
    if (commentForm.message.trim()) {
      handleCommentSubmit();
    }
  }
};

// Details Modal state
const isDetailsModalOpen = ref(false);
const selectedDetails = ref(null);
const isDetailsLoading = ref(false);
const isDetailsError = ref(false);
// state for showing revision reason
const showReviseReason = ref(false);

// formatter for assignees
const formatAssignees = (assignees) => {
  if (!assignees || !Array.isArray(assignees)) return "N/A";
  return assignees.map((user) => user.name).join(", ");
};
// the fields to be displayed in the details modal for an task
const taskDetailFields = ref([
  { key: "title", label: "Task Name" },
  { key: "description", label: "Description" },
  { key: "assignees", label: "Assignees", formatter: formatAssignees },
  { key: "collateral", label: "Collaterals" },
  { key: "created_at", label: "Started", formatter: longDate },
  { key: "deadline", label: "Deadline", formatter: longDate },
  { key: "priority", label: "Priority" },
  { key: "status", label: "Status" },
]);

// Function to fetch task details
const fetchTaskDetails = async (taskId) => {
  isDetailsLoading.value = true;
  isDetailsModalOpen.value = true;
  selectedDetails.value = null;
  isDetailsError.value = false;

  try {
    const response = await axios.get(`/task/${taskId}`);
    selectedDetails.value = response.data;
    // Reset comment form with new task ID
    commentForm.commentable_id = response.data.id;
    commentForm.message = "";
  } catch (error) {
    console.error("Error fetching task details:", error);
    selectedDetails.value = null;
    isDetailsError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isDetailsLoading.value = false;
  }
};
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchTaskDetails);

// Handler for viewing task details and details modal function
const handleViewDetails = (task) => {
  showReviseReason.value = false;
  fetchTaskDetails(task.id);
};
const closeDetailsModal = () => {
  isDetailsModalOpen.value = false;
  showReviseReason.value = false;
};
const statusClassMap = {
  done: "text-success",
  revision: "text-error",
  "in progress": "text-accent",
  pending: "text-info",
};
const priorityClassMap = {
  low: "text-info",
  medium: "text-accent",
  high: "text-error",
};
function getFieldClass(field, item) {
  if (field.key === "status") {
    return statusClassMap[item.status] || "";
  }
  if (field.key === "priority") {
    return priorityClassMap[item.priority] || "";
  }
  return "";
}
const toggleReviseReason = () => {
  showReviseReason.value = !showReviseReason.value;
};

// Accomplishment Details state
const isAccomplishModalOpen = ref(false);
const selectedAccomplish = ref(null);
const isAccomplishLoading = ref(false);
const isAccomplishError = ref(false);
// Function to fetch accomplishment details
const fetchAccomplishDetails = async (accomplishmentId) => {
  isAccomplishLoading.value = true;
  isAccomplishModalOpen.value = true;
  selectedAccomplish.value = null;
  isAccomplishError.value = false;

  try {
    const response = await axios.get(`/accomplishment/${accomplishmentId}`);
    selectedAccomplish.value = response.data;
  } catch (error) {
    console.error("Error fetching accomplishment details:", error);
    isAccomplishError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isAccomplishLoading.value = false;
  }
};
// Handle back navigation from accomplishment modal
const handleBackFromAccomplish = () => {
  isAccomplishModalOpen.value = false;
  isDetailsModalOpen.value = true;
};
// Show back button in accomplishment modal
const showBackButtonInAccomplish = computed(() => {
  return isAccomplishModalOpen.value && selectedDetails.value !== null;
});
// Fields for accomplishment details modal
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
const handleViewAccomplish = (accomplishmentId) => {
  isDetailsModalOpen.value = false;
  fetchAccomplishDetails(accomplishmentId);
};
const closeAccomplishModal = () => {
  isAccomplishModalOpen.value = false;
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
        route("task"),
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
  const items = [
    { id: "active_tasks", label: "Active Tasks" },
    { id: "archived", label: "Archived" },
  ];

  if (
    isRegularTab.value ||
    isLeaderTab.value ||
    authUser.value.userType === "intern"
  ) {
    items.unshift({ id: "your_tasks", label: "Your Tasks" });
  }

  return items;
});
// handle tab navigation
function setTab(tabId) {
  if (tabId === props.activeTab) return;
  router.get(
    route("task"),
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

// Tanstack Table columns definition
const taskTableColumns = [
  {
    accessorKey: "title",
    header: "TITLE",
  },
  {
    id: "assignees",
    size: 220,
    accessorFn: (row) => row.assignees.map((a) => a.name).join(", "),
    header: "ASSIGNEES",
    cell: ({ row }) => {
      let assignees = [...row.original.assignees];

      if (!assignees || assignees.length === 0) {
        return h("span", { class: "text-gray-400 italic" }, "Unassigned");
      }

      // Move the current user to the top of the list
      const currentUserIndex = assignees.findIndex(
        (a) => a.id === authUser.value.id
      );
      if (currentUserIndex > -1) {
        const currentUser = assignees.splice(currentUserIndex, 1)[0];
        assignees.unshift(currentUser);
      }

      const visibleAssignees = assignees.slice(0, 3);
      const hiddenAssigneesCount = assignees.length - visibleAssignees.length;

      return h(
        "div",
        {
          class: "avatar-group p-1 -space-x-4 flex justify-center",
        },
        [
          ...visibleAssignees.map((assignee) =>
            h(
              "div",
              {
                class: "cursor-pointer hover:z-10 hover:scale-110",
                "data-tippy-content": assignee.name,
                key: assignee.id,
              },
              [
                h("div", { class: "avatar" }, [
                  h("div", { class: "w-10 @sm:w-12 bg-neutral" }, [
                    h("img", {
                      src: assignee.picture || "/profile-images/default.png",
                      alt: assignee.name,
                    }),
                  ]),
                ]),
              ]
            )
          ),
          hiddenAssigneesCount > 0
            ? h(
                "div",
                {
                  class:
                    "avatar cursor-pointer hover:z-10 hover:scale-110 avatar-placeholder flex-none",
                  "data-tippy-content": `${hiddenAssigneesCount} more`,
                },
                [
                  h(
                    "div",
                    { class: "w-10 @sm:w-12 bg-neutral text-neutral-content" },
                    [`+${hiddenAssigneesCount}`]
                  ),
                ]
              )
            : null,
        ]
      );
    },
  },
  {
    header: "STARTED",
    accessorFn: (row) => longDate(row.created_at),
    id: "started-date",
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
          onClick: () => handleViewDetails(row.original),
          class:
            "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];

const statusColor = {
  "in progress": "badge-accent",
  "for approval": "badge-info",
  done: "badge-success",
  revision: "badge-error",
};

const capitalizedType = computed(() => {
  if (!props.currentType) return "";
  return props.currentType.charAt(0).toUpperCase() + props.currentType.slice(1);
});

const showUpdateButton = computed(() => {
  // 1. Must not be super admin
  if (authUser.value?.userType === "super_admin") return false;

  // 2. Must have selected task details
  if (!selectedDetails.value) return false;

  // 3. Must be in "your_tasks" tab
  if (props.activeTab !== "your_tasks") return false;

  // 4. Must be not in "done" status
  if (selectedDetails.value.status === "done") return false;

  // 5. Current user must be in assignees
  const isAssignee = selectedDetails.value.assignees.some(
    (assignee) => assignee.id === authUser.value.id
  );

  return isAssignee;
});

const showValidateButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isLeader = authUser.value?.hierarchy === "Leader";
  const selectedStatus = selectedDetails.value?.status;

  // 1. Must be super admin or leader
  if (!isSuperAdmin && !isLeader) {
    return false;
  }

  // 2. Must have selected task details
  if (!selectedDetails.value) {
    return false;
  }

  // 3. Must be in "active_tasks" tab
  if (props.activeTab !== "active_tasks") {
    return false;
  }

  // 4. Must be in "for approval" status
  if (selectedStatus !== "for approval") {
    return false;
  }

  return true;
});

const showNewButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isLeader = authUser.value?.hierarchy === "Leader";

  // 1. Must be super admin or leader
  if (!isSuperAdmin && !isLeader) {
    return false;
  }

  // 2. Must be not in "archived" tab
  if (props.activeTab === "archived") {
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
        {{ capitalizedType }} Task
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
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
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

    <!-- Task Table -->
    <DataTable
      :data="props.tasks"
      :columns="taskTableColumns"
      enable-tooltips
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleNewTask"
          v-if="showNewButton"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          New Task
        </button>
      </template>
    </DataTable>

    <!-- Update Task Modal -->
    <FormModal
      :isOpen="isUpdateModalOpen"
      :inert="isConfirmModalOpen"
      :showBackButton="showBackButtonInUpdate"
      title="UPDATE TASK"
      :form="updateTaskForm"
      :fields="updateFormFields"
      submitText="Add"
      disabledButton
      @close="closeAllModal"
      @back="handleBackFromUpdate"
      @submit="handleUpdateSubmit"
    />

    <!-- Validate Task Modal -->
    <FormModal
      :isOpen="isValidateModalOpen"
      :inert="isConfirmModalOpen"
      :showBackButton="showBackButtonInValidate"
      title="VALIDATE TASK"
      :form="validateTaskForm"
      :fields="validateFormFields"
      submitText="Submit"
      disabledButton
      @close="closeAllModal"
      @back="handleBackFromValidate"
      @submit="handleValidateSubmit"
    />

    <!-- New Task Modal -->
    <FormModal
      :isOpen="isNewTaskModalOpen"
      :inert="isConfirmModalOpen"
      title="ADD NEW TASK"
      :form="newTaskForm"
      :fields="newTaskFormFields"
      submitText="Add"
      :panel-class="'w-full max-w-3xl'"
      @close="closeAllModal"
      @submit="handleNewTaskSubmit"
    >
      <template #custom-fields="{ fields, form }">
        <div class="grid grid-cols-1 @2xl:grid-cols-2 gap-4">
          <div v-for="field in fields" :key="field.key">
            <label :for="field.key" class="block text-sm font-bold ms-3">
              {{ field.label }}
            </label>
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
            <p
              v-if="form.errors[field.key]"
              class="mt-1 text-sm font-semibold text-error ms-3"
            >
              {{ form.errors[field.key] }}
            </p>
          </div>
        </div>
      </template>
    </FormModal>

    <!-- Task Details Modal -->
    <DetailsModal
      :isOpen="isDetailsModalOpen"
      :inert="isConfirmModalOpen"
      :item="selectedDetails"
      :loading="isDetailsLoading"
      :error="isDetailsError"
      title="TASK DETAILS"
      :fields="taskDetailFields"
      :panel-class="'w-full max-w-4xl'"
      @close="closeDetailsModal"
    >
      <!-- Custom Skeleton -->
      <template #skeleton="{ skeletonFieldCount }">
        <div
          class="grid grid-cols-1 @2xl:grid-cols-[1.5fr_2.5fr] @3xl:grid-cols-[2fr_2fr] gap-4 py-6 px-0 @2xl:px-3"
        >
          <div class="space-y-3">
            <div
              v-for="i in skeletonFieldCount"
              :key="`custom-skel-${i}`"
              class="grid grid-cols-[1fr_3fr] gap-2 items-center"
            >
              <div class="skeleton h-6 @2xl:h-8 w-full" />
              <div class="skeleton h-6 @2xl:h-8 w-full" />
            </div>
          </div>
          <div class="rounded-xl bg-base-200 p-3">
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-1" checked="checked" />
              <div class="collapse-title text-sm font-medium">
                <div class="skeleton h-6 @2xl:h-8 w-full" />
              </div>
              <div class="collapse-content space-y-1">
                <div class="skeleton h-6 @2xl:h-8 w-full" />
                <div class="skeleton h-6 @2xl:h-8 w-full" />
              </div>
            </div>
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-2" checked="checked" />
              <div class="collapse-title text-sm font-medium">
                <div class="skeleton h-6 @2xl:h-8 w-full" />
              </div>
              <div class="collapse-content space-y-1">
                <div class="skeleton h-6 @2xl:h-8 w-full" />
                <div class="skeleton h-6 @2xl:h-8 w-full" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item, getFieldValue }">
        <div
          class="grid grid-cols-1 @2xl:grid-cols-[1.5fr_2.5fr] @3xl:grid-cols-[2fr_2fr] gap-4 py-6 px-0 @2xl:px-3"
        >
          <div class="space-y-3">
            <div
              v-for="field in taskDetailFields"
              :key="field.key"
              class="grid grid-cols-1 @3xl:grid-cols-[1fr_4fr] gap-1 @3xl:gap-2"
            >
              <label class="block text-sm font-bold mt-0 @3xl:mt-2">
                {{ field.label }}
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
                    v-if="item.status === 'revision'"
                    class="pi pi-info-circle text-xl text-error cursor-pointer ml-2"
                    @click="toggleReviseReason"
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
                      item && item.status === 'revision' && showReviseReason
                    "
                    class="grid grid-cols-1 gap-4 items-center overflow-hidden"
                  >
                    <div
                      class="text-sm bg-base-200 rounded-xl px-3 py-2 mt-2 overflow-hidden space-y-2"
                    >
                      <label class="block text-sm font-bold">
                        Reason for Revision:
                      </label>
                      <p class="truncate font-medium text-error">
                        {{ item.revise_reason || "No reason provided" }}
                      </p>
                    </div>
                  </div>
                </transition>
              </div>

              <!-- Other fields -->
              <p
                v-else
                :class="[
                  'text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate',
                  getFieldClass(field, item),
                ]"
              >
                {{ getFieldValue(item, field) }}
              </p>
            </div>
          </div>
          <div class="rounded-xl bg-base-200 @sm:p-2 @3xl:p-3">
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-3" checked="checked" />
              <div class="collapse-title font-semibold">History Updates</div>
              <div class="collapse-content text-sm px-2 @sm:px-4">
                <ul
                  class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-60 list-scroll"
                  v-if="item.accomplishments && item.accomplishments.length"
                >
                  <li
                    v-for="accomplishment in item.accomplishments"
                    :key="accomplishment.id"
                    class="list-row gap-0 hover:bg-base-300 hover:cursor-pointer"
                    @click="handleViewAccomplish(accomplishment.id)"
                  >
                    <div>
                      <div class="font-semibold truncate">
                        {{ accomplishment.user_name }}
                      </div>
                      <div
                        class="text-xs uppercase font-semibold opacity-60 truncate"
                      >
                        {{ accomplishment.title }}
                      </div>
                    </div>
                  </li>
                </ul>
                <div
                  v-else
                  role="alert"
                  class="alert alert-warning alert-soft font-semibold"
                >
                  <span>No accomplishment found</span>
                </div>
              </div>
            </div>
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300 mt-1"
            >
              <input type="radio" name="my-accordion-3" />
              <div class="collapse-title font-semibold">Comments</div>
              <div class="collapse-content text-sm px-2 @sm:px-4">
                <ul
                  class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-60 list-scroll"
                >
                  <!-- Comments list -->
                  <li
                    v-for="comment in selectedDetails.comments"
                    :key="comment.id"
                    class="list-row gap-0 p-2 pe-0"
                  >
                    <div class="chat chat-start">
                      <div class="chat-image avatar">
                        <div class="w-8 @4xl:w-10 rounded-full">
                          <img
                            :src="comment.user_picture"
                            :alt="comment.user_name"
                          />
                        </div>
                      </div>

                      <div class="chat-bubble max-w-full whitespace-pre-wrap">
                        {{ comment.message }}
                        <div class="text-xs opacity-50">
                          {{ comment.user_name }} -
                          {{ shortDateTime(comment.created_at) }}
                        </div>
                      </div>
                    </div>
                  </li>

                  <div class="m-3 grid grid-cols-[4fr_1fr]">
                    <textarea
                      v-model="commentForm.message"
                      @keydown="handleEnterKey"
                      placeholder="Write a comment..."
                      class="textarea textarea-primary min-h-4 w-full textarea-sm"
                      required
                    ></textarea>
                    <div class="flex justify-center items-center">
                      <button
                        @click="handleCommentSubmit"
                        :disabled="!commentForm.message.trim()"
                        class="btn btn-sm @md:btn-md btn-circle btn-primary"
                      >
                        <i class="pi pi-send text-lg" />
                      </button>
                    </div>
                  </div>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </template>

      <template #custom-buttons>
        <button
          v-if="showUpdateButton"
          @click="handleUpdateTask"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          Update
        </button>
        <button
          v-if="showValidateButton"
          @click="handleValidateTask"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
        >
          Validate
        </button>
      </template>
    </DetailsModal>

    <!-- Accomplishment Details Modal -->
    <DetailsModal
      :isOpen="isAccomplishModalOpen"
      :item="selectedAccomplish"
      :loading="isAccomplishLoading"
      :error="isAccomplishError"
      title="ACCOMPLISHMENT DETAILS"
      :fields="accomplishDetailFields"
      @close="closeAccomplishModal"
    >
      <template #custom-buttons>
        <button
          v-if="showBackButtonInAccomplish"
          class="btn btn-sm @sm:btn-md btn-soft rounded-full me-2"
          @click="handleBackFromAccomplish"
        >
          <i class="pi pi-arrow-left me-1" /> Back
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

<style scoped>
.list-scroll::-webkit-scrollbar {
  width: 6px;
}
.list-scroll::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background-color: var(--color-green-primary-1);
}
.list-scroll::-webkit-scrollbar-track {
  margin: 6px;
  background-color: transparent;
}
</style>
