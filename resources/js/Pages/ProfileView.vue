<script setup>
import { ref, reactive } from "vue";
import { useForm } from "@inertiajs/vue3";
import PictureModal from "../Components/PictureModal.vue";
import ConfirmModal from "../Components/ConfirmModal.vue";

// Props received from Inertia
const props = defineProps({
  profile: Object,
});

// State for modals
const isPictureModalOpen = ref(false);
const isConfirmModalOpen = ref(false);
// Holds the action to be executed on confirmation
const pendingAction = ref(null);

const form = useForm({
  picture: null,
});

// Holds the properties for the confirmation modal
const confirmModalProps = reactive({
  title: "",
  message: "",
  confirmText: "",
  confirmButtonBg: "",
  iconName: "",
});

const openPictureModal = () => {
  isPictureModalOpen.value = true;
};

const closeCancelModal = () => {
  isConfirmModalOpen.value = false;
};

const closeAllModals = () => {
  isPictureModalOpen.value = false;
  isConfirmModalOpen.value = false;
};

// --- Change Picture Flow ---
const handleChangeRequest = (imageBlob) => {
  form.picture = imageBlob; // Store the cropped blob in the form

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
    form.post(route("profile.picture.update"), {
      onSuccess: () => closeAllModals(),
    });

  isConfirmModalOpen.value = true;
};

// --- Delete Picture Flow ---
const handleDeleteRequest = () => {
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
    form.delete(route("profile.picture.delete"), {
      onSuccess: () => closeAllModals(),
    });

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
    </div>

    <!-- Profile Picture Modal -->
    <PictureModal
      :isOpen="isPictureModalOpen"
      :pictureUrl="profile.picture"
      :inert="isConfirmModalOpen"
      @close="closeAllModals"
      @change="handleChangeRequest"
      @delete="handleDeleteRequest"
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="isConfirmModalOpen"
      v-bind="confirmModalProps"
      @cancel="closeCancelModal"
      @confirm="executeConfirm"
    />
  </div>
</template>
