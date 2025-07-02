<script setup>
import { computed, useAttrs } from "vue";

const attrs = useAttrs();
const props = defineProps({
  modelValue: File,
  accept: {
    type: String,
    default: "*/*",
  },
  maxSize: {
    type: Number,
    default: 5 * 1024 * 1024,
  },
});

// Separate non-change attributes for input binding
const inputAttrs = computed(() => {
  const { onChange, ...rest } = attrs;
  return rest;
});

// Format max size for display
const formattedMaxSize = computed(() => {
  const sizeMB = props.maxSize / (1024 * 1024);
  return `${sizeMB} MB`;
});

// Format accepted file types for display
const formattedAcceptTypes = computed(() => {
  if (!props.accept || props.accept === "*/*") return "any file type";

  return props.accept
    .split(",")
    .map((type) => {
      type = type.trim();
      if (type.startsWith(".")) {
        return type.slice(1).toUpperCase();
      }
      if (type.includes("/")) {
        return type.split("/")[1].toUpperCase();
      }
      return type;
    })
    .join(", ");
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <input
    type="file"
    v-bind="inputAttrs"
    :accept="accept"
    @change="$emit('update:modelValue', $event.target.files[0])"
    class="file-input file-input-neutral block w-full shadow-md rounded-xl text-sm font-semibold border-0 ring focus:outline-none focus:ring-2"
  />
  <small class="font-semibold text-gray-500 ms-3">
    Max size: {{ formattedMaxSize }} • Accepted: {{ formattedAcceptTypes }}
  </small>
</template>
