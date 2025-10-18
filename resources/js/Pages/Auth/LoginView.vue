<script>
export default {
  layout: null,
};
</script>

<script setup>
import { useForm, Link } from "@inertiajs/vue3";

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
  <Head title="Login" />
  <main
    class="bg-[url('../assets/img/login-bg.png')] bg-cover bg-center h-screen flex items-center justify-center"
  >
    <div
      class="flex w-96 sm:w-3xl rounded-3xl shadow-2xl overflow-hidden p-1 bg-gray-300 sm:bg-gray-400 sm:bg-[url('../assets/img/login-inner-bg.png')] bg-cover bg-center mx-2"
    >
      <!-- Left Container -->
      <div
        class="w-full sm:w-3/4 md:w-2/3 flex flex-col items-center pl-0 sm:pl-5"
      >
        <!-- Logo -->
        <div class="w-full flex justify-center">
          <img
            src="../../../assets/img/bb88-logo.png"
            alt=""
            class="w-2/3 md:w-1/2 my-5"
          />
        </div>

        <!-- Title -->
        <div class="text-center">
          <h2 class="text-lg sm:text-2xl font-bold text-neutral-800">
            Welcome back!
          </h2>
          <p class="font-semibold text-sm sm:text-base text-gray-primary">
            Sign in by entering the information below.
          </p>
        </div>

        <!-- Login Tabs -->
        <div class="w-full">
          <!-- Form -->
          <div class="w-full overflow-hidden px-2 sm:px-0">
            <form @submit.prevent="handleLogin">
              <div class="w-[95%] mx-auto my-4 text-sm">
                <label class="block font-bold ms-1 text-gray-primary"
                  >Email</label
                >
                <input
                  type="email"
                  v-model="form.email"
                  @input="form.clearErrors('auth')"
                  placeholder="Enter your email"
                  :class="[
                    'w-full p-1.5 sm:p-2 border border-gray-primary rounded bg-white text-black',
                    {
                      'border-red-500 border-2': form.errors.auth,
                    },
                  ]"
                  required
                />
              </div>
              <div class="w-[95%] mx-auto mt-4 text-sm">
                <label class="block font-bold ms-1 text-gray-primary"
                  >Password</label
                >
                <input
                  type="password"
                  v-model="form.password"
                  @input="form.clearErrors('auth')"
                  placeholder="Enter your password"
                  :class="[
                    'w-full p-1.5 sm:p-2 border border-gray-primary rounded bg-white text-black',
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

              <div class="text-right text-sm sm:text-bas">
                <Link
                  :href="route('password.request')"
                  class="text-gray-primary mb-2 font-bold me-4 sm:me-0"
                >
                  Forgot Password?
                </Link>
              </div>
              <div class="text-center text-sm sm:text-base">
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
      <div class="hidden sm:w-80 md:w-96 sm:flex items-center">
        <img
          src="../../../assets/img/login-inner-img.png"
          alt=""
          class="w-full md:w-[85%] h-60"
        />
      </div>
    </div>
  </main>
</template>
