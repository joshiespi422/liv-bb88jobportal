<script setup>
const props = defineProps({
  tasks: {
    type: Array,
    default: () => [],
  },
});
</script>

<template><h1>Task Management</h1></template>
