import { computed } from "vue";
import TextInput from "../../Components/fields/TextInput.vue";

export function useAddDepartmentFormFields() {
  return computed(() => [
    {
      key: "dept_name",
      label: "Department",
      component: TextInput,
      attrs: { required: true, placeholder: "Enter department name" },
    },
  ]);
}
