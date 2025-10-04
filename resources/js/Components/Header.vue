<script setup>
import { useSidebarStore } from "../Stores/sidebarStore.js";
import { useThemeStore } from "../Stores/themeStore.js";
import { useNotificationStore } from "../Stores/notificationStore.js";
import { ref, computed } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import { onClickOutside } from "@vueuse/core";
import { formatDistanceToNow } from "date-fns";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// display purpose only
const formattedDetails = computed(() => {
  if (!authUser.value.userType) return "";

  if (authUser.value.userType === "super_admin") return "Super Admin";
  if (authUser.value.userType === "employee")
    return `${authUser.value.department.name} - ${authUser.value.hierarchy}`;
  if (authUser.value.userType === "intern")
    return `${authUser.value.department.name} - Intern`;
});
const formattedUserType = computed(() => {
  if (!authUser.value.userType) return "";

  if (authUser.value.userType === "super_admin") return "Super Admin";
  if (authUser.value.userType === "employee") return "Employee";
  if (authUser.value.userType === "intern") return "Intern";
});

// --- Profile Dropdown Logic ---
const showProfileDropdown = ref(false);
const profileDropdownRef = ref(null);
const avatarRef = ref(null);
function toggleProfileDropdown() {
  showProfileDropdown.value = !showProfileDropdown.value;
}
onClickOutside(profileDropdownRef, (event) => {
  // Close if click is outside the dropdown AND the avatar button
  if (
    profileDropdownRef.value &&
    !profileDropdownRef.value.contains(event.target) &&
    avatarRef.value &&
    !avatarRef.value.contains(event.target)
  ) {
    showProfileDropdown.value = false;
  }
});

// --- Notification Dropdown Logic ---
const notificationStore = useNotificationStore();
const showNotificationDropdown = ref(false);
const notificationDropdownRef = ref(null);
const bellRef = ref(null);
function toggleNotificationDropdown() {
  showNotificationDropdown.value = !showNotificationDropdown.value;
}
onClickOutside(notificationDropdownRef, (event) => {
  if (
    notificationDropdownRef.value &&
    !notificationDropdownRef.value.contains(event.target) &&
    bellRef.value &&
    !bellRef.value.contains(event.target)
  ) {
    showNotificationDropdown.value = false;
  }
});
// notification actions
function gotoNotifications() {
  showNotificationDropdown.value = false;
  router.get(route("notification"));
}
function handleNotificationClick(notification) {
  showNotificationDropdown.value = false;
  notificationStore.handleNotificationClick(notification);
}
const markAllAsRead = () => notificationStore.markAllAsRead();
const deleteNotif = (id) => {
  showNotificationDropdown.value = false;
  notificationStore.deleteNotification(id);
};
function formatTimeAgo(dateString) {
  return formatDistanceToNow(new Date(dateString), { addSuffix: true });
}

const sidebarStore = useSidebarStore();
const themeStore = useThemeStore();
</script>

