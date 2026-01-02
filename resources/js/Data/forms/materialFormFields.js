import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";

/**
 * @param {import('vue').ComputedRef<String>} today - A computed ref for today's date string.
 */
export function useRequestMaterialFormFields(today) {
  return computed(() => {
    const fields = [
      {
        key: "name",
        label: "Material Name",
        component: TextInput,
        attrs: {
          required: true,
          placeholder: "Example Material Name",
        },
      },
      {
        key: "description",
        label: "Description",
        component: TextArea,
        attrs: {
          required: true,
          placeholder: "Please provide a description of the material request",
        },
      },
      {
        key: "purpose",
        label: "Purpose",
        component: TextArea,
        attrs: {
          required: true,
          placeholder: "Please provide the purpose of the material request",
        },
      },
      {
        key: "quantity",
        label: "Quantity",
        component: TextInput,
        attrs: {
          required: true,
          type: "number",
          placeholder: "10",
        },
      },
      {
        key: "amount",
        label: "Amount",
        component: TextInput,
        attrs: {
          required: true,
          type: "number",
          placeholder: "200.00",
        },
      },
      {
        key: "date_needed",
        label: "Date Needed",
        component: DateInput,
        attrs: { required: true, min: today.value },
      },
      {
        key: "remarks",
        label: "Remarks",
        component: TextArea,
        attrs: {
          placeholder: "Optional Remarks",
        },
      },
    ];

    return fields;
  });
}
