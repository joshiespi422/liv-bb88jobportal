<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, router, useForm, Link } from "@inertiajs/vue3";
import { useUrlParameter } from "../Composables/useUrlParameter";
import DataTable from "../Components/DataTable.vue";
import Department from "../Components/Department.vue";
import DetailsModal from "../Components/modals/DetailsModal.vue";
import FormModal from "../Components/modals/FormModal.vue";
import ConfirmModal from "../Components/modals/ConfirmModal.vue";
import { useDetailsModal } from "../Composables/useDetailsModal";
import { useMaterialReqColumns } from "../Data/tableColumns";
import { statusText } from "../Composables/useClassMap";

const props = defineProps({
  materialRequests: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  currentDepartmentId: {
    type: Number,
    default: null,
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Handler for viewing material request and details modal function
const handleViewDetails = (materialReqId) => {
  //
};

// Tanstack Table columns definition
const materialReqTableColumns = useMaterialReqColumns({ handleViewDetails });
</script>

<template>
  <Head title="Material Request" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Material Request
      </h1>
      <div
        v-if="
          authUser?.userType === 'super_admin' ||
          authUser?.department?.name === 'Admin'
        "
        class="w-52 md:w-60 lg:w-72"
      >
        <Department
          :departments="props.departments"
          :current-department-id="props.currentDepartmentId"
          :auth-user="authUser"
          route-name="material.request"
        />
      </div>
    </div>

    <!-- Material Request Table -->
    <DataTable
      :data="props.materialRequests"
      :columns="materialReqTableColumns"
      :enable-view-toggle="true"
    />
  </div>
</template>
