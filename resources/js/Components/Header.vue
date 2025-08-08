<script setup>
import { useSidebarStore } from "../Stores/sidebarStore.js";
import { useThemeStore } from "../Stores/themeStore.js";
import { useNotificationStore } from "../Stores/notificationStore.js";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { onClickOutside } from "@vueuse/core";
import { formatDistanceToNow } from "date-fns";

const notificationStore = useNotificationStore();
const showDropdown = ref(false);
const dropdownRef = ref(null);
const bellRef = ref(null);

function toggleDropdown() {
  showDropdown.value = !showDropdown.value;
}

onClickOutside(dropdownRef, (event) => {
  if (
    dropdownRef.value &&
    !dropdownRef.value.contains(event.target) &&
    bellRef.value &&
    !bellRef.value.contains(event.target)
  ) {
    showDropdown.value = false;
  }
});

function gotoNotifications() {
  showDropdown.value = false;
  router.get(route("notifications.index"));
}

const markAllAsRead = () => notificationStore.markAllAsRead();
const deleteNotif = (id) => notificationStore.deleteNotification(id);

function formatTimeAgo(dateString) {
  return formatDistanceToNow(new Date(dateString), { addSuffix: true });
}

const sidebarStore = useSidebarStore();
const themeStore = useThemeStore();
</script>

<template>
  <div
    class="h-18 flex items-center transition-all duration-300 ease-in-out relative header"
  >
    <div class="w-2/3">
      <i
        :class="[
          'pi p-2 text-3xl ml-5 cursor-pointer relative inline-block transition-all duration-300 ease-in-out hover:scale-125 gradient-text',
          {
            'pi-angle-double-right': !sidebarStore.isCollapsed,
            'pi-angle-double-left': sidebarStore.isCollapsed,
          },
        ]"
        @click="sidebarStore.toggleSidebar"
      ></i>
    </div>
    <div class="w-1/3 flex justify-end items-center">
      <button
        @click="themeStore.toggleTheme"
        class="mr-4 rounded-full bg-gradient-to-tr from-green-secondary to-green-primary-1 hover:scale-110 transition-all duration-300 ease-in-out cursor-pointer"
        aria-label="Toggle theme"
      >
        <i
          :class="[
            'pi p-2 text-xl text-white-primary',
            themeStore.theme.value === 'fantasy' ? 'pi-moon' : 'pi-sun',
          ]"
        ></i>
      </button>
      <div class="relative">
        <i
          ref="bellRef"
          class="pi pi-bell p-2 text-xl mr-5 cursor-pointer text-white-primary bg-gradient-to-tr from-green-secondary to-green-primary-1 rounded-full hover:scale-110 transition-all duration-300 ease-in-out"
          @click="toggleDropdown"
        />
        <span
          v-if="notificationStore.unreadCount > 0"
          class="absolute -top-1 right-3 bg-red-500 text-white font-bold text-xs rounded-full h-5 w-5 flex items-center justify-center"
        >
          {{ notificationStore.unreadCount }}
        </span>

        <!-- Notification Dropdown -->
        <div
          v-if="showDropdown"
          ref="dropdownRef"
          class="absolute right-5 mt-2 w-100 bg-base-100 shadow-lg rounded-md z-50 border-3 border-green-primary-1"
        >
          <div
            class="p-3 border-b-2 border-green-primary-1 font-bold flex justify-between items-center"
          >
            Notifications

            <button
              @click="markAllAsRead"
              class="text-blue-500 underline text-sm hover:text-blue-600 cursor-pointer me-1"
            >
              Mark all as read
            </button>
          </div>

          <div class="max-h-70 overflow-y-auto list-scroll">
            <template v-if="notificationStore.notifications.length">
              <div
                v-for="notification in notificationStore.notifications"
                :key="notification.id"
                :class="[
                  'p-3 border-b border-dashed border-gray-400 cursor-pointer text-sm font-semibold',
                  { 'bg-indigo-100 text-black': !notification.read },
                ]"
              >
                <div>{{ notification.message }}</div>
                <div class="text-xs text-gray-500 mt-0.5">
                  {{ formatTimeAgo(notification.created_at) }}
                  <button
                    @click="deleteNotif(notification.id)"
                    class="text-red-500 underline text-xs hover:text-red-600 cursor-pointer ms-1"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </template>
            <div v-else class="p-3 text-center text-gray-500">
              No notifications
            </div>
          </div>

          <div
            v-if="notificationStore.total > 20"
            class="p-3 border-t text-center"
          >
            <button
              @click="gotoNotifications"
              class="text-blue-500 hover:underline font-medium"
            >
              Show all notifications ({{ notificationStore.total }})
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.header::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background-image: linear-gradient(
    to right,
    var(--color-green-primary-1) 0%,
    var(--color-green-secondary) 70%
  );
}

.gradient-text {
  background-image: linear-gradient(
    to bottom,
    var(--color-green-primary-1) 50%,
    var(--color-green-secondary) 70%
  );
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.list-scroll::-webkit-scrollbar {
  width: 6px;
}
.list-scroll::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background-color: var(--color-green-primary-1);
}
.list-scroll::-webkit-scrollbar-track {
  margin: 6px;
  background-color: transparent;
}
</style>
