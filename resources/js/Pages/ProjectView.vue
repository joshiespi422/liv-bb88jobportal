<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, useForm } from "@inertiajs/vue3";
import {
  longDate,
  longDateTime,
  shortDateTime,
} from "../Composables/useDateFormatter";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/DetailsModal.vue";
import FormModal from "../Components/FormModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import TextInput from "../Components/forms/TextInput.vue";
import ComboBox from "../Components/forms/ComboBox.vue";
import TextArea from "../Components/forms/TextArea.vue";

const props = defineProps({
  projects: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);
// for notification click
const { onMountedHandleParameter } = useUrlParameter();

// State for modals for forms
const isNewProjectModalOpen = ref(false);
const isAddIssueModalOpen = ref(false);
const isResolveModalOpen = ref(false);
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

// New project form state
const newProjectForm = useForm({
  title: "",
  description: "",
  client: "",
  deadline: "",
  department_ids: [],
});

// add issue form state
const projectsList = ref([]);
const addIssueForm = useForm({
  project: "",
  title: "",
  description: "",
});

// resolve issue form state
const resolveIssueForm = useForm({
  solution: "",
});

// add comment form state
const commentForm = useForm({
  message: "",
  commentable_id: null,
  commentable_type: "App\\Models\\Task",
});

// Form field configuration for adding new project
const newProjectFormFields = computed(() => {
  return [
    {
      key: "department_ids",
      label: "Departments",
      component: ComboBox,
      attrs: {
        options: props.departments,
        placeholder: "Select Departments",
        multiple: true,
      },
    },
    {
      key: "title",
      label: "Project",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Project" },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },
    {
      key: "client",
      label: "Client",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Client" },
    },
    {
      key: "deadline",
      label: "Deadline",
      component: TextInput,
      attrs: { type: "date", required: true, min: today.value },
    },
  ];
});
// for min attribute deadline
const today = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
});

// form field configuration for adding new issue
const addIssueFormFields = computed(() => {
  return [
    {
      key: "project",
      label: "Project",
      component: ComboBox,
      attrs: {
        options: projectsList.value,
        placeholder: "Select Project",
      },
    },
    {
      key: "title",
      label: "Issue",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Issue" },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },
  ];
});
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
// Add immediate watch for non-super_admin
watch(
  () => authUser.value.department?.id,
  async (departmentId) => {
    if (authUser.value.userType !== "super_admin" && departmentId) {
      await fetchProjectsList(departmentId);
    }
  },
  { immediate: true }
);

// Form field configuration for resolving issue
const resolveIssueFormFields = computed(() => {
  return [
    {
      key: "issue_title",
      label: "Issue Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedIssue.value?.title || "N/A",
      },
    },
    {
      key: "solution",
      label: "Solution",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Solution" },
    },
  ];
});

