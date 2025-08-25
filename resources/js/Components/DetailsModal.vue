<script setup>
import { computed, ref } from "vue";
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
  hideCloseBtn: {
    type: Boolean,
    default: false,
  },
  inert: Boolean,
});

const emit = defineEmits(["close"]);

const requestDialogClose = () => {
  emit("close");
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

const focusElement = ref(null);
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog
      as="div"
      class="relative z-10"
      :inert="inert"
      @close="!inert && requestDialogClose()"
      :initial-focus="inert ? undefined : focusElement"
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
                  <div class="space-y-4 my-5">
                    <div
                      v-for="i in skeletonFieldCount"
                      :key="`skeleton-field-${i}`"
                      class="grid grid-cols-[1fr_3fr] gap-2 items-center"
                    >
                      <div class="skeleton h-7 w-full"></div>
                      <div class="skeleton h-7 w-full"></div>
                    </div>
                  </div>
                </slot>
              </div>

              <!-- Content Slot -->
              <div v-else-if="item">
                <slot
                  name="content"
                  :item="item"
                  :getFieldValue="getFieldValue"
                >
                  <!-- Default Content -->
                  <div class="space-y-4 my-5">
                    <div
                      v-for="field in fields"
                      :key="field.key"
                      class="grid grid-cols-[1fr_4fr] gap-4 items-center"
                    >
                      <label class="block text-sm font-bold">
                        {{ field.label }}:
                      </label>
                      <!-- HTML Content -->
                      <div
                        v-if="field.html"
                        class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate"
                        v-html="getFieldValue(item, field)"
                      ></div>
                      <!-- Regular Content -->
                      <p
                        v-else
                        class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate"
                      >
                        {{ getFieldValue(item, field) }}
                      </p>
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
                <i class="pi pi-times-circle text-2xl" />
                <p class="text-sm font-semibold">Something went wrong</p>
              </div>

              <!-- No Data Slot -->
              <div
                v-else-if="!item && !loading"
                role="alert"
                class="alert alert-soft alert-warning my-10"
              >
                <i class="pi pi-exclamation-triangle text-2xl" />
                <p class="text-sm font-semibold">
                  No details available to display
                </p>
              </div>

              <div class="mt-6 flex justify-end">
                <!-- custom buttons here -->
                <slot name="custom-buttons" />
                <button
                  v-if="!hideCloseBtn"
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
