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
    class="overflow-x-auto rounded-3xl border-4 border-green-primary-1 bg-base-100 p-5 shadow-xl"
  >
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between items-start gap-2 p-2"
    >
      <div class="w-auto">
        <select
          :value="table.getState().pagination.pageSize"
          @change="table.setPageSize(Number($event.target.value))"
          class="select select-sm rounded-lg border border-base-content"
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
          class="block w-52 sm:w-64 px-3 py-2 border-2 text-sm border-base-content rounded-xl shadow-md focus:outline-none focus:ring-green-primary-1 focus:border-green-primary-1"
        />
        <!-- custom actions here -->
        <slot name="custom-actions"></slot>
      </div>
    </div>

    <table class="table text-center font-semibold my-5">
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
              {{ header.column.getIsSorted() === "asc" ? " 🡩" : " 🡫" }}
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in table.getRowModel().rows"
          :key="row.id"
          class="hover:bg-base-200"
        >
          <td v-for="cell in row.getVisibleCells()" :key="cell.id">
            <FlexRender
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </td>
        </tr>
        <tr v-if="table.getRowModel().rows.length === 0">
          <td :colspan="table.getHeaderGroups()[0].headers.length">
            <div role="alert" class="alert alert-soft alert-info">
              <i class="pi pi-info-circle text-xl"></i>
              <p class="text-sm font-semibold">
                No data available in the table
              </p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div
      class="flex flex-col sm:flex-row justify-between items-center gap-2 p-2"
    >
      <div class="text-xs font-semibold">
        Page
        <strong>{{ table.getState().pagination.pageIndex + 1 }}</strong> of
        <strong>{{ table.getPageCount() }}</strong>
        <span class="hidden sm:inline">
          | Showing {{ table.getRowModel().rows.length }} of
          {{ table.getPrePaginationRowModel().rows.length }} total rows
        </span>
      </div>
      <div class="flex items-center space-x-2">
        <button
          @click="table.setPageIndex(0)"
          :disabled="!table.getCanPreviousPage()"
          class="btn btn-circle border-2 border-base-content bg-green-primary-1 shadow-md hover:bg-green-primary-3 not-disabled:text-white"
        >
          <i class="pi pi-angle-double-left text-xl"></i>
        </button>
        <button
          @click="table.previousPage()"
          :disabled="!table.getCanPreviousPage()"
          class="btn btn-circle border-2 border-base-content bg-green-primary-1 shadow-md hover:bg-green-primary-3 not-disabled:text-white"
        >
          <i class="pi pi-angle-left text-xl"></i>
        </button>
        <button
          @click="table.nextPage()"
          :disabled="!table.getCanNextPage()"
          class="btn btn-circle border-2 border-base-content bg-green-primary-1 shadow-md hover:bg-green-primary-3 not-disabled:text-white"
        >
          <i class="pi pi-angle-right text-xl"></i>
        </button>
        <button
          @click="table.setPageIndex(table.getPageCount() - 1)"
          :disabled="!table.getCanNextPage()"
          class="btn btn-circle border-2 border-base-content bg-green-primary-1 shadow-md hover:bg-green-primary-3 not-disabled:text-white"
        >
          <i class="pi pi-angle-double-right text-xl"></i>
        </button>
      </div>
    </div>
  </div>
</template>
