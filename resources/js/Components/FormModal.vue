<script setup>
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { ref } from "vue";
const props = defineProps({
  isOpen: Boolean,
  title: String,
  form: Object,
  fields: Array,
  submitText: {
    type: String,
    default: "Submit",
  },
  showBackButton: {
    type: Boolean,
    default: false,
  },
  disabledButton: {
    type: Boolean,
    default: false,
  },
  panelClass: {
    type: String,
    default: "w-full max-w-md",
  },
});

const emit = defineEmits(["close", "submit", "back"]);
const focusElement = ref(null);
</script>

<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog
      as="div"
      class="relative z-50 @container"
      @close="$emit('close')"
      :initial-focus="focusElement"
    >
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/30 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 pb-10">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              :class="[
                'transform bg-base-100 rounded-lg text-left shadow-xl transition-all sm:w-full p-4 sm:p-6',
                panelClass,
              ]"
            >
              <form @submit.prevent="$emit('submit')">
                <div>
                  <DialogTitle
                    as="h3"
                    class="text-xl @sm:text-2xl font-bold text-center @sm:text-start"
                  >
                    {{ title }}
                  </DialogTitle>

                  <!-- Content Form Fields -->
                  <div class="mt-6 space-y-4">
                    <!-- Fields Slot if provided by parent -->
                    <slot name="custom-fields" :fields="fields" :form="form">
                      <!-- Default field layout -->
                      <div v-for="field in fields" :key="field.key">
                        <label
                          :for="field.key"
                          class="block text-sm font-semibold @sm:font-bold ms-3"
                        >
                          {{ field.label }}
                        </label>
                        <component
                          :is="field.component"
                          :id="field.key"
                          v-bind="field.attrs"
                          v-model="form[field.key]"
                          :class="{
                            'ring-error': form.errors[field.key],
                            'focus:ring-indigo-600': !form.errors[field.key],
                          }"
                          @change="form.clearErrors(field.key)"
                        />
                        <p
                          v-if="form.errors[field.key]"
                          class="mt-1 text-sm font-semibold text-error ms-3"
                        >
                          {{ form.errors[field.key] }}
                        </p>
                      </div>
                    </slot>
                  </div>
                </div>

                <div class="mt-10 flex items-center justify-end gap-x-1">
                  <button
                    v-if="showBackButton"
                    type="button"
                    class="btn btn-sm @sm:btn-md btn-ghost font-bold rounded-full text-green-primary-1 border-2"
                    @click="$emit('back')"
                  >
                    <i class="pi pi-arrow-left me-1" /> Back
                  </button>
                  <button
                    v-if="!disabledButton"
                    type="button"
                    class="btn btn-sm @sm:btn-md btn-ghost rounded-full text-green-primary-1 border-2"
                    @click="$emit('close')"
                  >
                    Cancel
                  </button>
                  <button
                    ref="focusElement"
                    type="submit"
                    :disabled="form.processing"
                    class="btn btn-sm @sm:btn-md btn-soft px-7 rounded-full text-white bg-green-primary-1 hover:bg-green-primary-3 border-2 border-base-content shadow-md"
                  >
                    {{ submitText }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>

      <slot></slot>
    </Dialog>
  </TransitionRoot>
</template>
