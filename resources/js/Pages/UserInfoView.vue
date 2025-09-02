<script>
export default {
  layout: null,
};
</script>

<script setup>
defineProps({
  userInfo: {
    type: Object,
    default: null, // Default to null if no user is found
  },
});
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-200 p-4">
    <template v-if="userInfo">
      <div
        class="w-full max-w-3xl bg-white border rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.3)] p-8 text-center"
      >
        <img
          class="w-32 h-32 mb-4 rounded-full shadow-lg mx-auto object-cover"
          :src="userInfo.picture"
          alt="User profile picture"
        />
        <h1 class="mb-1 text-2xl font-bold text-gray-900">
          {{ userInfo.name }}
        </h1>
        <h2 class="text-md text-gray-500">
          {{ userInfo.position || "Position not specified" }}
        </h2>
      </div>
    </template>

    <template v-else>
      <div
        class="w-full max-w-sm bg-white border rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.3)] p-8 text-center"
      >
        <div class="flex flex-col items-center">
          <i class="pi pi-exclamation-circle text-5xl text-red-500 mb-4"></i>
          <h1 class="mb-1 text-2xl font-bold text-gray-900">User Not Found</h1>
          <p class="text-md font-semibold text-gray-500">
            The provided QR code is invalid or the user does not exist.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>
