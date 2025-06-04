<script setup>
import { ref, computed, toRefs } from "vue";
import {
  useVueTable,
  FlexRender,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
} from "@tanstack/vue-table";

const props = defineProps({
  data: {
    type: Array,
    required: true,
    default: () => [],
  },
  columns: {
    type: Array,
    required: true,
    default: () => [],
  },
  // add more props here for other table features
});

const { data: propsData, columns: propsColumns } = toRefs(props);

// Computed properties for reactivity if props change
const tableData = computed(() => propsData.value);
const tableColumns = computed(() => propsColumns.value);

// Reactive state for table features
const sorting = ref([]);
const globalFilter = ref("");

const table = useVueTable({
  // Provide reactive getters for data and columns
  get data() {
    return tableData.value;
  },
  get columns() {
    return tableColumns.value;
  },

  state: {
    get sorting() {
      return sorting.value;
    },
    get globalFilter() {
      return globalFilter.value;
    },
  },
  // Handlers for state changes
  onSortingChange: (updater) => {
    sorting.value =
      typeof updater === "function" ? updater(sorting.value) : updater;
  },
  // onGlobalFilterChange: (updater) => {
  //   filter.value = typeof updater === "function" ? updater(filter.value) : updater;
  // },

  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  // add more table options here, potentially passed via props
});

const availablePageSizes = [10, 25, 50, 100];
</script>

<template>
  <div
    class="overflow-x-auto rounded-box border border-base-content/20 bg-base-100"
  >
    <div
      class="flex flex-col sm:flex-row justify-between items-center gap-2 p-2"
    >
      <div class="w-full sm:w-auto">
        <select
          :value="table.getState().pagination.pageSize"
          @change="table.setPageSize(Number($event.target.value))"
          class="block w-full pl-3 pr-5 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
        >
          <option v-for="size in availablePageSizes" :key="size" :value="size">
            Show {{ size }}
          </option>
        </select>
      </div>
      <div class="flex gap-2 w-full sm:w-auto">
        <input
          type="text"
          v-model="globalFilter"
          placeholder="Search"
          class="block w-full sm:w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
        <!-- custom actions here -->
        <slot name="custom-actions"></slot>
      </div>
    </div>

    <table class="table">
      <thead>
        <tr
          v-for="headerGroup in table.getHeaderGroups()"
          :key="headerGroup.id"
        >
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            scope="col"
            :class="{
              'cursor-pointer select-none': header.column.getCanSort(),
            }"
            @click="
              header.column.getCanSort()
                ? header.column.getToggleSortingHandler()?.($event)
                : null
            "
          >
            <FlexRender
              :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
            <span v-if="header.column.getIsSorted()">
              {{ header.column.getIsSorted() === "asc" ? "🔼" : "🔽" }}
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in table.getRowModel().rows" :key="row.id">
          <td v-for="cell in row.getVisibleCells()" :key="cell.id">
            <FlexRender
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <div
      class="flex flex-col sm:flex-row justify-between items-center gap-2 p-2"
    >
      <div class="text-sm text-gray-700">
        Page
        <strong>{{ table.getState().pagination.pageIndex + 1 }}</strong> of
        <strong>{{ table.getPageCount() }}</strong>
        <span class="hidden sm:inline">
          | Showing {{ table.getRowModel().rows.length }} of
          {{ table.getPrePaginationRowModel().rows.length }} total rows
        </span>
      </div>
      <div class="flex items-center space-x-1">
        <button
          @click="table.setPageIndex(0)"
          :disabled="!table.getCanPreviousPage()"
          class="px-2 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          First
        </button>
        <button
          @click="table.previousPage()"
          :disabled="!table.getCanPreviousPage()"
          class="px-2 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="table.nextPage()"
          :disabled="!table.getCanNextPage()"
          class="px-2 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
        <button
          @click="table.setPageIndex(table.getPageCount() - 1)"
          :disabled="!table.getCanNextPage()"
          class="px-2 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Last
        </button>
      </div>
    </div>
  </div>
</template>
