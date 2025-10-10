import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";
import FileInput from "../../Components/fields/FileInput.vue";
import ComboBox from "../../Components/fields/ComboBox.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";

/**
 * Generates fields for the new task form.
 * @param {import('vue').ComputedRef<Object>} authUser - A computed ref for the authenticated user.
 * @param {Object} props - The component's props.
 * @param {import('vue').Ref<Array>} projectsList - A ref to the list of projects.
 * @param {import('vue').Ref<Array>} assigneesList - A ref to the list of assignees.
 * @param {import('vue').ComputedRef<String>} today - A computed ref for today's date string.
 */
export function useNewTaskFormFields(
  authUser,
  props,
  projectsList,
  assigneesList,
  today
) {
  return computed(() => [
    {
      key: "title",
      label: "Task Name",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Task" },
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
              required: true,
            }
          : {
              readonly: true,
              value: authUser.value.department?.name || "N/A",
            },
    },
    {
      key: "collateral",
      label: "Collateral",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Collateral" },
    },
    {
      key: "deadline",
      label: "Deadline",
      component: DateInput,
      attrs: { required: true, min: today.value },
    },
    {
      key: "project",
      label: "Project (optional)",
      component: ComboBox,
      attrs: {
        options: projectsList.value,
        placeholder: "Select a project",
      },
    },
    {
      key: "assignees",
      label: "Assignees",
      component: ComboBox,
      attrs: {
        multiple: true,
        options: assigneesList.value,
        placeholder: "Select Assignees",
      },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },

    {
      key: "priority",
      label: "Priority",
      component: SelectInput,
      attrs: {
        required: true,
        placeholder: "Select Priority",
        options: [
          { value: "high", label: "High" },
          { value: "medium", label: "Medium" },
          { value: "low", label: "Low" },
        ],
      },
    },
  ]);
}

/**
 * Generates fields for the update task form.
 * @param {import('vue').Ref<Object>} selectedDetails - A ref to the selected task details.
 * @param {import('vue').ComputedRef<Array>} statusOptions - A computed ref for the status options.
 */
export function useUpdateTaskFormFields(selectedDetails, statusOptions) {
  return computed(() => [
    {
      key: "task_title",
      label: "Task Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.title || "N/A",
      },
    },
    {
      key: "title",
      label: "Accomplish Name",
      component: TextInput,
      attrs: {
        required: true,
        placeholder: "Example Accomplishment",
      },
    },
    {
      key: "status",
      label: "Status",
      component: SelectInput,
      attrs: {
        required: true,
        placeholder: "Select a status",
        options: statusOptions.value,
      },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },
    {
      key: "link",
      label: "Reference Link (optional)",
      component: TextInput,
      attrs: { placeholder: "https://example.com" },
    },
    {
      key: "attachment",
      label: "Attachment (optional)",
      component: FileInput,
      attrs: {
        accept: ".pdf,.doc,.docx,.jpg,.jpeg,.png",
      },
    },
  ]);
}

/**
 * Generates fields for the task validation form.
 * @param {import('@inertiajs/vue3').Form} form - The reactive form object from useForm.
 * @param {import('vue').Ref<Object>} selectedDetails - A ref to the selected task details.
 */
export function useValidateTaskFormFields(form, selectedDetails) {
  return computed(() => {
    const fields = [
      {
        key: "task_title",
        label: "Task Selected",
        component: TextInput,
        attrs: {
          disabled: true,
          value: selectedDetails.value?.title || "N/A",
        },
      },
      {
        key: "status",
        label: "Status",
        component: SelectInput,
        attrs: {
          required: true,
          placeholder: "Select a status",
          options: [
            { value: "done", label: "Mark as Done" },
            { value: "revision", label: "For Revision" },
          ],
        },
      },
    ];

    if (form.status === "revision") {
      fields.push({
        key: "revise_reason",
        label: "Reason for Revision",
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
