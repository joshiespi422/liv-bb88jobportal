<script setup>
import { ref, reactive } from "vue";
import { useForm } from "@inertiajs/vue3";
import PictureModal from "../Components/PictureModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";
import FormModal from "../Components/FormModal.vue";
import PasswordInput from "../Components/forms/PasswordInput.vue";
import TextInput from "../Components/forms/TextInput.vue";
import SelectInput from "../Components/forms/SelectInput.vue";

// Props received from Inertia
const props = defineProps({
  profile: Object,
});

// State for modals
const isPictureModalOpen = ref(false);
const isConfirmModalOpen = ref(false);
const isPasswordModalOpen = ref(false);
const isDetailsModalOpen = ref(false);
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
  name: props.profile.name,
  position: props.profile.position,
  department: props.profile.department || "",
  hierarchy: props.profile.hierarchy || "",
  school: props.profile.school || "",
  address: props.profile.address,
  bday: props.profile.bday,
  gender: props.profile.gender,
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
    attrs: { placeholder: "Enter current password" },
  },
  {
    key: "password",
    label: "New Password",
    component: PasswordInput,
    attrs: { placeholder: "Enter new password" },
  },
  {
    key: "password_confirmation",
    label: "Confirm New Password",
    component: PasswordInput,
    attrs: { placeholder: "Confirm new password" },
  },
];

// Fields for the edit details form
const detailsFields = [
  {
    key: "name",
    label: "Name",
    component: TextInput,
    attrs: { disabled: true },
  },
  {
    key: "position",
    label: "Position",
    component: TextInput,
    attrs: { disabled: true },
  },
  ...(props.profile.department
    ? [
        {
          key: "department",
          label: "Department",
          component: TextInput,
          attrs: { disabled: true },
        },
      ]
    : []),
  ...(props.profile.hierarchy
    ? [
        {
          key: "hierarchy",
          label: "Hierarchy",
          component: TextInput,
          attrs: { disabled: true },
        },
      ]
    : []),
  ...(props.profile.school
    ? [
        {
          key: "school",
          label: "School",
          component: TextInput,
          attrs: { disabled: true },
        },
      ]
    : []),
  {
    key: "address",
    label: "Address",
    component: TextInput,
    attrs: { placeholder: "Enter your address" },
  },
  {
    key: "bday",
    label: "Birthday",
    component: TextInput,
    attrs: { type: "date" },
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
    },
  },
];

const openPasswordModal = () => {
  isPasswordModalOpen.value = true;
};

const openPictureModal = () => {
  isPictureModalOpen.value = true;
};

const openDetailsModal = () => {
  isDetailsModalOpen.value = true;
};

const closeConfirmModal = () => {
  isConfirmModalOpen.value = false;
};

const closeAllModals = () => {
  isPictureModalOpen.value = false;
  isConfirmModalOpen.value = false;
  isPasswordModalOpen.value = false;
  isDetailsModalOpen.value = false;
  passwordForm.reset();
  passwordForm.errors = {};
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

  pendingAction.value = () =>
    pictureForm.post(route("profile.picture.update"), {
      onSuccess: () => closeAllModals(),
    });

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

  pendingAction.value = () =>
    pictureForm.delete(route("profile.picture.delete"), {
      onSuccess: () => closeAllModals(),
    });

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
    passwordForm.post(route("profile.password.update"), {
      onSuccess: () => closeAllModals(),
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
      onSuccess: () => {
        closeAllModals();
      },
    });
  };

  isConfirmModalOpen.value = true;
};

const executeConfirm = () => {
  if (pendingAction.value) {
    pendingAction.value();
  }
};
</script>

<template>
  <div class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Profile</h2>

    <div class="flex flex-col items-center">
      <!-- Clickable profile picture -->
      <div class="relative group cursor-pointer" @click="openPictureModal">
        <img
          :src="profile.picture || '/profile-images/default.png'"
          class="w-32 h-32 rounded-full object-cover border-2 border-gray-300 shadow-md"
        />
        <div
          class="absolute inset-0 bg-black/30 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
        >
          <span class="text-white font-medium">Edit</span>
        </div>
      </div>

      <!-- User information -->
      <div class="mt-4 text-center">
        <h3 class="text-xl font-bold">{{ profile.name }}</h3>
        <p class="text-gray-600">{{ profile.position }}</p>
        <p class="text-gray-500">{{ profile.department }}</p>
      </div>
      <div class="mt-6">
        <button
          @click="openPasswordModal"
          class="w-full sm:w-auto px-4 py-2 btn btn-primary rounded-md transition-colors"
        >
          Change Password
        </button>
      </div>
      <div class="mt-6">
        <button
          @click="openDetailsModal"
          class="w-full sm:w-auto px-4 py-2 btn btn-primary rounded-md transition-colors"
        >
          Update Profile Details
        </button>
      </div>
    </div>

    <!-- Profile Picture Modal -->
    <PictureModal
      :isOpen="isPictureModalOpen"
      :pictureUrl="profile.picture"
      :inert="isConfirmModalOpen"
      @close="closeAllModals"
      @change="handleChangePicture"
      @delete="handleDeletePicture"
    />

    <!-- Password Modal -->
    <FormModal
      :isOpen="isPasswordModalOpen"
      :inert="isConfirmModalOpen"
      title="Change Password"
      :form="passwordForm"
      :fields="passwordFields"
      submitText="Change Password"
      @close="closeAllModals"
      @submit="handlePasswordChange"
    />

    <!-- Details Edit Modal -->
    <FormModal
      :isOpen="isDetailsModalOpen"
      :inert="isConfirmModalOpen"
      title="Edit Profile Details"
      :form="detailsForm"
      :fields="detailsFields"
      submitText="Save Changes"
      @close="closeAllModals"
      @submit="handleDetailsEdit"
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
