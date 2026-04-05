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
import { useToast } from "../Composables/useToast";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import ListBox from "../Components/ListBox.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { useExcelExporter } from "../Composables/useExcelExporter";
import {
  useBiMonthlyReportColumns,
  useAttendanceReportColumns,
} from "../Data/tableColumns";

const props = defineProps({
  biMonthlyReports: {
    type: Array,
    default: () => [],
  },
  currentPeriod: Object,
  periodDates: Object,
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);
const { error } = useToast();

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

// --- Bi-Monthly Report Details ---
const {
  isOpen: isBiMonthlyModalOpen,
  isLoading: isBiMonthlyReportLoading,
  isError: isBiMonthlyReportError,
  data: selectedBiMonthlyReport,
  open: openBiMonthlyReport,
  close: closeBiMonthlyReport,
} = useDetailsModal({ baseUrl: "/bi-monthly" });

// Outer table — list of bi-monthly periods
const biMonthlyReportTableColumns = useBiMonthlyReportColumns({
  openBiMonthlyReport,
});

// Handle re-compute single
const recompute = () => {
  Object.assign(confirmModalProps, {
    title: "Re-Compute Attendance Report",
    message: `Are you sure you want to re-compute attendance reports in ${props.periodDates.label}`,
    confirmText: "Re-Compute",
    confirmButtonBg: "bg-blue-600 hover:bg-blue-700",
    iconName: "pi pi-wallet",
    iconColor: "text-blue-600",
    iconBgColor: "bg-blue-100",
  });

  pendingAction.value = () => {
    isConfirmLoading.value = true;
    router.post(
      route("bi.monthly.recompute"),
      {
        salary_period_id: props.currentPeriod.id,
      },
      {
        preserveScroll: true,
        onError: (errors) => {
          error(errors.message);
        },
        onFinish: () => {
          closeConfirmModal();
          isBiMonthlyModalOpen.value = false;
          setTimeout(() => {
            isConfirmLoading.value = false;
          }, 500);
        },
      },
    );
  };

  isConfirmModalOpen.value = true;
};

// Inner table — attendance rows inside the modal
const attendanceReportColumns = useAttendanceReportColumns();

const modalTitle = computed(() => {
  if (!selectedBiMonthlyReport.value?.period) return "BI-MONTHLY DETAILS";
  const { label, days } = selectedBiMonthlyReport.value.period;
  return `${label} (${days} days)`;
});

const { exportToExcel } = useExcelExporter();
const innerDataTableRef = ref(null);
const handleExport = async () => {
  const table = innerDataTableRef.value?.table;
  if (!table) {
    error("No table available to export");
    return;
  }

  // Respect any active search/filter on the inner table
  const filteredRows = table.getFilteredRowModel().rows;
  if (filteredRows.length === 0) {
    error("No data available to export");
    return;
  }

  // Data is already fully loaded — no extra API call needed
  const dataToExport = filteredRows.map((row) => row.original);

  const exportColumns = [
    { header: "Name", key: "name", width: 25 },
    { header: "Position", key: "position", width: 20 },
    { header: "Absent", key: "absent", width: 12 },
    { header: "Halfday", key: "halfday", width: 12 },
    { header: "Holiday", key: "holiday", width: 15 },
    { header: "Lates (HR)", key: "lates", width: 14 },
    { header: "Overtime (HR)", key: "overtime", width: 16 },
    { header: "Total", key: "total", width: 12 },
  ];

  // Use the modal title as the Excel report heading
  await exportToExcel(
    dataToExport,
    exportColumns,
    "attendance_report",
    modalTitle.value,
  );
};

const canRecompute = computed(() => {
  return selectedBiMonthlyReport.value?.period.id === props.currentPeriod?.id;
});
</script>

<template>
  <Head title="Bi-Monthly Report" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12 space-y-14">
    <div>
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold mb-3.5 ms-5">
        Bi-Monthly Report
      </h1>
      <!-- Bi-Monthly Report Table -->
      <DataTable
        :data="props.biMonthlyReports"
        :columns="biMonthlyReportTableColumns"
        :enable-view-toggle="true"
      />
    </div>
  </div>

  <DetailsModal
    :isOpen="isBiMonthlyModalOpen"
    :item="selectedBiMonthlyReport"
    :loading="isBiMonthlyReportLoading"
    :error="isBiMonthlyReportError"
    :title="modalTitle"
    @close="closeBiMonthlyReport"
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
          <div class="grid grid-cols-3 gap-3 mb-3 items-center">
            <div class="skeleton h-7 @md:h-8 w-full" />
            <div class="skeleton h-7 @md:h-8 w-full" />
            <div class="skeleton h-7 @md:h-8 w-full" />
          </div>
        </div>
      </div>
    </template>

    <!-- Custom Content Layout -->
    <template #content="{ item }">
      <DataTable
        ref="innerDataTableRef"
        :data="item.employees ?? []"
        :columns="attendanceReportColumns"
        :enable-view-toggle="true"
        class="mt-3"
      >
        <template #custom-actions>
          <button
            v-if="canRecompute"
            @click="recompute"
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
          >
            <i class="pi pi-undo mr-1" />
            Re-compute
          </button>
          <button
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
            @click="handleExport"
          >
            <i class="pi pi-download" />
            Export
          </button>
        </template>
      </DataTable>
    </template>

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="isConfirmModalOpen"
      v-bind="confirmModalProps"
      :loading="isConfirmLoading"
      @cancel="closeConfirmModal"
      @confirm="executeConfirm"
    />
  </DetailsModal>
</template>
