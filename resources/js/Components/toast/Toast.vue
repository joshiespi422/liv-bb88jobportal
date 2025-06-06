<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  type: {
    type: String,
    default: "success",
    validator: (value) =>
      ["success", "error", "info", "warning"].includes(value),
  },
  message: String,
  duration: {
    type: Number,
    default: 5000,
  },
});

const show = ref(true);
const alertClass = computed(() => `alert-${props.type}`);

// Auto-hide after duration
watch(show, (value) => {
  if (value) {
    setTimeout(() => {
      show.value = false;
    }, props.duration);
  }
});
</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="toast toast-top toast-end mt-16">
      <div :class="['alert', alertClass]">
        <span>{{ message }}</span>
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
