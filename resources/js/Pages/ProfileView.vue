<script setup>
import { ref, reactive, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import QrcodeVue from "qrcode.vue";
import { formatDate } from "../Composables/useDateFormatter";
import PictureModal from "../Components/modals/PictureModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import PasswordInput from "../Components/forms/PasswordInput.vue";
import TextInput from "../Components/forms/TextInput.vue";
import SelectInput from "../Components/forms/SelectInput.vue";
import DateInput from "../Components/forms/DateInput.vue";

// Props received from Inertia
const props = defineProps({
  profile: Object,
});

// Fields to be displayed in the profile view
const profileFields = [
  { key: "name", label: "Full Name" },
  { key: "position", label: "Position" },
  { key: "department", label: "Department" },
  { key: "qr_code", label: "QR Code" },
  { key: "hierarchy", label: "Hierarchy" },
  { key: "school", label: "School" },
  { key: "address", label: "Address" },
  { key: "bday", label: "Birthday", formatter: formatDate },
  { key: "gender", label: "Gender" },
];
// Filter fields to only those present in profile data
const visibleFields = computed(() => {
  return profileFields.filter((field) =>
    props.profile.hasOwnProperty(field.key)
  );
});
// Format values with fallback
const displayValue = (field, value) => {
  if (value === null || value === undefined || value === "") {
    return "N/A";
  }
  return field.formatter ? field.formatter(value) : value;
};

// State for modals
const isPictureModalOpen = ref(false);
const isConfirmModalOpen = ref(false);
const isConfirmLoading = ref(false);
const isPasswordModalOpen = ref(false);
const isEditDetailsModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);

const pictureForm = useForm({
  picture: null,
});

