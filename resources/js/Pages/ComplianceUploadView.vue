<script setup>
import { computed } from "vue";
import { usePage, Link, Head } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";
import { useComplianceUploadsColumns } from "../Data/tableColumns";

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
  complianceForm: {
    type: Object,
    required: true,
  },
  complianceUploads: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

//  Opens the upload's PDF document in a new browser tab.
function handleViewUploads(upload) {
  window.open(upload.document_url, "_blank", "noopener,noreferrer");
}

// Tanstack Table columns definition
const complianceUploadsTableColumns = useComplianceUploadsColumns({
  handleViewUploads,
  complianceForm: props.complianceForm,
});
</script>

<template>
  <Head title="Compliance Uploads" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        {{ complianceForm.code }} — {{ complianceForm.name }}
      </h1>
    </div>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs text-sm mx-4 mb-3">
      <ul>
        <li>
          <Link :href="route('compliance')">Compliance</Link>
        </li>
        <li>
          <Link :href="route('compliance.forms', { company: company.slug })">
            {{ company.name }}
          </Link>
        </li>
        <li class="font-semibold text-base-content">
          {{ complianceForm.code }}
        </li>
      </ul>
    </div>

    <!-- Compliance Uploads Table -->
    <DataTable
      :data="props.complianceUploads"
      :columns="complianceUploadsTableColumns"
      :enable-view-toggle="true"
    >
      <template #custom-actions>
        <!-- <button
         @click=""
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Upload {{ complianceForm.code }}
        </button> -->
      </template>
    </DataTable>
  </div>
</template>