<template>
  <div
    class="@container h-18 flex items-center transition-all duration-300 ease-in-out relative header"
  >
    <div class="w-2/3 flex items-center">
      <i
        :class="[
          'pi p-2 text-3xl ml-5 cursor-pointer relative inline-block transition-all duration-300 ease-in-out hover:scale-125 gradient-text',
          {
            'pi-angle-double-left': !sidebarStore.isCollapsed,
            'pi-angle-double-right': sidebarStore.isCollapsed,
          },
        ]"
        @click="sidebarStore.toggleSidebar"
      ></i>
      <div class="ms-2 @xl:ms-5 hidden @lg:block">
        <p class="font-mono font-semibold">{{ formattedDetails }}</p>
      </div>
    </div>
    <div class="w-1/3 flex justify-end items-center">
      <div class="relative">
        <button
          ref="avatarRef"
          @click="toggleProfileDropdown"
          class="mr-4 hover:scale-110 transition-all duration-300 ease-in-out cursor-pointer"
          aria-label="Open user menu"
        >
          <div class="avatar">
            <div class="w-9 border-2 border-indigo-500 rounded-full">
              <img :src="authUser.picture" alt="User avatar" />
            </div>
          </div>
        </button>

        <div
          v-if="showProfileDropdown"
          ref="profileDropdownRef"
          class="absolute -right-20 @sm:-right-10 @md:right-0 mt-2 w-64 @sm:w-72 origin-top-right rounded-md bg-base-100 shadow-lg z-20 border-3 border-indigo-500"
        >
          <div class="p-4 text-center">
            <img
              class="w-20 h-20 @sm:w-24 @sm:h-24 rounded-full mx-auto border-4 border-indigo-300"
              :src="authUser.picture"
              alt="User avatar"
            />
            <h3 class="mt-2 @sm:text-lg text-base font-semibold truncate">
              {{ authUser.name }}
            </h3>
            <p class="text-sm text-base-content/70 truncate">
              {{ authUser.email }}
            </p>
            <div class="divider my-2"></div>
            <div class="text-left text-sm space-y-1">
              <p class="font-bold truncate">
                <strong class="font-normal">Role:</strong>
                {{ formattedUserType }}
              </p>
              <p v-if="authUser.department" class="font-bold truncate">
                <strong class="font-normal">Department:</strong>
                {{ authUser.department.name }}
              </p>
              <p v-if="authUser.hierarchy" class="font-bold truncate">
                <strong class="font-normal">Hierarchy:</strong>
                {{ authUser.hierarchy }}
              </p>
            </div>
          </div>
          <div class="p-2">
            <Link
              :href="route('profile')"
              @click="showProfileDropdown = false"
              class="block w-full text-center px-4 py-2 text-sm font-bold text-white-primary bg-gradient-to-tr from-green-secondary to-green-primary-1 rounded-md hover:opacity-90 transition-opacity"
            >
              View Profile
            </Link>
          </div>
        </div>
      </div>
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
          @click="toggleNotificationDropdown"
        />
        <span
          v-if="notificationStore.unreadCount > 0"
          class="absolute -top-1 right-3 bg-red-500 text-white font-bold text-xs rounded-full h-5 w-5 flex items-center justify-center"
        >
          {{ notificationStore.unreadCount }}
        </span>

        <!-- Notification Dropdown -->
        <div
          v-if="showNotificationDropdown"
          ref="notificationDropdownRef"
          class="absolute right-2 @sm:right-5 mt-2 w-[95vw] @sm:w-[350px] @md:w-96 @xl:w-100 bg-base-100 shadow-lg rounded-md z-20 border-3 border-green-primary-1"
        >
          <div
            class="p-3 border-b-2 border-green-primary-1 text-sm @md:text-base font-semibold @md:font-bold flex justify-between items-center"
          >
            Notifications

            <button
              @click="markAllAsRead"
              class="text-blue-500 underline text-sm font-semibold @md:font-bold hover:text-blue-600 cursor-pointer me-1"
            >
              Mark all as read
            </button>
          </div>

          <div class="max-h-70 overflow-y-auto list-scroll">
            <template v-if="notificationStore.notifications.length">
              <div
                v-for="notification in notificationStore.notifications"
                :key="notification.id"
                @click="handleNotificationClick(notification)"
                :class="[
                  'p-1.5 @md:p-3 border-b border-dashed border-gray-400 cursor-pointer text-sm font-semibold',
                  { 'bg-indigo-100 text-black': !notification.read },
                ]"
              >
                <div class="truncate">{{ notification.message }}</div>
                <div class="text-xs text-gray-500 mt-0.5">
                  {{ formatTimeAgo(notification.created_at) }}
                  <button
                    @click.stop="deleteNotif(notification.id)"
                    class="text-red-500 underline text-xs hover:text-red-600 cursor-pointer ms-1"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </template>
            <div
              v-else
              class="py-6 font-semibold italic text-sm @md:text-base text-center opacity-70"
            >
              No notifications
            </div>
          </div>

          <div class="p-1.5 @sm:p-2.5 border-t text-center">
            <button
              @click="gotoNotifications"
              class="text-blue-500 hover:underline text-sm @md:text-base font-medium cursor-pointer"
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
