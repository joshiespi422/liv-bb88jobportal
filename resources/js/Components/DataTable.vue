<script setup>
import {
  ref,
  computed,
  toRefs,
  onMounted,
  onUpdated,
  onBeforeUnmount,
  shallowRef,
} from "vue";
import {
  useVueTable,
  FlexRender,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
} from "@tanstack/vue-table";
import { startOfDay, endOfDay, isWithinInterval } from "date-fns";
import { parseDateString } from "../Composables/useDateFormatter";

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
  enableTooltips: {
    type: Boolean,
    default: false,
  },
  displayMode: {
    type: String,
    default: "table",
    validator: (value) => ["table", "card"].includes(value),
  },
  dateFilter: {
    type: Array,
    default: null,
  },
  filterKey: {
    type: String,
    default: "created_at",
  },
  // add more props here for other table features
});

const { data: propsData, columns: propsColumns } = toRefs(props);

// Computed properties for reactivity if props change
// const tableData = computed(() => propsData.value); // deprecated since we are using filteredTableData
const tableColumns = computed(() => propsColumns.value);

// Reactive state for table features
const sorting = ref([]);
const globalFilter = ref("");

// configure table to include date filtering
const filteredTableData = computed(() => {
  let data = propsData.value;

  // Apply date filter if it exists
  if (
    props.dateFilter &&
    Array.isArray(props.dateFilter) &&
    props.dateFilter.length === 2
  ) {
    const [startDate, endDate] = props.dateFilter;

    if (startDate && endDate) {
      data = data.filter((row) => {
        const rowDate = parseDateString(row[props.filterKey]);

        if (!rowDate) return false; // skip invalid dates

        return isWithinInterval(rowDate, {
          start: startOfDay(startDate),
          end: endOfDay(endDate),
        });
      });
    }
  }

  return data;
});

const table = useVueTable({
  // Provide reactive getters for data and columns
  get data() {
    return filteredTableData.value;
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

  enableColumnResizing: true,
  columnResizeMode: "onChange",

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

// Initialize tooltips for dynamic content
const tableRef = ref(null);
const tippy = shallowRef(null); // Tippy module reference
const tooltipInstances = ref([]); // Store individual tooltip instances

const initTooltips = async () => {
  if (!props.enableTooltips || !tableRef.value) return;

  try {
    if (!tippy.value) {
      const tippyModule = await import("tippy.js");
      tippy.value = tippyModule.default;
      await import("tippy.js/dist/tippy.css");
    }

    const elements = tableRef.value.querySelectorAll(
      "[data-tippy-content]:not([data-tippy-initialized])"
    );

    elements.forEach((el) => {
      const instance = tippy.value(el, {
        content: el.getAttribute("data-tippy-content"),
        theme: "light-border",
        arrow: true,
        placement: "bottom",
        appendTo: () => document.body,
        allowHTML: true,
        interactive: false,
        moveTransition: "transform 0.2s ease-out",
      });

      el.setAttribute("data-tippy-initialized", "true");
      tooltipInstances.value.push(instance); // Store instance
    });
  } catch (error) {
    console.error("Tooltip initialization error:", error);
  }
};

// Debounced initialization
let initTimeout = null;
const debouncedInitTooltips = () => {
  clearTimeout(initTimeout);
  initTimeout = setTimeout(initTooltips, 150);
};

onMounted(() => props.enableTooltips && debouncedInitTooltips());
onUpdated(() => props.enableTooltips && debouncedInitTooltips());

// Proper cleanup
onBeforeUnmount(() => {
  clearTimeout(initTimeout);

  // Destroy all tooltip instances
  tooltipInstances.value.forEach((instance) => {
    try {
      instance.destroy();
    } catch (e) {
      console.warn("Tooltip destroy error:", e);
    }
  });
  tooltipInstances.value = [];
});
</script>

<template>
  <div
    ref="tableRef"
    class="overflow-x-clip rounded-3xl border-4 border-green-primary-1 bg-base-100 p-5 shadow-xl"
  >
    <!-- Header Controls -->
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
      <div class="flex gap-2 w-full sm:w-auto relative">
        <input
          type="text"
          v-model="globalFilter"
          placeholder="Search"
          class="block w-52 h-10 sm:w-64 px-3 py-2 border-2 text-sm border-base-content rounded-xl shadow-md focus:outline-none focus:border-green-primary-1"
        />
        <!-- custom actions here -->
        <slot name="custom-actions" />
      </div>
    </div>

    <!-- Table View -->
    <div v-if="displayMode === 'table'" class="overflow-x-auto">
      <table
        class="table text-center font-semibold my-5"
        style="table-layout: fixed"
      >
        <thead>
          <tr
            v-for="headerGroup in table.getHeaderGroups()"
            :key="headerGroup.id"
          >
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              scope="col"
              :style="{ width: `${header.getSize()}px` }"
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
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :style="{ width: `${cell.column.getSize()}px` }"
              class="max-w-full truncate"
            >
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
    </div>

    <!-- Card View -->
    <div v-else-if="displayMode === 'card'" class="my-5">
      <div v-if="table.getRowModel().rows.length === 0" class="py-4">
        <div
          role="alert"
          class="alert alert-soft alert-info inline-flex w-full"
        >
          <i class="pi pi-info-circle text-xl"></i>
          <p class="text-sm font-semibold">No data available</p>
        </div>
      </div>

      <div
        v-else
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4"
      >
        <!-- Card Layout using slot -->
        <div v-for="row in table.getRowModel().rows" :key="row.id">
          <slot
            name="card-item"
            :row="row.original"
            :index="row.index"
            :columns="tableColumns"
            :cells="row.getVisibleCells()"
          >
            <!-- Default card layout if no slot provided -->
            <div
              class="card shadow-lg border-2 border-base-300 hover:shadow-xl transition-shadow"
            >
              <div class="card-body">
                <div
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  class="flex justify-between items-center py-2 border-b border-base-200 last:border-b-0"
                >
                  <span class="font-semibold text-base-content/70">
                    {{ cell.column.columnDef.header }}:
                  </span>
                  <span class="ms-7 truncate">
                    <FlexRender
                      :render="cell.column.columnDef.cell"
                      :props="cell.getContext()"
                    />
                  </span>
                </div>
              </div>
            </div>
          </slot>
        </div>
      </div>
    </div>

    <!-- Pagination -->
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
