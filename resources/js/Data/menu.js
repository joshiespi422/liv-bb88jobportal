export const menuItems = [
  {
    name: "DASHBOARD",
    icon: "pi pi-home",
    userType: ["super_admin", "employee", "intern"],
    routeName: "dashboard",
  },
  {
    name: "ATTENDANCE",
    icon: "pi pi-address-book",
    userType: ["super_admin"],
    hasSubmenu: true,
    submenu: [
      {
        name: "TODAY", // Inherits parent permission
        icon: "pi pi-book",
        routeName: "attendance.today",
      },
      {
        name: "TIME LOGS", // Inherits parent permission
        icon: "pi pi-book",
        routeName: "attendance.timelogs",
      },
    ],
  },
  {
    name: "USERS",
    icon: "pi pi-users",
    userType: ["employee", "super_admin"],
    isLeader: true,
    hasSubmenu: true,
    submenu: [
      {
        name: "EMPLOYEES",
        icon: "pi pi-user",
        routeName: "team.employees",
      },
      {
        name: "INTERNS",
        icon: "pi pi-user",
        routeName: "team.interns",
      },
    ],
  },
  {
    name: "EMPLOYEES",
    icon: "pi pi-users",
    userType: ["employee"],
    isMember: true,
    routeName: "team.employees",
  },
  {
    name: "INTERNS",
    icon: "pi pi-users",
    userType: ["intern"],
    routeName: "team.interns",
  },
  {
    name: "TASKS",
    icon: "pi pi-clipboard",
    userType: ["employee", "intern"],
    isMember: true,
    routeName: "task",
  },
  {
    name: "TASKS",
    icon: "pi pi-clipboard",
    userType: ["employee", "super_admin"],
    isLeader: true,
    hasSubmenu: true,
    submenu: [
      {
        name: "EMPLOYEE",
        icon: "pi pi-check-square",
        routeName: "task",
        routeQuery: { type: "employee" },
      },
      {
        name: "INTERN",
        icon: "pi pi-check-square",
        routeName: "task",
        routeQuery: { type: "intern" },
      },
    ],
  },
  {
    name: "ACCOMPLISHMENTS",
    icon: "pi pi-chart-bar",
    userType: ["employee", "intern"],
    isMember: true,
    routeName: "accomplishment",
  },
  {
    name: "ACCOMPLISHMENTS",
    icon: "pi pi-chart-bar",
    userType: ["employee", "super_admin"],
    isLeader: true,
    hasSubmenu: true,
    submenu: [
      {
        name: "EMPLOYEE",
        icon: "pi pi-chart-line",
        routeName: "accomplishment",
        routeQuery: { type: "employee" },
      },
      {
        name: "INTERN",
        icon: "pi pi-chart-line",
        routeName: "accomplishment",
        routeQuery: { type: "intern" },
      },
    ],
  },
  {
    name: "OTHERS",
    icon: "pi pi-cog",
    userType: ["super_admin", "employee", "intern"],
    hasSubmenu: true,
    submenu: [
      {
        name: "PROFILE", // Inherits parent permission
        icon: "pi pi-credit-card",
        routeName: "profile",
      },
      {
        name: "LEAVE FORM",
        icon: "pi pi-folder-open",
        userType: ["super_admin", "employee"], // Different from parent
        routeName: "leave",
      },
    ],
  },
];
