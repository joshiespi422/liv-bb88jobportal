import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";
import { longDate } from "../../Composables/useDateFormatter";

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

/**
 * Generates fields for the overtime validation form.
 * @param {import('@inertiajs/vue3').Form} form - The reactive form object from useForm (validateForm).
 * @param {import('vue').Ref<Object>} selectedDetails - A ref to the selected overtime details.
 */
export function useValidateOverTimeFormFields(form, selectedDetails) {
  return computed(() => {
    const fields = [
      {
        key: "overtime_selected",
        label: "Overtime Request Selected",
        component: TextInput,
        attrs: {
          disabled: true,
          value: longDate(selectedDetails.value?.date) || "N/A",
        },
      },
      {
        key: "requester_selected",
        label: "Requester",
        component: TextInput,
        attrs: {
          disabled: true,
          value: selectedDetails.value?.requester || "N/A",
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
            { value: "approved", label: "Approved" },
            { value: "rejected", label: "Rejected" },
          ],
        },
      },
    ];

    // If the selected status is 'rejected', add the reason text input
    if (form.status === "rejected") {
      fields.push({
        key: "reject_reason",
        label: "Reason",
        component: TextArea,
        attrs: {
          required: true,
          placeholder: "Reason for rejection",
        },
      });
    }

    return fields;
  });
}
