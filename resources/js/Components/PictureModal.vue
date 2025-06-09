<script setup>
import { ref } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  isOpen: Boolean,
  pictureUrl: String,
});

const emit = defineEmits(["close"]);

const fileInput = ref(null);
const form = useForm({
  picture: null,
});

function openFilePicker() {
  fileInput.value.click();
}

function handleFileChange(e) {
  form.picture = e.target.files[0];
  submitNewPicture();
}

function submitNewPicture() {
  form.post(route("profile.picture.update"), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      emit("close");
    },
  });
}

function deletePicture() {
  useForm({}).delete(route("profile.picture.delete"), {
    preserveScroll: true,
    onSuccess: () => emit("close"),
  });
}
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" class="relative z-10" @close="emit('close')">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div
          class="flex min-h-full items-center justify-center p-4 text-center"
        >
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
            >
              <DialogTitle
                as="h3"
                class="text-lg font-medium leading-6 text-gray-900"
              >
                Profile Picture
              </DialogTitle>

              <div class="mt-4 flex justify-center">
                <img
                  :src="pictureUrl"
                  class="w-48 h-48 rounded-full object-cover border-2 border-gray-200"
                />
              </div>

              <input
                type="file"
                ref="fileInput"
                class="hidden"
                accept="image/*"
                @change="handleFileChange"
              />

              <div class="mt-6 flex justify-center space-x-4">
                <button
                  type="button"
                  class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none"
                  @click="openFilePicker"
                  :disabled="form.processing"
                >
                  <span v-if="form.processing">Uploading...</span>
                  <span v-else>Change</span>
                </button>

                <button
                  type="button"
                  class="inline-flex justify-center rounded-md border border-transparent bg-red-100 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-200 focus:outline-none"
                  @click="deletePicture"
                >
                  Delete
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
