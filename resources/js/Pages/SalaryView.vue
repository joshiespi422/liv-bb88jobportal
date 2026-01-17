<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import ListBox from "../Components/ListBox.vue";
import { useSalaryColumns } from "../Data/tableColumns";

const props = defineProps({
  employees: {
    type: Array,
    default: () => [],
  },
  currentPeriod: Object,
  periodDates: Object,
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// for listbox
const selectedEmployeeId = ref(props.employees[0]?.id);
// Find the selected employee object and their salary for this period
const selectedEmployee = computed(() =>
  props.employees.find((e) => e.id === selectedEmployeeId.value),
);
const salaryData = computed(
  () => selectedEmployee.value?.salaries?.[0] || null,
);
const employeeHasSetSalary = computed(
  () => selectedEmployee.value?.employee_details?.current_salary,
);

// checker if all employee salaries are approved
const computeAllChecker = computed(() => {
  if (props.employees.length === 0) return false;

  return props.employees.every((employee) => {
    const salary = employee.salaries?.[0];
    const hasCurrentSalary = employee.employee_details?.current_salary;
    // Check if they have an approved salary OR if they are missing their base current_salary
    return (salary && salary.status_name === "approved") || !hasCurrentSalary;
  });
});
</script>

<template>
  <Head title="Salary Payroll" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div
        class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
      >
        <h1 class="text-lg @4xl:text-xl font-bold">
          Pending payroll for
          <span class="text-green-600">{{ periodDates.label }}</span>
        </h1>

        <div class="w-80">
          <ListBox
            v-model="selectedEmployeeId"
            :options="employees.map((e) => ({ value: e.id, label: e.name }))"
            placeholder="Filter by employee..."
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-between gap-2 mx-4 mb-5">
        <button
          :disabled="computeAllChecker"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          <i class="pi pi-undo mr-1" />
          Re-compute All
        </button>

        <div class="flex gap-2">
          <button
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-check-circle mr-1" />
            Approve
          </button>
          <button
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-undo mr-1" />
            Re-compute
          </button>
        </div>
      </div>

      <div
        v-if="!salaryData"
        class="text-center py-20 border-2 border-dashed rounded-2xl"
      >
        <div role="alert" class="alert alert-error alert-soft w-1/2 mx-auto">
          <i class="pi pi-exclamation-circle text-xl"></i>
          <span v-if="!employeeHasSetSalary"
            >No current salary is set for this employee. Please set a salary in
            users page</span
          >
          <span v-else
            >No payroll data found for this employee in the current
            period.</span
          >
        </div>
        <button
          v-if="employeeHasSetSalary"
          @click="recomputeSingle"
          class="mt-5 text-blue-500 font-semibold underline cursor-pointer"
        >
          Generate Initial Computation
        </button>
      </div>

      <!-- Payslip -->
      <div
        v-if="salaryData"
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
            <span class="font-semibold inline-block border-b-2 w-2/3 ps-4">{{
              periodDates.label
            }}</span>
          </div>
          <div class="grid grid-cols-[2fr_1.5fr] items-center">
            <p class="text-end me-7">EMP #</p>
            <p
              class="py-3 text-center bg-lime-200 text-black border-black border-3 border-r-0"
            >
              {{ selectedEmployee.qr_code || "N/A" }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-2 items-center">
          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="font-semibold ms-10">EMPLOYEE</span>
              <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                >{{ selectedEmployee.name }}
              </span>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Position</span>
              <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                >{{ selectedEmployee.position || "N/A" }}
              </span>
            </div>
          </div>
          <div class="py-4 space-y-1.5">
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Rate/Month</span>
              <div class="flex justify-between w-2/3 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>{{ salaryData?.rate_month }}</span>
              </div>
            </div>
            <div class="grid grid-cols-[1fr_2fr]">
              <span class="ms-10">Rate/Day</span>
              <div class="flex justify-between w-2/3 px-2 border-b-2">
                <span class="font-semibold flex w-full"> ₱ </span>
                <span>{{ salaryData?.rate_day }}</span>
              </div>
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
                  {{ salaryData.absent_day }}
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
                  {{ salaryData.overtime_hour }}
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
                <span>{{ salaryData.rate_month / 2 }}</span>
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
                <span>{{ salaryData.overtime_amount }}</span>
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
                <span class="border-b-2 px-2">{{ salaryData.gross_pay }}</span>
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
                <span>{{ salaryData.absent_deduction }}</span>
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
  </div>
</template>
