<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatDate } from "../Composables/useDateFormatter";

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const props = defineProps({
  totalCounts: {
    type: Object,
  },
  userDetails: {
    type: Object,
  },
});
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      v-if="authUser?.userType !== 'super_admin'"
      class="p-4 rounded-2xl shadow-md bg-base-200 border-3 border-green-primary-1"
    >
      <div class="flex items-center justify-between">
        <h1 class="text-xl xl:text-2xl font-bold flex-none">
          Attendance Today ({{ formatDate(props.userDetails?.date) }})
        </h1>
        <div>
          <button
            class="btn btn-success text-white border-2 border-white rounded-full hover:scale-105 transition-all duration-200 ease-in-out me-3"
          >
            Time In
          </button>
          <button
            class="btn btn-error text-white border-2 border-white rounded-full hover:scale-105 transition-all duration-200 ease-in-out"
          >
            Time Out
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
