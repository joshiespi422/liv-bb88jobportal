<script setup>
import { computed, ref } from "vue";
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
} from "@headlessui/vue";
import { onClickOutside } from "@vueuse/core"; // Correct import from @vueuse/core

const props = defineProps({
  modelValue: [Array, Object],
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: "Select...",
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  disabled: Boolean,
});

const emit = defineEmits(["update:modelValue"]);

const query = ref("");
const open = ref(false);

// Create a ref for the component's root element
const comboboxRoot = ref(null);

// Use the onClickOutside composable
// It will call closeDropdown() when a click occurs outside the element referenced by comboboxRoot
onClickOutside(comboboxRoot, () => {
  open.value = false;
});

const selectedValue = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const filteredOptions = computed(() =>
  query.value === ""
    ? props.options
    : props.options.filter((option) =>
        option.name.toLowerCase().includes(query.value.toLowerCase())
      )
);

// Show placeholder only if no items are selected in multiple mode
const placeholderText = computed(() => {
  if (props.multiple && selectedValue.value?.length > 0) {
    return "";
  }
  return props.placeholder;
});

// Function to remove a selected item (for multiple mode)
const removeValue = (itemToRemove) => {
  if (props.multiple) {
    selectedValue.value = selectedValue.value.filter(
      (item) => item.id !== itemToRemove.id
    );
  }
};

// Keep the list open after selection in multiple mode
const handleSelection = () => {
  if (!props.multiple) {
    open.value = false;
  }
  // Clear the query after selection to keep the input clean
  query.value = "";
};
</script>

<template>
  <Combobox
    ref="comboboxRoot"
    v-model="selectedValue"
    :multiple="multiple"
    as="div"
    class="relative"
    @update:modelValue="handleSelection"
  >
    <div
      :class="[
        'relative block w-full rounded-full mt-0.5 shadow-md text-sm font-semibold ring focus-within:outline-none focus-within:ring-2 overflow-hidden',
        {
          'bg-base-200 cursor-not-allowed ring-0': disabled,
          'ring-indigo-600': open,
        },
      ]"
    >
      <div class="flex flex-wrap items-center gap-1 p-1.5 pr-10">
        <template v-if="multiple && selectedValue?.length">
          <span
            v-for="value in selectedValue"
            :key="value.id"
            class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700"
          >
            {{ value.name }}
            <button
              type="button"
              @click.prevent="removeValue(value)"
              class="ml-1.5 inline-flex flex-shrink-0 rounded-full p-0.5 text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500"
            >
              <span class="sr-only">Remove {{ value.name }}</span>
              <i class="pi pi-times h-3 w-3" aria-hidden="true"></i>
            </button>
          </span>
        </template>

        <ComboboxInput
          :class="[
            'w-0 flex-1 border-none bg-transparent p-0 text-sm focus:ring-0',
            { 'cursor-not-allowed': disabled },
          ]"
          :display-value="
            multiple ? () => query : (option) => option?.name || ''
          "
          :placeholder="placeholderText"
          :disabled="disabled"
          @change="query = $event.target.value"
          @focus="open = true"
        />
      </div>

      <ComboboxButton
        @click="open = !open"
        class="absolute inset-y-0 right-0 flex items-center pr-4"
      >
        <i
          class="pi pi-chevron-down h-5 w-5 mt-1 text-gray-400"
          aria-hidden="true"
        />
      </ComboboxButton>
    </div>

    <Transition
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <ComboboxOptions
        v-show="open"
        static
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
      >
        <div
          v-if="filteredOptions.length === 0 && query !== ''"
          class="relative cursor-default select-none px-4 py-2 text-gray-700"
        >
          Nothing found.
        </div>

        <ComboboxOption
          v-for="option in filteredOptions"
          v-slot="{ active, selected }"
          :key="option.id"
          :value="option"
          as="template"
        >
          <li
            :class="[
              'relative cursor-default select-none py-2 pl-10 pr-4',
              active ? 'bg-indigo-600 text-white' : 'text-gray-900',
            ]"
          >
            <span
              :class="[
                'block truncate',
                selected ? 'font-medium' : 'font-normal',
              ]"
            >
              {{ option.name }}
            </span>
            <span
              v-if="selected"
              :class="[
                'absolute inset-y-0 left-0 flex items-center pl-3',
                active ? 'text-white' : 'text-indigo-600',
              ]"
            >
              <i class="pi pi-check h-5 w-5" aria-hidden="true" />
            </span>
          </li>
        </ComboboxOption>
      </ComboboxOptions>
    </Transition>
  </Combobox>
</template>
