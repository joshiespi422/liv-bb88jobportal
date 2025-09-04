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
  <div
    class="@container flex items-center justify-center min-h-screen bg-gray-200"
  >
    <template v-if="userInfo">
      <div
        class="w-full max-w-3xl bg-white rounded-xl shadow-[0_0_10px_rgba(0,0,0,0.3)] overflow-hidden m-5"
      >
        <!-- Header Section with Background -->
        <div
          class="bg-[url('../assets/img/profile-header-1.png')] @max-[425px]:bg-[url('../assets/img/profile-header-3.png')] @max-[425px]:h-[240px] @max-[425px]:-mt-12 @max-[767px]:bg-[url('../assets/img/profile-header-2.png')] @max-[767px]:bg-cover @max-[767px]:bg-center bg-no-repeat h-[230px] -mt-2.5 -ms-1 flex justify-center pt-3 @max-[767px]:pt-0"
        >
          <img
            src="../../assets/img/bb88-logo.png"
            class="w-[380px] h-[90px] mt-2.5 @max-[767px]:w-[320px] @max-[767px]:h-20 @max-[767px]:mt-10 @max-[425px]:w-[240px] @max-[425px]:h-14 @max-[425px]:mt-[70px]"
            alt="BB88 Logo"
          />
        </div>

        <!-- Profile Content -->
        <div class="md:px-10 md:-mt-11">
          <!-- Profile Image and Basic Info Row -->
          <div class="flex items-center flex-col md:flex-row">
            <div class="flex items-center flex-col md:flex-row">
              <!-- Profile Image with Gradient Border -->
              <div
                class="relative w-[110px] h-[105px] rounded-full p-1 bg-gradient-to-b from-green-primary-2 via-green-primary-2 to-green-secondary @max-[767px]:-mt-24"
              >
                <div
                  class="w-full h-full rounded-full overflow-hidden bg-white p-0.5"
                >
                  <img
                    class="w-full h-full rounded-full object-cover"
                    :src="userInfo.picture"
                    alt="User profile picture"
                  />
                </div>
              </div>

              <!-- Name and Email -->
              <div class="ml-0 md:ml-3 mt-3 text-center md:text-left">
                <h2
                  class="mb-0 text-[#48887b] text-2xl font-bold @max-[425px]:text-xl"
                >
                  {{ userInfo.name }}
                </h2>
                <p class="text-gray-500 font-semibold @max-[425px]:text-sm">
                  {{ userInfo.email || "N/A" }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Fields Section -->
        <div class="px-5 mt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
            <!-- Left Column -->
            <div class="space-y-4">
              <!-- ID No -->
              <div>
                <span class="block text-sm font-semibold text-slate-500 mb-1">
                  Id No.
                </span>
                <span
                  class="block text-sm font-semibold text-white bg-gray-500 px-3 py-2 rounded-xl"
                >
                  {{ userInfo.qrCode || "N/A" }}
                </span>
              </div>

              <!-- Department (if not s-admin) -->
              <div v-if="userInfo.role !== 'super_admin'">
                <span class="block text-sm font-semibold text-slate-500 mb-1">
                  Department
                </span>
                <span
                  class="block text-sm font-semibold text-white bg-gray-500 px-3 py-2 rounded-xl"
                >
                  {{ userInfo.department || "N/A" }}
                </span>
              </div>

              <!-- Position -->
              <div>
                <span class="block text-sm font-semibold text-slate-500 mb-1">
                  Position
                </span>
                <span
                  class="block text-sm font-semibold text-white bg-gray-500 px-3 py-2 rounded-xl"
                >
                  {{ userInfo.position || "N/A" }}
                </span>
              </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4 mt-4 md:mt-0">
              <!-- Gender (if not s-admin) -->
              <div v-if="userInfo.role !== 'super_admin'">
                <span class="block text-sm font-semibold text-slate-500 mb-1">
                  Gender
                </span>
                <span
                  class="block text-sm font-semibold text-white bg-gray-500 px-3 py-2 rounded-xl"
                >
                  {{ userInfo.gender || "N/A" }}
                </span>
              </div>

              <!-- Birthday -->
              <div>
                <span class="block text-sm font-semibold text-slate-500 mb-1">
                  Birthday
                </span>
                <span
                  class="block text-sm font-semibold text-white bg-gray-500 px-3 py-2 rounded-xl"
                >
                  {{ userInfo.bday || "N/A" }}
                </span>
              </div>

              <!-- Address -->
              <div>
                <span class="block text-sm font-semibold text-slate-500 mb-1">
                  Address
                </span>
                <span
                  class="block text-sm font-semibold text-white bg-gray-500 px-3 py-2 rounded-xl"
                >
                  {{ userInfo.address || "N/A" }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Section -->
        <div class="px-2 sm:px-3 py-4">
          <div
            class="flex flex-col md:flex-row justify-center text-center md:space-x-3"
          >
            <small class="text-slate-700 font-semibold">
              Expires on: December 31, 2025</small
            >
            <small class="text-emerald-600 md:ml-3 font-semibold">
              Official Employee Details</small
            >
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div
        class="w-full max-w-sm bg-white rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.3)] p-8 text-center mx-2"
      >
        <div class="flex flex-col items-center">
          <i class="pi pi-exclamation-circle text-5xl text-red-500 mb-4"></i>
          <h1 class="text-xl sm:text-2xl mb-1 font-bold text-gray-900">
            User Not Found
          </h1>
          <p class="text-sm sm:text-md font-semibold text-gray-500">
            The provided QR code is invalid or the user does not exist.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>
