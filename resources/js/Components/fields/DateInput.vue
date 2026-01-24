<script setup>
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const props = defineProps({
  modelValue: [String, Date, Object],
  disabled: Boolean,
  placeholder: String,
  min: String,
  type: {
    type: String,
    default: "date",
  },
});
const emit = defineEmits(["update:modelValue"]);

// Helper to format the output based on type
function formatValue(value) {
  if (!value) return null;

  if (props.type === "time") {
    // value is { hours: number, minutes: number }
    const { hours, minutes } = value;
    const h = String(hours).padStart(2, "0");
    const m = String(minutes).padStart(2, "0");
    return `${h}:${m}`; // Returns "HH:mm" format
  }

  // Default Date logic
  const date = new Date(value);
  if (isNaN(date)) return null;
  return date.toISOString().split("T")[0];
}

// Helper to parse the input value for the datepicker
const internalValue = (val) => {
  if (!val) return null;
  if (props.type === "time") {
    const [hours, minutes] = val.split(":");
    return { hours: parseInt(hours), minutes: parseInt(minutes) };
  }
  return new Date(val);
};

function onUpdate(value) {
  emit("update:modelValue", formatValue(value));
}
</script>

<template>
  <Datepicker
    :model-value="internalValue(modelValue)"
    @update:model-value="onUpdate"
    :disabled="disabled"
    :time-picker="type === 'time'"
    :enable-time-picker="type === 'time'"
    :is-24="false"
    :auto-apply="true"
    :min-date="min && type === 'date' ? new Date(min) : null"
    :ui="{
      input:
        '@sm:!py-2 !py-1.5 !bg-base-100 !font-medium !rounded-xl !text-sm !text-base-content !border-base-content !shadow-md hover:!border-0 hover:!ring-indigo-600 hover:!ring-2',
      menu: '!p-0 !bg-base-100 !border-2 !border-base-content !rounded-xl !text-xs @sm:!text-sm !shadow-md hover:!border-indigo-600',
    }"
    :placeholder="
      placeholder || (type === 'time' ? 'Select time' : 'Select date')
    "
  />
</template>

<style scoped>
:deep(.dp__overlay_container) {
  background-color: var(--color-base-100);
  color: var(--color-base-content);
}
:deep(.dp__cell_inner),
:deep(.dp__calendar_header_item),
:deep(.dp__month_year_select),
:deep(.dp__time_display_block) {
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
