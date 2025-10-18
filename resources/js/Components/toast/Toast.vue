<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";

const props = defineProps({
  type: {
    type: String,
    default: "success",
    validator: (value) =>
      ["success", "error", "info", "warning"].includes(value),
  },
  message: String,
  duration: Number,
});

const emit = defineEmits(["close"]);

const show = ref(true);
const isDragging = ref(false);
const dragX = ref(0);
const startX = ref(0);
const toastRef = ref(null);
let timeoutId;

const SWIPE_THRESHOLD = 100; // pixels to swipe before dismissing

// Auto-hide after duration
onMounted(() => {
  timeoutId = setTimeout(() => {
    closeToast();
  }, props.duration);
});

// Cleanup timeout
onBeforeUnmount(() => {
  clearTimeout(timeoutId);
});

const closeToast = () => {
  show.value = false;
  setTimeout(() => {
    emit("close");
  }, 500);
};

// Touch and Mouse event handlers
const handleStart = (e) => {
  isDragging.value = true;
  const clientX = e.type.includes("mouse") ? e.clientX : e.touches[0].clientX;
  startX.value = clientX - dragX.value;
};
const handleMove = (e) => {
  if (!isDragging.value) return;

  e.preventDefault();
  const clientX = e.type.includes("mouse") ? e.clientX : e.touches[0].clientX;
  const newX = clientX - startX.value;

  // Only allow dragging to the right
  if (newX > 0) {
    dragX.value = newX;
  }
};
const handleEnd = () => {
  if (!isDragging.value) return;

  isDragging.value = false;

  // If dragged past threshold, dismiss the toast
  if (dragX.value > SWIPE_THRESHOLD) {
    closeToast();
  } else {
    // Snap back to original position
    dragX.value = 0;
  }
};
</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="toast toast-top toast-end mt-16">
      <div
        ref="toastRef"
        class="alert text-lg px-6 py-4 cursor-grab active:cursor-grabbing select-none touch-pan-y"
        :class="{
          'alert-success': type === 'success',
          'alert-error': type === 'error',
          'alert-info': type === 'info',
          'alert-warning': type === 'warning',
        }"
        :style="{
          transform: `translateX(${dragX}px)`,
          opacity: isDragging ? Math.max(0.3, 1 - dragX / 200) : 1,
          transition: isDragging
            ? 'none'
            : 'transform 0.3s ease, opacity 0.3s ease',
        }"
        @mousedown="handleStart"
        @mousemove="handleMove"
        @mouseup="handleEnd"
        @mouseleave="handleEnd"
        @touchstart="handleStart"
        @touchmove="handleMove"
        @touchend="handleEnd"
      >
        <i
          class="pi"
          :class="{
            'pi-check-circle': type === 'success',
            'pi-times-circle': type === 'error',
            'pi-info-circle': type === 'info',
            'pi-exclamation-triangle': type === 'warning',
          }"
        ></i>
        <span class="font-semibold">{{ message }}</span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
