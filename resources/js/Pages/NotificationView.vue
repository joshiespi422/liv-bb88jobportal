<script setup>
import { ref, reactive } from "vue";
import { Link } from "@inertiajs/vue3";
import { shortDateTime } from "../Composables/useDateFormatter";
import { useNotificationStore } from "../Stores/notificationStore.js";
import ConfirmModal from "../Components/ConfirmModal.vue";

// The 'notifications' prop is now the Paginator object from Laravel
const props = defineProps({
  notifications: {
    type: Object,
    required: true,
  },
});

const notificationStore = useNotificationStore();

const handleDelete = (id) => {
  if (isLoading.value) return;
  isLoading.value = true;

  notificationStore.deleteNotification(id, {
    onFinish: () => {
      setTimeout(() => {
        isLoading.value = false;
      }, 1000);
    },
  });
};

// confirmation before deleting
const isConfirmModalOpen = ref(false);
const isConfirmLoading = ref(false);
const pendingAction = ref(null);
const isLoading = ref(false);
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

const handleDeleteAll = () => {
  Object.assign(confirmModalProps, {
    title: "Delete All Notifications",
    message: "Are you sure you want to delete ALL notifications?",
    confirmText: "Delete All",
    confirmButtonBg: "bg-red-600 hover:bg-red-700",
    iconName: "pi pi-times-circle",
    iconColor: "text-red-600",
    iconBgColor: "text-red-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    notificationStore.deleteAllNotifications({
      onFinish: () => {
        closeConfirmModal();
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  isConfirmModalOpen.value = true;
};
</script>

<template>
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">Your Notifications</h1>

      <button
        v-if="notifications.data.length > 0"
        @click="handleDeleteAll"
        class="btn btn-error text-white"
      >
        Delete All
      </button>
    </div>

    <div class="space-y-3">
      <div
        v-for="notification in notifications.data"
        :key="notification.id"
        @click="
          !isLoading && notificationStore.handleNotificationClick(notification)
        "
        class="px-4 py-2 border-2 rounded-lg shadow-md"
        :class="{
          'bg-pink-50 border-pink-200 cursor-pointer':
            notification.read && !isLoading,
          'bg-blue-50 border-blue-200 cursor-pointer':
            !notification.read && !isLoading,
          'bg-gray-50 border-gray-200 opacity-50 cursor-not-allowed': isLoading,
        }"
      >
        <div class="flex justify-between items-center font-semibold">
          <p class="text-gray-800 truncate">
            {{ notification.message || "No message content." }}
          </p>
          <button
            @click.stop="handleDelete(notification.id)"
            class="btn btn-circle btn-sm btn-error text-white"
            :disabled="isLoading"
          >
            <i class="pi pi-times" />
          </button>
        </div>
        <div class="text-sm text-gray-500 font-semibold flex items-center">
          <span>{{ shortDateTime(notification.created_at) }}</span>
          <span
            v-if="!notification.read"
            class="badge border-0 ms-2 text-blue-800 bg-blue-200"
          >
            New
          </span>
        </div>
      </div>

      <div
        v-if="notifications.data.length === 0"
        class="text-center opacity-70 text-xl font-semibold bg-base-300 py-8 rounded-2xl"
      >
        You have no notifications
      </div>
      <div
        v-if="notifications.links.length > 3"
        class="mt-8 flex flex-wrap justify-center items-center space-x-1"
      >
        <template v-for="(link, index) in notifications.links" :key="index">
          <span
            v-if="!link.url"
            class="px-4 py-2 text-sm opacity-50 rounded-xl border-2 cursor-not-allowed"
            v-html="link.label"
          />

          <Link
            v-else
            :href="link.url"
            class="px-4 py-2 text-sm border-2 rounded-xl"
            :class="{
              'bg-blue-600 text-white font-bold': link.active,
              'bg-gray-200 text-gray-600 hover:bg-gray-300': !link.active,
            }"
            v-html="link.label"
          />
        </template>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="isConfirmModalOpen"
      v-bind="confirmModalProps"
      :loading="isConfirmLoading"
      @cancel="closeConfirmModal"
      @confirm="executeConfirm"
    />
  </div>
</template>
