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
  inert: Boolean,
  title: String,
  form: Object,
  fields: Array,
  submitText: {
    type: String,
    default: "Submit",
  },
});

const emit = defineEmits(["close", "submit"]);
const focusElement = ref(null);
</script>

<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog
      as="div"
      class="relative z-10"
      :inert="inert"
      @close="!inert && $emit('close')"
      :initial-focus="inert ? undefined : focusElement"
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

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-0">
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
              class="relative transform overflow-hidden bg-base-100 rounded-lg px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
            >
              <form @submit.prevent="$emit('submit')">
                <div>
                  <DialogTitle as="h3" class="text-2xl font-semibold">
                    {{ title }}
                  </DialogTitle>

                  <div class="mt-6 space-y-4">
                    <div v-for="field in fields" :key="field.key">
                      <label
                        :for="field.key"
                        class="block text-sm font-medium ms-3"
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
                  </div>
                </div>

                <div class="mt-10 flex items-center justify-end gap-x-1">
                  <button
                    type="button"
                    class="btn btn-ghost text-lg rounded-full text-green-primary-1 border-2"
                    @click="$emit('close')"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn btn-soft text-lg px-7 rounded-full text-white bg-green-primary-1 hover:bg-green-primary-3 border-2 border-base-content shadow-md"
                  >
                    {{ submitText }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
