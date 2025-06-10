<script setup>
import { ref, watch } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import VueCropper from "vue-cropperjs";

const props = defineProps({
  isOpen: Boolean,
  pictureUrl: String,
  isLoading: Boolean,
});

const emit = defineEmits(["close", "change", "delete"]);

const cropper = ref(null);
const fileInput = ref(null);
const selectedImage = ref(null);
const mimeType = ref("image/jpeg");

// When the modal opens, reset the state
watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      selectedImage.value = null;
    }
  }
);

const triggerFileInput = () => {
  fileInput.value.click();
};

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  mimeType.value = file.type;
  const reader = new FileReader();
  reader.onload = (e) => {
    selectedImage.value = e.target.result;
  };
  reader.readAsDataURL(file);
  // Reset file input to allow selecting the same file again
  event.target.value = "";
};

const saveCrop = () => {
  if (!cropper.value) return;

  cropper.value.getCroppedCanvas().toBlob((blob) => {
    emit("change", blob);
  }, mimeType.value);
};

const requestDelete = () => {
  emit("delete");
};
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
                {{ selectedImage ? "Adjust Picture" : "Profile Picture" }}
              </DialogTitle>

              <div class="mt-4 flex justify-center">
                <div v-if="selectedImage">
                  <VueCropper
                    ref="cropper"
                    :src="selectedImage"
                    :aspect-ratio="1"
                    alt="Crop source"
                    class="w-48 h-48 rounded-full object-cover border-2 border-gray-200"
                  />
                </div>
                <img
                  v-else
                  :src="pictureUrl"
                  @error="$event.target.src = '/profile-images/default.png'"
                  class="w-48 h-48 rounded-full object-cover border-2 border-gray-200"
                />
              </div>

              <input
                type="file"
                ref="fileInput"
                class="hidden"
                accept="image/*"
                @change="handleFileSelect"
              />

              <div class="mt-6 flex justify-center space-x-4">
                <template v-if="selectedImage">
                  <button
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none"
                    @click="saveCrop"
                    :disabled="isLoading"
                  >
                    Save Changes
                  </button>

                  <button
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-red-100 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-200 focus:outline-none"
                    @click="selectedImage = null"
                    :disabled="isLoading"
                  >
                    Cancel
                  </button>
                </template>
                <template v-else>
                  <button
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none"
                    @click="triggerFileInput"
                    :disabled="isLoading"
                  >
                    Change
                  </button>

                  <button
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-red-100 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-200 focus:outline-none"
                    @click="emit('delete')"
                    :disabled="isLoading"
                  >
                    Delete
                  </button>
                </template>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
