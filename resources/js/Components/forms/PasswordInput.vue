<script setup>
import { ref } from "vue";

defineOptions({
  inheritAttrs: false,
});

defineProps(["modelValue"]);
defineEmits(["update:modelValue"]);

const showPassword = ref(false);
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};
</script>

<template>
  <div class="relative">
    <input
      :type="showPassword ? 'text' : 'password'"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="block w-full rounded-full py-1.5 @sm:py-2 ps-5 pe-10 mt-0.5 shadow-md text-sm font-semibold ring focus:outline-none focus:ring-2"
      v-bind="$attrs"
    />
    <i
      class="pi absolute top-1/2 right-4 -translate-y-1/2 cursor-pointer"
      :class="showPassword ? 'pi-eye' : 'pi-eye-slash'"
      @click="togglePasswordVisibility"
    />
  </div>
</template>
