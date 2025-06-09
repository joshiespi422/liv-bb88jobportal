<script setup>
import { ref } from "vue";
import PictureModal from "../Components/PictureModal.vue";

// Props received from Inertia
const props = defineProps({
  profile: Object,
});

const isModalOpen = ref(false);
</script>

<template>
  <div class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Profile</h2>

    <div class="flex flex-col items-center">
      <!-- Clickable profile picture -->
      <div class="relative group cursor-pointer" @click="isModalOpen = true">
        <img
          :src="profile.picture"
          class="w-32 h-32 rounded-full object-cover border-2 border-gray-300 shadow-md"
        />
        <div
          class="absolute inset-0 bg-black/30 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
        >
          <span class="text-white font-medium">Edit</span>
        </div>
      </div>

      <!-- User information -->
      <div class="mt-4 text-center">
        <h3 class="text-xl font-bold">{{ profile.name }}</h3>
        <p class="text-gray-600">{{ profile.position }}</p>
        <p class="text-gray-500">{{ profile.department }}</p>
      </div>
    </div>

    <!-- Profile Picture Modal -->
    <PictureModal
      :isOpen="isModalOpen"
      :pictureUrl="profile.picture"
      @close="isModalOpen = false"
    />
  </div>
</template>
