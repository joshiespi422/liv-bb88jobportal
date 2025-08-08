import { defineStore } from "pinia";

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

    async deleteNotification(id) {
      try {
        await axios.delete(route("notification.destroy", { notification: id }));
        // Remove from local state and update total
        this.notifications = this.notifications.filter((n) => n.id !== id);
        this.total = Math.max(0, this.total - 1);
      } catch (error) {
        console.error("Error deleting notification:", error);
      }
    },
  },
  getters: {
    unreadCount: (state) => state.notifications.filter((n) => !n.read).length,
  },
});
