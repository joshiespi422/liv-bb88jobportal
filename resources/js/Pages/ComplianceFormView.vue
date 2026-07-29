<script setup>
import { computed } from "vue";
import { usePage, Link, Head } from "@inertiajs/vue3";
import DataTable from "../Components/DataTable.vue";

const props = defineProps({
  company: {
    type: Object,
    required: true,
  },
  activeTab: String,
  complianceTabs: {
    type: Array,
    required: true,
  },
  complianceForms: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const returnTypeBadgeClass = (type) => {
  return (
    {
      monthly: "badge-info",
      quarterly: "badge-warning",
      annual: "badge-success",
      custom: "badge-neutral",
    }[type] ?? "badge-neutral"
  );
};
</script>

<template>
  <Head :title="`${company.name} - Compliance Forms`" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        {{ company.name }}
      </h1>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-box my-3 tabs-sm @sm:tabs-md">
      <Link
        v-for="tab in complianceTabs"
        :key="tab.id"
        :href="
          route('compliance.forms', { company: company.slug, tab: tab.id })
        "
        :class="[
          'tab',
          activeTab === tab.id
            ? 'tab-active font-bold pointer-events-none'
            : 'hover:bg-base-300',
        ]"
        preserve-state
        preserve-scroll
        replace
      >
        {{ tab.label }}
      </Link>
    </div>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs text-sm mx-4 mb-3">
      <ul>
        <li>
          <Link :href="route('compliance')">Compliance</Link>
        </li>
        <li class="font-semibold text-base-content">{{ company.name }}</li>
      </ul>
    </div>

    <!-- Compliance Forms Table -->
    <DataTable
      :data="props.complianceForms"
      display-mode="card"
      enable-tooltips
    >
      <template #card-item="{ row }">
        <div
          class="card bg-base-100 shadow-md border-2 border-base-200 hover:shadow-lg transition-shadow duration-200"
        >
          <div class="card-body p-5">
            <!-- Card Header -->
            <div class="flex items-start justify-between gap-2">
              <h2
                class="card-title text-base sm:text-lg font-semibold text-base-content"
              >
                {{ row.code }}
              </h2>

              <span
                class="badge badge-sm badge-soft uppercase tracking-wider font-medium shrink-0"
                :class="returnTypeBadgeClass(row.return_type)"
              >
                {{ row.return_type_label }}
              </span>
            </div>
            <span class="text-xs text-base-content/60">
              {{ row.name }}
            </span>

            <div class="divider my-1"></div>

            <!-- Card Content -->
            <div class="space-y-1.5 text-sm text-base-content/80 my-1">
              <p class="text-xs line-clamp-2 text-base-content/70 italic">
                {{ row.description || "No description provided" }}
              </p>
              <div class="flex items-center justify-between mt-2">
                <span class="font-medium text-base-content/50 text-xs"
                  >Uploads:</span
                >
                <span class="badge badge-sm badge-ghost">
                  {{ row.uploads_count }}
                </span>
              </div>
            </div>

            <!-- Card Actions -->
            <div
              class="card-actions justify-end mt-3 pt-2 border-t border-base-200"
            >
              <!-- <Link
                :href="
                  route('compliance.uploads', {
                    company: company.slug,
                    form: row.id,
                  })
                "
                class="btn btn-sm btn-secondary w-full sm:w-auto"
              >
                Manage Uploads
              </Link> -->
            </div>
          </div>
        </div>
      </template>
    </DataTable>
  </div>
</template>
