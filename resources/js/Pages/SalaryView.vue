<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import {
  formatDate,
  shortDate,
  shortMonthDay,
} from "../Composables/useDateFormatter";
import { capitalizeFirst } from "../Data/detailFields";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import ListBox from "../Components/ListBox.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { useSalaryColumns } from "../Data/tableColumns";

const props = defineProps({
  employees: {
    type: Array,
    default: () => [],
  },
  historyPeriods: {
    type: Array,
    default: () => [],
  },
  currentPeriod: Object,
  periodDates: Object,
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Holds the action to be executed on confirmation
const pendingAction = ref(null);
// confirmation before request
const isConfirmModalOpen = ref(false);
const isConfirmLoading = ref(false);

// Holds the properties for the confirmation modal
const confirmModalProps = reactive({
  title: "",
  message: "",
  confirmText: "",
  confirmButtonBg: "",
  iconName: "",
});
// Closes the confirmation modal
const closeConfirmModal = () => {
  isConfirmModalOpen.value = false;
};
// Executes the action on confirmation
const executeConfirm = () => {
  if (pendingAction.value) {
    pendingAction.value();
  }
};

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
const isPendingSalary = computed(
  () => salaryData.value?.status.status_name === "pending",
);

// Handle re-compute single
const recomputeSingle = () => {
  Object.assign(confirmModalProps, {
    title: "Re-Compute Salary",
    message: `Are you sure you want to re-compute ${selectedEmployee.value.name}'s salary?`,
    confirmText: "Re-Compute",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-wallet",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    router.post(
      route("salary.recompute.single"),
      {
        user_id: selectedEmployeeId.value,
        salary_period_id: props.currentPeriod.id,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          closeConfirmModal();
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      },
    );
  };

  isConfirmModalOpen.value = true;
};

// Handle approve single
const approveSingle = () => {
  Object.assign(confirmModalProps, {
    title: "Approve Salary",
    message: `Are you sure you want to approve ${selectedEmployee.value.name}'s salary?`,
    confirmText: "Approve",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-wallet",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    router.patch(
      route("salary.approve", salaryData.value.id),
      {},
      {
        preserveScroll: true,
        onFinish: () => {
          closeConfirmModal();
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      },
    );
  };

  isConfirmModalOpen.value = true;
};

// Handle re-compute all
const recomputeAll = () => {
  Object.assign(confirmModalProps, {
    title: "Re-Compute All Salaries",
    message: `Are you sure you want to re-compute all pending salaries in ${props.periodDates.label}?`,
    confirmText: "Re-Compute All",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-wallet",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    router.post(
      route("salary.recompute.all"),
      {
        salary_period_id: props.currentPeriod.id,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          closeConfirmModal();
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      },
    );
  };

  isConfirmModalOpen.value = true;
};

// checker for compute all
const computeAllChecker = computed(() => {
  if (props.employees.length === 0) return false;

  return props.employees.every((employee) => {
    const salary = employee.salaries?.[0];
    const hasCurrentSalary = employee.employee_details?.current_salary;
    // Check if they have an approved salary OR if they are missing their base current_salary
    return (salary && salary.status_name === "approved") || !hasCurrentSalary;
  });
});

// checker for compute single
const computeSingleChecker = computed(() => {
  const hasSalaryRecord = !!salaryData.value;
  const hasBaseSalarySet = !!employeeHasSetSalary.value;
  const isPending = isPendingSalary.value;
  // If have a record, AND have base salary, AND it's pending
  return (hasSalaryRecord && !isPending) || !hasBaseSalarySet;
});

// checker for approve single
const approveSingleChecker = computed(() => {
  const hasSalaryRecord = !!salaryData.value;
  const hasBaseSalarySet = !!employeeHasSetSalary.value;
  const isPending = isPendingSalary.value;
  // If DON'T have a record, OR DON'T have base salary, OR it's NOT pending
  return !(hasSalaryRecord && hasBaseSalarySet && isPending);
});

const formatCurrency = (value) => {
  const num = parseFloat(value);
  if (isNaN(num) || num === 0) {
    return "-";
  }
  return new Intl.NumberFormat("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num);
};

// --- Payroll List Details ---
const {
  isOpen: isPayrollListModalOpen,
  isLoading: isPayrollListLoading,
  isError: isPayrollListError,
  data: selectedPayrollList,
  open: openPayrollList,
  close: closePayrollList,
} = useDetailsModal({ baseUrl: "/salary/payroll" });

// --- Payslip Details ---
const {
  isOpen: isPayslipModalOpen,
  isLoading: isPayslipLoading,
  isError: isPayslipError,
  data: selectedPayslip,
  open: openPayslip,
  close: closePayslip,
} = useDetailsModal({ baseUrl: "/salary" });

// Tanstack Table columns definition
const historyPeriodTableColumns = useSalaryColumns(authUser, {
  openPayrollList,
  openPayslip,
});

// control log back button visibility
const showBackButtonInPayslip = computed(() => {
  return isPayslipModalOpen.value && selectedPayslip.value !== null;
});
// handle log back navigation
const handleBackFromPayslip = () => {
  isPayslipModalOpen.value = false;
  isPayrollListModalOpen.value = true;
};
</script>

<template>
  <Head title="Salary Payroll" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12 space-y-14">
    <div v-if="authUser.userType === 'super_admin'" class="max-w-7xl mx-auto">
      <!-- Header -->
      <div
        class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
      >
        <h1 class="text-lg @4xl:text-xl font-bold">
          Pending payroll for
          <span class="text-green-600">{{ periodDates.label }}</span>
        </h1>

        <div class="w-72 @sm:w-80">
          <ListBox
            v-model="selectedEmployeeId"
            :options="employees.map((e) => ({ value: e.id, label: e.name }))"
            placeholder="Filter by employee..."
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap justify-between gap-2 mx-4 mb-5">
        <button
          :disabled="computeAllChecker"
          @click="recomputeAll"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          <i class="pi pi-undo mr-1" />
          Re-compute All
        </button>

        <div class="flex gap-2">
          <button
            :disabled="approveSingleChecker"
            @click="approveSingle"
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-check-circle mr-1" />
            Approve
          </button>
          <button
            :disabled="computeSingleChecker"
            @click="recomputeSingle"
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-undo mr-1" />
            Re-compute
          </button>
        </div>
      </div>

      <div
        v-if="!salaryData || !employeeHasSetSalary"
        class="text-center @5xl:py-20 @xl:py-14 py-8 border-2 border-dashed rounded-2xl"
      >
        <div
          role="alert"
          class="alert alert-error alert-soft @5xl:w-1/2 @xl:w-3/4 w-11/12 mx-auto mb-5"
        >
          <i class="pi pi-exclamation-circle text-xl"></i>
          <span v-if="!employeeHasSetSalary"
            >No current salary is set for {{ selectedEmployee?.name }}. Please
            set a salary in users page</span
          >
          <span v-else
            >No payroll data found for {{ selectedEmployee?.name }} in the
            current period.</span
          >
        </div>
        <button
          v-if="employeeHasSetSalary"
          @click="recomputeSingle"
          class="text-blue-500 font-semibold underline cursor-pointer"
        >
          Generate Initial Computation
        </button>
        <Link
          v-else
          :href="route('team.employees')"
          class="text-blue-500 font-semibold underline cursor-pointer"
        >
          Set Current Salary
        </Link>
      </div>

      <div
        v-if="!isPendingSalary && salaryData"
        class="text-center @5xl:py-20 @xl:py-14 py-8 border-2 border-dashed rounded-2xl"
      >
        <div
          role="alert"
          class="alert alert-success alert-soft @5xl:w-1/2 @xl:w-3/4 w-11/12 mx-auto mb-5"
        >
          <i class="pi pi-exclamation-circle text-xl"></i>
          <span
            >{{ selectedEmployee?.name }} salary payroll has been approved for
            the current period</span
          >
        </div>
      </div>

      <!-- Payslip -->
      <div
        v-if="salaryData && employeeHasSetSalary && isPendingSalary"
        class="border-4 border-green-primary-1 rounded-2xl shadow-xl/20 overflow-hidden"
      >
        <div class="overflow-x-auto">
          <div class="w-[1272px]">
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
                <span
                  class="font-semibold inline-block border-b-2 w-2/3 ps-4"
                  >{{ periodDates.label }}</span
                >
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
                    <span>{{ formatCurrency(salaryData?.rate_month) }}</span>
                  </div>
                </div>
                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="ms-10">Rate/Day</span>
                  <div class="flex justify-between w-2/3 px-2 border-b-2">
                    <span class="font-semibold flex w-full"> ₱ </span>
                    <span>{{ formatCurrency(salaryData?.rate_day) }}</span>
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
                      {{ salaryData.absent_day || "-" }}
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
                      {{ salaryData.overtime_hour || "-" }}
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
                    <span>{{ formatCurrency(salaryData.rate_month / 2) }}</span>
                  </div>
                </div>
                <div v-for="holiday in salaryData.holidays">
                  <div class="grid grid-cols-[1fr_2fr]">
                    <span
                      >{{ capitalizeFirst(holiday.type) }} Holiday
                      <span class="ps-3">{{
                        shortMonthDay(holiday.date)
                      }}</span></span
                    >

                    <div class="flex justify-between w-2/3 px-2 border-b-2">
                      <span class="font-semibold flex w-full"> ₱ </span>
                      <span>{{ formatCurrency(holiday.pivot.amount) }}</span>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="ps-5">Overtime</span>
                  <div class="flex justify-between w-2/3 px-2 border-b-2">
                    <span class="font-semibold flex w-full"> ₱ </span>
                    <span>{{
                      formatCurrency(salaryData.overtime_amount)
                    }}</span>
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
                    <span class="border-b-2 px-2">{{
                      formatCurrency(salaryData.gross_pay)
                    }}</span>
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
                    <span>{{
                      formatCurrency(salaryData.absent_deduction)
                    }}</span>
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
                    <span class="border-b-2 px-2">{{
                      formatCurrency(salaryData.absent_deduction)
                    }}</span>
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
                <span>{{ formatCurrency(salaryData.net_pay) }}</span>
              </div>
            </div>

            <div class="grid grid-cols-2 pt-3 my-7">
              <div class="grid grid-cols-[1fr_2fr]">
                <span class="text-center">Approved by:</span>
                <div class="flex justify-between w-2/3">
                  <span
                    class="font-semibold flex w-full justify-center border-b-2"
                  >
                  </span>
                </div>
              </div>
              <div class="grid grid-cols-[1fr_2fr]">
                <span class="text-center">Received by:</span>
                <div class="flex justify-between w-2/3">
                  <span
                    class="font-semibold flex w-full justify-center border-b-2"
                  >
                    {{ selectedEmployee.name }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold mb-3.5 ms-5">
        Payroll History
      </h1>
      <!-- History Period Table -->
      <DataTable
        :data="props.historyPeriods"
        :columns="historyPeriodTableColumns"
        :enable-view-toggle="true"
      />
    </div>
  </div>

  <!-- Confirmation Modal -->
  <ConfirmModal
    :show="isConfirmModalOpen"
    v-bind="confirmModalProps"
    :loading="isConfirmLoading"
    @cancel="closeConfirmModal"
    @confirm="executeConfirm"
  />

  <!-- Payroll List Details -->
  <DetailsModal
    :isOpen="isPayrollListModalOpen"
    :item="selectedPayrollList"
    :loading="isPayrollListLoading"
    :error="isPayrollListError"
    title="PAYROLL LIST"
    panelClass="w-full max-w-lg"
    @close="closePayrollList"
  >
    <!-- Custom Skeleton Layout -->
    <template #skeleton>
      <div class="my-5">
        <div v-for="n in 6" :key="n">
          <div class="grid grid-cols-[2fr_0.5fr] items-center gap-2 mb-3">
            <div class="skeleton h-8 w-full" />
            <div class="skeleton h-8 w-full" />
          </div>
        </div>
      </div>
    </template>

    <!-- Custom Content Layout -->
    <template #content="{ item }">
      <div class="my-3">
        <div class="flex items-center justify-start mt-3 px-3">
          <p class="font-semibold text-slate-500">
            {{ formatDate(item.startDate) }} - {{ formatDate(item.endDate) }}
          </p>
        </div>
        <div v-if="item.employeeList.length === 0" class="p-2">
          <div role="alert" class="alert alert-warning alert-soft mt-5">
            <i class="pi pi-exclamation-circle text-xl"></i>
            <span>No approved payroll data found for this period</span>
          </div>
        </div>
        <div
          v-for="(employee, index) in item.employeeList"
          :key="index"
          class="p-2"
        >
          <div class="grid grid-cols-[1fr_auto] items-center gap-2">
            <p class="font-semibold bg-base-200 py-2 px-4 rounded-md">
              {{ employee.name }}
            </p>
            <button
              @click="
                openPayslip(employee.id, item.periodId);
                closePayrollList();
              "
              class="btn bg-green-primary-1 hover:bg-green-primary-3 rounded-full"
            >
              <i class="pi pi-caret-right text-xl text-white-primary"></i>
            </button>
          </div>
        </div>
      </div>
    </template>
  </DetailsModal>

  <!-- Payslip Details Modal -->
  <DetailsModal
    :isOpen="isPayslipModalOpen"
    :item="selectedPayslip"
    :loading="isPayslipLoading"
    :error="isPayslipError"
    title="PAYSLIP DETAILS"
    @close="closePayslip"
    panelClass="w-full max-w-7xl"
  >
    <!-- Custom Skeleton Loader -->
    <template #skeleton>
      <div class="my-2">
        <div class="grid grid-cols-1 gap-3 rounded-lg p-3 mb-5">
          <div v-for="n in 2" :key="n">
            <div class="skeleton h-7 @md:h-8 w-full" />
          </div>
        </div>

        <div v-for="n in 4" :key="n">
          <div class="grid grid-cols-2 gap-3 mb-3 items-center">
            <div class="skeleton h-7 @md:h-8 w-full" />
            <div class="skeleton h-7 @md:h-8 w-full" />
          </div>
        </div>
      </div>
    </template>

    <!-- Custom Content Layout -->
    <template #content="{ item }">
      <!-- Payslip -->
      <div
        v-if="item.employee.salaries.length > 0"
        class="border-4 border-green-primary-1 rounded-2xl shadow-xl/20 overflow-hidden mt-5 mb-10"
      >
        <div class="overflow-x-auto">
          <div class="w-[1224px]">
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
                <span
                  class="font-semibold inline-block border-b-2 w-2/3 ps-4"
                  >{{
                    shortMonthDay(item.startDate) +
                    " - " +
                    shortDate(item.endDate)
                  }}</span
                >
              </div>
              <div class="grid grid-cols-[2fr_1.5fr] items-center">
                <p class="text-end me-7">EMP #</p>
                <p
                  class="py-3 text-center bg-lime-200 text-black border-black border-3 border-r-0"
                >
                  {{ item.employee.qr_code || "N/A" }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-2 items-center">
              <div class="py-4 space-y-1.5">
                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="font-semibold ms-10">EMPLOYEE</span>
                  <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                    >{{ item.employee.name }}
                  </span>
                </div>
                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="ms-10">Position</span>
                  <span class="font-semibold inline-block border-b-2 w-2/3 ps-2"
                    >{{ item.employee.position || "N/A" }}
                  </span>
                </div>
              </div>
              <div class="py-4 space-y-1.5">
                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="ms-10">Rate/Month</span>
                  <div class="flex justify-between w-2/3 px-2 border-b-2">
                    <span class="font-semibold flex w-full"> ₱ </span>
                    <span>{{
                      formatCurrency(item.employee.salaries[0].rate_month)
                    }}</span>
                  </div>
                </div>
                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="ms-10">Rate/Day</span>
                  <div class="flex justify-between w-2/3 px-2 border-b-2">
                    <span class="font-semibold flex w-full"> ₱ </span>
                    <span>{{
                      formatCurrency(item.employee.salaries[0].rate_day)
                    }}</span>
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
                      {{ item.employee.salaries[0].absent_day || "-" }}
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
                      {{ item.employee.salaries[0].overtime_hour || "-" }}
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
                    <span>{{
                      formatCurrency(item.employee.salaries[0].rate_month / 2)
                    }}</span>
                  </div>
                </div>
                <div v-for="holiday in item.employee.salaries[0].holidays">
                  <div class="grid grid-cols-[1fr_2fr]">
                    <span
                      >{{ capitalizeFirst(holiday.type) }}
                      <span class="ps-3">{{
                        shortMonthDay(holiday.date)
                      }}</span></span
                    >

                    <div class="flex justify-between w-2/3 px-2 border-b-2">
                      <span class="font-semibold flex w-full"> ₱ </span>
                      <span>{{ formatCurrency(holiday.pivot.amount) }}</span>
                    </div>
                  </div>
                </div>
                <div class="grid grid-cols-[1fr_2fr]">
                  <span class="ps-5">Overtime</span>
                  <div class="flex justify-between w-2/3 px-2 border-b-2">
                    <span class="font-semibold flex w-full"> ₱ </span>
                    <span>{{
                      formatCurrency(item.employee.salaries[0].overtime_amount)
                    }}</span>
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
                    <span class="border-b-2 px-2">{{
                      formatCurrency(item.employee.salaries[0].gross_pay)
                    }}</span>
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
                    <span>{{
                      formatCurrency(item.employee.salaries[0].absent_deduction)
                    }}</span>
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
                    <span class="border-b-2 px-2">{{
                      formatCurrency(item.employee.salaries[0].absent_deduction)
                    }}</span>
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
                <span>{{
                  formatCurrency(item.employee.salaries[0].net_pay)
                }}</span>
              </div>
            </div>

            <div class="grid grid-cols-2 pt-3 my-7">
              <div class="grid grid-cols-[1fr_2fr]">
                <span class="text-center">Approved by:</span>
                <div class="flex justify-between w-2/3">
                  <span
                    class="font-semibold flex w-full justify-center border-b-2"
                  >
                    {{ item.employee.salaries[0].approver.name }}
                  </span>
                </div>
              </div>
              <div class="grid grid-cols-[1fr_2fr]">
                <span class="text-center">Received by:</span>
                <div class="flex justify-between w-2/3">
                  <span
                    class="font-semibold flex w-full justify-center border-b-2"
                  >
                    {{ item.employee.name }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="p-2">
        <div role="alert" class="alert alert-warning alert-soft mt-5">
          <i class="pi pi-exclamation-circle text-xl"></i>
          <span
            >{{ authUser.name }} doesn't have an approved payroll data for this
            period.</span
          >
        </div>
      </div>
    </template>

    <template #custom-buttons>
      <button
        v-if="showBackButtonInPayslip"
        class="btn btn-sm @sm:btn-md btn-soft rounded-full me-2"
        @click="handleBackFromPayslip"
      >
        <i class="pi pi-arrow-left me-1" /> Back
      </button>
    </template>
  </DetailsModal>
</template>
