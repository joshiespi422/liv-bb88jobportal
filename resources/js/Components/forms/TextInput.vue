<script setup>
defineProps({
  modelValue: [String, Number],
  disabled: Boolean,
});
defineEmits(["update:modelValue"]);
</script>

<template>
  <input
    type="text"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    :disabled="disabled"
    class="block w-full rounded-full py-1.5 @sm:py-2 px-5 mt-0.5 shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2 disabled:bg-base-200 disabled:cursor-not-allowed disabled:ring-0"
  />
</template>
