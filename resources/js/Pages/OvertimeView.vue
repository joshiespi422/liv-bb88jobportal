<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { overtimeDetailFields } from "../Data/detailFields";
import { useOvertimeColumns } from "../Data/tableColumns";
import { statusText } from "../Composables/useClassMap";
import {
  useRequestOverTimeFormFields,
  useValidateOverTimeFormFields,
} from "../Data/forms/overtimeFormFields";

const props = defineProps({
  overtimes: {
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
const isRequestModalOpen = ref(false);
const isValidateModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);
// confirmation before request
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

// request form state
const requestForm = useForm({
  date: "",
  start_time: "",
  end_time: "",
  reason: "",
});
// validate form state
const validateForm = useForm({
  status: "",
  reject_reason: "",
});

// -- OverTime Request Details Logic --
const {
  isOpen: isOverTimeModalOpen,
  isLoading: isOverTimeLoading,
  isError: isOverTimeError,
  data: selectedOverTime,
  open: fetchOverTimeDetails,
  close: closeOverTimeModal,
} = useDetailsModal({ baseUrl: "/overtime" });
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchOverTimeDetails);
// state for showing rejection reason
const showRejectReason = ref(false);

// Function to toggle rejection reason
const toggleRejectReason = () => {
  showRejectReason.value = !showRejectReason.value;
};
// Handler for viewing overtime request and details modal function
const handleViewDetails = (overtimeId) => {
  showRejectReason.value = false;
  fetchOverTimeDetails(overtimeId);
};

// handle request overtime
const handleRequestOvertime = () => {
  isRequestModalOpen.value = true;
};
// handle validate overtime
const handleValidateOvertime = () => {
  if (!selectedOverTime.value) return;
  isValidateModalOpen.value = true;
  isOverTimeModalOpen.value = false;
};
const closeAllModal = () => {
  isRequestModalOpen.value = false;
  isConfirmModalOpen.value = false;
  isValidateModalOpen.value = false;
};

// control validate back button visibility
const showBackButtonInValidate = computed(() => {
  return isValidateModalOpen.value && selectedOverTime.value !== null;
});
// handle validate back navigation
const handleBackFromValidate = () => {
  isValidateModalOpen.value = false;
  isOverTimeModalOpen.value = true;
};

// Form field configuration for requesting overtime
const requestFormFields = useRequestOverTimeFormFields();

// form field configuration for validating overtime
const validateFormFields = useValidateOverTimeFormFields(
  validateForm,
  selectedOverTime,
);

// -- Request Overtime Flow --
const handleRequestSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Request Overtime",
    message: "Are you sure you want to request this overtime?",
    confirmText: "Request",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-calendar-clock",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    requestForm.post(route("overtime.store"), {
      preserveScroll: true,
      onSuccess: () => {
        closeAllModal();
        requestForm.reset();
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

// -- Sign Overtime Req Flow --
const handleSignSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Sign Overtime Request",
    message: "Are you sure you want to sign this request?",
    confirmText: "Sign",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-calendar-clock",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    router.patch(
      route("overtime.sign", selectedOverTime.value.id),
      {},
      {
        preserveScroll: true,
        onFinish: () => {
          closeConfirmModal();
          isOverTimeModalOpen.value = false;
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      },
    );
  };

  isConfirmModalOpen.value = true;
};

// -- Validate Overtime Flow --
const handleValidateSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Validate Overtime Request",
    message: "Are you sure you want to validate this request?",
    confirmText: "Validate",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-calendar-clock",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    validateForm.patch(route("overtime.validate", selectedOverTime.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        closeAllModal();
        validateForm.reset();
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

// Tanstack Table columns definition
const overtimeTableColumns = useOvertimeColumns({ handleViewDetails });

const showRequestButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";

  // 1. Must not be super admin
  if (isSuperAdmin) {
    return false;
  }

  return true;
});
const showSignButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";
  const isHead = authUser.value?.isHead;

  // 1. Must not be super admin
  if (isSuperAdmin) {
    return false;
  }
  // 2. Must be head
  if (!isHead) {
    return false;
  }
  // 3. Must have selected overtime request details
  if (!selectedOverTime.value) {
    return false;
  }
  // 4. Must be in "pending" status
  if (selectedOverTime.value.status !== "pending") {
    return false;
  }

  return true;
});
const showValidateButton = computed(() => {
  const isSuperAdmin = authUser.value?.userType === "super_admin";

  // 1. Must be super admin
  if (!isSuperAdmin) {
    return false;
  }
  // 2. Must have selected overtime request details
  if (!selectedOverTime.value) {
    return false;
  }
  // 3. Must be in "for approval" status
  if (selectedOverTime.value.status !== "for approval") {
    return false;
  }

  return true;
});
</script>

<template>
  <Head title="Overtime Request" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Overtime Request
      </h1>
    </div>

    <!-- Overtime Request Table -->
    <DataTable
      :data="props.overtimes"
      :columns="overtimeTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleRequestOvertime"
          v-if="showRequestButton"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Request Overtime
        </button>
      </template>
    </DataTable>
  </div>

  <!-- Request Overtime Modal -->
  <FormModal
    :isOpen="isRequestModalOpen"
    title="OVERTIME REQUEST"
    :form="requestForm"
    :fields="requestFormFields"
    submitText="Request"
    @close="closeAllModal"
    @submit="handleRequestSubmit"
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

  <!-- Validate Overtime Modal -->
  <FormModal
    :isOpen="isValidateModalOpen"
    :showBackButton="showBackButtonInValidate"
    title="VALIDATE OVERTIME REQUEST"
    :form="validateForm"
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

  <!-- Overtime Request Details Modal -->
  <DetailsModal
    :isOpen="isOverTimeModalOpen"
    :item="selectedOverTime"
    :loading="isOverTimeLoading"
    :error="isOverTimeError"
    panel-class="w-full max-w-lg"
    title="OVERTIME REQUEST DETAILS"
    :fields="overtimeDetailFields"
    @close="(closeOverTimeModal(), (showRejectReason = false))"
  >
    <!-- custom content slot  -->
    <template #content="{ item, getFieldValue }">
      <div class="space-y-4 my-5">
        <div
          v-for="field in overtimeDetailFields"
          :key="field.key"
          class="grid grid-cols-1 @sm:grid-cols-[1fr_4fr] gap-1 @sm:gap-4"
        >
          <label
            class="block text-sm ps-2 @sm:ps-0 font-semibold @sm:font-bol mt-0 @sm:mt-2"
          >
            {{ field.label }}:
          </label>

          <!-- Status field with click handler -->
          <div v-if="field.key === 'status'">
            <div
              class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate flex justify-between"
            >
              <span :class="statusText[item.status]">{{
                item.status || "N/A"
              }}</span>
              <i
                v-if="item.status === 'rejected'"
                class="pi pi-info-circle text-xl text-error cursor-pointer ml-2"
                @click="toggleRejectReason"
              ></i>
            </div>
            <!-- Rejection reason row (appears below status) -->
            <transition
              enter-active-class="transition-all duration-300 ease-out"
              leave-active-class="transition-all duration-200 ease-in"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-20"
              leave-from-class="opacity-100 max-h-20"
              leave-to-class="opacity-0 max-h-0"
            >
              <div
                v-if="item && item.status === 'rejected' && showRejectReason"
                class="grid grid-cols-1 gap-4 items-center overflow-hidden"
              >
                <div
                  class="text-sm bg-base-200 rounded-xl px-3 py-2 mt-2 overflow-hidden space-y-2"
                >
                  <label class="block text-sm font-bold">
                    Reason for Rejection:
                  </label>
                  <p class="truncate font-medium text-error">
                    {{ item.reject_reason || "No reason provided" }}
                  </p>
                </div>
              </div>
            </transition>
          </div>

          <!-- Other fields -->
          <div
            v-else-if="field.html"
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate"
            v-html="getFieldValue(item, field)"
          ></div>

          <p
            v-else
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate"
          >
            {{ getFieldValue(item, field) }}
          </p>
        </div>
      </div>
    </template>

    <template #custom-buttons>
      <button
        v-if="showSignButton"
        @click="handleSignSubmit"
        class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
      >
        Sign
      </button>
      <button
        v-if="showValidateButton"
        @click="handleValidateOvertime"
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
</template>
