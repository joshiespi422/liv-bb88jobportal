<script setup>
import { computed } from "vue";
import { useAssignees } from "../Composables/useAssignees";

const props = defineProps({
  assignees: {
    type: Array,
    default: () => [],
  },
  maxVisible: {
    type: Number,
    default: 3,
  },
  avatarSizeClass: {
    type: String,
    default: "w-9 h-9 @sm:w-11 @sm:h-11", // Default from project parent
  },
  spacingClass: {
    type: String,
    default: "-space-x-3", // Default from project parent
  },
});

const { renderAssignees } = useAssignees();

const processed = computed(() => {
  return renderAssignees(props.assignees, props.maxVisible);
});
</script>

<template>
  <div
    v-if="processed.visibleAssignees.length > 0"
    class="avatar-group p-1"
    :class="spacingClass"
  >
    <div
      v-for="assignee in processed.visibleAssignees"
      :key="assignee.id"
      class="avatar border-0 bg-neutral cursor-pointer hover:z-10 hover:scale-110 transition-transform"
      :class="avatarSizeClass"
      :data-tippy-content="assignee.name"
    >
      <div>
        <img
          :src="assignee.picture || '/profile-images/default.png'"
          :alt="assignee.name"
        />
      </div>
    </div>

    <div
      v-if="processed.hiddenCount > 0"
      class="avatar border-0 placeholder cursor-pointer hover:z-10 hover:scale-110 transition-transform"
      :class="avatarSizeClass"
      :data-tippy-content="`${processed.hiddenCount} more assignees`"
    >
      <div class="bg-neutral text-neutral-content">
        <span
          class="text-xs font-bold flex items-center justify-center w-full h-full"
          >+{{ processed.hiddenCount }}</span
        >
      </div>
    </div>
  </div>

  <div v-else class="text-gray-400 italic text-sm">Unassigned</div>
</template>
