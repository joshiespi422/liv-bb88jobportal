<script setup>
import { ref, computed, reactive, watch } from "vue";
import { useForm, usePage, router, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import TaskPanel from "../Components/modals/TaskPanel.vue";
import {
  useNewTaskFormFields,
  useUpdateTaskFormFields,
  useValidateTaskFormFields,
} from "../Data/forms/taskFormFields";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { taskDetailFields, accomplishDetailFields } from "../Data/detailFields";
import { useTaskColumns } from "../Data/tableColumns";
import { statusText } from "../Composables/useClassMap";

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
  drop_reason: "",
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

// -- Task Details Logic --
const {
  isOpen: isTaskDetailsOpen,
  isLoading: isTaskLoading,
  isError: isTaskError,
  data: selectedTask,
  open: fetchTaskDetails,
  close: closeTaskDetails,
} = useDetailsModal({ baseUrl: "/task" });
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchTaskDetails);
// state for showing revision reason
const showReasonForStatus = ref(null);
// watchers for updating comment form
watch(selectedTask, (newTask) => {
  if (newTask && newTask.id) {
    commentForm.commentable_id = newTask.id;
    commentForm.message = "";
  } else {
    commentForm.reset();
  }
});
// -- Accomplishment Details Logic --
const {
  isOpen: isAccomplishModalOpen,
  isLoading: isAccomplishLoading,
  isError: isAccomplishError,
  data: selectedAccomplish,
  open: fetchAccomplishDetails,
  close: closeAccomplishModal,
} = useDetailsModal({ baseUrl: "/accomplishment" });

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
      }),
    );
    assigneesList.value = response.data;
  } catch (error) {
    console.error("Failed to fetch assignable users:", error);
    assigneesList.value = [];
  }
};
// fetch projects for new task
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
      }),
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
      projectsList.value = [];
      await fetchAssigneesList(newDeptId, props.currentType);
      await fetchProjectsList(newDeptId);
    }
  },
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
  { immediate: true },
);
// Form field configuration for adding new task
const newTaskFormFields = useNewTaskFormFields(
  authUser,
  props,
  projectsList,
  assigneesList,
  today,
);

// dynamic status options for update task
const statusOptions = computed(() => {
  if (!selectedTask.value) return [];

  const currentStatus = selectedTask.value.status;

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
// Form field configuration for updating task
const updateFormFields = useUpdateTaskFormFields(selectedTask, statusOptions);

// Form field configuration for validating task
const validateFormFields = useValidateTaskFormFields(
  validateTaskForm,
  selectedTask,
);

// update task modal state
const handleUpdateTask = () => {
  if (!selectedTask.value) return;
  isUpdateModalOpen.value = true;
  isTaskDetailsOpen.value = false;
};
// validate task modal state
const handleValidateTask = () => {
  if (!selectedTask.value) return;
  isValidateModalOpen.value = true;
  isTaskDetailsOpen.value = false;
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
  return isUpdateModalOpen.value && selectedTask.value !== null;
});
// handle update back navigation
const handleBackFromUpdate = () => {
  isUpdateModalOpen.value = false;
  isTaskDetailsOpen.value = true;
};
// control validate back button visibility
const showBackButtonInValidate = computed(() => {
  return isValidateModalOpen.value && selectedTask.value !== null;
});
// handle validate back navigation
const handleBackFromValidate = () => {
  isValidateModalOpen.value = false;
  isTaskDetailsOpen.value = true;
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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    updateTaskForm.post(route("task.update", { task: selectedTask.value.id }), {
      preserveScroll: true,
      onSuccess: () => {
        closeAllModal();
        updateTaskForm.reset();
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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    validateTaskForm.post(
      route("task.validate", { task: selectedTask.value.id }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          validateTaskForm.reset();
        },
        onError: () => closeConfirmModal(),
        onFinish: () => {
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 300);
        },
      },
    );
  };

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

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    newTaskForm
      .transform(() => transformedData)
      .post(route("task.store"), {
        preserveScroll: true,
        onSuccess: () => {
          closeAllModal();
          newTaskForm.reset();
        },
        onError: () => closeConfirmModal(),
        onFinish: () => {
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 300);
        },
      });
  };

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
        }, 300);
      },
    });
  };

  isConfirmModalOpen.value = true;
};

