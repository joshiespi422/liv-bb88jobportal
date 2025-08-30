<script setup>
defineProps(["modelValue"]);
defineEmits(["update:modelValue"]);
</script>

<template>
  <textarea
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    rows="2"
    class="block w-full rounded-lg py-2 ps-4 pe-2 mt-0.5 shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2"
  ></textarea>
</template>
