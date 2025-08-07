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
  },
  getters: {
    unreadCount: (state) => state.notifications.filter((n) => !n.read).length,
  },
});