// Handler for viewing task details and details modal function
const handleViewDetails = (task) => {
  showReasonForStatus.value = null;
  fetchTaskDetails(task.id);
};
// Show reason for status if available
const reasonConfig = {
  revision: {
    reasonKey: "revise_reason",
    label: "Reason for Revision:",
  },
  dropped: {
    reasonKey: "drop_reason",
    label: "Reason for Dropping:",
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

// Handle back navigation from accomplishment modal
const handleBackFromAccomplish = () => {
  isAccomplishModalOpen.value = false;
  isTaskDetailsOpen.value = true;
};
// Show back button in accomplishment modal
const showBackButtonInAccomplish = computed(() => {
  return isAccomplishModalOpen.value && selectedTask.value !== null;
});
const handleViewAccomplish = (accomplishmentId) => {
  isTaskDetailsOpen.value = false;
  fetchAccomplishDetails(accomplishmentId);
};

// tab handling navigation
const isRegularTab = computed(
  () =>
    authUser.value.userType === "employee" &&
    authUser.value.hierarchy !== "Leader",
);
const isLeaderTab = computed(
  () =>
    authUser.value.userType === "employee" &&
    authUser.value.hierarchy === "Leader" &&
    props.currentType === "employee",
);
const tabs = computed(() => {
  const items = [
    { id: "active", label: "Active Tasks" },
    { id: "archived", label: "Archived" },
  ];

  if (
    isRegularTab.value ||
    isLeaderTab.value ||
    authUser.value.userType === "intern"
  ) {
    items.unshift({ id: "own", label: "Your Tasks" });
  }

  return items;
});

// Tanstack Table columns definition
const taskTableColumns = useTaskColumns({ handleViewDetails });

const capitalizedType = computed(() => {
  if (!props.currentType) return "";
  return props.currentType.charAt(0).toUpperCase() + props.currentType.slice(1);
});

const showUpdateButton = computed(() => {
  // 1. Must not be super admin
  if (authUser.value?.userType === "super_admin") return false;
  // 2. Must have selected task details
  if (!selectedTask.value) return false;
  // 3. Must be in "own" tab
  if (props.activeTab !== "own") return false;
  // 4. Must be not in "done" status
  if (selectedTask.value.status === "done") return false;
  // 5. Current user must be in assignees
  const isAssignee = selectedTask.value.assignees.some(
    (assignee) => assignee.id === authUser.value.id,
  );

  return isAssignee;
});

const showValidateButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isLeader = authUser.value?.hierarchy === "Leader";

  // 1. Must be super admin or leader
  if (!isSuperAdmin && !isLeader) {
    return false;
  }
  // 2. Must have selected task details
  if (!selectedTask.value) {
    return false;
  }
  // 3. Must be in "active" tab
  if (props.activeTab !== "active") {
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
  <Head title="Task" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        {{ capitalizedType }} Task
      </h1>
      <div
        v-if="
          authUser?.userType === 'super_admin' ||
          (authUser?.hierarchy === 'Leader' && props.currentType === 'intern')
        "
        class="w-52 md:w-60 lg:w-72"
      >
        <Department
          :departments="props.departments"
          :current-department-id="props.currentDepartmentId"
          :auth-user="authUser"
          route-name="task"
          :other-params="{ type: props.currentType }"
        />
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in tabs"
        :key="tab.id"
        :href="route('task', { ...route().params, tab: tab.id })"
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
      :showBackButton="showBackButtonInUpdate"
      title="UPDATE TASK"
      :form="updateTaskForm"
      :fields="updateFormFields"
      submitText="Add"
      disabledButton
      @close="closeAllModal"
      @back="handleBackFromUpdate"
      @submit="handleUpdateSubmit"
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

    <!-- Validate Task Modal -->
    <FormModal
      :isOpen="isValidateModalOpen"
      :showBackButton="showBackButtonInValidate"
      title="VALIDATE TASK"
      :form="validateTaskForm"
      :fields="validateFormFields"
      submitText="Submit"
      disabledButton
      @close="closeAllModal"
      @back="handleBackFromValidate"
      @submit="handleValidateSubmit"
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

    <!-- New Task Modal -->
    <FormModal
      :isOpen="isNewTaskModalOpen"
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

      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="isConfirmModalOpen"
        v-bind="confirmModalProps"
        :loading="isConfirmLoading"
        @cancel="closeConfirmModal"
        @confirm="executeConfirm"
      />
    </FormModal>

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
      @close="(closeTaskDetails(), (showReasonForStatus = false))"
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
              v-if="reasonConfig[item.status]"
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
              v-if="showReasonForStatus === item.status && currentReasonConfig"
              class="grid grid-cols-1 gap-4 items-center overflow-hidden"
            >
              <div
                class="text-sm bg-base-200 rounded-xl px-3 py-2 mt-2 overflow-hidden space-y-2"
              >
                <label class="block text-sm font-bold">
                  {{ currentReasonConfig.label }}
                </label>
                <p class="truncate font-medium text-error">
                  {{
                    item[currentReasonConfig.reasonKey] || "No reason provided"
                  }}
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
  </div>
</template>
