<script setup>
const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
  authUser: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["view-task", "view-issue"]);

// Assignee info
const renderAssignees = (assignees, maximum = 3) => {
  if (!assignees || assignees.length === 0) {
    return [];
  }

  // Move current user to top (same logic as table)
  let sortedAssignees = [...assignees];
  const currentUserIndex = sortedAssignees.findIndex(
    (a) => a.id === props.authUser.id
  );
  if (currentUserIndex > -1) {
    const currentUser = sortedAssignees.splice(currentUserIndex, 1)[0];
    sortedAssignees.unshift(currentUser);
  }

  const visibleAssignees = sortedAssignees.slice(0, maximum);
  const hiddenCount = sortedAssignees.length - visibleAssignees.length;

  return { visibleAssignees, hiddenCount };
};
</script>

<template>
  <div class="rounded-xl bg-base-200 p-0 @sm:p-2 @2xl:p-3">
    <div class="collapse collapse-plus bg-base-100 border border-base-300">
      <input type="radio" name="my-accordion-3" checked="checked" />
      <div class="collapse-title font-semibold">Tasks List</div>
      <div class="collapse-content text-sm px-2 @sm:px-4">
        <ul
          class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-40 list-scroll"
          v-if="project.tasks && project.tasks.length"
        >
          <li
            v-for="task in project.tasks"
            :key="task.id"
            class="list-row gap-0 hover:bg-base-300 hover:cursor-pointer"
            @click="emit('view-task', task.id)"
          >
            <div>
              <div class="font-semibold truncate">
                {{ task.title }}
              </div>
              <div
                v-if="task.assignees && task.assignees.length > 0"
                class="avatar-group p-1 -space-x-1"
              >
                <div
                  v-for="assignee in renderAssignees(task.assignees, 5)
                    .visibleAssignees"
                  class="avatar w-8 h-8 flex-none border-0 bg-neutral hover:z-10 hover:-mt-1 transition-all duration-200"
                >
                  <div>
                    <img :src="assignee.picture" />
                  </div>
                </div>

                <div
                  v-if="renderAssignees(task.assignees, 5).hiddenCount > 0"
                  class="avatar w-8 h-8 flex-none border-0 placeholder hover:z-10 hover:-mt-1 transition-all duration-200"
                >
                  <div class="bg-neutral text-neutral-content">
                    <span class="font-bold flex mt-1.5 justify-center"
                      >+{{ renderAssignees(task.assignees).hiddenCount }}</span
                    >
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
        <div
          v-else
          role="alert"
          class="alert alert-warning alert-soft font-semibold"
        >
          <span>No tasks found</span>
        </div>
      </div>
    </div>
    <div class="collapse collapse-plus bg-base-100 border border-base-300 mt-1">
      <input type="radio" name="my-accordion-3" />
      <div class="collapse-title font-semibold">Issues List</div>
      <div class="collapse-content text-sm">
        <ul
          class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-40 list-scroll"
          v-if="project.issues && project.issues.length"
        >
          <li
            v-for="issue in project.issues"
            :key="issue.id"
            class="list-row hover:bg-base-300 hover:cursor-pointer"
            @click="emit('view-issue', issue.id)"
          >
            <div>
              <div class="font-semibold truncate">
                {{ issue.user_name }}
              </div>
              <div class="text-xs uppercase font-semibold opacity-60">
                {{ issue.title }}
              </div>
            </div>
          </li>
        </ul>
        <div
          v-else
          role="alert"
          class="alert alert-warning alert-soft font-semibold"
        >
          <span>No issues found</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.list-scroll::-webkit-scrollbar {
  width: 6px;
}
.list-scroll::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background-color: var(--color-green-primary-1);
}
.list-scroll::-webkit-scrollbar-track {
  margin: 6px;
  background-color: transparent;
}
</style>
