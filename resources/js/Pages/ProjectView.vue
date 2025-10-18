<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, useForm } from "@inertiajs/vue3";
import { longDate } from "../Composables/useDateFormatter";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import {
  useNewProjectFormFields,
  useAddIssueFormFields,
  useResolveIssueFormFields,
} from "../Data/forms/projectFormFields";
import { useDetailsModal } from "../Composables/useDetailsModal";
import {
  projectDetailFields,
  issueDetailFields,
  taskDetailFields,
  accomplishDetailFields,
} from "../Data/detailFields";
import { projectTableColumns } from "../Data/tableColumns";
import TaskPanel from "../Components/modals/TaskPanel.vue";
import ProjectPanel from "../Components/modals/ProjectPanel.vue";
import AssigneeGroup from "../Components/AssigneeGroup.vue";
import { statusText } from "../Composables/useClassMap";

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

// -- Project Details Logic --
const {
  isOpen: isProjectDetailsOpen,
  isLoading: isProjectLoading,
  isError: isProjectError,
  data: selectedProject,
  open: fetchProjectDetails,
  close: closeProjectDetails,
} = useDetailsModal({ baseUrl: "/project" });
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchProjectDetails);

// -- Issue Details Logic -- (uses a custom fetcher for the ziggy route)
const {
  isOpen: isIssueDetailsOpen,
  isLoading: isIssueLoading,
  isError: isIssueError,
  data: selectedIssue,
  open: fetchIssueDetails,
  close: closeIssueDetails,
} = useDetailsModal({
  fetcher: (issueId) =>
    axios.get(route("project.issue.show", { issue: issueId })),
});

// -- Task Details Logic
const {
  isOpen: isTaskDetailsOpen,
  isLoading: isTaskLoading,
  isError: isTaskError,
  data: selectedTask,
  open: fetchTaskDetails,
  close: closeTaskDetails,
} = useDetailsModal({ baseUrl: "/task" });
// state for showing revision reason
const showReviseReason = ref(false);
// watchers for updating comment form
watch(selectedTask, (newTask) => {
  if (newTask && newTask.id) {
    commentForm.commentable_id = newTask.id;
    commentForm.message = "";
  } else {
    commentForm.reset();
  }
});

// -- Accomplishment Details Logic
const {
  isOpen: isAccomplishModalOpen,
  isLoading: isAccomplishLoading,
  isError: isAccomplishError,
  data: selectedAccomplish,
  open: fetchAccomplishDetails,
  close: closeAccomplishModal,
} = useDetailsModal({ baseUrl: "/accomplishment" });

// for min attribute deadline
const today = computed(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
});
// Form field configuration for adding new project
const newProjectFormFields = useNewProjectFormFields(
  computed(() => props.departments),
  today
);

// Fetch projects list
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
// form field configuration for adding new issue
const addIssueFormFields = useAddIssueFormFields(projectsList);

// Form field configuration for resolving issue
const resolveIssueFormFields = useResolveIssueFormFields(selectedIssue);

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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    addIssueForm
      .transform(() => transformedData)
      .post(route("project.issue.store"), {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          addIssueForm.reset();
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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    resolveIssueForm.patch(
      route("project.issue.resolve", { issue: selectedIssue.value.id }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          resolveIssueForm.reset();
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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
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
      onFinish: () => {
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    newProjectForm
      .transform(() => transformedData)
      .post(route("project.store"), {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          newProjectForm.reset();
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

// Handler for viewing project details and details modal function
const handleViewDetails = (projectId) => {
  fetchProjectDetails(projectId);
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
const handleViewIssue = (issueId) => {
  isProjectDetailsOpen.value = false;
  fetchIssueDetails(issueId);
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
const handleViewTask = (taskId) => {
  isProjectDetailsOpen.value = false;
  showReviseReason.value = false;
  fetchTaskDetails(taskId);
};
const toggleReviseReason = () => {
  showReviseReason.value = !showReviseReason.value;
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
const handleViewAccomplish = (accomplishmentId) => {
  isTaskDetailsOpen.value = false;
  fetchAccomplishDetails(accomplishmentId);
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
  <Head title="Project" />
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
                  <AssigneeGroup :assignees="row.assignees" />
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
      layout-type="default2"
      @close="closeProjectDetails"
    >
      <!-- Custom Content Layout -->
      <template #right-panel="{ item }">
        <ProjectPanel
          :project="item"
          :auth-user="authUser"
          @view-task="handleViewTask"
          @view-issue="handleViewIssue"
        />
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
          class="btn btn-sm @sm:btn-md btn-soft rounded-full me-2"
          @click="handleBackFromIssue"
        >
          <i class="pi pi-arrow-left me-1" /> Back
        </button>
        <button
          v-if="showResolveButton"
          @click="handleResolveIssue"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Resolve
        </button>
      </template>
    </DetailsModal>

    <!-- Task Details Modal -->
    <DetailsModal
      :isOpen="isTaskDetailsOpen"
      :item="selectedTask"
      :loading="isTaskLoading"
      :error="isTaskError"
      title="TASK DETAILS"
      :fields="taskDetailFields"
      :panel-class="'w-full max-w-4xl'"
      layout-type="default2"
      @close="closeTaskDetails(), (showReviseReason = false)"
    >
      <template #field-status="{ item }">
        <div>
          <div
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate flex justify-between"
          >
            <span :class="statusText[item.status]">{{
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
              v-if="item && item.status === 'revision' && showReviseReason"
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
      </template>

      <template #right-panel="{ item }">
        <TaskPanel
          :task="item"
          :comment-message="commentForm.message"
          :comment-error="commentForm.errors.message"
          @update:comment-message="commentForm.message = $event"
          @clear-comment-error="commentForm.clearErrors('message')"
          @submit-comment="handleCommentSubmit"
          @view-accomplishment="handleViewAccomplish"
        />
      </template>

      <template #custom-buttons>
        <button
          v-if="showBackButtonInTask"
          class="btn btn-sm @sm:btn-md btn-soft rounded-full me-2"
          @click="handleBackFromTask"
        >
          <i class="pi pi-arrow-left me-1" /> Back
        </button>
      </template>

      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="isConfirmModalOpen"
        v-bind="confirmModalProps"
        :loading="isConfirmLoading"
        @cancel="closeConfirmModal"
        @confirm="executeConfirm"
      />
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

    <!-- New Project Modal -->
    <FormModal
      :isOpen="isNewProjectModalOpen"
      title="ADD PROJECT"
      :form="newProjectForm"
      :fields="newProjectFormFields"
      submitText="Add"
      @close="closeAllModal"
      @submit="handleNewProjectSubmit"
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

    <!-- Add Issue Modal -->
    <FormModal
      :isOpen="isAddIssueModalOpen"
      title="ADD ISSUE"
      :form="addIssueForm"
      :fields="addIssueFormFields"
      submitText="Add"
      @close="closeAllModal"
      @submit="handleAddIssueSubmit"
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

    <!-- Resolve Issue Modal -->
    <FormModal
      :isOpen="isResolveModalOpen"
      :showBackButton="showBackButtonInResolve"
      title="RESOLVE ISSUE"
      :form="resolveIssueForm"
      :fields="resolveIssueFormFields"
      submitText="Resolve"
      disabledButton
      @back="handleBackFromResolve"
      @close="closeAllModal"
      @submit="handleResolveIssueSubmit"
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
  </div>
</template>
