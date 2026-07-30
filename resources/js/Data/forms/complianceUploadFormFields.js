import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import FileInput from "../../Components/fields/FileInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";

export function useNewUploadFormFields() {
  return computed(() => [
    {
      key: "year",
      label: "Year",
      component: TextInput,
      attrs: { required: true, placeholder: "e.g. 2023", type: "number" },
    },
    {
      key: "period",
      label: "Period",
      component: TextInput,
      attrs: { required: true, placeholder: "e.g. 1", type: "number" },
    },
    {
      key: "start_date",
      label: "Start Date",
      component: DateInput,
      attrs: { required: true, placeholder: "e.g. 01/01/2023" },
    },
    {
      key: "end_date",
      label: "End Date",
      component: DateInput,
      attrs: { required: true, placeholder: "e.g. 01/01/2023" },
    },
    {
      key: "document",
      label: "Document",
      component: FileInput,
      attrs: { required: true, accept: ".pdf", maxSize: 2 * 1024 * 1024 },
    },
    {
      key: "remarks",
      label: "Remarks (Optional)",
      component: TextArea,
      attrs: { placeholder: "e.g. Example Remarks" },
    },
  ]);
}
