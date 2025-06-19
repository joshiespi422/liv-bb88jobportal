<script setup>
import { ref, watch } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { Cropper } from "vue-advanced-cropper";
import "vue-advanced-cropper/dist/style.css";

const props = defineProps({
  isOpen: Boolean,
  pictureUrl: String,
  inert: Boolean,
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

  const { canvas } = cropper.value.getResult();

  canvas.toBlob((blob) => {
    emit("change", blob);
  }, mimeType.value);
};

const requestDelete = () => {
  emit("delete");
};

const focusElement = ref(null);
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog
      as="div"
      class="relative z-10"
      :inert="inert"
      @close="!inert && $emit('close')"
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
              class="w-full max-w-md transform overflow-hidden rounded-2xl bg-base-100 p-6 text-left align-middle shadow-xl transition-all"
            >
              <DialogTitle as="h3" class="text-2xl font-semibold">
                {{ selectedImage ? "ADJUST PICTURE" : "PROFILE PICTURE" }}
              </DialogTitle>

              <div class="mt-4 flex justify-center">
                <div v-if="selectedImage" class="w-full mx-20 my-5">
                  <Cropper
                    ref="cropper"
                    :src="selectedImage"
                    :stencil-props="{
                      aspectRatio: 1,
                    }"
                    class="w-full h-auto"
                  />
                </div>
                <img
                  v-else
                  :src="pictureUrl || '/profile-images/default.png'"
                  class="w-52 h-52 rounded-full object-cover my-5 shadow-xl/20"
                />
              </div>

              <input
                type="file"
                ref="fileInput"
                class="hidden"
                accept="image/*"
                @change="handleFileSelect"
              />

              <div class="mt-10 flex justify-end space-x-3">
                <template v-if="selectedImage">
                  <button
                    type="button"
                    class="btn btn-soft btn-info shadow-lg rounded-full"
                    @click="saveCrop"
                  >
                    Save Changes
                  </button>

                  <button
                    type="button"
                    class="btn btn-soft btn-error shadow-lg rounded-full"
                    @click="selectedImage = null"
                  >
                    Cancel
                  </button>
                </template>
                <template v-else>
                  <button
                    type="button"
                    class="btn btn-soft btn-success shadow-lg rounded-full"
                    @click="triggerFileInput"
                  >
                    Change
                  </button>

                  <button
                    type="button"
                    class="btn btn-soft btn-error shadow-lg rounded-full"
                    @click="requestDelete"
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
