<script setup>
import { computed } from "vue";
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
} from "@headlessui/vue";
import "primeicons/primeicons.css";

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    required: true,
  },
  options: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: "Select an option",
  },
  hasFooter: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue"]);

const selectedLabel = computed(() => {
  const selectedOption = props.options.find(
    (option) => option.value === props.modelValue,
  );
  return selectedOption ? selectedOption.label : props.placeholder;
});
</script>

<template>
  <Listbox
    :model-value="props.modelValue"
    @update:model-value="(value) => emit('update:modelValue', value)"
    v-slot="{ open }"
  >
    <div class="relative">
      <ListboxButton
        class="relative text-sm @sm:text-base w-full cursor-pointer rounded-xl py-2 pl-3 pr-10 text-left border-2 border-green-primary-1 shadow-xl"
      >
        <span class="block truncate font-semibold">{{ selectedLabel }}</span>
        <span
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
        >
          <i
            class="pi pi-chevron-down h-5 w-5 mt-1 text-green-primary-1"
            aria-hidden="true"
          />
        </span>
      </ListboxButton>

      <transition
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="open"
          class="absolute mt-2 w-full rounded-md bg-base-100 ring-2 ring-green-primary-1 ring-opacity-5 z-10 overflow-hidden"
        >
          <!-- Scrollable list -->
          <ListboxOptions
            class="text-sm @sm:text-base max-h-64 overflow-auto py-1 focus:outline-none list-scroll"
          >
            <ListboxOption
              v-for="option in props.options"
              :key="option.value"
              :value="option.value"
              v-slot="{ active, selected }"
              as="template"
            >
              <li
                :class="[
                  active ? 'bg-green-primary-2 text-white' : '',
                  'py-1 @sm:py-2 relative cursor-pointer select-none pl-10 pr-4',
                ]"
              >
                <span
                  :class="[
                    selected ? 'font-medium' : 'font-normal',
                    'block truncate',
                  ]"
                >
                  {{ option.label }}
                </span>
                <span
                  v-if="selected"
                  :class="[
                    'absolute inset-y-0 left-0 flex items-center pl-3 text-lg',
                    active ? 'text-white' : 'text-green-primary-1',
                  ]"
                >
                  <i class="pi pi-check h-5 w-5" aria-hidden="true" />
                </span>
              </li>
            </ListboxOption>
          </ListboxOptions>

          <!-- Non-scrollable footer -->
          <slot name="footer" v-if="hasFooter" />
        </div>
      </transition>
    </div>
  </Listbox>
</template>

<style scoped>
.list-scroll::-webkit-scrollbar {
  width: 6px;
}
.list-scroll::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background-color: var(--color-green-primary-1);
}
.list-scroll::-webkit-scrollbar-track {
  margin: 6px;
  background-color: transparent;
}
</style>