// -- Add Issue Flow --
const handleAddIssueSubmit = () => {
  const transformedData = {
    ...addIssueForm.data(),
    project_id: addIssueForm.project?.id || null,
  };

  Object.assign(confirmModalProps, {
    title: "Create New Issue",
    message: "Are you sure you want to create a new issue?",
    confirmText: "Create",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-check-square",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    addIssueForm
      .transform(() => transformedData)
      .post(route("project.issue.store"), {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          addIssueForm.reset();
        },
        onError: () => closeConfirmModal(),
      });

  isConfirmModalOpen.value = true;
};
// handle add issue
const handleAddIssue = () => {
  isAddIssueModalOpen.value = true;
};

// -- Resolve Issue Flow --
const handleResolveIssueSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Resolve Issue",
    message: "Are you sure you want to resolve this issue?",
    confirmText: "Resolve",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-check-square",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    resolveIssueForm.patch(
      route("project.issue.resolve", { issue: selectedIssue.value.id }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          resolveIssueForm.reset();
        },
        onError: () => closeConfirmModal(),
      }
    );
  isConfirmModalOpen.value = true;
};
// handle resolve issue
const handleResolveIssue = () => {
  if (!selectedIssue.value) return;
  isIssueDetailsOpen.value = false;
  isResolveModalOpen.value = true;
};
// control resolve back button visibility
const showBackButtonInResolve = computed(() => {
  return isResolveModalOpen.value && selectedIssue.value !== null;
});
// handle resolve back navigation
const handleBackFromResolve = () => {
  isIssueDetailsOpen.value = true;
  isResolveModalOpen.value = false;
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
        if (selectedTask.value) {
          fetchTaskDetails(selectedTask.value.id);
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

// -- New Project Flow --
const handleNewProjectSubmit = () => {
  const transformedData = {
    ...newProjectForm.data(),
    department_ids: newProjectForm.department_ids.map((a) => a.id),
  };
  Object.assign(confirmModalProps, {
    title: "Create New Project",
    message: "Are you sure you want to create a new project?",
    confirmText: "Create",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-briefcase",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () =>
    newProjectForm
      .transform(() => transformedData)
      .post(route("project.store"), {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          newProjectForm.reset();
        },
        onError: () => closeConfirmModal(),
      });

  isConfirmModalOpen.value = true;
};
// handle new project
const handleNewProject = () => {
  isNewProjectModalOpen.value = true;
};
const closeAllModal = () => {
  isNewProjectModalOpen.value = false;
  isAddIssueModalOpen.value = false;
  isResolveModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// Project Details Modal state
const isProjectDetailsOpen = ref(false);
const selectedProject = ref(null);
const isProjectLoading = ref(false);
const isProjectError = ref(false);

// formatter for departments
const formatDepartments = (departments) => {
  if (!departments || departments.length === 0) return "N/A";
  return departments.join(", ");
};
// the fields to be displayed in the project details modal for an project
const projectDetailFields = ref([
  { key: "title", label: "Project" },
  { key: "description", label: "Description" },
  { key: "client", label: "Client" },
  { key: "departments", label: "Departments", formatter: formatDepartments },
  { key: "created_at", label: "Started", formatter: longDate },
  { key: "deadline", label: "Deadline", formatter: longDate },
]);

// Function to fetch project details
const fetchProjectDetails = async (projectId) => {
  isProjectLoading.value = true;
  isProjectDetailsOpen.value = true;
  selectedProject.value = null;
  isProjectError.value = false;

  try {
    const response = await axios.get(`/project/${projectId}`);
    selectedProject.value = response.data;
  } catch (error) {
    console.error("Error fetching project details:", error);
    selectedProject.value = null;
    isProjectError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isProjectLoading.value = false;
  }
};
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchProjectDetails);

// Handler for viewing project details and details modal function
const handleViewDetails = (projectId) => {
  fetchProjectDetails(projectId);
};
const closeProjectDetails = () => {
  isProjectDetailsOpen.value = false;
};

// Issue Details Modal state
const isIssueDetailsOpen = ref(false);
const selectedIssue = ref(null);
const isIssueLoading = ref(false);
const isIssueError = ref(false);

const fetchIssueDetails = async (issueId) => {
  isIssueLoading.value = true;
  isIssueDetailsOpen.value = true;
  selectedIssue.value = null;
  isIssueError.value = false;

  try {
    const response = await axios.get(
      route("project.issue.show", { issue: issueId })
    );
    selectedIssue.value = response.data;
  } catch (error) {
    console.error("Error fetching issue details:", error);
    selectedIssue.value = null;
    isIssueError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isIssueLoading.value = false;
  }
};
// Handle back navigation from issue modal
const handleBackFromIssue = () => {
  isIssueDetailsOpen.value = false;
  isProjectDetailsOpen.value = true;
};
// Show back button in issue modal
const showBackButtonInIssue = computed(() => {
  return isIssueDetailsOpen.value && selectedIssue.value !== null;
});
// Fields for issue details modal
const issueDetailFields = ref([
  { key: "project_title", label: "Project" },
  { key: "user_name", label: "Submitted" },
  { key: "title", label: "Issue" },
  { key: "description", label: "Description" },
  { key: "status", label: "Status" },
  { key: "created_at", label: "Created At", formatter: longDate },
  { key: "solution", label: "Solution" },
]);
const handleViewIssue = (issueId) => {
  isProjectDetailsOpen.value = false;
  fetchIssueDetails(issueId);
};
const closeIssueDetails = () => {
  isIssueDetailsOpen.value = false;
};

// Task Details Modal state
const isTaskDetailsOpen = ref(false);
const selectedTask = ref(null);
const isTaskLoading = ref(false);
const isTaskError = ref(false);
// state for showing revision reason
const showReviseReason = ref(false);

const fetchTaskDetails = async (taskId) => {
  isTaskLoading.value = true;
  isTaskDetailsOpen.value = true;
  selectedTask.value = null;
  isTaskError.value = false;

  try {
    const response = await axios.get(`/task/${taskId}`);
    selectedTask.value = response.data;
    // Reset comment form with new task ID
    commentForm.commentable_id = response.data.id;
    commentForm.message = "";
  } catch (error) {
    console.error("Error fetching task details:", error);
    selectedTask.value = null;
    isTaskError.value = true;
  } finally {
    await new Promise((resolve) => setTimeout(resolve, 1000)); // Wait for 1 second
    isTaskLoading.value = false;
  }
};
// Handle back navigation from task modal
const handleBackFromTask = () => {
  isTaskDetailsOpen.value = false;
  isProjectDetailsOpen.value = true;
};
// Show back button in task modal
const showBackButtonInTask = computed(() => {
  return isTaskDetailsOpen.value && selectedTask.value !== null;
});
// formatter for assignees
const formatAssignees = (assignees) => {
  if (!assignees || !Array.isArray(assignees)) return "N/A";
  return assignees.map((user) => user.name).join(", ");
};
// the fields to be displayed in task details modal
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
const handleViewTask = (taskId) => {
  isProjectDetailsOpen.value = false;
  showReviseReason.value = false;
  fetchTaskDetails(taskId);
};
const closeTaskDetails = () => {
  isTaskDetailsOpen.value = false;
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
  isTaskDetailsOpen.value = true;
};
// Show back button in accomplishment modal
const showBackButtonInAccomplish = computed(() => {
  return isAccomplishModalOpen.value && selectedAccomplish.value !== null;
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
  isTaskDetailsOpen.value = false;
  fetchAccomplishDetails(accomplishmentId);
};
const closeAccomplishModal = () => {
  isAccomplishModalOpen.value = false;
};

// Tanstack Table columns definition
const projectTableColumns = [
  {
    accessorKey: "title",
    header: "TITLE",
  },
  {
    accessorKey: "description",
    header: "DESCRIPTION",
  },
  {
    id: "assignees",
    accessorFn: (row) => row.assignees.map((a) => a.name).join(", "),
    header: "ASSIGNEES",
  },
  {
    accessorFn: (row) => longDate(row.created_at),
    id: "started-date",
    header: "STARTED DATE",
  },
  {
    accessorFn: (row) => row.departments.join(", "),
    header: "DEPARTMENTS",
  },
];

// Assignee info
const renderAssignees = (assignees, maximum = 3) => {
  if (!assignees || assignees.length === 0) {
    return [];
  }

  // Move current user to top (same logic as table)
  let sortedAssignees = [...assignees];
  const currentUserIndex = sortedAssignees.findIndex(
    (a) => a.id === authUser.value.id
  );
  if (currentUserIndex > -1) {
    const currentUser = sortedAssignees.splice(currentUserIndex, 1)[0];
    sortedAssignees.unshift(currentUser);
  }

  const visibleAssignees = sortedAssignees.slice(0, maximum);
  const hiddenCount = sortedAssignees.length - visibleAssignees.length;

  return { visibleAssignees, hiddenCount };
};

const showResolveButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const selectedStatus = selectedIssue.value?.status;

  // 1. Must be super admin
  if (!isSuperAdmin) {
    return false;
  }

  // 2. Must have selected issue details
  if (!selectedIssue.value) {
    return false;
  }

  // 3. Must be in "pending" status
  if (selectedStatus !== "pending") {
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
        Project Management
      </h1>
    </div>

    <!-- Project Table -->
    <DataTable
      :data="props.projects"
      :columns="projectTableColumns"
      display-mode="card"
      enable-tooltips
    >
      <!-- Custom card layout -->
      <template #card-item="{ row }">
        <div
          class="card bg-gradient-to-r from-green-50 to-green-100 shadow-lg border-l-4 border-green-primary-1 hover:shadow-xl hover:border-pink-500 transition-all duration-300"
        >
          <div class="card-body">
            <!-- Date in top right -->
            <p
              class="text-xs text-end font-medium text-gray-500 -mt-3 truncate"
            >
              {{ longDate(row.created_at) }}
            </p>

            <!-- Title and Description -->
            <div class="flex flex-col">
              <h3 class="card-title font-bold text-gray-800 truncate">
                {{ row.title }}
              </h3>
              <p class="text-sm font-medium text-gray-600 truncate mb-4">
                {{ row.description }}
              </p>

              <!-- Departments as badges -->
              <div class="mb-3">
                <div
                  class="flex flex-wrap gap-2"
                  v-if="row.departments && row.departments.length > 0"
                >
                  <div
                    v-for="dept in row.departments"
                    :key="dept"
                    class="badge badge-sm @sm:badge-md badge-ghost font-semibold"
                  >
                    {{ dept }}
                  </div>
                </div>
                <span v-else class="text-gray-400 italic text-sm"
                  >No departments</span
                >
              </div>

              <!-- Bottom section with assignees and button -->
              <div class="flex justify-between items-center overflow-hidden">
                <!-- Assignees Avatar Group -->
                <div class="flex-1">
                  <div
                    v-if="row.assignees && row.assignees.length > 0"
                    class="avatar-group p-1 -space-x-3"
                  >
                    <!-- Visible assignees -->
                    <div
                      v-for="assignee in renderAssignees(row.assignees)
                        .visibleAssignees"
                      class="avatar border-0 bg-neutral w-9 h-9 @sm:w-11 @sm:h-11 cursor-pointer hover:z-10 hover:scale-110 transition-transform"
                      :data-tippy-content="assignee.name"
                    >
                      <div>
                        <img
                          :src="
                            assignee.picture || '/profile-images/default.png'
                          "
                          :alt="assignee.name"
                        />
                      </div>
                    </div>

                    <!-- Counter for hidden assignees -->
                    <div
                      v-if="renderAssignees(row.assignees).hiddenCount > 0"
                      class="avatar w-9 h-9 @sm:w-11 @sm:h-11 border-0 placeholder cursor-pointer hover:z-10 hover:scale-110 transition-transform"
                      :data-tippy-content="`${
                        renderAssignees(row.assignees).hiddenCount
                      } more assignees`"
                    >
                      <div class="bg-neutral text-neutral-content">
                        <span
                          class="font-bold flex mt-1.5 @sm:mt-2.5 justify-center"
                          >+{{
                            renderAssignees(row.assignees).hiddenCount
                          }}</span
                        >
                      </div>
                    </div>
                  </div>

                  <!-- No assignees state -->
                  <div v-else class="text-gray-400 italic text-sm">
                    Unassigned
                  </div>
                </div>

                <!-- View details button -->
                <button
                  @click="handleViewDetails(row.id)"
                  class="btn btn-sm bg-green-primary-1 rounded-full border-0 text-white hover:bg-green-primary-3"
                >
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom button -->
      <template #custom-actions>
        <button
          v-if="authUser?.userType === 'super_admin'"
          @click="handleNewProject"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          New Project
        </button>
        <button
          v-if="authUser?.userType !== 'super_admin'"
          @click="handleAddIssue"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Issue
        </button>
      </template>
    </DataTable>

    <!-- Project Details Modal -->
    <DetailsModal
      :isOpen="isProjectDetailsOpen"
      :item="selectedProject"
      :loading="isProjectLoading"
      :error="isProjectError"
      title="PROJECT DETAILS"
      :fields="projectDetailFields"
      :panel-class="'w-full max-w-4xl'"
      @close="closeProjectDetails"
    >
      <!-- Custom Skeleton -->
      <template #skeleton="{ skeletonFieldCount }">
        <div class="grid grid-cols-[2fr_2fr] gap-4 py-6 px-3">
          <div class="space-y-3">
            <div
              v-for="i in skeletonFieldCount"
              :key="`custom-skel-${i}`"
              class="grid grid-cols-[1fr_3fr] gap-2 items-center"
            >
              <div class="skeleton h-8 w-full" />
              <div class="skeleton h-8 w-full" />
            </div>
          </div>
          <div class="rounded-xl bg-base-200 p-3">
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-1" checked="checked" />
              <div class="collapse-title text-sm font-medium">
                <div class="skeleton h-8 w-full" />
              </div>
              <div class="collapse-content space-y-1">
                <div class="skeleton h-8 w-full" />
                <div class="skeleton h-8 w-full" />
              </div>
            </div>
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-2" checked="checked" />
              <div class="collapse-title text-sm font-medium">
                <div class="skeleton h-8 w-full" />
              </div>
              <div class="collapse-content space-y-1">
                <div class="skeleton h-8 w-full" />
                <div class="skeleton h-8 w-full" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item, getFieldValue }">
        <div class="grid grid-cols-[2fr_2fr] gap-4 py-6 px-3">
          <div class="space-y-3">
            <div
              v-for="field in projectDetailFields"
              :key="field.key"
              class="grid grid-cols-[1.5fr_4fr] gap-2 items-center"
            >
              <label class="block text-sm font-bold">
                {{ field.label }}
              </label>

              <p
                class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate"
              >
                {{ getFieldValue(item, field) }}
              </p>
            </div>
          </div>
          <div class="rounded-xl bg-base-200 p-3">
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-3" checked="checked" />
              <div class="collapse-title font-semibold">Tasks List</div>
              <div class="collapse-content text-sm">
                <ul
                  class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-40 list-scroll"
                  v-if="item.tasks && item.tasks.length"
                >
                  <li
                    v-for="task in item.tasks"
                    :key="task.id"
                    class="list-row hover:bg-base-300 hover:cursor-pointer"
                    @click="handleViewTask(task.id)"
                  >
                    <div>
                      <div class="font-semibold truncate">
                        {{ task.title }}
                      </div>
                      <div
                        v-if="task.assignees && task.assignees.length > 0"
                        class="avatar-group p-1 -space-x-1"
                      >
                        <div
                          v-for="assignee in renderAssignees(task.assignees, 5)
                            .visibleAssignees"
                          class="avatar w-8 h-8 border-0 bg-neutral hover:z-10 hover:-mt-1 transition-all duration-200"
                        >
                          <div>
                            <img :src="assignee.picture" />
                          </div>
                        </div>

                        <div
                          v-if="
                            renderAssignees(task.assignees, 5).hiddenCount > 0
                          "
                          class="avatar w-8 h-8 border-0 placeholder hover:z-10 hover:-mt-1 transition-all duration-200"
                        >
                          <div class="bg-neutral text-neutral-content">
                            <span class="font-bold flex mt-1.5 justify-center"
                              >+{{
                                renderAssignees(task.assignees).hiddenCount
                              }}</span
                            >
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
                <div
                  v-else
                  role="alert"
                  class="alert alert-warning alert-soft font-semibold"
                >
                  <span>No tasks found</span>
                </div>
              </div>
            </div>
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300 mt-1"
            >
              <input type="radio" name="my-accordion-3" />
              <div class="collapse-title font-semibold">Issues List</div>
              <div class="collapse-content text-sm">
                <ul
                  class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-40 list-scroll"
                  v-if="item.issues && item.issues.length"
                >
                  <li
                    v-for="issue in item.issues"
                    :key="issue.id"
                    class="list-row hover:bg-base-300 hover:cursor-pointer"
                    @click="handleViewIssue(issue.id)"
                  >
                    <div>
                      <div class="font-semibold truncate">
                        {{ issue.user_name }}
                      </div>
                      <div class="text-xs uppercase font-semibold opacity-60">
                        {{ issue.title }}
                      </div>
                    </div>
                  </li>
                </ul>
                <div
                  v-else
                  role="alert"
                  class="alert alert-warning alert-soft font-semibold"
                >
                  <span>No issues found</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </DetailsModal>

    <!-- Issue Details Modal -->
    <DetailsModal
      :isOpen="isIssueDetailsOpen"
      :item="selectedIssue"
      :loading="isIssueLoading"
      :error="isIssueError"
      title="ISSUE DETAILS"
      :fields="issueDetailFields"
      hide-close-btn
      @close="closeIssueDetails"
    >
      <template #custom-buttons>
        <button
          v-if="showBackButtonInIssue"
          class="btn btn-soft rounded-full me-2"
          @click="handleBackFromIssue"
        >
          <i class="pi pi-arrow-left me-1" /> Back
        </button>
        <button
          v-if="showResolveButton"
          @click="handleResolveIssue"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Resolve
        </button>
      </template>
    </DetailsModal>

    <!-- Task Details Modal -->
    <DetailsModal
      :isOpen="isTaskDetailsOpen"
      :inert="isConfirmModalOpen"
      :item="selectedTask"
      :loading="isTaskLoading"
      :error="isTaskError"
      title="TASK DETAILS"
      :fields="taskDetailFields"
      :panel-class="'w-full max-w-4xl'"
      @close="closeTaskDetails"
    >
      <!-- Custom Skeleton -->
      <template #skeleton="{ skeletonFieldCount }">
        <div class="grid grid-cols-[2fr_1.5fr] gap-4 py-6 px-3">
          <div class="space-y-3">
            <div
              v-for="i in skeletonFieldCount"
              :key="`custom-skel-${i}`"
              class="grid grid-cols-[1fr_3fr] gap-2 items-center"
            >
              <div class="skeleton h-8 w-full" />
              <div class="skeleton h-8 w-full" />
            </div>
          </div>
          <div class="rounded-xl bg-base-200 p-3">
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-1" checked="checked" />
              <div class="collapse-title text-sm font-medium">
                <div class="skeleton h-8 w-full" />
              </div>
              <div class="collapse-content space-y-1">
                <div class="skeleton h-8 w-full" />
                <div class="skeleton h-8 w-full" />
              </div>
            </div>
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-2" checked="checked" />
              <div class="collapse-title text-sm font-medium">
                <div class="skeleton h-8 w-full" />
              </div>
              <div class="collapse-content space-y-1">
                <div class="skeleton h-8 w-full" />
                <div class="skeleton h-8 w-full" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Content Layout -->
      <template #content="{ item, getFieldValue }">
        <div class="grid grid-cols-[2fr_2fr] gap-4 py-6 px-3">
          <div class="space-y-3">
            <div
              v-for="field in taskDetailFields"
              :key="field.key"
              class="grid grid-cols-[1fr_4fr] gap-2"
            >
              <label class="block text-sm font-bold mt-2">
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
          <div class="rounded-xl bg-base-200 p-3">
            <div
              class="collapse collapse-plus bg-base-100 border border-base-300"
            >
              <input type="radio" name="my-accordion-3" checked="checked" />
              <div class="collapse-title font-semibold">History Updates</div>
              <div class="collapse-content text-sm">
                <ul
                  class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-60 list-scroll"
                  v-if="item.accomplishments && item.accomplishments.length"
                >
                  <li
                    v-for="accomplishment in item.accomplishments"
                    :key="accomplishment.id"
                    class="list-row hover:bg-base-300 hover:cursor-pointer"
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
              <div class="collapse-content text-sm">
                <ul
                  class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-60 list-scroll"
                >
                  <!-- Comments list -->
                  <li
                    v-for="comment in selectedTask.comments"
                    :key="comment.id"
                    class="list-row p-2 pe-0"
                  >
                    <div class="chat chat-start">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
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
                      class="textarea textarea-primary min-h-4 textarea-sm"
                      required
                    ></textarea>
                    <div class="flex justify-center items-center">
                      <button
                        @click="handleCommentSubmit"
                        :disabled="!commentForm.message.trim()"
                        class="btn btn-circle btn-primary"
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
          v-if="showBackButtonInTask"
          class="btn btn-soft rounded-full me-2"
          @click="handleBackFromTask"
        >
          <i class="pi pi-arrow-left me-1" /> Back
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
          class="btn btn-soft rounded-full me-2"
          @click="handleBackFromAccomplish"
        >
          <i class="pi pi-arrow-left me-1" /> Back
        </button>
      </template>
    </DetailsModal>

    <!-- New Project Modal -->
    <FormModal
      :isOpen="isNewProjectModalOpen"
      :inert="isConfirmModalOpen"
      title="ADD PROJECT"
      :form="newProjectForm"
      :fields="newProjectFormFields"
      submitText="Add"
      @close="closeAllModal"
      @submit="handleNewProjectSubmit"
    />

    <!-- Add Issue Modal -->
    <FormModal
      :isOpen="isAddIssueModalOpen"
      :inert="isConfirmModalOpen"
      title="ADD ISSUE"
      :form="addIssueForm"
      :fields="addIssueFormFields"
      submitText="Add"
      @close="closeAllModal"
      @submit="handleAddIssueSubmit"
    />

    <!-- Resolve Issue Modal -->
    <FormModal
      :isOpen="isResolveModalOpen"
      :inert="isConfirmModalOpen"
      :showBackButton="showBackButtonInResolve"
      title="RESOLVE ISSUE"
      :form="resolveIssueForm"
      :fields="resolveIssueFormFields"
      submitText="Resolve"
      disabledButton
      @back="handleBackFromResolve"
      @close="closeAllModal"
      @submit="handleResolveIssueSubmit"
    />

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
