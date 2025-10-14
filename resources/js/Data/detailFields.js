import {
  longDate,
  longDateTime,
  formatDate,
} from "../Composables/useDateFormatter";

// Formatter for assignees (can be shared)
const formatAssignees = (assignees) => {
  if (!assignees || !Array.isArray(assignees)) return "N/A";
  return assignees.map((user) => user.name).join(", ");
};

// Formatter for departments (can be shared)
const formatDepartments = (departments) => {
  if (!departments || departments.length === 0) return "N/A";
  return departments.join(", ");
};

// Formatter for attachments
const attachFormatter = (attachment) => {
  if (!attachment) return "N/A";
  return `
    <div class="flex items-center gap-2">
      <i class="pi pi-paperclip text-sm"></i>
      <a href="${attachment.url}"
         target="_blank"
         class="text-blue-500 hover:underline truncate"
         download="${attachment.name}">
        ${attachment.name}
      </a>
    </div>`;
};

export const accomplishDetailFields = [
  { key: "task_title", label: "Task" },
  {
    key: "user",
    label: "From",
    formatter: (user) => (user ? user.name : "N/A"),
  },
  { key: "title", label: "Title" },
  { key: "description", label: "Description" },
  {
    key: "link",
    label: "Link",
    formatter: (value) =>
      value
        ? `<a href="${value}" target="_blank" class="text-blue-500 hover:underline">${value}</a>`
        : "N/A",
    html: true,
  },
  {
    key: "attachment",
    label: "Attachment",
    formatter: (attachment) => {
      if (!attachment) return "N/A";
      return `
        <div class="flex items-center gap-2">
          <i class="pi pi-paperclip text-sm"></i>
          <a href="${attachment.url}"
             target="_blank"
             class="text-blue-500 hover:underline truncate"
             download="${attachment.name}">
            ${attachment.name}
          </a>
        </div>
      `;
    },
    html: true,
  },
  { key: "created_at", label: "Submitted", formatter: longDateTime },
];

export const taskDetailFields = [
  { key: "title", label: "Task Name" },
  { key: "description", label: "Description" },
  { key: "assignees", label: "Assignees", formatter: formatAssignees },
  { key: "collateral", label: "Collaterals" },
  { key: "created_at", label: "Started", formatter: longDate },
  { key: "deadline", label: "Deadline", formatter: longDate },
  { key: "priority", label: "Priority" },
  { key: "status", label: "Status" },
];

export const projectDetailFields = [
  { key: "title", label: "Project" },
  { key: "description", label: "Description" },
  { key: "client", label: "Client" },
  { key: "departments", label: "Departments", formatter: formatDepartments },
  { key: "created_at", label: "Started", formatter: longDate },
  { key: "deadline", label: "Deadline", formatter: longDate },
];

export const issueDetailFields = [
  { key: "project_title", label: "Project" },
  { key: "user_name", label: "Submitted" },
  { key: "title", label: "Issue" },
  { key: "description", label: "Description" },
  { key: "status", label: "Status" },
  { key: "created_at", label: "Created At", formatter: longDate },
  { key: "solution", label: "Solution" },
];

export const employeeDetailFields = [
  { key: "name", label: "Full Name" },
  { key: "email", label: "Email" },
  { key: "picture", label: "Picture" },
  { key: "position", label: "Position" },
  { key: "deptName", label: "Department" },
  { key: "hierarchy", label: "Hierarchy" },
  { key: "address", label: "Address" },
  { key: "gender", label: "Gender" },
  { key: "bday", label: "Birthday", formatter: formatDate },
];

export const internDetailFields = [
  { key: "name", label: "Full Name" },
  { key: "email", label: "Email" },
  { key: "picture", label: "Picture" },
  { key: "position", label: "Position" },
  { key: "deptName", label: "Department" },
  { key: "school", label: "School" },
  { key: "address", label: "Address" },
  { key: "gender", label: "Gender" },
  { key: "bday", label: "Birthday", formatter: formatDate },
];

// fields that needs reactive data
export const getLeaveDetailFields = (activeTab) => {
  const requestDateFormatter = (date) => {
    // Use the passed-in value, not props.activeTab
    if (activeTab === "special") return date;
    return longDate(date);
  };

  return [
    { key: "name", label: "Employee" },
    { key: "dept_name", label: "Department" },
    {
      key: "leave_type",
      label: "Leave Type",
      formatter: (value) => `${value} Leave`,
    },
    {
      key: "category",
      label: "Category",
      formatter: (value) => `${value} Leave`,
    },
    { key: "created_at", label: "Submitted", formatter: longDate },
    {
      key: "request_date",
      label: "Leave Date",
      formatter: requestDateFormatter,
    },
    { key: "reason", label: "Reason" },
    { key: "status", label: "Status" },
    {
      key: "proof",
      label: "Proof",
      formatter: attachFormatter,
      html: true,
    },
    {
      key: "hard_copy",
      label: "Hard Copy",
      formatter: attachFormatter,
      html: true,
    },
  ];
};
