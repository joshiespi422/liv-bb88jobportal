import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { useAddDepartmentFormFields } from "../Data/forms/departmentFormFields";

// We pass the component's props into the composable
export function useDepartments(props) {
  // --- State ---
  const isAddDeptModalOpen = ref(false);
  const showConfirmModal = ref(false);
  const isConfirmLoading = ref(false);

  // --- Forms ---
  const addDeptForm = useForm({
    dept_name: "",
  });
  const deptFormFields = useAddDepartmentFormFields();

  // core logic for the super_admin filter
  const selectedDepartment = computed({
    get() {
      return props.currentDepartmentId;
    },
    set(newDeptId) {
      if (!newDeptId) return;

      const isSuperAdmin = props.authUser.userType === "super_admin";
      const isLeaveRouteAdmin =
        props.routeName === "leave" &&
        props.authUser.userType === "employee" &&
        props.authUser.department?.name === "Admin";

      if (isSuperAdmin || isLeaveRouteAdmin) {
        const queryParams = {
          dept: newDeptId,
          ...props.otherParams,
        };
        router.get(route(props.routeName), queryParams, {
          preserveState: true,
          preserveScroll: true,
          replace: true,
        });
      }
    },
  });

  // Formats departments for the ListBox
  const departmentOptions = computed(() => {
    return props.departments.map((d) => ({
      value: d.id,
      label: d.dept_name,
    }));
  });

  // --- Methods ---
  const handleAddDept = () => {
    showConfirmModal.value = true;
  };
  const closeConfirmModal = () => {
    showConfirmModal.value = false;
  };
  const submitAddDeptForm = () => {
    isConfirmLoading.value = true;
    addDeptForm.post(route("department.store"), {
      onSuccess: () => {
        isAddDeptModalOpen.value = false;
        addDeptForm.reset();
      },
      onFinish: () => {
        closeConfirmModal();
        setTimeout(() => {
          isConfirmLoading.value = false;
        }, 500);
      },
    });
  };

  // --- Expose everything the template needs ---
  return {
    // State
    isAddDeptModalOpen,
    showConfirmModal,
    isConfirmLoading,

    // Forms
    addDeptForm,
    deptFormFields,

    // Computed
    selectedDepartment,
    departmentOptions,

    // Methods
    handleAddDept,
    submitAddDeptForm,
    closeConfirmModal,
  };
}
