import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";
import PasswordInput from "../../Components/fields/PasswordInput.vue";

/**
 * @param {import('vue').ComputedRef<Object>} authUser - A computed ref for the authenticated user.
 * @param {Object} props - The component's props.
 */
export function useInternFormFields(authUser, props) {
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
      key: "school",
      label: "School",
      component: TextInput,
      attrs: { required: true, placeholder: "Example School" },
    },
    {
      key: "position",
      label: "Position",
      component: TextInput,
      attrs: { readonly: true, value: "Intern" },
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
      key: "password",
      label: "Password",
      component: PasswordInput,
      attrs: { required: true, placeholder: "Enter password" },
    },
  ]);
}

/**
 * @param {import('@inertiajs/vue3').Form} form - The reactive form object from useForm.
 * @param {import('vue').Ref<Object>} selectedIntern - A ref to the selected intern details.
 */
export function useUpdateFormFields(form, selectedIntern) {
  return computed(() => {
    const allStatusOptions = [
      { value: "completed", label: "Mark as Completed" },
      { value: "active", label: "Promote to Employee" },
    ];
    const filteredStatusOptions = allStatusOptions.filter(
      (opt) => opt.value !== selectedIntern.value?.status
    );

    const fields = [
      {
        key: "intern_name",
        label: "Selected",
        component: TextInput,
        attrs: {
          disabled: true,
          value: selectedIntern.value?.name || "N/A",
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

    if (form.status === "active") {
      fields.push(
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
        }
      );
    }

    return fields;
  });
}
