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

const show = ref(true);
let timeoutId;

// Auto-hide after duration
onMounted(() => {
  timeoutId = setTimeout(() => {
    show.value = false;
  }, props.duration);
});

// Cleanup timeout
onBeforeUnmount(() => {
  clearTimeout(timeoutId);
});
</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="toast toast-top toast-end mt-16">
      <div
        class="alert text-lg px-6 py-4"
        :class="{
          'alert-success': type === 'success',
          'alert-error': type === 'error',
          'alert-info': type === 'info',
          'alert-warning': type === 'warning',
        }"
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
