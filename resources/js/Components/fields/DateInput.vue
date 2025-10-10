<script setup>
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const props = defineProps({
  modelValue: [String, Date],
  disabled: Boolean,
  placeholder: {
    type: String,
    default: "Select date",
  },
  min: {
    type: String,
    default: null,
  },
});
const emit = defineEmits(["update:modelValue"]);

function formatDate(date) {
  if (!(date instanceof Date) || isNaN(date)) return null;
  return date.toISOString().split("T")[0];
}

function onUpdate(value) {
  emit("update:modelValue", formatDate(value));
}
</script>

<template>
  <Datepicker
    :model-value="modelValue ? new Date(modelValue) : null"
    @update:model-value="onUpdate"
    :disabled="disabled"
    :enable-time-picker="false"
    :auto-apply="true"
    :min-date="min ? new Date(min) : null"
    :ui="{
      input:
        '@sm:!py-2 !py-1.5 !bg-base-100 !font-medium !rounded-xl !text-sm !text-base-content !border-base-content !shadow-md hover:!border-0 hover:!ring-indigo-600 hover:!ring-2',
      menu: '!p-0 !bg-base-100 !border-2 !border-base-content !rounded-xl !text-xs @sm:!text-sm !shadow-md hover:!border-indigo-600',
    }"
    :placeholder="placeholder"
  />
</template>

<style scoped>
:deep(.dp__cell_inner),
:deep(.dp__calendar_header_item),
:deep(.dp__month_year_select) {
  color: var(--color-base-content);
}
:deep(.dp__cell_inner):hover,
:deep(.dp__action_cancel):hover,
:deep(.dp__month_year_select):hover {
  background-color: var(--color-base-300);
}
:deep(.dp__cell_offset),
:deep(.dp__selection_preview) {
  color: var(--color-slate-500);
}
:deep(.dp__range_between) {
  background-color: var(--color-green-primary-1);
  color: var(--color-white);
  border: 0;
}
:deep(.dp__range_start),
:deep(.dp__range_end) {
  color: var(--color-white);
}
:deep(.dp__today) {
  background-color: var(--color-indigo-500);
  color: var(--color-white);
  border: 0;
}
:deep(.dp__action_cancel) {
  color: var(--color-base-content);
  border: 0;
}
:deep(.dp__action_select) {
  background-color: var(--color-indigo-500);
}
:deep(.dp__action_select):hover {
  background-color: var(--color-indigo-600);
}
:deep(.dp__overlay) {
  border-radius: 12px;
}
</style>
