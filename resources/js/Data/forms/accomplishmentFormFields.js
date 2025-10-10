import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import FileInput from "../../Components/fields/FileInput.vue";

/**
 * @param {import('vue').Ref<Object>} selectedDetails - A ref to the accomplishment details.
 */
export function useEditAccomplishmentFormFields(selectedDetails) {
  return computed(() => [
    {
      key: "task_title",
      label: "Task Selected",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.task_title || "N/A",
      },
    },
    {
      key: "user_name",
      label: "From",
      component: TextInput,
      attrs: {
        disabled: true,
        value: selectedDetails.value?.user_name || "N/A",
      },
    },
    {
      key: "title",
      label: "Accomplish Name",
      component: TextInput,
      attrs: { disabled: true, value: selectedDetails.value?.title || "N/A" },
    },
    {
      key: "description",
      label: "Description",
      component: TextArea,
      attrs: {
        required: true,
        placeholder: "Example Description",
      },
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
