import { computed } from "vue";

import TextInput from "../../Components/fields/TextInput.vue";
import TextArea from "../../Components/fields/TextArea.vue";
import DateInput from "../../Components/fields/DateInput.vue";
import SelectInput from "../../Components/fields/SelectInput.vue";
import FileInput from "../../Components/fields/FileInput.vue";

/**
 * Generates fields for the leave request form.
 * @param {import('@inertiajs/vue3').Form} form - The reactive form object from useForm (requestForm).
 * @param {Object} props - The component's props.
 * @param {import('vue').Ref<Array>} categoriesList - A ref to the list of leave categories.
 * @param {import('vue').ComputedRef<String>} selectedLeaveType - A computed ref for the name of the selected leave type.
 * @param {import('vue').ComputedRef<String>} today - A computed ref for today's date string.
 */
export function useRequestLeaveFormFields(
  form,
  props,
  categoriesList,
  selectedLeaveType,
  today
) {
  return computed(() => {
    const fields = [
      {
        key: "leave_type_id",
        label: "Leave Type",
        component: SelectInput,
        attrs: {
          options: props.leaveTypes.map((l) => ({
            value: l.id,
            label: l.name,
          })),
          required: true,
          placeholder: "Select leave type",
        },
      },
      {
        key: "leave_category_id",
        label: "Leave Category",
        component: SelectInput,
        attrs: {
          required: true,
          placeholder: "Select leave category",
          options: categoriesList.value.map((c) => ({
            value: c.id,
            label: c.name,
          })),
        },
      },
    ];

    if (selectedLeaveType.value === "Regular") {
      fields.push({
        key: "request_date",
        label: "Request Date",
        component: DateInput,
        attrs: { required: true, min: today.value },
      });
    }

    if (selectedLeaveType.value === "Special") {
      const selectedCategory = categoriesList.value.find(
        (c) => c.id == form.leave_category_id
      );
      fields.push({
        key: "days_display",
        label: "Entitled days of leave:",
        component: TextInput,
        attrs: {
          disabled: true,
          value: selectedCategory
            ? `${selectedCategory.days} days`
            : "Select a category to see the number of days.",
        },
      });
    }

    fields.push(
      {
        key: "reason",
        label: "Reason",
        component: TextArea,
        attrs: { required: true, placeholder: "Example reason" },
      },
      {
        key: "proof",
        label: "Proof (optional)",
        component: FileInput,
        attrs: { accept: ".pdf,.jpg,.jpeg,.png", maxSize: 2 * 1024 * 1024 },
      }
    );

    return fields;
  });
}

/**
 * Generates fields for the leave validation form.
 * @param {import('@inertiajs/vue3').Form} form - The reactive form object from useForm (validateForm).
 * @param {import('vue').Ref<Object>} selectedDetails - A ref to the selected leave details.
 */
export function useValidateLeaveFormFields(form, selectedDetails) {
  return computed(() => {
    const fields = [
      {
        key: "leave_selected",
        label: "Leave Selected",
        component: TextInput,
        attrs: {
          disabled: true,
          value: selectedDetails.value?.reason || "N/A",
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

    fields.push({
      key: "hard_copy",
      label: "Hard Copy",
      component: FileInput,
      attrs: {
        required: true,
        accept: ".pdf,.jpg,.jpeg,.png",
        maxSize: 2 * 1024 * 1024,
      },
    });

    return fields;
  });
}
