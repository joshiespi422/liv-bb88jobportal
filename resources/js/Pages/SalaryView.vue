<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useSalaryColumns } from "../Data/tableColumns";

const props = defineProps({
  salaryPeriods: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Tanstack Table columns definition
const salaryTableColumns = useSalaryColumns(authUser, {
  openEmployeeList,
  openPayslip,
});

function openPayslip(row) {
  //
}

function openEmployeeList(row) {
  //
}
</script>

<template>
  <Head title="Salary" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div
        class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
      >
        <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
          Current Payroll
        </h1>
      </div>

      <!-- Payslip -->
      <div
        class="border-4 border-green-primary-1 rounded-2xl shadow-xl/20 overflow-hidden"
      >
        <div class="grid grid-cols-[1fr_auto] items-center">
          <h2 class="text-xl font-semibold text-center">
            BB 88 Advertising and Digital Solutions Inc.
          </h2>
          <div class="p-2 pe-5 text-end">
            <p>
              Unit D, 2nd Floor Plaza Victoria Bldg. Sto. Rosario St. Sto.
              Domingo Angeles
            </p>
            <p class="text-center">2009 Philippines</p>
          </div>
        </div>

        <div class="grid grid-cols-2 items-center border-b pb-3">
          <div class="ms-10">
            Payslip for the period of:
            <span class="font-semibold inline-block border-b-2 w-2/3 ps-4"
              >September 16 - September 30, 2025</span
            >
          </div>
          <div class="grid grid-cols-[2fr_1.5fr] items-center">
            <p class="text-end me-7">EMP #</p>
            <p
              class="py-3 text-center bg-lime-200 text-black border-black border-3 border-r-0"
            >
              02-E00025-2425
            </p>
          </div>
        </div>

        <div class="grid grid-cols-2 items-center">
          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="font-semibold ms-10">EMPLOYEE</span>
              <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                >Juan Dela Cruz
              </span>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Position</span>
              <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                >Juan Dela Cruz
              </span>
            </div>
          </div>
          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Rate/Day</span>
              <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                >₱
              </span>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Allow/Day</span>
              <span class="font-semibold inline-block border-b-2 w-2/3 ps-2">
              </span>
            </div>
          </div>
        </div>

        <!-- Salary -->
        <p class="block bg-slate-500 ps-5 font-semibold text-white-primary">
          Salary
        </p>
        <div class="grid grid-cols-2">
          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Absent</span>
              <div class="flex justify-between w-1/2 ps-2">
                <span
                  class="font-semibold flex w-full justify-center border-b-2"
                >
                  1
                </span>
                <span>(days)</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Total OT Hours</span>
              <div class="flex justify-between w-1/3 ps-2">
                <span
                  class="font-semibold flex w-full justify-center border-b-2"
                >
                  14
                </span>
                <span>(hrs)</span>
              </div>
            </div>
          </div>

          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-5">Total Pay</span>
              <div class="flex justify-between w-2/3 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>9,000.00</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span>Special Holiday <span class="ps-3">Dec. 8</span></span>

              <div class="flex justify-between w-2/3 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>207.69</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span>Special Holiday <span class="ps-3">Dec. 11</span></span>

              <div class="flex justify-between w-2/3 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>207.69</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ps-5">Overtime</span>
              <div class="flex justify-between w-2/3 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>1,211.54</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ps-5 bg-slate-200 font-semibold py-2 text-black"
                >Gross Salary</span
              >
              <div
                class="flex justify-between w-2/3 bg-slate-200 text-black py-2 px-2"
              >
                <span class="font-semibold flex w-full border-b-2 px-2">
                  ₱
                </span>
                <span class="border-b-2 px-2">10,626.92</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Deduction -->
        <p class="block bg-slate-500 ps-5 font-semibold text-white-primary">
          Deduction
        </p>
        <div class="grid grid-cols-2 items-center">
          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2.5fr]">
              <span class="ms-10">Absent</span>
              <div class="flex justify-between w-2/5 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>692.31</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2.5fr]">
              <span class="ms-10">Half Day</span>
              <div class="flex justify-between w-2/5 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>-</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2.5fr]">
              <span class="ms-10">Others</span>
              <div class="flex justify-between w-2/5 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>-</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2.5fr]">
              <span class="ms-10">Loan</span>
              <div class="flex justify-between w-2/5 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>-</span>
              </div>
            </div>
          </div>

          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span
                class="ms-10 bg-slate-200 text-black font-semibold py-2 px-4"
                >Total Deduction</span
              >
              <div
                class="flex justify-between w-2/3 px-2 bg-slate-200 text-black py-2"
              >
                <span class="font-semibold flex w-full border-b-2 px-2">
                  ₱
                </span>
                <span class="border-b-2 px-2">692.31</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Net Pay -->
        <div
          class="bg-slate-500 ps-5 font-semibold text-white-primary py-2 grid grid-cols-[2fr_1fr] items-center"
        >
          NET PAY
          <div class="flex justify-between w-2/3 px-2 border-b-2 py-1">
            <span class="font-semibold flex w-full"> ₱ </span>
            <span>9,000.00</span>
          </div>
        </div>

        <div class="grid grid-cols-2 pt-3 my-7">
          <div class="grid grid-cols-[1fr_2fr]">
            <span class="text-center">Approved by:</span>
            <div class="flex justify-between w-2/3">
              <span class="font-semibold flex w-full justify-center border-b-2">
              </span>
            </div>
          </div>
          <div class="grid grid-cols-[1fr_2fr]">
            <span class="text-center">Received by:</span>
            <div class="flex justify-between w-2/3">
              <span class="font-semibold flex w-full justify-center border-b-2">
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Salary Payslip
      </h1>
    </div>

    <!-- Salary Table -->
    <DataTable
      :data="props.salaryPeriods"
      :columns="salaryTableColumns"
      :enable-view-toggle="true"
    />
  </div>
</template>
