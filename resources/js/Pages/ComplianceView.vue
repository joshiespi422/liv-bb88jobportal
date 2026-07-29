<script setup>
import { ref, computed, reactive, watch } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";

const props = defineProps({
  companies: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);
</script>

<template>
  <Head title="Company Compliance" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Company Compliance
      </h1>
    </div>

    <!-- Companies Table -->
    <DataTable :data="props.companies" display-mode="card" enable-tooltips>
      <!-- Custom card layout -->
      <template #card-item="{ row }">
        <div
          class="card bg-base-100 shadow-md border-2 border-base-200 hover:shadow-lg transition-shadow duration-200"
        >
          <div class="card-body p-5">
            <!-- Card Header: Name & Slug/Badge -->
            <div class="flex items-start justify-between gap-2">
              <div>
                <h2
                  class="card-title text-base sm:text-lg font-semibold text-base-content"
                >
                  {{ row.name }}
                </h2>
                <span class="text-xs font-mono text-base-content/60">
                  @{{ row.slug }}
                </span>
              </div>
              <span
                class="badge badge-sm badge-info badge-soft uppercase tracking-wider font-medium"
              >
                Active
              </span>
            </div>

            <!-- Divider -->
            <div class="divider my-1"></div>

            <!-- Card Content Details -->
            <div class="space-y-1.5 text-sm text-base-content/80 my-1">
              <div class="flex items-center justify-between">
                <span class="font-medium text-base-content/50 text-xs"
                  >TIN:</span
                >
                <span class="font-mono text-xs bg-base-200 px-2 py-0.5 rounded">
                  {{ row.tin || "N/A" }}
                </span>
              </div>
              <div class="flex flex-col gap-0.5">
                <span class="font-medium text-base-content/50 text-xs"
                  >Address:</span
                >
                <p class="text-xs line-clamp-2 text-base-content/70">
                  {{ row.address || "No address provided" }}
                </p>
              </div>
            </div>

            <!-- Card Actions -->
            <div
              v-if="row.slug"
              class="card-actions justify-end mt-3 pt-2 border-t border-base-200"
            >
              <Link
                :href="route('compliance.forms', { company: row.slug })"
                class="btn btn-sm btn-secondary w-full sm:w-auto"
              >
                View Details
              </Link>
            </div>
          </div>
        </div>
      </template>
    </DataTable>
  </div>
</template>
