import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";

export function useCreateHolidayFormFields() {
  return computed(() => {
    const fields = [
      {
        key: "name",
        label: "Holiday Name",
        component: TextInput,
        attrs: {
          required: true,
          placeholder: "Example Holiday Name",
        },
      },
      {
        key: "date",
        label: "Date",
        component: DateInput,
        attrs: { required: true },
      },
      {
        key: "type",
        label: "Type",
        component: SelectInput,
        attrs: {
          required: true,
          placeholder: "Select Type",
          options: [
            { value: "regular", label: "Regular" },
            { value: "special", label: "Special" },
          ],
        },
      },
    ];

    return fields;
  });
}
