import { defineStore } from "pinia";
import { computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

export const useNotificationStore = defineStore("notification", {
  state: () => ({
    notifications: [],
    total: 0,
    loaded: false,
  }),
  actions: {
    async fetchNotifications() {
      try {
        const response = await axios.get(route("notification.latest"));
        this.notifications = response.data.notifications;
        this.total = response.data.total;
        this.loaded = true;
      } catch (error) {
        console.error("Error fetching notifications:", error);
      }
    },

    async markAllAsRead() {
      try {
        await axios.put(route("notification.markAllAsRead"));
        // Update local state without refetching
        this.notifications = this.notifications.map((n) => ({
          ...n,
          read: true,
        }));
      } catch (error) {
        console.error("Error marking notifications as read:", error);
      }
    },

    deleteNotification(id, { onFinish } = {}) {
      router.delete(route("notification.destroy", { notification: id }), {
        preserveScroll: true,
        onSuccess: () => {
          if (this.notifications.length === 1) {
            // If the deleted latest notification was the last one, refetch
            this.fetchNotifications();
          } else {
            // Remove from local state and update total
            this.notifications = this.notifications.filter((n) => n.id !== id);
            this.total = Math.max(0, this.total - 1);
          }
        },
        onFinish,
      });
    },

    deleteAllNotifications({ onFinish } = {}) {
      router.delete(route("notification.destroyAll"), {
        preserveScroll: true,
        onSuccess: () => {
          // Reset local state
          this.notifications = [];
          this.total = 0;
        },
        onFinish,
      });
    },

    async markAsRead(id) {
      try {
        await axios.patch(
          route("notification.markAsRead", { notification: id })
        );
        // Optimistically update local state
        const index = this.notifications.findIndex((n) => n.id === id);
        if (index !== -1) {
          this.notifications[index].read = true;
        }
      } catch (error) {
        console.error("Error marking notification as read:", error);
      }
    },

    async handleNotificationClick(notification) {
      // Mark as read if unread
      if (!notification.read) {
        await this.markAsRead(notification.id);
      }

      // Navigate based on notifiable type
      switch (notification.notifiable_type) {
        case "App\\Models\\Task":
          this.navigateToTask(notification);
          break;
        case "App\\Models\\Accomplishment":
          this.navigateToAccomplishment(notification);
          break;
        case "App\\Models\\Project":
          this.navigateToProject(notification);
          break;
        case "App\\Models\\Leave":
          this.navigateToLeave(notification);
          break;
        case "App\\Models\\Comment":
          // If the notification is for a comment, redirect to the comment's parent.
          const parent = notification.notifiable.commentable;
          if (!parent) return; // Safety check

          // Create a "proxy" notification object to pass to the existing navigation functions.
          const parentNotification = { notifiable: parent };

          switch (notification.notifiable.commentable_type) {
            case "App\\Models\\Task":
              this.navigateToTask(parentNotification);
              break;
            case "App\\Models\\Accomplishment":
              this.navigateToAccomplishment(parentNotification);
              break;
            case "App\\Models\\Project":
              this.navigateToProject(parentNotification);
              break;
          }
          break;
        // Add more cases as needed
      }
    },

    navigateToTask(notification) {
      const task = notification.notifiable;
      const userType = task.user_type.type_name;
      let activeTab = "active_tasks";

      // Determine tab based on task status
      if (task.status.status_name === "done") {
        activeTab = "archived";
      } else if (task.users.some((u) => u.id === authUser.value.id)) {
        activeTab = "your_tasks";
      }

      const routeParams = {
        tab: activeTab,
        type: userType,
        open: task.id,
      };

      // Add dept only for super_admin
      if (authUser.value.userType === "super_admin") {
        routeParams.dept = task.department?.id;
      }

      router.visit(route("task", routeParams), {
        preserveState: false,
        preserveScroll: true,
        replace: false,
      });
    },

    navigateToAccomplishment(notification) {
      const accomplishment = notification.notifiable;
      const userType = accomplishment.user.user_type.type_name;
      let activeTab = "all_accomplishments";

      // Determine tab based on ownership
      if (accomplishment.user.id === authUser.value.id) {
        activeTab = "your_accomplishments";
      }

      const routeParams = {
        tab: activeTab,
        type: userType,
        open: accomplishment.id,
      };

      // Add dept only for super_admin
      if (authUser.value.userType === "super_admin") {
        routeParams.dept = accomplishment.tasks[0]?.department?.id;
      }

      router.visit(route("accomplishment", routeParams), {
        preserveState: false,
        preserveScroll: true,
        replace: false,
      });
    },

    navigateToProject(notification) {
      const project = notification.notifiable;
      router.visit(route("project", { open: project.id }), {
        preserveState: false,
        preserveScroll: true,
        replace: false,
      });
    },

    navigateToLeave(notification) {
      const leave = notification.notifiable;
      let activeTab = "regular";

      // Determine tab based on leave type
      if (leave.leave_type.name === "Special") {
        activeTab = "special";
      }

      const routeParams = {
        tab: activeTab,
        open: leave.id,
      };

      // Add dept only for super_admin
      if (authUser.value.userType === "super_admin") {
        routeParams.dept = leave.user?.employee_details?.department?.id;
      }

      router.visit(route("leave", routeParams), {
        preserveState: false,
        preserveScroll: true,
        replace: false,
      });
    },
  },
  getters: {
    unreadCount: (state) => state.notifications.filter((n) => !n.read).length,
  },
});
