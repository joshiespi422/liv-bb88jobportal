import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";
import PasswordInput from "../../Components/fields/PasswordInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";

/**
 * @param {import('vue').ComputedRef<Object>} authUser - A computed ref for the authenticated user.
 * @param {Object} props - The component's props.
 */
export function useEmployeeFormFields(authUser, props) {
  return computed(() => [
    {
      key: "email",
      label: "Email Address",
      component: TextInput,
      attrs: {
        type: "email",
        required: true,
        placeholder: "h1D2y@example.com",
      },
    },
    {
      key: "name",
      label: "Name",
      component: TextInput,
      attrs: { required: true, placeholder: "John Doe" },
    },
    {
      key: "qr_code",
      label: "QR Code",
      component: TextInput,
      attrs: {
        placeholder: "02-E0001-1925",
        pattern: "^[A-Z0-9]{2}-[A-Z0-9]{5}-[A-Z0-9]{4}$",
      },
    },
    {
      key: "position",
      label: "Position",
      component: TextInput,
      attrs: { required: true, placeholder: "Software Engineer" },
    },
    {
      key: "department_id",
      label: "Department",
      component:
        authUser.value.userType === "super_admin" ? SelectInput : TextInput,
      attrs:
        authUser.value.userType === "super_admin"
          ? {
              options: props.departments.map((d) => ({
                value: d.id,
                label: d.dept_name,
              })),
              placeholder: "Select a department",
            }
          : {
              readonly: true,
              value: authUser.value.department?.name || "N/A",
            },
    },
    {
      key: "hierarchy",
      label: "Hierarchy",
      component: SelectInput,
      attrs: {
        required: true,
        options: [
          { value: "Leader", label: "Leader" },
          { value: "Member", label: "Member" },
        ],
        placeholder: "Select a hierarchy",
      },
    },
    {
      key: "password",
      label: "Password",
      component: PasswordInput,
      attrs: { required: true, placeholder: "Enter password" },
    },
  ]);
}

/**
 * @param {import('@inertiajs/vue3').Form} form - The reactive form object from useForm.
 * @param {import('vue').Ref<Object>} selectedEmployee - A ref to the selected employee details.
 */
export function useUpdateFormFields(form, selectedEmployee) {
  return computed(() => {
    const allStatusOptions = [
      { value: "active", label: "Active" },
      { value: "resigned", label: "Mark as Resigned" },
      { value: "terminated", label: "Mark as Terminated" },
    ];
    const filteredStatusOptions = allStatusOptions.filter(
      (opt) => opt.value !== selectedEmployee.value?.status
    );

    const fields = [
      {
        key: "employee_name",
        label: "Selected",
        component: TextInput,
        attrs: {
          disabled: true,
          value: selectedEmployee.value?.name || "N/A",
        },
      },
      {
        key: "status",
        label: "Status",
        component: SelectInput,
        attrs: {
          required: true,
          options: filteredStatusOptions,
          placeholder: "Select a status",
        },
      },
    ];

    if (form.status === "terminated") {
      fields.push({
        key: "terminate_reason",
        label: "Reason for Termination",
        component: TextArea,
        attrs: {
          required: true,
          placeholder: "Please provide a reason",
        },
      });
    }
    return fields;
  });
}
