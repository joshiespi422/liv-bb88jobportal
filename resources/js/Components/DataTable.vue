<script setup>
import { computed, toRefs } from "vue";
import { useVueTable, FlexRender, getCoreRowModel } from "@tanstack/vue-table";

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

const table = useVueTable({
  // Provide reactive getters for data and columns
  get data() {
    return tableData.value;
  },
  get columns() {
    return tableColumns.value;
  },
  getCoreRowModel: getCoreRowModel(),
  // add more table options here, potentially passed via props
});
</script>

<template>
  <table>
    <thead>
      <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
        <th v-for="header in headerGroup.headers" :key="header.id">
          <FlexRender
            :render="header.column.columnDef.header"
            :props="header.getContext()"
          />
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
</template>
