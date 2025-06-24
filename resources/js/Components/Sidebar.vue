<script setup>
import { ref, nextTick, computed, onMounted, onUnmounted } from "vue";
import { useSidebarStore } from "../Stores/sidebarStore.js";
import { Link, usePage, router } from "@inertiajs/vue3";
import { menuItems } from "../Data/menu.js";
import ConfirmModal from "./ConfirmModal.vue";

const sidebarStore = useSidebarStore();
const page = usePage();
const showLogoutModal = ref(false);

// logged in user data
const userType = computed(() => page.props.auth.user?.userType || "");

const promptLogout = () => {
  showLogoutModal.value = true;
};

const handleLogout = () => {
  router.post(route("logout"));
};

const menuItemRefs = ref(new Map());
const activeSubmenu = ref(null);
const activeNestedSubmenu = ref(null);
const submenuTopPosition = ref(null);
const allMenuItems = ref(menuItems);

// Recursive filtering for menu items
const filterMenuItems = (items, parentPermissions = []) => {
  return items.reduce((result, item) => {
    // Determine effective permissions
    const effectivePermissions = item.userType || parentPermissions;

    // Skip if user doesn't have permission
    if (!effectivePermissions.includes(userType.value)) {
      return result;
    }

    // Process submenus
    let newItem = { ...item };
    if (item.hasSubmenu && item.submenu) {
      const filteredSubmenu = filterMenuItems(
        item.submenu,
        effectivePermissions // Pass down permissions
      );

      // Skip if no visible subitems
      if (filteredSubmenu.length === 0) return result;

      newItem.submenu = filteredSubmenu;
    }

    result.push(newItem);
    return result;
  }, []);
};

const filteredMenuItems = computed(() => {
  return filterMenuItems(menuItems); // Use imported menuItems
});

// Inertia event listener for route changes
let removeInertiaListener;

onMounted(() => {
  // Listen for Inertia navigation events
  removeInertiaListener = router.on("navigate", () => {
    activeSubmenu.value = null;
    activeNestedSubmenu.value = null;
  });
});

onUnmounted(() => {
  // Clean up the event listener
  if (removeInertiaListener) {
    removeInertiaListener();
  }
});

const toggleSubmenu = async (itemName) => {
  if (activeSubmenu.value === itemName) {
    activeSubmenu.value = null;
    activeNestedSubmenu.value = null;
  } else {
    activeSubmenu.value = itemName;

    // calculate position when opening submenu
    await nextTick();
    const menuItem = menuItemRefs.value.get(itemName);
    if (menuItem) {
      const rect = menuItem.getBoundingClientRect();
      submenuTopPosition.value = rect.top;
    }
  }
};

const toggleNestedSubmenu = (event, itemName) => {
  event.stopPropagation();
  if (activeNestedSubmenu.value === itemName) {
    activeNestedSubmenu.value = null;
  } else {
    activeNestedSubmenu.value = itemName;
  }
};

const setMenuItemRef = (el, itemName) => {
  if (el && itemName && menuItemRefs.value) {
    menuItemRefs.value.set(itemName, el);
  }
};

const activeStates = computed(() => {
  const states = {};
  const currentRoute = page.props.ziggy;

  const checkActive = (item) => {
    if (item.routeName) {
      return currentRoute.current === item.routeName;
    }
    if (item.submenu) {
      return item.submenu.some((subItem) => checkActive(subItem));
    }
    return false;
  };

  filteredMenuItems.value.forEach((item) => {
    states[item.name] = checkActive(item);
  });

  return states;
});

const generateRoute = (item) => {
  if (item.routeQuery) {
    return route(item.routeName, item.routeQuery);
  }
  return route(item.routeName);
};
</script>

