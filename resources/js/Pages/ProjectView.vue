<script setup>
import { h } from "vue";
import { longDate } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";

const props = defineProps({
  projects: {
    type: Array,
    default: () => [],
  },
});

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
    header: "STARTED",
    accessorFn: (row) => longDate(row.created_at),
    id: "started-date",
    cell: ({ cell }) => {
      return h("span", {}, cell.getValue());
    },
  },
  {
    id: "details",
    header: "DETAILS",
    cell: ({ row }) =>
      h(
        "button",
        {
          onClick: () => handleViewDetails(row.original),
          class:
            "btn rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
        },
        "View Details"
      ),
    enableSorting: false,
  },
];
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
    >
      <!-- Custom card layout -->
      <template #card-item="{ row }">
        <div
          class="card bg-gradient-to-r from-green-50 to-green-100 shadow-lg border-l-4 border-green-primary-1 hover:shadow-xl hover:border-pink-500 transition-all duration-300"
        >
          <div class="card-body">
            <p
              class="text-xs text-end font-medium text-gray-500 -mt-3 truncate"
            >
              {{ longDate(row.created_at) }}
            </p>

            <div class="flex flex-col">
              <h3 class="card-title text-lg font-bold text-gray-800 truncate">
                {{ row.title }}
              </h3>
              <p class="text-sm font-medium text-gray-600 truncate mb-4">
                {{ row.description }}
              </p>

              <div class="card-actions justify-end">
                <button
                  @click="handleViewDetails(row)"
                  class="btn bg-green-primary-1 rounded-full border-0 text-white hover:bg-green-primary-3"
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
