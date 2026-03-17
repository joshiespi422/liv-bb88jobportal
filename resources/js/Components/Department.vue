<script setup>
import { useDepartments } from "../Composables/useDepartment";
import FormModal from "./modals/FormModal.vue";
import ConfirmModal from "./modals/ConfirmModal.vue";
import ListBox from "./ListBox.vue";

// define the props this component needs from the parent page
const props = defineProps({
  departments: {
    type: Array,
    default: () => [],
  },
  currentDepartmentId: {
    type: Number,
    default: null,
  },
  authUser: {
    type: Object,
    default: null,
  },
  routeName: {
    type: String,
    default: null,
  },
  otherParams: {
    type: Object,
    default: () => {},
  },
});

// get all logic, state, and methods from the composable
const {
  isAddDeptModalOpen,
  showConfirmModal,
  isConfirmLoading,
  addDeptForm,
  deptFormFields,
  selectedDepartment,
  departmentOptions,
  handleAddDept,
  submitAddDeptForm,
  closeConfirmModal,
} = useDepartments(props); // pass the props
</script>

<template>
  <ListBox
    v-model="selectedDepartment"
    :options="departmentOptions"
    placeholder="Select a department"
    has-footer
  >
    <template #footer>
      <div
        v-if="authUser.userType === 'super_admin'"
        class="border-t border-green-primary-1 bg-base-100 p-2"
      >
        <button
          class="w-full text-sm font-semibold hover:underline text-center cursor-pointer"
          @click="isAddDeptModalOpen = true"
        >
          <i class="pi pi-plus mr-1"> </i> Create new department
        </button>
      </div>
    </template>
  </ListBox>

  <FormModal
    :isOpen="isAddDeptModalOpen"
    title="ADD NEW DEPARTMENT"
    :form="addDeptForm"
    :fields="deptFormFields"
    submitText="Submit"
    @close="isAddDeptModalOpen = false"
    @submit="handleAddDept"
  >
    <ConfirmModal
      :show="showConfirmModal"
      title="Confirm Department Creation"
      message="Are you sure you want to add a new department?"
      iconName="pi pi-folder-plus"
      iconColor="text-blue-600"
      iconBgColor="bg-blue-100"
      confirmButtonBg="bg-blue-600 hover:bg-blue-700"
      confirmText="Yes, Add Department"
      :loading="isConfirmLoading"
      @confirm="submitAddDeptForm"
      @cancel="closeConfirmModal"
    />
  </FormModal>
</template>
