<script setup>
import { ref, computed, reactive, watch, h } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { useHolidayColumns } from "../Data/tableColumns";
import { statusText } from "../Composables/useClassMap";
import { useCreateHolidayFormFields } from "../Data/forms/holidayFormFields";

const props = defineProps({
  holidays: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// State for modals for forms
const isCreateModalOpen = ref(false);
const isErrorModalOpen = ref(false);
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

// create form state
const createForm = useForm({
  date: "",
  name: "",
  type: "",
});

// delete form state
const deleteForm = useForm({});

// handle create holiday
const handleAddHoliday = () => {
  isCreateModalOpen.value = true;
};
const closeAllModal = () => {
  isCreateModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// Form field configuration for creating holiday
const createFormFields = useCreateHolidayFormFields();

// -- Create Holiday Flow --
const handleCreateSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Add Holiday",
    message: "Are you sure you want to add this holiday?",
    confirmText: "Add",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-calendar-minus",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    createForm.post(route("holiday.store"), {
      preserveScroll: true,
      onSuccess: () => {
        closeAllModal();
        createForm.reset();
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

// -- Delete Flow --
const handleDelete = (holidayId) => {
  Object.assign(confirmModalProps, {
    title: "Delete Holiday",
    message: "Are you sure you want to delete this holiday?",
    confirmText: "Delete",
    confirmButtonBg: "bg-rose-600 hover:bg-rose-700",
    iconName: "pi pi-calendar-minus",
    iconColor: "text-rose-600",
    iconBgColor: "bg-rose-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    deleteForm.delete(route("holiday.destroy", holidayId), {
      preserveScroll: true,
      onError: () => {
        closeConfirmModal();
        isErrorModalOpen.value = true;
      },
      onFinish: () => {
        closeConfirmModal();
        isCreateModalOpen.value = false;
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
};

// Tanstack Table columns definition
const holidayTableColumns = useHolidayColumns(authUser, { handleDelete });
</script>

<template>
  <Head title="Holiday" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Holiday Management
      </h1>
    </div>

    <!-- Holiday Table -->
    <DataTable
      :data="props.holidays"
      :columns="holidayTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleAddHoliday"
          v-if="authUser?.userType === 'super_admin'"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Holiday
        </button>
      </template>
    </DataTable>
  </div>

  <!-- Create Overtime Modal -->
  <FormModal
    :isOpen="isCreateModalOpen"
    title="ADD HOLIDAY"
    :form="createForm"
    :fields="createFormFields"
    submitText="Add"
    @close="closeAllModal"
    @submit="handleCreateSubmit"
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

  <!-- Confirmation Modal -->
  <ConfirmModal
    :show="isConfirmModalOpen"
    v-bind="confirmModalProps"
    :loading="isConfirmLoading"
    @cancel="closeConfirmModal"
    @confirm="executeConfirm"
  />

  <!-- Error Modal -->
  <ConfirmModal
    :show="isErrorModalOpen"
    title="Deletion Error"
    :message="deleteForm.errors.holiday"
    icon-name="pi pi-exclamation-circle"
    disabled-button
    @close="isErrorModalOpen = false"
  >
    <template #custom-buttons>
      <button
        @click="isErrorModalOpen = false"
        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 hover:bg-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
      >
        Okay
      </button>
    </template>
  </ConfirmModal>
</template>
