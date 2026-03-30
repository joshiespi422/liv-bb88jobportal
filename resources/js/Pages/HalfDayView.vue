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
import { halfDayDetailFields } from "../Data/detailFields";
import { useHalfDayColumns } from "../Data/tableColumns";
import { statusText } from "../Composables/useClassMap";
// import {
//   useRequestOverTimeFormFields,
//   useValidateOverTimeFormFields,
// } from "../Data/forms/overtimeFormFields";

const props = defineProps({
  halfDays: {
    type: Array,
    default: () => [],
  },
});

// logged in user data
const page = usePage();
const authUser = computed(() => page.props.auth.user);
// for notification click
const { onMountedHandleParameter } = useUrlParameter();

// -- Half Day Request Details Logic --
const {
  isOpen: isHalfDayModalOpen,
  isLoading: isHalfDayLoading,
  isError: isHalfDayError,
  data: selectedHalfDay,
  open: fetchHalfDayDetails,
  close: closeHalfDayModal,
} = useDetailsModal({ baseUrl: "/halfday" });
// Auto-handle 'open' parameter on mount
onMountedHandleParameter("open", fetchHalfDayDetails);
// state for showing rejection reason
const showRejectReason = ref(false);

// Function to toggle rejection reason
const toggleRejectReason = () => {
  showRejectReason.value = !showRejectReason.value;
};
// Handler for viewing half day request and details modal function
const handleViewDetails = (halfDayId) => {
  showRejectReason.value = false;
  fetchHalfDayDetails(halfDayId);
};

// Tanstack Table columns definition
const halfDayTableColumns = useHalfDayColumns({ handleViewDetails });
</script>

<template>
  <Head title="Half Day Request" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div
      class="flex flex-col items-center gap-2 sm:flex-row sm:justify-between sm:gap-0 mx-4 mb-5"
    >
      <h1 class="text-lg @sm:text-2xl @4xl:text-3xl font-bold">
        Half Day Request
      </h1>
    </div>

    <!-- Half Day Request Table -->
    <DataTable
      :data="props.halfDays"
      :columns="halfDayTableColumns"
      :enable-view-toggle="true"
    >
      <!-- <template #custom-actions>
        <button
          @click="handleRequestHalfDay"
          v-if="showRequestButton"
          class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3"
        >
          Request Half Day
        </button>
      </template> -->
    </DataTable>
  </div>

  <!-- Half Day Request Details Modal -->
  <DetailsModal
    :isOpen="isHalfDayModalOpen"
    :item="selectedHalfDay"
    :loading="isHalfDayLoading"
    :error="isHalfDayError"
    panel-class="w-full max-w-lg"
    title="HALF DAY REQUEST DETAILS"
    :fields="halfDayDetailFields"
    @close="(closeHalfDayModal(), (showRejectReason = false))"
  >
    <!-- custom content slot  -->
    <template #content="{ item, getFieldValue }">
      <div class="space-y-4 my-5">
        <div
          v-for="field in halfDayDetailFields"
          :key="field.key"
          class="grid grid-cols-1 @sm:grid-cols-[1fr_4fr] gap-1 @sm:gap-4"
        >
          <label
            class="block text-sm ps-2 @sm:ps-0 font-semibold @sm:font-bol mt-0 @sm:mt-2"
          >
            {{ field.label }}:
          </label>

          <!-- Status field with click handler -->
          <div v-if="field.key === 'status'">
            <div
              class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate flex justify-between"
            >
              <span :class="statusText[item.status]">{{
                item.status || "N/A"
              }}</span>
              <i
                v-if="item.status === 'rejected'"
                class="pi pi-info-circle text-xl text-error cursor-pointer ml-2"
                @click="toggleRejectReason"
              ></i>
            </div>
            <!-- Rejection reason row (appears below status) -->
            <transition
              enter-active-class="transition-all duration-300 ease-out"
              leave-active-class="transition-all duration-200 ease-in"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-20"
              leave-from-class="opacity-100 max-h-20"
              leave-to-class="opacity-0 max-h-0"
            >
              <div
                v-if="item && item.status === 'rejected' && showRejectReason"
                class="grid grid-cols-1 gap-4 items-center overflow-hidden"
              >
                <div
                  class="text-sm bg-base-200 rounded-xl px-3 py-2 mt-2 overflow-hidden space-y-2"
                >
                  <label class="block text-sm font-bold">
                    Reason for Rejection:
                  </label>
                  <p class="truncate font-medium text-error">
                    {{ item.reject_reason || "No reason provided" }}
                  </p>
                </div>
              </div>
            </transition>
          </div>

          <!-- Other fields -->
          <div
            v-else-if="field.html"
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium truncate"
            v-html="getFieldValue(item, field)"
          ></div>

          <p
            v-else
            class="text-sm bg-base-200 rounded-xl px-3 py-2 font-medium text-wrap truncate"
          >
            {{ getFieldValue(item, field) }}
          </p>
        </div>
      </div>
    </template>

    <template #custom-buttons>
      <!-- <button
        v-if="showSignButton"
        @click="handleSignSubmit"
        class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
      >
        Sign
      </button>
      <button
        v-if="showValidateButton"
        @click="handleValidateOvertime"
        class="btn btn-sm @sm:btn-md rounded-full border-2 border-base-content text-white bg-green-primary-1 shadow-md hover:bg-green-primary-3 me-2"
      >
        Validate
      </button> -->
    </template>

    <!-- Confirmation Modal -->
    <!-- <ConfirmModal
      :show="isConfirmModalOpen"
      v-bind="confirmModalProps"
      :loading="isConfirmLoading"
      @cancel="closeConfirmModal"
      @confirm="executeConfirm"
    /> -->
  </DetailsModal>
</template>
