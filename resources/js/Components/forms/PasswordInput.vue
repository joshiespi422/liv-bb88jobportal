<script setup>
defineProps(["modelValue"]);
defineEmits(["update:modelValue"]);
</script>

<template>
  <input
    type="password"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    class="block w-full rounded-full py-2 px-5 mt-0.5 shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2"
  />
</template>
