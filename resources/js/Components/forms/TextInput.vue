<script setup>
defineProps(["modelValue", "disabled"]);
defineEmits(["update:modelValue"]);
</script>

<template>
  <input
    type="text"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    :disabled="disabled"
    :class="[
      'block w-full rounded-full py-2 px-5 mt-0.5 shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2',
      {
        'bg-base-200 cursor-not-allowed ring-0': disabled,
      },
    ]"
  />
</template>
