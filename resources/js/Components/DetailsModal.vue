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
  error: {
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
  panelClass: {
    type: String,
    default: "w-full max-w-md", // Default size
  },
  customSkeleton: {
    type: Boolean,
    default: false,
  },
  customContent: {
    type: Boolean,
    default: false,
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
              :class="[
                'bg-base-100 transform overflow-hidden rounded-2xl p-6 text-left align-middle shadow-xl transition-all',
                panelClass,
              ]"
            >
              <DialogTitle as="h3" class="text-2xl font-bold">
                {{ title }}
              </DialogTitle>

              <!-- Skeleton Slot -->
              <div v-if="loading">
                <slot name="skeleton" :skeletonFieldCount="skeletonFieldCount">
                  <!-- Default Skeleton -->
                  <div v-if="!customSkeleton" class="space-y-4 my-6 mx-3">
                    <div class="grid grid-cols-1 gap-4">
                      <div
                        v-for="i in skeletonFieldCount"
                        :key="`skeleton-field-${i}`"
                      >
                        <div class="skeleton h-5 w-[40%] mb-1"></div>
                        <div class="skeleton h-5 w-full"></div>
                      </div>
                    </div>
                  </div>
                </slot>
              </div>

              <!-- Content Slot -->
              <div v-else-if="item && fields.length > 0">
                <slot
                  name="content"
                  :item="item"
                  :getFieldValue="getFieldValue"
                >
                  <!-- Default Content -->
                  <div v-if="!customContent" class="space-y-4 my-5">
                    <div class="grid grid-cols-1 gap-4">
                      <div v-for="field in fields" :key="field.key">
                        <label class="block text-sm font-medium">
                          {{ field.label }}
                        </label>
                        <p class="mt-1 text-sm">
                          {{ getFieldValue(item, field) }}
                        </p>
                      </div>
                    </div>
                  </div>
                </slot>
              </div>

              <!-- Error Slot -->
              <div
                v-else-if="error && !loading"
                role="alert"
                class="alert alert-soft alert-error my-10"
              >
                <i class="pi pi-times-circle text-2xl"></i>
                <p class="text-sm font-semibold">Something went wrong</p>
              </div>

              <!-- No Data Slot -->
              <div
                v-else-if="!item && !loading"
                role="alert"
                class="alert alert-soft alert-warning my-10"
              >
                <i class="pi pi-exclamation-triangle text-2xl"></i>
                <p class="text-sm font-semibold">
                  No details available to display
                </p>
              </div>

              <div class="mt-6 flex justify-end">
                <!-- custom buttons here -->
                <slot name="custom-buttons"></slot>
                <button
                  type="button"
                  class="btn btn-soft rounded-full"
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
