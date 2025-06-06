<script setup>
import { computed } from "vue";
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
} from "@headlessui/vue";

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  selected: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(["change"]);

const selectedItem = computed(() =>
  props.items.find((item) => item.id === props.selected)
);

const handleChange = (item) => {
  emit("change", item.id);
};
</script>

<template>
  <div class="w-50">
    <Listbox :model-value="selectedItem" @update:model-value="handleChange">
      <ListboxButton
        class="relative w-full cursor-default rounded-md bg-white py-2 pl-3 pr-10 text-left shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm"
      >
        <span class="block truncate">
          {{ selectedItem?.dept_name || "Select Department" }}
        </span>
        <i
          class="pi-chevron-down pointer-events-none absolute inset-y-0 right-0 ml-3 flex items-center pr-2 h-5 w-5 text-gray-400"
        />
      </ListboxButton>

      <ListboxOptions
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
      >
        <ListboxOption
          v-for="item in items"
          :key="item.id"
          :value="item"
          as="template"
          v-slot="{ active, selected }"
        >
          <li
            :class="[
              active ? 'bg-indigo-600 text-white' : 'text-gray-900',
              'relative cursor-default select-none py-2 pl-3 pr-9',
            ]"
          >
            <span
              :class="[
                selected ? 'font-semibold' : 'font-normal',
                'block truncate',
              ]"
            >
              {{ item.dept_name }}
            </span>
            <span
              v-if="selected"
              :class="[
                active ? 'text-white' : 'text-indigo-600',
                'absolute inset-y-0 right-0 flex items-center pr-4',
              ]"
            >
              <i class="pi pi-check h-5 w-5" />
            </span>
          </li>
        </ListboxOption>
      </ListboxOptions>
    </Listbox>
  </div>
</template>
