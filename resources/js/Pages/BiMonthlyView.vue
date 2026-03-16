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
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// --- Bi-Monthly Report Details ---
const {
  isOpen: isBiMonthlyModalOpen,
  isLoading: isBiMonthlyReportLoading,
  isError: isBiMonthlyReportError,
  data: selectedBiMonthlyReport,
  open: openBiMonthlyReport,
  close: closeBiMonthlyReport,
} = useDetailsModal({ baseUrl: "/bi-monthly" });

// Outer table — list of salary periods
const biMonthlyReportTableColumns = useBiMonthlyReportColumns({
  openBiMonthlyReport,
});

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
            class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
            @click="handleExport"
          >
            <i class="pi pi-download" />
            Export
          </button>
        </template>
      </DataTable>
    </template>
  </DetailsModal>
</template>
