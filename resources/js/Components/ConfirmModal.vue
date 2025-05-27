<script setup>
defineProps({
  show: Boolean,
  title: {
    type: String,
    default: "Confirm Action",
  },
  message: {
    type: String,
    default: "Are you sure you want to perform this action?",
  },
  confirmText: {
    type: String,
    default: "Confirm",
  },
  cancelText: {
    type: String,
    default: "Cancel",
  },
  iconName: {
    type: String,
    default: "pi pi-exclamation-triangle",
  },
  iconColor: {
    type: String,
    default: "text-red-600",
  },
  iconBgColor: {
    type: String,
    default: "bg-red-100",
  },
  confirmButtonBg: {
    type: String,
    default: "bg-red-600 hover:bg-red-500",
  },
});

defineEmits(["confirm", "cancel"]);
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="ease-out duration-300"
      leave-active-class="ease-in duration-200"
    >
      <div v-show="show" class="relative z-50">
        <!-- Background backdrop -->
        <div
          v-show="show"
          class="fixed inset-0 bg-gray-500/75 transition-opacity"
          aria-hidden="true"
          @click.self="$emit('cancel')"
        />

        <!-- Modal container -->
        <div
          class="fixed inset-0 z-10 w-screen overflow-y-auto pointer-events-none"
        >
          <div
            class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
          >
            <transition
              enter-active-class="ease-out duration-300"
              enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to-class="opacity-100 translate-y-0 sm:scale-100"
              leave-active-class="ease-in duration-200"
              leave-from-class="opacity-100 translate-y-0 sm:scale-100"
              leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <div
                v-show="show"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg pointer-events-auto"
              >
                <!-- Modal content -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <div class="sm:flex sm:items-start">
                    <div
                      class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:size-10"
                      :class="iconBgColor"
                    >
                      <i class="text-2xl" :class="[iconName, iconColor]"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                      <h3
                        class="text-base font-semibold text-gray-900"
                        id="modal-title"
                      >
                        {{ title }}
                      </h3>
                      <div class="mt-2">
                        <p class="text-sm text-gray-500">{{ message }}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div
                  class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                >
                  <button
                    type="button"
                    class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-xs sm:ml-3 sm:w-auto cursor-pointer"
                    :class="confirmButtonBg"
                    @click="$emit('confirm')"
                  >
                    {{ confirmText }}
                  </button>
                  <button
                    type="button"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 hover:bg-gray-100 sm:mt-0 sm:w-auto cursor-pointer"
                    @click="$emit('cancel')"
                  >
                    {{ cancelText }}
                  </button>
                </div>
              </div>
            </transition>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
