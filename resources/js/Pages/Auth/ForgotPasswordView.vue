<script>
export default {
  layout: null,
};
</script>

<script setup>
import { ref, onMounted } from "vue";
import { useForm, Link } from "@inertiajs/vue3";

defineProps({
  status: String,
});

const form = useForm({
  email: "",
});

// --- Timer Logic ---
const cooldownSeconds = ref(0);
const timerRunning = ref(false);
const COOLDOWN_DURATION = 120;

let timerInterval = null;

// Function to start the countdown
const startCooldownTimer = () => {
  timerRunning.value = true;
  cooldownSeconds.value = COOLDOWN_DURATION;

  // Store the end time in localStorage
  const cooldownEndTime = Date.now() + COOLDOWN_DURATION * 1000;
  localStorage.setItem("password_reset_cooldown_end", cooldownEndTime);

  timerInterval = setInterval(() => {
    cooldownSeconds.value--;
    if (cooldownSeconds.value <= 0) {
      clearInterval(timerInterval);
      timerRunning.value = false;
      localStorage.removeItem("password_reset_cooldown_end");
    }
  }, 1000);
};

// Check for an existing cooldown when the component mounts
onMounted(() => {
  const cooldownEndTime = localStorage.getItem("password_reset_cooldown_end");
  if (cooldownEndTime) {
    const remainingTime = Math.round((cooldownEndTime - Date.now()) / 1000);
    if (remainingTime > 0) {
      cooldownSeconds.value = remainingTime;
      startCooldownTimer(); // This will re-initiate the countdown
    } else {
      localStorage.removeItem("password_reset_cooldown_end");
    }
  }
});

const submit = () => {
  form.post(route("password.email"), {
    onSuccess: () => {
      // Only start the timer if the request was successful
      if (!form.hasErrors) {
        startCooldownTimer();
      }
    },
  });
};
</script>

<template>
  <Head title="Forgot Password" />
  <main
    class="bg-[url('../assets/img/login-bg.png')] bg-cover bg-center h-screen flex items-center justify-center"
  >
    <div class="w-full max-w-md p-8 bg-stone-200 rounded-lg shadow-md">
      <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">
        Forgot Password
      </h2>
      <p class="text-center text-gray-600 mb-6">
        No problem. Just let us know your email address and we will email you a
        password reset link.
      </p>

      <div
        v-if="status"
        class="mb-4 font-semibold text-sm text-green-600 bg-green-100 p-3 rounded"
      >
        {{ status }}
      </div>

      <form @submit.prevent="submit">
        <div>
          <label class="block font-semibold text-sm ms-1 text-gray-700"
            >Email</label
          >
          <input
            v-model="form.email"
            type="email"
            placeholder="mail@site.com"
            class="block w-full rounded-md p-2 mt-0.5 shadow-md text-sm text-black font-medium ring ring-gray-500 focus:outline-none focus:ring-2"
            required
            autofocus
          />
          <div
            v-if="form.errors.email"
            class="text-sm font-semibold text-red-600 mt-2 ms-2"
          >
            {{ form.errors.email }}
          </div>
        </div>

        <div class="flex items-center justify-end mt-8">
          <button
            :class="{ 'opacity-25': form.processing || timerRunning }"
            :disabled="form.processing || timerRunning"
            class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 cursor-pointer disabled:cursor-not-allowed"
          >
            <span v-if="timerRunning"> Resend in {{ cooldownSeconds }}s </span>
            <span v-else> Email Password Reset Link </span>
          </button>
        </div>

        <div class="text-center mt-4">
          <Link
            :href="route('login')"
            class="underline text-sm text-gray-600 hover:text-gray-900 font-semibold"
          >
            Back to login
          </Link>
        </div>
      </form>
    </div>
  </main>
</template>
