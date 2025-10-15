import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

/**
 * A composable for processing and rendering lists of assignees.
 */
export function useAssignees() {
  const page = usePage();
  const authUser = computed(() => page.props.auth.user);

  /**
   * Sorts assignees to show the current user first, then returns a visible list
   * and a count of the remaining hidden assignees.
   *
   * @param {Array} assignees - The array of assignee objects.
   * @param {number} maximum - The maximum number of assignees to show.
   * @returns {{visibleAssignees: Array, hiddenCount: number}}
   */
  const renderAssignees = (assignees, maximum = 3) => {
    if (!assignees || assignees.length === 0) {
      return { visibleAssignees: [], hiddenCount: 0 };
    }

    // Create a copy to avoid mutating the original prop
    let sortedAssignees = [...assignees];

    // Find and move the current user to the beginning of the list
    if (authUser.value) {
      const currentUserIndex = sortedAssignees.findIndex(
        (a) => a.id === authUser.value.id
      );

      if (currentUserIndex > -1) {
        const currentUser = sortedAssignees.splice(currentUserIndex, 1)[0];
        sortedAssignees.unshift(currentUser);
      }
    }

    const visibleAssignees = sortedAssignees.slice(0, maximum);
    const hiddenCount = sortedAssignees.length - visibleAssignees.length;

    return { visibleAssignees, hiddenCount };
  };

  return { renderAssignees };
}
