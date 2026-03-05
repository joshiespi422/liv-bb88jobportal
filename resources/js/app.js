import "./bootstrap";
import "./echo";

import { createApp, h } from "vue";
import { createInertiaApp, Head } from "@inertiajs/vue3";
import { createPinia } from "pinia";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import "primeicons/primeicons.css";
import AppLayout from "./Layouts/AppLayout.vue";

const appName = import.meta.env.VITE_APP_NAME || "BB88 Job Portal";

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  resolve: (name) => {
    const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
    const page = pages[`./Pages/${name}.vue`];
    // fallback layout
    if (page.default.layout === undefined) {
      page.default.layout = AppLayout;
    }

    return page;
  },
  setup({ el, App, props, plugin }) {
    // always unmount any existing app to prevent duplicate hydration
    if (el.__vue_app__) {
      el.__vue_app__.unmount();
      el.innerHTML = "";
    }

    const app = createApp({ render: () => h(App, props) });
    app.use(plugin);
    app.use(ZiggyVue);
    app.use(createPinia());
    app.component("Head", Head);
    app.mount(el);
  },
});
