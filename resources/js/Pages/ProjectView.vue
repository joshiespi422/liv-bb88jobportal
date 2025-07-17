<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { longDate } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";

const props = defineProps({
  projects: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Tanstack Table columns definition
const projectTableColumns = [
  {
    accessorKey: "title",
    header: "TITLE",
  },
  {
    accessorKey: "description",
    header: "DESCRIPTION",
  },
  {
    id: "assignees",
    accessorFn: (row) => row.assignees.map((a) => a.name).join(", "),
    header: "ASSIGNEES",
  },
  {
    accessorFn: (row) => longDate(row.created_at),
    id: "started-date",
    header: "STARTED DATE",
  },
  {
    accessorKey: "departments",
    header: "DEPARTMENTS",
  },
];

// Assignee info
const renderAssignees = (assignees) => {
  if (!assignees || assignees.length === 0) {
    return [];
  }

  // Move current user to top (same logic as table)
  let sortedAssignees = [...assignees];
  const currentUserIndex = sortedAssignees.findIndex(
    (a) => a.id === authUser.value.id
  );
  if (currentUserIndex > -1) {
    const currentUser = sortedAssignees.splice(currentUserIndex, 1)[0];
    sortedAssignees.unshift(currentUser);
  }

  const visibleAssignees = sortedAssignees.slice(0, 3);
  const hiddenCount = sortedAssignees.length - visibleAssignees.length;

  return { visibleAssignees, hiddenCount };
};
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-2xl lg:text-3xl font-bold">Project Management</h1>
    </div>
    <!-- Project Table -->
    <DataTable
      :data="props.projects"
      :columns="projectTableColumns"
      display-mode="card"
      enable-tooltips
    >
      <!-- Custom card layout -->
      <template #card-item="{ row }">
        <div
          class="card bg-gradient-to-r from-green-50 to-green-100 shadow-lg border-l-4 border-green-primary-1 hover:shadow-xl hover:border-pink-500 transition-all duration-300"
        >
          <div class="card-body">
            <!-- Date in top right -->
            <p
              class="text-xs text-end font-medium text-gray-500 -mt-3 truncate"
            >
              {{ longDate(row.created_at) }}
            </p>

            <!-- Title and Description -->
            <div class="flex flex-col">
              <h3 class="card-title text-lg font-bold text-gray-800 truncate">
                {{ row.title }}
              </h3>
              <p class="text-sm font-medium text-gray-600 truncate mb-4">
                {{ row.description }}
              </p>

              <!-- Departments as badges -->
              <div class="mb-3">
                <div
                  class="flex flex-wrap gap-2"
                  v-if="row.departments && row.departments.length > 0"
                >
                  <div
                    v-for="dept in row.departments"
                    :key="dept"
                    class="badge badge-ghost text-sm"
                  >
                    {{ dept }}
                  </div>
                </div>
                <span v-else class="text-gray-400 italic text-sm"
                  >No departments</span
                >
              </div>

              <!-- Bottom section with assignees and button -->
              <div class="flex justify-between items-center overflow-hidden">
                <!-- Assignees Avatar Group -->
                <div class="flex-1">
                  <div
                    v-if="row.assignees && row.assignees.length > 0"
                    class="avatar-group p-1 -space-x-3"
                  >
                    <!-- Visible assignees -->
                    <div
                      v-for="assignee in renderAssignees(row.assignees)
                        .visibleAssignees"
                      class="avatar border-0 bg-neutral w-11 h-11 cursor-pointer hover:z-10 hover:scale-110 transition-transform"
                      :data-tippy-content="assignee.name"
                    >
                      <div>
                        <img
                          :src="
                            assignee.picture || '/profile-images/default.png'
                          "
                          :alt="assignee.name"
                        />
                      </div>
                    </div>

                    <!-- Counter for hidden assignees -->
                    <div
                      v-if="renderAssignees(row.assignees).hiddenCount > 0"
                      class="avatar w-11 h-11 border-0 placeholder cursor-pointer hover:z-10 hover:scale-110 transition-transform"
                      :data-tippy-content="`${
                        renderAssignees(row.assignees).hiddenCount
                      } more assignees`"
                    >
                      <div class="bg-neutral text-neutral-content rounded-full">
                        <span class="font-bold flex mt-2.5 justify-center"
                          >+{{
                            renderAssignees(row.assignees).hiddenCount
                          }}</span
                        >
                      </div>
                    </div>
                  </div>

                  <!-- No assignees state -->
                  <div v-else class="text-gray-400 italic text-sm">
                    Unassigned
                  </div>
                </div>

                <!-- View details button -->
                <button
                  @click="handleViewDetails(row)"
                  class="btn btn-sm bg-green-primary-1 rounded-full border-0 text-white hover:bg-green-primary-3"
                >
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </DataTable>
  </div>
</template>
