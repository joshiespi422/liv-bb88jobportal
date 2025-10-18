<script>
export default {
  layout: null,
};
</script>

<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  email: String,
  token: String,
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: "",
  password_confirmation: "",
});

const submit = () => {
  form.post(route("password.update"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
};
</script>

<template>
  <Head title="Reset Password" />
  <main
    class="bg-[url('../assets/img/login-bg.png')] h-screen flex items-center justify-center"
  >
    <div class="w-full max-w-md p-8 bg-stone-200 rounded-lg shadow-md">
      <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
        Reset Password
      </h2>

      <form @submit.prevent="submit">
        <div>
          <label
            for="email"
            class="block font-semibold ms-1 text-sm text-gray-700"
            >Email</label
          >
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="block bg-white w-full rounded-md p-2 mt-0.5 shadow-md text-sm text-black font-medium ring ring-gray-500 focus:outline-none focus:ring-2"
            required
            readonly
          />
          <div v-if="form.errors.email" class="text-sm text-red-600 mt-2">
            {{ form.errors.email }}
          </div>
        </div>

        <div class="mt-4">
          <label
            for="password"
            class="block font-semibold ms-1 text-sm text-gray-700"
            >Password</label
          >
          <input
            id="password"
            v-model="form.password"
            type="password"
            placeholder="p@ssword2025"
            class="block w-full rounded-md p-2 mt-0.5 shadow-md text-sm text-black font-medium ring ring-gray-500 focus:outline-none focus:ring-2"
            required
          />
          <div v-if="form.errors.password" class="text-sm text-red-600 mt-2">
            {{ form.errors.password }}
          </div>
        </div>

        <div class="mt-4">
          <label
            for="password_confirmation"
            class="block font-semibold ms-1 text-sm text-gray-700"
            >Confirm Password</label
          >
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            placeholder="p@ssword2025"
            class="block w-full rounded-md p-2 mt-0.5 shadow-md text-sm text-black font-medium ring ring-gray-500 focus:outline-none focus:ring-2"
            required
          />
        </div>

        <div class="flex items-center justify-end mt-4">
          <button
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 cursor-pointer"
          >
            Reset Password
          </button>
        </div>
      </form>
    </div>
  </main>
</template>
