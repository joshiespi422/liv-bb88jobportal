<script setup>
import { ref } from "vue";
import DataTable from "../Components/DataTable.vue";

// Define the props received from Inertia (passed by EmployeeController)
const props = defineProps({
  employees: {
    type: Array,
    default: () => [],
  },
  // any other props your page might need
});

// Define the columns specifically for the employees table
// This structure matches what Tanstack Table expects for column definitions.
const employeeTableColumns = ref([
  // Using ref if you might dynamically change columns, otherwise const is fine
  {
    accessorKey: "name", // Corresponds to the key in your employee data objects
    header: "Name",
    // You can add cell formatting here if needed:
    // cell: info => info.getValue(), // Default rendering
    // size: 200, // Optional: define column width
  },
  {
    accessorKey: "dept_name",
    header: "Department",
  },
  {
    accessorKey: "hierarchy",
    header: "Hierarchy",
  },
  // Example of a custom cell render:
  // {
  //   accessorKey: 'id', // Assuming your employee data has an ID
  //   header: 'Actions',
  //   cell: ({ row }) => h('button', { onClick: () => handleEdit(row.original) }, 'Edit')
  //   // Make sure to import `h` from `vue` if you use render functions: import { h } from 'vue';
  //   // Or use <template> syntax within the cell definition for more complex custom cells
  //   enableSorting: false,
  // },
]);
</script>

<template>
  <div class="p-20">
    <h1>EmployeesView</h1>

    <DataTable :data="props.employees" :columns="employeeTableColumns" />
  </div>
</template>
