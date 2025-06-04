<script setup>
import { computed } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  item: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "Details",
  },
  fields: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["close", "afterLeave"]);

const requestDialogClose = () => {
  emit("close");
};

const handleAfterLeave = () => {
  emit("afterLeave");
};

// Helper function to get the value for a field and apply formatting
const getFieldValue = (dataItem, field) => {
  if (
    !dataItem ||
    !field ||
    typeof dataItem[field.key] === "undefined" ||
    dataItem[field.key] === null
  ) {
    return "N/A";
  }
  const value = dataItem[field.key];
  if (field.formatter && typeof field.formatter === "function") {
    return field.formatter(value);
  }
  return value;
};

// Computed property for the number of skeleton items, based on fields count
const skeletonFieldCount = computed(() => {
  return props.fields && props.fields.length > 0 ? props.fields.length : 7; // Fallback to 7 if fields aren't defined yet
});
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog
      as="div"
      @close="requestDialogClose"
      @after-leave="handleAfterLeave"
      class="relative z-10"
    >
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/30 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div
          class="flex min-h-full items-center justify-center p-4 text-center"
        >
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <DialogPanel
              class="bg-base-100 w-full max-w-md transform overflow-hidden rounded-2xl p-6 text-left align-middle shadow-xl transition-all"
            >
              <DialogTitle as="h3" class="text-lg font-medium leading-6 mb-4">
                {{ title }}
              </DialogTitle>

              <!-- Skeleton loader -->
              <div v-if="loading" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                  <div
                    v-for="i in skeletonFieldCount"
                    :key="`skeleton-field-${i}`"
                  >
                    <div class="skeleton h-4 w-16 mb-1"></div>
                    <div class="skeleton h-4 w-3/4"></div>
                  </div>
                </div>
              </div>

              <!-- item details -->
              <div v-else-if="item && fields.length > 0" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                  <div v-for="field in fields" :key="field.key">
                    <label class="block text-sm font-medium">{{
                      field.label
                    }}</label>
                    <p class="mt-1 text-sm">
                      {{ getFieldValue(item, field) }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-else-if="!item && !loading">
                <p class="text-sm text-gray-500">
                  No details available to display.
                </p>
              </div>

              <div class="mt-6 flex justify-end">
                <button
                  type="button"
                  class="inline-flex justify-center rounded-md border border-transparent bg-indigo-100 px-4 py-2 text-sm font-medium text-indigo-900 hover:bg-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                  @click="requestDialogClose"
                >
                  Close
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
