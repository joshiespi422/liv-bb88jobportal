export const statusText = {
  done: "text-success",
  revision: "text-error",
  "in progress": "text-accent",
  pending: "text-accent",
  approved: "text-success",
  rejected: "text-error",
  "for approval": "text-info",
  dropped: "text-error",
};
export const priorityText = {
  low: "text-info",
  medium: "text-accent",
  high: "text-error",
};
export function getTextClass(field, item) {
  if (field.key === "status") {
    return statusText[item.status] || "";
  }
  if (field.key === "priority") {
    return priorityText[item.priority] || "";
  }
  return "";
}

export const statusBadge = {
  "in progress": "badge-accent",
  "for approval": "badge-info",
  done: "badge-success",
  revision: "badge-error",
  pending: "badge-accent",
  approved: "badge-success",
  rejected: "badge-error",
  dropped: "badge-error",
};
