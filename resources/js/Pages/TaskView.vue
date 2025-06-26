<script setup>
import { ref, h, computed } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
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
    default: null,
  },
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
        route("team.employees"),
        { dept: newDeptId },
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

// Tanstack Table columns definition
const taskTableColumns = [
  {
    accessorKey: "title",
    header: "TITLE",
  },
  {
    id: "assignees", // Unique ID for the column
    accessorFn: (row) => row.assignees.join(", "), // For filtering
    header: "ASSIGNEES",
    cell: ({ row }) => {
      const assignees = row.original.assignees;

      // Handle empty assignees
      if (!assignees || assignees.length === 0) {
        return h("span", { class: "text-gray-400 italic" }, "Unassigned");
      }

      return h(
        "div",
        { class: "flex flex-wrap gap-1 justify-center" },
        assignees.map((name) =>
          h(
            "span",
            {
              class:
                "badge bg-neutral-content text-neutral text-sm px-3.5 py-3.5",
            },
            name
          )
        )
      );
    },
  },
  {
    accessorKey: "created_at",
    header: "STARTED",
  },
  {
    header: "STATUS",
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
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">Task Management</h1>
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

    <!-- Task Table -->
    <DataTable :data="props.tasks" :columns="taskTableColumns">
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
