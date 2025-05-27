<script>
export default {
  layout: null,
};
</script>

<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
  email: null,
  password: null,
});

const handleLogin = () => {
  form.post(route("login"), {
    onError: () => form.reset("password"),
  });
};
</script>

<template>
  <main
    class="bg-[url('../assets/img/login-bg.png')] bg-cover bg-center h-screen flex items-center justify-center"
  >
    <div
      class="flex w-3xl rounded-3xl overflow-hidden p-1 bg-[url('../assets/img/login-inner-bg.png')] bg-cover bg-center"
    >
      <!-- Left Container -->
      <div class="w-2/3 flex flex-col items-center pl-5">
        <!-- Logo -->
        <div class="w-full flex justify-center">
          <img src="../../assets/img/bb88-logo.png" alt="" class="w-1/2 my-5" />
        </div>

        <!-- Title -->
        <div class="text-center">
          <h2 class="text-2xl font-bold">Welcome back!</h2>
          <p class="font-medium text-gray-primary">
            Sign in by entering the information below.
          </p>
        </div>

        <!-- Login Tabs -->
        <div class="w-full">
          <!-- Form -->
          <div class="w-full overflow-hidden">
            <form @submit.prevent="handleLogin">
              <div class="my-4 text-sm">
                <label class="block font-bold ms-1 text-gray-primary"
                  >Email</label
                >
                <input
                  type="email"
                  v-model="form.email"
                  @input="form.clearErrors('auth')"
                  placeholder="Enter your email"
                  :class="[
                    'w-full p-2 border border-gray-primary rounded bg-white',
                    {
                      'border-red-500 border-2': form.errors.auth,
                    },
                  ]"
                  required
                />
              </div>
              <div class="mt-4 text-sm">
                <label class="block font-bold ms-1 text-gray-primary"
                  >Password</label
                >
                <input
                  type="password"
                  v-model="form.password"
                  @input="form.clearErrors('auth')"
                  placeholder="Enter your password"
                  :class="[
                    'w-full p-2 border border-gray-primary rounded bg-white',
                    {
                      'border-red-500 border-2': form.errors.auth,
                    },
                  ]"
                  required
                />
              </div>
              <!-- Add error message -->
              <div
                v-if="form.errors.auth"
                class="text-red-500 text-center font-bold"
              >
                <small>{{ form.errors.auth }}</small>
              </div>

              <div class="text-right">
                <a href="#" class="text-gray-primary mb-2 font-bold"
                  >Forgot Password?</a
                >
              </div>
              <div class="text-center">
                <button
                  :disabled="form.processing"
                  :class="[
                    'w-1/2 bg-green-primary-2 text-white rounded-xl py-2 my-3 font-bold',
                    { 'cursor-pointer': !form.processing },
                    { 'opacity-50 cursor-not-allowed': form.processing },
                  ]"
                  type="submit"
                >
                  Sign In
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Right Image -->
      <div class="w-96 flex items-center">
        <img
          src="../../assets/img/login-inner-img.png"
          alt=""
          class="w-[85%] h-60"
        />
      </div>
    </div>
  </main>
</template>
