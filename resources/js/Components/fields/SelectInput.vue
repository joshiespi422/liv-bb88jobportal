<script setup>
defineProps({
  modelValue: [String, Number],
  options: Array,
  placeholder: String,
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <select
    :value="modelValue"
    @change="$emit('update:modelValue', $event.target.value)"
    class="block w-full select h-8.5 @sm:h-10 rounded-full shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2 border-0"
  >
    <option v-if="placeholder" disabled value="">{{ placeholder }}</option>
    <option v-for="option in options" :key="option.value" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>
