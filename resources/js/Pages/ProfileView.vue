<script setup>
import { ref, reactive, computed } from "vue";
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

// Fields to be displayed in the profile view
const profileFields = [
  { key: "name", label: "Full Name" },
  { key: "position", label: "Position" },
  { key: "department", label: "Department" },
  { key: "qr_code", label: "QR Code" },
  { key: "hierarchy", label: "Hierarchy" },
  { key: "school", label: "School" },
  { key: "address", label: "Address" },
  { key: "bday", label: "Birthday" },
  { key: "gender", label: "Gender" },
];
// Filter fields to only those present in profile data
const visibleFields = computed(() => {
  return profileFields.filter((field) =>
    props.profile.hasOwnProperty(field.key)
  );
});
// Format values with fallback
const displayValue = (value) => {
  return value || "N/A";
};

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
  name: props.profile.name || "",
  position: props.profile.position || "",
  department: props.profile.department || "",
  hierarchy: props.profile.hierarchy || "",
  school: props.profile.school || "",
  qr_code: props.profile.qr_code || "",
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
    attrs: { disabled: true },
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
    component: TextInput,
    attrs: { type: "date", required: true },
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
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="border-4 border-green-primary-1 max-w-6xl mx-auto rounded-3xl shadow-xl/20"
    >
      <div
        class="h-20 bg-gradient-to-b from-green-primary-1 to-green-secondary rounded-t-2xl"
      />
      <!-- Clickable profile picture -->
      <div
        class="flex flex-col items-center md:flex-row md:justify-between p-6 mx-6"
      >
        <div class="flex items-center gap-4">
          <div class="relative group cursor-pointer" @click="openPictureModal">
            <img
              :src="profile.picture || '/profile-images/default.png'"
              class="w-28 h-28 rounded-full object-cover shadow-xl/20"
            />
            <div
              class="absolute inset-0 bg-black/30 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
            >
              <span class="text-white font-medium">Edit</span>
            </div>
          </div>
          <div>
            <h3 class="text-2xl font-bold">{{ profile.name }}</h3>
            <p class="text-gray-500 font-semibold">{{ profile.email }}</p>
          </div>
        </div>

        <button
          @click="openDetailsModal"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          <i class="pi pi-pen-to-square mr-2" />
          Edit
        </button>
      </div>

      <!-- User information -->
      <div class="grid md:grid-cols-2 gap-4 mx-6">
        <div v-for="field in visibleFields" :key="field.key" class="w-full">
          <p class="text-sm font-semibold ms-5">{{ field.label }}</p>
          <input
            :value="displayValue(profile[field.key])"
            type="text"
            class="input font-bold w-[95%] rounded-full px-5"
            disabled
          />
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4 p-6 mx-6">
        <div>
          <h3 class="text-lg font-semibold mb-1">My Email Address</h3>
          <div class="flex items-center gap-2">
            <i
              class="pi pi-envelope rounded-full p-2.5 text-lg text-white-primary bg-green-primary-1"
            ></i>
            <p class="font-semibold">{{ profile.email }}</p>
          </div>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-1">Change Password</h3>
          <div class="flex items-center gap-2">
            <input
              value="**********"
              type="text"
              class="input font-bold rounded-full px-5"
              disabled
            />
            <button
              @click="openPasswordModal"
              class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
            >
              <i class="pi pi-lock mr-2" />
              Change
            </button>
          </div>
        </div>
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
      title="CHANGE PASSWORD"
      :form="passwordForm"
      :fields="passwordFields"
      submitText="Submit"
      @close="closeAllModals"
      @submit="handlePasswordChange"
    />

    <!-- Details Edit Modal -->
    <FormModal
      :isOpen="isDetailsModalOpen"
      :inert="isConfirmModalOpen"
      title="EDIT PROFILE DETAILS"
      :form="detailsForm"
      :fields="detailsFields"
      submitText="Save"
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
