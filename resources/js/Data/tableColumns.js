import { h, computed } from "vue";
import AssigneeGroup from "../Components/AssigneeGroup.vue";
import { statusBadge } from "../Composables/useClassMap";
import {
  longDateTime,
  longDate,
  shortDate,
  formatTime,
  formatDate,
  shortMonthDay,
} from "../Composables/useDateFormatter";
import { capitalizeFirst } from "./detailFields";

/**
 * @param {Object} props - The component's props (specifically `activeTab`).
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.handleViewDetails - The function to call when 'View Details' is clicked.
 * @returns {ComputedRef<Array>} A computed property that returns the column array.
 */
export function useAccomplishmentColumns(props, { handleViewDetails }) {
  return computed(() => {
    const columns = [];

    // Conditionally add "FROM" column
    if (props.activeTab === "all") {
      columns.push({
        id: "employee",
        header: "FROM",
        accessorFn: (row) => row.user.name,
        cell: ({ cell }) => {
          const userPicture = cell.row.original.user.picture;
          return h(
            "span",
            {
              class: "flex items-center justify-center gap-2",
            },
            [
              h("img", {
                src: userPicture || "/profile-images/default.png",
                class: "avatar w-10 rounded-full",
              }),
              h(
                "span",
                {
                  class: "truncate",
                },
                cell.getValue(),
              ),
            ],
          );
        },
      });
    }

    // Common columns
    columns.push(
      {
        accessorKey: "task_title",
        header: "TASK",
      },
      {
        accessorKey: "title",
        header: "TITLE",
      },
      {
        header: "SUBMITTED",
        accessorFn: (row) => longDateTime(row.created_at),
        id: "started-date",
        cell: ({ cell }) => h("span", {}, cell.getValue()),
      },
      {
        id: "details",
        header: "DETAILS",
        cell: ({ row }) =>
          h(
            "button",
            {
              onClick: () => handleViewDetails(row.original),
              class:
                "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
            },
            "View Details",
          ),
        enableSorting: false,
      },
    );

    return columns;
  });
}

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.handleViewDetails - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useTaskColumns({ handleViewDetails }) {
  return [
    {
      accessorKey: "title",
      header: "TITLE",
    },
    {
      id: "assignees",
      size: 220,
      accessorFn: (row) => row.assignees.map((a) => a.name).join(", "),
      header: "ASSIGNEES",
      cell: ({ row }) => {
        // If there are no assignees
        if (!row.original.assignees || row.original.assignees.length === 0) {
          return h("span", { class: "text-gray-400 italic" }, "Unassigned");
        }

        // Render the AssigneeGroup component using h()
        return h(AssigneeGroup, {
          assignees: row.original.assignees,
          maxVisible: 3,
          avatarSizeClass: "w-10 h-10 @sm:w-12 @sm:h-12",
          spacingClass: "flex justify-center -space-x-4",
        });
      },
    },
    {
      header: "STARTED",
      accessorFn: (row) => longDate(row.created_at),
      id: "started-date",
      cell: ({ cell }) => {
        return h("span", {}, cell.getValue());
      },
    },
    {
      header: "STATUS",
      accessorKey: "status",
      cell: ({ row }) => {
        const status = row.original.status;
        const badgeClass = statusBadge[status] || "badge-primary";
        return h(
          "span",
          {
            class: `badge badge-soft ${badgeClass} text-sm px-3.5 py-3.5`,
          },
          status,
        );
      },
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => handleViewDetails(row.original),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * For Project Table Columns.
 * This one is simple and has no dependencies, so we can export it directly.
 */
export const projectTableColumns = [
  {
    accessorKey: "title",
    header: "TITLE",
  },
  {
    accessorKey: "description",
    header: "DESCRIPTION",
  },
  {
    id: "assignees",
    accessorFn: (row) => row.assignees.map((a) => a.name).join(", "),
    header: "ASSIGNEES",
  },
  {
    accessorFn: (row) => longDate(row.created_at),
    id: "started-date",
    header: "STARTED DATE",
  },
  {
    accessorFn: (row) => row.departments.join(", "),
    header: "DEPARTMENTS",
  },
];

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.handleViewDetails - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useLeaveColumns({ handleViewDetails }) {
  return [
    {
      id: "employee",
      header: "EMPLOYEE",
      accessorFn: (row) => row.user.name,
      cell: ({ cell }) => {
        const userPicture = cell.row.original.user.picture;
        return h(
          "span",
          {
            class: "flex items-center justify-center gap-2",
          },
          [
            h("img", {
              src: userPicture || "/profile-images/default.png",
              class: "avatar w-10 rounded-full",
            }),
            h(
              "span",
              {
                class: "truncate",
              },
              cell.getValue(),
            ),
          ],
        );
      },
    },
    {
      header: "SUBMITTED",
      accessorFn: (row) => longDate(row.created_at),
      id: "submitted-date",
      cell: ({ cell }) => {
        return h("span", {}, cell.getValue());
      },
    },
    {
      header: "STATUS",
      accessorKey: "status",
      cell: ({ row }) => {
        const status = row.original.status;
        const badgeClass = statusBadge[status] || "badge-primary";
        return h(
          "span",
          {
            class: `badge badge-soft ${badgeClass} text-sm px-3.5 py-3.5`,
          },
          status,
        );
      },
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => handleViewDetails(row.original.id),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.handleViewDetails - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useInternColumns({ handleViewDetails }) {
  return [
    {
      header: "NAME",
      size: 200,
      accessorFn: (row) => row.name,
      cell: ({ cell }) => {
        const userPicture = cell.row.original.picture;
        return h(
          "span",
          {
            class: "flex items-center justify-center gap-2",
          },
          [
            h("img", {
              src: userPicture || "/profile-images/default.png",
              class: "avatar w-10 rounded-full",
            }),
            h(
              "span",
              {
                class: "truncate",
              },
              cell.getValue(),
            ),
          ],
        );
      },
    },
    {
      accessorKey: "deptName",
      header: "DEPARTMENT",
    },
    {
      accessorKey: "school",
      header: "SCHOOL",
    },
    {
      header: "STATUS",
      accessorKey: "status",
      cell: ({ row }) => {
        const status = row.original.status;
        const badgeClass = statusBadge[status] || "badge-primary";
        return h(
          "span",
          {
            class: `badge badge-soft ${badgeClass} text-sm px-3.5 py-3.5`,
          },
          status,
        );
      },
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => handleViewDetails(row.original),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.handleViewDetails - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useEmployeeColumns({ handleViewDetails }) {
  return [
    {
      header: "NAME",
      size: 200,
      accessorFn: (row) => row.name,
      cell: ({ cell }) => {
        const userPicture = cell.row.original.picture;
        return h(
          "span",
          {
            class: "flex items-center justify-center gap-2",
          },
          [
            h("img", {
              src: userPicture || "/profile-images/default.png",
              class: "avatar w-10 rounded-full",
            }),
            h(
              "span",
              {
                class: "truncate",
              },
              cell.getValue(),
            ),
          ],
        );
      },
    },
    {
      accessorKey: "deptName",
      header: "DEPARTMENT",
    },
    {
      accessorKey: "hierarchy",
      header: "HIERARCHY",
    },
    {
      header: "STATUS",
      accessorKey: "status",
      cell: ({ row }) => {
        const status = row.original.status;
        const badgeClass = statusBadge[status] || "badge-primary";
        return h(
          "span",
          {
            class: `badge badge-soft ${badgeClass} text-sm px-3.5 py-3.5`,
          },
          status,
        );
      },
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => handleViewDetails(row.original),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * For Dashboard Table Columns.
 * This one is simple and has no dependencies, so we can export it directly.
 */
export const attendanceColumns = [
  {
    header: "DATE",
    accessorFn: (row) => shortDate(row.date),
  },
  {
    header: "FIRST IN",
    accessorFn: (row) =>
      row.firstIn !== "N/A" ? formatTime(row.firstIn) : "N/A",
  },
  {
    header: "1ST BREAK",
    accessorFn: (row) =>
      row.secondIn !== "N/A" ? formatTime(row.secondIn) : "N/A",
  },
  {
    header: "LUNCH",
    accessorFn: (row) =>
      row.thirdIn !== "N/A" ? formatTime(row.thirdIn) : "N/A",
  },
  {
    header: "2ND BREAK",
    accessorFn: (row) =>
      row.fourthIn !== "N/A" ? formatTime(row.fourthIn) : "N/A",
  },
  {
    header: "LAST OUT",
    accessorFn: (row) =>
      row.lastOut !== "N/A" ? formatTime(row.lastOut) : "N/A",
  },
];

/**
 * For Dashboard Table Columns.
 * This one is simple and has no dependencies, so we can export it directly.
 */
export const onlineUsersColumns = [
  {
    accessorKey: "name",
    header: "NAME",
  },
  {
    accessorKey: "department",
    header: "DEPARTMENT",
  },
  {
    accessorKey: "position",
    header: "POSITION",
  },
  {
    accessorKey: "status",
    header: "STATUS",
    // Custom cell render to apply green text style
    cell: ({ getValue }) =>
      h("span", { class: "font-bold text-green-600" }, getValue()),
  },
];

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.openLogModal - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useTodayListColumns({ openLogModal }) {
  return [
    {
      accessorKey: "name",
      header: "NAME",
    },
    {
      accessorKey: "department",
      header: "DEPARTMENT",
    },
    {
      accessorKey: "position",
      header: "POSITION",
    },
    {
      header: "DATE",
      accessorFn: (row) => formatDate(row.date),
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => openLogModal(row.original.id, row.original.date),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.openDeptLogModal - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useDeptAttendanceColumns({ openDeptLogModal }) {
  return [
    {
      accessorKey: "department",
      header: "DEPARTMENT",
    },
    {
      header: "DATE",
      accessorFn: (row) => longDate(row.date),
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => openDeptLogModal(row.original.id, row.original.date),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.handleViewDetails - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useMaterialReqColumns({ handleViewDetails }) {
  return [
    {
      id: "requester",
      header: "REQUESTED BY",
      accessorFn: (row) => row.requester.name,
      cell: ({ cell }) => {
        const requesterPicture = cell.row.original.requester.picture;
        return h(
          "span",
          {
            class: "flex items-center justify-center gap-2",
          },
          [
            h("img", {
              src: requesterPicture || "/profile-images/default.png",
              class: "avatar w-10 rounded-full",
            }),
            h(
              "span",
              {
                class: "truncate",
              },
              cell.getValue(),
            ),
          ],
        );
      },
    },
    {
      header: "MATERIAL",
      accessorKey: "material",
    },
    {
      header: "SUBMITTED",
      accessorFn: (row) => longDateTime(row.created_at),
      id: "submitted-date",
      cell: ({ cell }) => {
        return h("span", {}, cell.getValue());
      },
    },
    {
      header: "STATUS",
      accessorKey: "status",
      cell: ({ row }) => {
        const status = row.original.status;
        const badgeClass = statusBadge[status] || "badge-primary";
        return h(
          "span",
          {
            class: `badge badge-soft ${badgeClass} text-sm px-3.5 py-3.5`,
          },
          status,
        );
      },
    },
    {
      id: "details",
      header: "DETAILS",
      cell: ({ row }) =>
        h(
          "button",
          {
            onClick: () => handleViewDetails(row.original.id),
            class:
              "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
          },
          "View Details",
        ),
      enableSorting: false,
    },
  ];
}

/**
 * @param {Object} authUser - The authenticated user.
 * @param {Object} handlers - An object containing handler functions from the parent.
 * @param {Function} handlers.openPayslip - The function to call when 'View Details' is clicked.
 * @param {Function} handlers.openPayrollList - The function to call when 'View Details' is clicked.
 * @returns {Array} The static column definition array.
 */
export function useSalaryColumns(authUser, { openPayrollList, openPayslip }) {
  return computed(() => {
    const columns = [
      {
        accessorFn: (row) => capitalizeFirst(row.month),
        header: "MONTH",
      },
      {
        accessorFn: (row) =>
          `${shortMonthDay(row.start_date)} - ${shortMonthDay(row.end_date)}`,
        header: "RANGE",
      },
      {
        accessorFn: (row) =>
          `${row.cycle} half of ${capitalizeFirst(row.month)}`,
        header: "PERIOD",
      },
      {
        header: "YEAR",
        accessorKey: "year",
      },
    ];

    if (authUser.value.userType === "super_admin") {
      columns.push({
        header: "DETAILS",
        cell: ({ row }) =>
          h(
            "button",
            {
              onClick: () => openPayrollList(row.original.id),
              class:
                "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
            },
            "View Details",
          ),
        enableSorting: false,
      });
    } else {
      columns.push({
        header: "DETAILS",
        cell: ({ row }) =>
          h(
            "button",
            {
              onClick: () => openPayslip(row.original.id),
              class:
                "btn btn-sm @sm:btn-md rounded-full bg-green-primary-1 text-white hover:bg-green-primary-3",
            },
            "View Details",
          ),
        enableSorting: false,
      });
    }
    return columns;
  });
}
