<script setup>
import { ref, h, computed } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { longDate } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";
import ListBox from "../Components/ListBox.vue";

const props = defineProps({
  tasks: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  currentDepartmentId: {
    type: Number,
    default: null,
  },
  currentType: {
    type: String,
    default: "employee",
  },
  activeTab: String,
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// core logic for the super_admin filter
const selectedDepartment = computed({
  // GET: This runs on initial load and whenever props change.
  get() {
    return props.currentDepartmentId;
  },
  // SET: This runs when the user selects a new item in the ListBox.
  set(newDeptId) {
    if (authUser.value.userType === "super_admin" && newDeptId) {
      router.get(
        route("task"),
        { dept: newDeptId, type: props.currentType },
        {
          preserveState: true, // Keeps Vue component state
          preserveScroll: true, // Keeps scroll position
          replace: true, // Avoids polluting browser history
        }
      );
    }
  },
});
// format of departments for the ListBox component
const departmentOptions = computed(() => {
  return props.departments.map((d) => ({ value: d.id, label: d.dept_name }));
});

// tab handling navigation
const tabs = computed(() => {
  const items = [
    { id: "active_tasks", label: "Active Tasks" },
    { id: "archived", label: "Archived" },
  ];

  if (authUser.value.userType !== "super_admin") {
    items.unshift({ id: "your_tasks", label: "Your Tasks" });
  }

  return items;
});
// handle tab navigation
function setTab(tabId) {
  if (tabId === props.activeTab) return;
  router.get(
    route("task"),
    {
      ...route().params,
      tab: tabId,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
}

// Tanstack Table columns definition
const taskTableColumns = [
  {
    accessorKey: "title",
    header: "TITLE",
  },
  {
    id: "assignees",
    accessorFn: (row) => row.assignees.map((a) => a.name).join(", "),
    header: "ASSIGNEES",
    cell: ({ row }) => {
      let assignees = [...row.original.assignees];

      if (!assignees || assignees.length === 0) {
        return h("span", { class: "text-gray-400 italic" }, "Unassigned");
      }

      // Move the current user to the top of the list
      const currentUserIndex = assignees.findIndex(
        (a) => a.id === authUser.value.id
      );
      if (currentUserIndex > -1) {
        const currentUser = assignees.splice(currentUserIndex, 1)[0];
        assignees.unshift(currentUser);
      }

      const visibleAssignees = assignees.slice(0, 3);
      const hiddenAssigneesCount = assignees.length - visibleAssignees.length;

      return h(
        "div",
        {
          class: "avatar-group p-1 -space-x-4 flex justify-center",
        },
        [
          ...visibleAssignees.map((assignee) =>
            h(
              "div",
              {
                class: "cursor-pointer hover:z-10 hover:scale-110",
                "data-tippy-content": assignee.name,
                key: assignee.id,
              },
              [
                h("div", { class: "avatar" }, [
                  h("div", { class: "w-12" }, [
                    h("img", {
                      src: assignee.picture || "/profile-images/default.png",
                      alt: assignee.name,
                    }),
                  ]),
                ]),
              ]
            )
          ),
          hiddenAssigneesCount > 0
            ? h(
                "div",
                {
                  class:
                    "avatar cursor-pointer hover:z-10 hover:scale-110 avatar-placeholder",
                  "data-tippy-content": `${hiddenAssigneesCount} more`,
                },
                [
                  h("div", { class: "w-12 bg-neutral text-neutral-content" }, [
                    `+${hiddenAssigneesCount}`,
                  ]),
                ]
              )
            : null,
        ]
      );
    },
  },
  {
    header: "STARTED",
    accessorFn: (row) => longDate(row.created_at),
    id: "started-date",
    cell: ({ cell }) => {
      return h("span", {}, cell.getValue());
    },
  },
  {
    header: "STATUS",
    accessorKey: "status",
    cell: ({ row }) => {
      const status = row.original.status;

      return h(
        "span",
        {
          class: "badge bg-neutral-content text-neutral text-sm px-3.5 py-3.5",
        },
        status
      );
    },
  },
  // {
  //   id: "details",
  //   header: "DETAILS",
  //   cell: ({ row }) =>
  //     h(
  //       "button",
  //       {
  //         onClick: () => handleViewDetails(row.original),
  //         class:
  //           "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
  //       },
  //       "View Details"
  //     ),
  //   enableSorting: false,
  // },
];

const capitalizedType = computed(() => {
  if (!props.currentType) return "";
  return props.currentType.charAt(0).toUpperCase() + props.currentType.slice(1);
});
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">{{ capitalizedType }} Task</h1>
      <div
        v-if="authUser?.userType === 'super_admin'"
        class="w-52 md:w-60 lg:w-72"
      >
        <ListBox
          v-model="selectedDepartment"
          :options="departmentOptions"
          placeholder="Select a department"
        />
      </div>
    </div>

    <div class="tabs tabs-box my-3">
      <a
        v-for="tab in tabs"
        :key="tab.id"
        @click.prevent="setTab(tab.id)"
        :class="[
          'tab',
          activeTab === tab.id ? 'tab-active font-bold' : 'hover:bg-base-300',
        ]"
      >
        {{ tab.label }}
      </a>
    </div>

    <!-- Task Table -->
    <DataTable :data="props.tasks" :columns="taskTableColumns" enable-tooltips>
      <!-- <template #custom-actions>
        <button
          @click="handleAddNewEmployee"
          class="btn rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Employee
        </button>
      </template> -->
    </DataTable>
  </div>
</template>
