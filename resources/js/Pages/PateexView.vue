<script setup>
const props = defineProps({
  registrantLogs: Array,
});
</script>

<template>
  <div class="p-8 font-sans">
    <h1 class="text-3xl font-bold mb-6">Patex - Daily Registrant Logs</h1>

    <!-- Check if there are logs to display -->
    <div v-if="props.registrantLogs && props.registrantLogs.length > 0">
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
          <thead>
            <tr
              class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal"
            >
              <th class="py-3 px-6 text-left">Full Name</th>
              <th class="py-3 px-6 text-left">Email</th>
              <th class="py-3 px-6 text-left">Mobile</th>
              <th class="py-3 px-6 text-center">Age</th>
              <th class="py-3 px-6 text-center">Time In</th>
              <th class="py-3 px-6 text-center">Time Out</th>
            </tr>
          </thead>
          <tbody class="text-gray-600 text-sm font-light">
            <!-- Loop through each log entry -->
            <tr
              v-for="log in props.registrantLogs"
              :key="log.email"
              class="border-b border-gray-200 hover:bg-gray-100"
            >
              <td class="py-3 px-6 text-left whitespace-nowrap">
                {{ log.full_name }}
              </td>
              <td class="py-3 px-6 text-left">
                {{ log.email }}
              </td>
              <td class="py-3 px-6 text-left">
                {{ log.mobile_number }}
              </td>
              <td class="py-3 px-6 text-center">
                {{ log.age }}
              </td>
              <td class="py-3 px-6 text-center font-mono">
                {{ log.time_in }}
              </td>
              <td class="py-3 px-6 text-center font-mono">
                <!-- Display 'N/A' if time_out is null or empty -->
                {{ log.time_out || "N/A" }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Show a message if no logs were found -->
    <div
      v-else
      class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md"
      role="alert"
    >
      <p class="font-bold">No Data</p>
      <p>No registrant logs found for today.</p>
    </div>
  </div>
</template>
