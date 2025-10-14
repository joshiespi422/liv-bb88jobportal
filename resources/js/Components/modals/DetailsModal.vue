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
  layoutType: {
    type: String,
    default: "default",
    validator: (value) => ["default", "default2"].includes(value),
  },
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

const statusClassMap = {
  done: "text-success",
  revision: "text-error",
  "in progress": "text-accent",
  pending: "text-info",
};
const priorityClassMap = {
  low: "text-info",
  medium: "text-accent",
  high: "text-error",
};
function getFieldClass(field, item) {
  if (field.key === "status") {
    return statusClassMap[item.status] || "";
  }
  if (field.key === "priority") {
    return priorityClassMap[item.priority] || "";
  }
  return "";
}
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog
      as="div"
      class="relative z-50"
      @close="requestDialogClose()"
      :initial-focus="focusElement"
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

      <div class="@container fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 pb-10">
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
                'bg-base-100 transform overflow-hidden rounded-lg p-4 sm:p-6 shadow-xl transition-all',
                panelClass,
              ]"
            >
              <DialogTitle
                as="h3"
                class="text-xl @sm:text-2xl font-bold text-center @sm:text-start"
              >
                {{ title }}
              </DialogTitle>

              <!-- Skeleton Slot -->
              <div v-if="loading">
                <slot name="skeleton" :skeletonFieldCount="skeletonFieldCount">
                  <!-- Default Skeleton -->
                  <div v-if="layoutType === 'default'" class="space-y-4 my-5">
                    <div
                      v-for="i in skeletonFieldCount"
                      :key="`skeleton-field-${i}`"
                      class="grid grid-cols-1 @sm:grid-cols-[1fr_3fr] gap-1 @sm:gap-2 items-center"
                    >
                      <div class="skeleton h-5 @sm:h-7 w-1/3 @sm:w-full"></div>
                      <div class="skeleton h-6 @sm:h-7 w-full"></div>
                    </div>
                  </div>
                  <!-- Default2 Skeleton -->
                  <div
                    v-else-if="layoutType === 'default2'"
                    class="grid grid-cols-1 @2xl:grid-cols-[1.5fr_2.5fr] @3xl:grid-cols-[2fr_2fr] gap-4 py-6 px-0 @2xl:px-3"
                  >
                    <div class="space-y-3">
                      <div
                        v-for="i in skeletonFieldCount"
                        :key="`custom-skel-${i}`"
                        class="grid grid-cols-[1fr_3fr] gap-2 items-center"
                      >
                        <div class="skeleton h-6 @2xl:h-8 w-full" />
                        <div class="skeleton h-6 @2xl:h-8 w-full" />
                      </div>
                    </div>
                    <div class="rounded-xl bg-base-200 p-3">
                      <div
                        class="collapse collapse-plus bg-base-100 border border-base-300"
                      >
                        <input
                          type="radio"
                          name="my-accordion-1"
                          checked="checked"
                        />
                        <div class="collapse-title text-sm font-medium">
                          <div class="skeleton h-6 @2xl:h-8 w-full" />
                        </div>
                        <div class="collapse-content space-y-1">
                          <div class="skeleton h-6 @2xl:h-8 w-full" />
                          <div class="skeleton h-6 @2xl:h-8 w-full" />
                        </div>
                      </div>
                      <div
                        class="collapse collapse-plus bg-base-100 border border-base-300"
                      >
                        <input
                          type="radio"
                          name="my-accordion-2"
                          checked="checked"
                        />
                        <div class="collapse-title text-sm font-medium">
                          <div class="skeleton h-6 @2xl:h-8 w-full" />
                        </div>
                        <div class="collapse-content space-y-1">
                          <div class="skeleton h-6 @2xl:h-8 w-full" />
                          <div class="skeleton h-6 @2xl:h-8 w-full" />
                        </div>
                      </div>
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
                  <div v-if="layoutType === 'default'" class="space-y-4 my-5">
                    <div
                      v-for="field in fields"
                      :key="field.key"
                      class="grid grid-cols-1 @sm:grid-cols-[1fr_4fr] gap-1 @sm:gap-4 items-center"
                    >
                      <label
                        class="block text-sm ps-2 @sm:ps-0 font-semibold @sm:font-bold"
                      >
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

                  <!-- Default2 Content -->
                  <div
                    v-if="layoutType === 'default2'"
                    class="grid grid-cols-1 @2xl:grid-cols-[1.5fr_2.5fr] @3xl:grid-cols-[2fr_2fr] gap-4 py-6 px-0 @2xl:px-3"
                  >
                    <div class="space-y-3">
                      <div
                        v-for="field in fields"
                        :key="field.key"
                        class="grid grid-cols-1 @3xl:grid-cols-[1fr_4fr] gap-1 @3xl:gap-2"
                      >
                        <label class="block text-sm font-bold mt-0 @3xl:mt-2">
                          {{ field.label }}
                        </label>

                        <slot
                          :name="`field-${field.key}`"
                          :item="item"
                          :getFieldValue="getFieldValue"
                        >
                          <p
                            :class="[
                              'text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate',
                              getFieldClass(field, item),
                            ]"
                          >
                            {{ getFieldValue(item, field) }}
                          </p>
                        </slot>
                      </div>
                    </div>

                    <slot name="right-panel" :item="item" />
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
                  class="btn btn-sm @sm:btn-md btn-soft rounded-full"
                  @click="requestDialogClose"
                >
                  Close
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>

      <slot></slot>
    </Dialog>
  </TransitionRoot>
</template>
