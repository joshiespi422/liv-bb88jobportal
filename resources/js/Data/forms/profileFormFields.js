import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";
import PasswordInput from "../../Components/fields/PasswordInput.vue";
import DateInput from "../../Components/fields/DateInput.vue";

export function usePasswordFormFields() {
  return computed(() => [
    {
      key: "current_password",
      label: "Current Password",
      component: PasswordInput,
      attrs: { required: true, placeholder: "Enter current password" },
    },
    {
      key: "password",
      label: "New Password",
      component: PasswordInput,
      attrs: { required: true, placeholder: "Enter new password" },
    },
    {
      key: "password_confirmation",
      label: "Confirm New Password",
      component: PasswordInput,
      attrs: { required: true, placeholder: "Confirm new password" },
    },
  ]);
}

/**
 * @param {Object} props - The component's props.
 */
export function useDetailsFormFields(props) {
  return computed(() => [
    {
      key: "name",
      label: "Name",
      component: TextInput,
      attrs: { disabled: true, value: props.profile.name || "N/A" },
    },
    {
      key: "position",
      label: "Position",
      component: TextInput,
      attrs: { disabled: true, value: props.profile.position || "N/A" },
    },
    ...(props.profile.department
      ? [
          {
            key: "department",
            label: "Department",
            component: TextInput,
            attrs: { disabled: true, value: props.profile.department || "N/A" },
          },
        ]
      : []),
    ...(props.profile.hierarchy
      ? [
          {
            key: "hierarchy",
            label: "Hierarchy",
            component: TextInput,
            attrs: { disabled: true, value: props.profile.hierarchy || "N/A" },
          },
        ]
      : []),
    ...(props.profile.school
      ? [
          {
            key: "school",
            label: "School",
            component: TextInput,
            attrs: { disabled: true, value: props.profile.school || "N/A" },
          },
        ]
      : []),
    {
      key: "qr_code",
      label: "QR Code",
      component: TextInput,
      attrs: { disabled: true, value: props.profile.qr_code || "N/A" },
    },
    {
      key: "address",
      label: "Address",
      component: TextInput,
      attrs: { placeholder: "Enter your address", required: true },
    },
    {
      key: "bday",
      label: "Birthday",
      component: DateInput,
      attrs: { required: true },
    },
    {
      key: "gender",
      label: "Gender",
      component: SelectInput,
      attrs: {
        options: [
          { value: "Male", label: "Male" },
          { value: "Female", label: "Female" },
          { value: "Other", label: "Other" },
          { value: "Prefer not to say", label: "Prefer not to say" },
        ],
        placeholder: "Select your gender",
        required: true,
      },
    },
  ]);
}
