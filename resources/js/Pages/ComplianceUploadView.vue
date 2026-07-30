<script setup>
import { computed, ref, reactive } from "vue";
import { usePage, Link, Head, useForm } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import { useComplianceUploadsColumns } from "../Data/tableColumns";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useNewUploadFormFields } from "../Data/forms/complianceUploadFormFields";

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
  complianceForm: {
    type: Object,
    required: true,
  },
  complianceUploads: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

//  Opens the upload's PDF document in a new browser tab.
function handleViewUploads(upload) {
  window.open(upload.document_url, "_blank", "noopener,noreferrer");
}

// Tanstack Table columns definition
const complianceUploadsTableColumns = useComplianceUploadsColumns({
  handleViewUploads,
  complianceForm: props.complianceForm,
});

// State for modals for forms
const isNewUploadModalOpen = ref(false);
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

// New upload form state
const newUploadForm = useForm({
  year: "",
  period: "",
  start_date: "",
  end_date: "",
  document: null,
  remarks: "",
});
// handle new upload
const handleNewUpload = () => {
  isNewUploadModalOpen.value = true;
};
const closeAllModal = () => {
  isNewUploadModalOpen.value = false;
};

// -- New Upload Flow --
const handleNewUploadSubmit = () => {
  Object.assign(confirmModalProps, {
    title: "Add New Upload",
    message: "Are you sure you want to add this new upload?",
    confirmText: "Upload",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-upload",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    newUploadForm.post(
      route("compliance.uploads.store", {
        company: props.company.slug,
        form: props.complianceForm.code,
      }),
      {
        preserveScroll: true,
        onSuccess: () => {
          newUploadForm.reset();
          closeAllModal();
        },
        onError: () => closeConfirmModal(),
        onFinish: () => {
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      },
    );
  };

  isConfirmModalOpen.value = true;
};

const newUploadFormFields = useNewUploadFormFields();
</script>

<template>
  <Head title="Compliance Uploads" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        {{ complianceForm.code }} — {{ complianceForm.name }}
      </h1>
    </div>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs text-sm mx-4 mb-3">
      <ul>
        <li>
          <Link :href="route('compliance')">Compliance</Link>
        </li>
        <li>
          <Link :href="route('compliance.forms', { company: company.slug })">
            {{ company.name }}
          </Link>
        </li>
        <li class="font-semibold text-base-content">
          {{ complianceForm.code }}
        </li>
      </ul>
    </div>

    <!-- Compliance Uploads Table -->
    <DataTable
      :data="props.complianceUploads"
      :columns="complianceUploadsTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleNewUpload"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Upload {{ complianceForm.code }}
        </button>
      </template>
    </DataTable>
  </div>

  <!-- New Upload Modal -->
  <FormModal
    :isOpen="isNewUploadModalOpen"
    :title="`ADD UPLOAD FOR ${complianceForm.code}`"
    :form="newUploadForm"
    :fields="newUploadFormFields"
    submitText="Add"
    @close="closeAllModal"
    @submit="handleNewUploadSubmit"
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
</template>
