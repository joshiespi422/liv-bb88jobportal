<script setup>
defineProps({
  modelValue: [String, Number, Date],
  disabled: Boolean,
  type: {
    type: String,
    default: "text",
  },
  min: {
    type: String,
    default: null,
  },
});
defineEmits(["update:modelValue"]);
</script>

<template>
  <input
    :type="type"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    :disabled="disabled"
    :min="min"
    :class="[
      'block w-full rounded-full py-2 px-5 mt-0.5 shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2',
      {
        'bg-base-200 cursor-not-allowed ring-0': disabled,
      },
    ]"
  />
</template>
