<script setup>
import Sidebar from "../Components/Sidebar.vue";
import Header from "../Components/Header.vue";
</script>

<template>
  <div class="grid grid-cols-[auto_1fr]">
    <Sidebar />
    <div class="grid grid-rows-[auto_1fr]">
      <Header />
      <!-- Dynamic content area -->
      <slot></slot>
    </div>
  </div>
</template>