<template>
  <div
    class="h-[97%] ml-2 my-2 relative bg-gradient-to-b from-green-primary-1 to-green-secondary rounded-3xl flex flex-col"
  >
    <div class="ml-2 mr-4 py-3 h-20" v-if="!sidebarStore.isCollapsed">
      <img src="../../assets/img/bb88-logo.png" alt="" />
    </div>
    <div class="flex justify-center py-3" v-else>
      <img src="../../assets/img/bb88-solo-logo.png" alt="" class="w-13" />
    </div>

    <div class="overflow-y-auto overflow-x-hidden flex-grow sidebar-scroll">
      <!-- Main navigation -->
      <nav class="ml-3 mt-3">
        <ul>
          <li
            v-for="item in filteredMenuItems"
            :key="item.name"
            class="text-white font-medium mb-1"
          >
            <!-- Menu item -->
            <component
              :is="item.hasSubmenu ? 'div' : Link"
              :ref="(el) => setMenuItemRef(el, item.name)"
              :href="item.hasSubmenu ? undefined : generateRoute(item)"
              :class="[
                'relative p-2 rounded-l-3xl flex items-center cursor-pointer',
                {
                  'bg-base-100 text-green-primary-1 font-extrabold item-active':
                    activeStates[item.name],
                  'hover:bg-[#f9f6f630]': !activeStates[item.name],
                  'justify-center pr-4': sidebarStore.isCollapsed,
                },
              ]"
              @click="item.hasSubmenu && toggleSubmenu(item.name)"
            >
              <i :class="`${item.icon} p-2`"></i>
              <span class="ml-2" v-if="!sidebarStore.isCollapsed">{{
                item.name
              }}</span>
              <i
                v-if="item.hasSubmenu && !sidebarStore.isCollapsed"
                :class="[
                  'pi pi-chevron-right ml-auto mr-5 transition-transform duration-300',
                  {
                    'rotate-90': activeSubmenu === item.name,
                    'rotate-0': activeSubmenu !== item.name,
                  },
                ]"
              ></i>
            </component>

            <!-- Submenu items if any -->
            <transition name="submenu">
              <div
                v-if="item.hasSubmenu && activeSubmenu === item.name"
                :class="{
                  'absolute left-full ml-2 bg-gradient-to-b from-green-primary-1 to-green-secondary rounded-lg shadow-lg min-w-[200px] z-50':
                    sidebarStore.isCollapsed,
                }"
                :style="
                  sidebarStore.isCollapsed
                    ? { top: submenuTopPosition + 'px' }
                    : {}
                "
              >
                <ul
                  :class="{
                    'pl-5 border-l border-white': !sidebarStore.isCollapsed,
                    'my-3': sidebarStore.isCollapsed,
                  }"
                >
                  <li
                    v-for="subItem in item.submenu"
                    :key="subItem.name"
                    class="text-white text-sm"
                  >
                    <!-- 1st Nested submenu -->
                    <component
                      :is="subItem.hasSubmenu ? 'div' : Link"
                      :href="
                        !subItem.hasSubmenu ? generateRoute(subItem) : undefined
                      "
                      class="p-2 pl-5 flex items-center cursor-pointer hover:bg-[#f9f6f630] rounded-l-3xl"
                      @click="
                        subItem.hasSubmenu &&
                          toggleNestedSubmenu($event, subItem.name)
                      "
                    >
                      <i :class="`${subItem.icon} mr-2`"></i>
                      <span>{{ subItem.name }}</span>
                      <i
                        v-if="subItem.hasSubmenu"
                        :class="[
                          'pi pi-chevron-right ml-auto mr-3 transition-transform duration-300',
                          {
                            'rotate-90': activeNestedSubmenu === subItem.name,
                            'rotate-0': activeNestedSubmenu !== subItem.name,
                          },
                        ]"
                      ></i>
                    </component>

                    <!-- 2nd Nested submenu -->
                    <transition name="nested-submenu">
                      <div
                        v-if="
                          subItem.hasSubmenu &&
                          activeNestedSubmenu === subItem.name
                        "
                      >
                        <ul
                          :class="{
                            'ml-4 pl-2 border-l border-white':
                              sidebarStore.isCollapsed,
                            'pl-6 border-l border-white':
                              !sidebarStore.isCollapsed,
                          }"
                        >
                          <li
                            v-for="nestedItem in subItem.submenu"
                            :key="nestedItem.name"
                            class="p-2 pl-4 text-white text-xs cursor-pointer hover:bg-[#f9f6f630] rounded-l-3xl"
                          >
                            <Link
                              :href="generateRoute(nestedItem)"
                              class="block"
                            >
                              <i :class="`${nestedItem.icon} mr-2`"></i>
                              <span>{{ nestedItem.name }}</span>
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </transition>
                  </li>
                </ul>
              </div>
            </transition>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Logout section at bottom -->
    <div class="mb-2 ml-3">
      <div
        class="p-2 text-white font-medium cursor-pointer flex items-center hover:bg-[#f9f6f630] rounded-l-3xl"
        @click="promptLogout"
      >
        <i class="pi pi-sign-out p-2"></i>
        <span class="ml-2" v-if="!sidebarStore.isCollapsed">LOGOUT</span>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <ConfirmModal
    :show="showLogoutModal"
    title="Logout Confirmation"
    message="Are you sure you want to logout?"
    confirm-text="Logout"
    icon-name="pi pi-sign-out"
    @confirm="handleLogout"
    @cancel="showLogoutModal = false"
  />
</template>

<style scoped>
.item-active::before,
.item-active::after {
  content: "";
  position: absolute;
  right: 0;
  width: 20px;
  height: 20px;
  cursor: default;
  color: var(--color-base-100);
}
.item-active::after {
  top: -20px;
  box-shadow: 6px 6px 0 6px;
  border-radius: 0 0 20px 0;
}
.item-active::before {
  bottom: -20px;
  box-shadow: 6px -6px 0 6px;
  border-radius: 0 20px 0 0;
}

.sidebar-scroll::-webkit-scrollbar {
  width: 8px;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.3);
}
.sidebar-scroll::-webkit-scrollbar-track {
  background-color: transparent;
}

.submenu-enter-active,
.submenu-leave-active,
.nested-submenu-enter-active,
.nested-submenu-leave-active {
  transition: all 0.5s ease-in-out;
  overflow: hidden;
  max-height: 500px;
}
.submenu-enter-from,
.submenu-leave-to,
.nested-submenu-enter-from,
.nested-submenu-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>
