import { computed } from "vue";

import ComboBox from "../../Components/fields/ComboBox.vue";
import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";

/**
 * @param {import('vue').ComputedRef<Array>} departments - A computed ref of the departments prop.
 * @param {import('vue').Ref<String>} today - A ref to today's date string.
 */
export function useNewProjectFormFields(departments, today) {
  return computed(() => [
    {
      key: "department_ids",
      label: "Departments",
      component: ComboBox,
      attrs: {
        options: departments.value,
        placeholder: "Select Departments",
        multiple: true,
      },
    },
    {
      key: "title",
      label: "Project",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Project" },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },
    {
      key: "client",
      label: "Client",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Client" },
    },
    {
      key: "deadline",
      label: "Deadline",
      component: DateInput,
      attrs: { required: true, min: today.value },
    },
  ]);
}

/**
 * @param {import('vue').Ref<Array>} projectsList - A ref to the list of projects.
 */
export function useAddIssueFormFields(projectsList) {
  return computed(() => [
    {
      key: "project",
      label: "Project",
      component: ComboBox,
      attrs: {
        options: projectsList.value,
        placeholder: "Select Project",
      },
    },
    {
      key: "title",
      label: "Issue",
      component: TextInput,
      attrs: { required: true, placeholder: "Example Issue" },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Description" },
    },
  ]);
}

/**
 * @param {import('vue').Ref<Object>} selectedIssue - A ref to the currently selected issue.
 */
export function useResolveIssueFormFields(selectedIssue) {
  return computed(() => [
    {
      key: "issue_title",
      label: "Issue Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedIssue.value?.title || "N/A",
      },
    },
    {
      key: "solution",
      label: "Solution",
      component: TextArea,
      attrs: { required: true, placeholder: "Example Solution" },
    },
  ]);
}
