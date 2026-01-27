<script setup>
import { ref, computed, reactive, watch, h } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { useHolidayColumns } from "../Data/tableColumns";
import { statusText } from "../Composables/useClassMap";
import {
  useRequestOverTimeFormFields,
  useValidateOverTimeFormFields,
} from "../Data/forms/overtimeFormFields";

const props = defineProps({
  holidays: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Tanstack Table columns definition
const holidayTableColumns = useHolidayColumns();
</script>

<template>
  <Head title="Holiday" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Holiday Management
      </h1>
    </div>

    <!-- Holiday Request Table -->
    <DataTable
      :data="props.holidays"
      :columns="holidayTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <button
          @click="handleAddHoliday"
          v-if="authUser?.userType === 'super_admin'"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Add Holiday
        </button>
      </template>
    </DataTable>
  </div>
</template>
