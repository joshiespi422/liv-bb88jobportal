<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { formatDate } from "../Composables/useDateFormatter";
import DataTable from "../Components/DataTable.vue";
import { formatTime } from "../Composables/useDateFormatter";

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
  attendanceList: {
    type: Array,
    default: () => [],
  },
});

// Tanstack Table columns definition
const attendanceColumns = [
  {
    accessorKey: "date",
    header: "DATE",
  },
  {
    accessorKey: "1stIn",
    header: "FIRST IN",
  },
  {
    accessorKey: "2ndIn",
    header: "1ST BREAK",
  },
  {
    accessorKey: "3rdIn",
    header: "LUNCH",
  },
  {
    accessorKey: "4thIn",
    header: "2ND BREAK",
  },
  {
    accessorKey: "lastOut",
    header: "LAST OUT",
  },
];
</script>

<template>
  <div class="p-4 md:p-8 lg:p-12 xl:p-16">
    <div
      v-if="authUser?.userType !== 'super_admin'"
      class="p-4 rounded-2xl shadow-md bg-base-200 border-3 border-green-primary-1 space-y-5"
    >
      <div class="flex items-center justify-between">
        <h1 class="text-xl xl:text-2xl font-bold flex-none">
          Attendance Today ({{ formatDate(props.userDetails?.date) }})
        </h1>
        <div>
          <button
            class="btn bg-emerald-600 text-white border-2 border-white rounded-full hover:scale-105 transition-all duration-200 ease-in-out me-3"
          >
            Time In
          </button>
          <button
            class="btn bg-rose-600 text-white border-2 border-white rounded-full hover:scale-105 transition-all duration-200 ease-in-out"
          >
            Time Out
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="avatar">
            <div
              class="ring-indigo-500 ring-offset-base-300 w-24 rounded-full ring-3 ring-offset-2"
            >
              <img
                :src="
                  props.userDetails?.picture || '/profile-images/default.png'
                "
              />
            </div>
          </div>
          <div>
            <h3 class="text-lg font-bold">
              {{ props.userDetails?.name }}
            </h3>
            <p class="text-slate-500 font-semibold">
              {{ props.userDetails?.department }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="avatar">
            <div class="w-24">
              <img
                v-if="props.userDetails?.status === 'Online'"
                src="../../assets/img/status-icon-on.png"
              />
              <img v-else src="../../assets/img/status-icon-off.png" />
            </div>
          </div>
          <div>
            <h3 class="text-lg font-bold">STATUS</h3>
            <p
              :class="[
                'font-semibold',
                {
                  'text-red-600': props.userDetails?.status === 'Offline',
                  'text-green-600': props.userDetails?.status === 'Online',
                },
              ]"
            >
              {{ props.userDetails?.status }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="w-24">
            <img src="../../assets/img/timein-icon.png" />
          </div>

          <div>
            <h3 class="text-lg font-bold">TIME IN</h3>
            <div
              v-if="props.userDetails?.time_in?.length"
              class="text-slate-500 font-semibold"
            >
              <div
                v-for="(time, index) in props.userDetails.time_in"
                :key="`in-${index}`"
              >
                {{ formatTime(time) }}
              </div>
            </div>
            <p v-else class="text-slate-500 font-semibold">N/A</p>
          </div>
        </div>

        <div class="flex items-center gap-4 p-4 rounded-3xl bg-base-100">
          <div class="w-24">
            <img src="../../assets/img/timeout-icon.png" />
          </div>

          <div>
            <h3 class="text-lg font-bold">TIME OUT</h3>
            <div
              v-if="props.userDetails?.time_out?.length"
              class="text-slate-500 font-semibold"
            >
              <div
                v-for="(time, index) in props.userDetails.time_out"
                :key="`out-${index}`"
              >
                {{ formatTime(time) }}
              </div>
            </div>
            <p v-else class="text-slate-500 font-semibold">N/A</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <DataTable
      v-if="authUser?.userType !== 'super_admin'"
      :data="props.attendanceList"
      :columns="attendanceColumns"
      class="mt-7"
    />
  </div>
</template>
