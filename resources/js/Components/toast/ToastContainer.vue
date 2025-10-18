<script setup>
import Toast from "./Toast.vue";
import { useToast } from "../../Composables/useToast";

const { toasts, removeToast } = useToast();

const handleClose = (id) => {
  removeToast(id);
};
</script>

<template>
  <Toast
    v-for="toast in toasts"
    :key="toast.id"
    :type="toast.type"
    :message="toast.message"
    :duration="toast.duration"
    @close="handleClose(toast.id)"
  />
</template>
