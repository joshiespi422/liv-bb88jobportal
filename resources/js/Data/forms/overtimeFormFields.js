import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";

export function useRequestOverTimeFormFields() {
  return computed(() => {
    const fields = [
      {
        key: "date",
        label: "Date",
        component: DateInput,
        attrs: { required: true },
      },
      {
        key: "start_time",
        label: "Start Time",
        component: DateInput,
        attrs: { required: true, type: "time" },
      },
      {
        key: "end_time",
        label: "End Time",
        component: DateInput,
        attrs: { required: true, type: "time" },
      },
      {
        key: "reason",
        label: "Reason",
        component: TextArea,
        attrs: {
          required: true,
          placeholder: "Please provide a reason for the overtime request",
        },
      },
    ];

    return fields;
  });
}
