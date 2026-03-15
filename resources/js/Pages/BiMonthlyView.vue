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
import { useBiMonthlyReportColumns } from "../Data/tableColumns";

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
} = useDetailsModal({ baseUrl: "/bi-monthly/show" });

// Tanstack Table columns definition
const biMonthlyReportTableColumns = useBiMonthlyReportColumns({
  openBiMonthlyReport,
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
</template>