const passwordForm = useForm({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const detailsForm = useForm({
  address: props.profile.address || "",
  bday: props.profile.bday || "",
  gender: props.profile.gender || "",
});

// Holds the properties for the confirmation modal
const confirmModalProps = reactive({
  title: "",
  message: "",
  confirmText: "",
  confirmButtonBg: "",
  iconName: "",
});

// Fields for the password change form
const passwordFields = [
  {
    key: "current_password",
    label: "Current Password",
    component: PasswordInput,
    attrs: { required: true, placeholder: "Enter current password" },
  },
  {
    key: "password",
    label: "New Password",
    component: PasswordInput,
    attrs: { required: true, placeholder: "Enter new password" },
  },
  {
    key: "password_confirmation",
    label: "Confirm New Password",
    component: PasswordInput,
    attrs: { required: true, placeholder: "Confirm new password" },
  },
];

// Fields for the edit details form
const detailsFields = [
  {
    key: "name",
    label: "Name",
    component: TextInput,
    attrs: { disabled: true, value: props.profile.name || "N/A" },
  },
  {
    key: "position",
    label: "Position",
    component: TextInput,
    attrs: { disabled: true, value: props.profile.position || "N/A" },
  },
  ...(props.profile.department
    ? [
        {
          key: "department",
          label: "Department",
          component: TextInput,
          attrs: { disabled: true, value: props.profile.department || "N/A" },
        },
      ]
    : []),
  ...(props.profile.hierarchy
    ? [
        {
          key: "hierarchy",
          label: "Hierarchy",
          component: TextInput,
          attrs: { disabled: true, value: props.profile.hierarchy || "N/A" },
        },
      ]
    : []),
  ...(props.profile.school
    ? [
        {
          key: "school",
          label: "School",
          component: TextInput,
          attrs: { disabled: true, value: props.profile.school || "N/A" },
        },
      ]
    : []),
  {
    key: "qr_code",
    label: "QR Code",
    component: TextInput,
    attrs: { disabled: true, value: props.profile.qr_code || "N/A" },
  },
  {
    key: "address",
    label: "Address",
    component: TextInput,
    attrs: { placeholder: "Enter your address", required: true },
  },
  {
    key: "bday",
    label: "Birthday",
    component: DateInput,
    attrs: { required: true },
  },
  {
    key: "gender",
    label: "Gender",
    component: SelectInput,
    attrs: {
      options: [
        { value: "Male", label: "Male" },
        { value: "Female", label: "Female" },
        { value: "Other", label: "Other" },
        { value: "Prefer not to say", label: "Prefer not to say" },
      ],
      placeholder: "Select your gender",
      required: true,
    },
  },
];

const openPasswordModal = () => {
  isPasswordModalOpen.value = true;
};

const openPictureModal = () => {
  isPictureModalOpen.value = true;
};

const openEditDetailsModal = () => {
  isEditDetailsModalOpen.value = true;
};

const closeConfirmModal = () => {
  isConfirmModalOpen.value = false;
};

const closeAllModals = () => {
  isPictureModalOpen.value = false;
  isConfirmModalOpen.value = false;
  isPasswordModalOpen.value = false;
  isEditDetailsModalOpen.value = false;
};

const resetPasswordForm = () => {
  passwordForm.reset();
  passwordForm.errors = {};
};

const cancelCropper = () => {
  pictureForm.reset();
  pictureForm.errors = {};
};

// --- Change Picture Flow ---
const handleChangePicture = (imageBlob) => {
  pictureForm.picture = imageBlob; // Store the cropped blob in the pictureForm

  Object.assign(confirmModalProps, {
    title: "Update Profile Picture",
    message: "Are you sure you want to save this new profile picture?",
    confirmText: "Save",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-question-circle",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    pictureForm.post(route("profile.picture.update"), {
      onSuccess: () => closeAllModals(),
      onError: () => (isConfirmModalOpen.value = false),
      onFinish: () => {
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
};

// --- Delete Picture Flow ---
const handleDeletePicture = () => {
  Object.assign(confirmModalProps, {
    title: "Delete Profile Picture",
    message:
      "Are you sure you want to delete your profile picture? This action cannot be undone.",
    confirmText: "Delete",
    confirmButtonBg: "bg-red-600 hover:bg-red-700",
    iconName: "pi pi-exclamation-triangle",
    iconColor: "text-red-600",
    iconBgColor: "bg-red-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    pictureForm.delete(route("profile.picture.delete"), {
      onSuccess: () => closeAllModals(),
      onError: () => (isConfirmModalOpen.value = false),
      onFinish: () => {
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
};

// --- Change Password Flow ---
const handlePasswordChange = () => {
  Object.assign(confirmModalProps, {
    title: "Change Password",
    message: "Are you sure you want to change your password?",
    confirmText: "Change Password",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-lock",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    passwordForm.post(route("profile.password.update"), {
      onSuccess: () => closeAllModals(),
      onError: () => (isConfirmModalOpen.value = false),
      onFinish: () => {
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
};

// --- Update Details Flow ---
const handleDetailsEdit = () => {
  Object.assign(confirmModalProps, {
    title: "Update Profile Details",
    message: "Are you sure you want to update your profile details?",
    confirmText: "Update",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-user-edit",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    detailsForm.post(route("profile.details.update"), {
      onSuccess: () => closeAllModals(),
      onError: () => (isConfirmModalOpen.value = false),
    });
  };

  isConfirmModalOpen.value = true;
};

const executeConfirm = () => {
  if (pendingAction.value) {
    pendingAction.value();
  }
};

// Qr Modal state
const isQrModalOpen = ref(false);
// Create the full URL for the QR code dynamically
const qrUrl = computed(() => {
  // Only generate a URL if the user has a qr_code ID
  if (props.profile.qr_code) {
    return `${window.location.origin}/info?id=${props.profile.qr_code}`;
  }
  return null; // Return null if no ID exists
});

const openQrModal = () => {
  isQrModalOpen.value = true;
};
const closeQrModal = () => {
  isQrModalOpen.value = false;
};
</script>

<template>
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="border-4 border-green-primary-1 max-w-6xl mx-auto rounded-3xl shadow-xl/20 overflow-hidden"
    >
      <div
        class="h-20 bg-gradient-to-b from-green-primary-1 to-green-secondary rounded-t-2xl"
      />
      <!-- Clickable profile picture -->
      <div
        class="flex flex-col items-center @3xl:flex-row @3xl:justify-between p-6 mx-6"
      >
        <div class="flex flex-col items-center @3xl:flex-row gap-4">
          <div class="relative group cursor-pointer" @click="openPictureModal">
            <img
              :src="profile.picture || '/profile-images/default.png'"
              class="w-24 h-24 @md:w-28 @md:h-28 rounded-full object-cover shadow-xl/20"
            />
            <div
              class="absolute inset-0 bg-black/30 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
            >
              <span class="text-white font-medium">Edit</span>
            </div>
          </div>
          <div class="overflow-hidden text-center @3xl:text-left">
            <h3 class="text-lg @md:text-2xl font-bold truncate">
              {{ profile.name }}
            </h3>
            <p
              class="text-sm @md:text-base text-gray-500 font-semibold truncate"
            >
              {{ profile.email }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 mt-6 @3xl:mt-0">
          <button
            @click="openEditDetailsModal"
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-pen-to-square mr-1" />
            Edit
          </button>
          <button
            @click="openQrModal"
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-qrcode mr-1" />
            QR Code
          </button>
        </div>
      </div>

      <!-- User information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mx-0 md:mx-6">
        <div
          v-for="field in visibleFields"
          :key="field.key"
          class="flex flex-col items-center md:items-start"
        >
          <div class="block w-full ps-2 md:ps-0">
            <p class="text-sm font-semibold ms-5">
              {{ field.label }}
            </p>
          </div>

          <input
            :value="displayValue(field, profile[field.key])"
            type="text text-xs"
            class="input font-bold w-[95%] rounded-full px-5"
            disabled
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 m-6">
        <div class="overflow-hidden">
          <h3 class="text-base @md:text-lg font-semibold mb-1">
            My Email Address
          </h3>
          <div class="flex items-center gap-2">
            <i
              class="pi pi-envelope rounded-full p-2.5 text-base @md:text-lg text-white-primary bg-green-primary-1"
            />
            <p class="text-sm @md:text-base font-semibold truncate">
              {{ profile.email }}
            </p>
          </div>
        </div>
        <div>
          <h3 class="text-base @md:text-lg font-semibold mb-1">
            Change Password
          </h3>
          <div
            class="flex flex-col items-start @sm:flex-row @2xl:items-center gap-2"
          >
            <input
              value="**********"
              type="text"
              class="input font-bold rounded-full px-5"
              disabled
            />
            <button
              @click="openPasswordModal"
              class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
            >
              <i class="pi pi-lock mr-2" />
              Change
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Modal -->
    <DetailsModal
      :isOpen="isQrModalOpen"
      :item="{ qr: qrUrl }"
      :loading="false"
      :error="false"
      title="SCAN QR CODE"
      @close="closeQrModal"
    >
      <!-- Custom Content Layout -->
      <template #content>
        <div class="flex flex-col items-center justify-center p-4">
          <div
            v-if="qrUrl"
            class="p-4 bg-white rounded-xl shadow-[0_0_10px_rgba(0,0,0,0.3)]"
          >
            <QrcodeVue :value="qrUrl" :size="200" level="H" />
          </div>

          <div
            v-else
            role="alert"
            class="alert alert-soft alert-error mt-10 mb-5 w-full"
          >
            <i class="pi pi-exclamation-circle text-2xl" />
            <p class="font-semibold">No available QR Code</p>
          </div>

          <p v-if="qrUrl" class="mt-6 text-sm text-gray-500 text-center">
            Scan this code to view public profile information.
          </p>
        </div>
      </template>
    </DetailsModal>

    <!-- Profile Picture Modal -->
    <PictureModal
      :isOpen="isPictureModalOpen"
      :pictureUrl="profile.picture"
      :error="pictureForm.errors.picture"
      @cancel="cancelCropper"
      @close="closeAllModals(), cancelCropper()"
      @change="handleChangePicture"
      @delete="handleDeletePicture"
    >
      <!-- Confirmation Modal -->
      <ConfirmModal
        :show="isConfirmModalOpen"
        v-bind="confirmModalProps"
        :loading="isConfirmLoading"
        @cancel="closeConfirmModal"
        @confirm="executeConfirm"
      />
    </PictureModal>

    <!-- Password Modal -->
    <FormModal
      :isOpen="isPasswordModalOpen"
      title="CHANGE PASSWORD"
      :form="passwordForm"
      :fields="passwordFields"
      submitText="Submit"
      @close="closeAllModals(), resetPasswordForm()"
      @submit="handlePasswordChange"
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

    <!-- Details Edit Modal -->
    <FormModal
      :isOpen="isEditDetailsModalOpen"
      title="EDIT PROFILE DETAILS"
      :form="detailsForm"
      :fields="detailsFields"
      submitText="Save"
      @close="closeAllModals"
      @submit="handleDetailsEdit"
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
