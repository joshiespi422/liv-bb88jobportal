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
        icon: "pi pi-users",
        routeName: "attendance.today",
      },
      {
        name: "TIME LOGS", // Inherits parent permission
        icon: "pi pi-users",
        routeName: "attendance.timelogs",
      },
    ],
  },
  {
    name: "USERS",
    icon: "pi pi-users",
    userType: ["super_admin"],
    hasSubmenu: true,
    submenu: [
      {
        name: "EMPLOYEES",
        icon: "pi pi-users",
        routeName: "team.employees",
      },
      {
        name: "INTERNS",
        icon: "pi pi-users",
        routeName: "team.interns",
      },
    ],
  },
  {
    name: "EMPLOYEES",
    icon: "pi pi-users",
    userType: ["employee"],
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
    routeName: "task",
  },
  {
    name: "TASKS",
    icon: "pi pi-clipboard",
    userType: ["super_admin"],
    hasSubmenu: true,
    submenu: [
      {
        name: "EMPLOYEE TASKS",
        icon: "pi pi-clipboard",
        routeName: "task",
        routeQuery: { type: "employee" },
      },
      {
        name: "INTERN TASKS",
        icon: "pi pi-clipboard",
        routeName: "task",
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
        icon: "pi pi-user",
        routeName: "profile",
      },
      {
        name: "LEAVE FORM",
        icon: "pi pi-folder-open",
        userType: ["super_admin", "employee"], // Different from parent
        hasSubmenu: true,
        submenu: [
          {
            name: "REGULAR LEAVE", // Inherits from immediate parent
            icon: "pi pi-folder",
            routeName: "leave.regular",
          },
          {
            name: "SPECIAL LEAVE", // Inherits from immediate parent
            icon: "pi pi-folder",
            routeName: "leave.special",
          },
        ],
      },
    ],
  },
  // {
  //   name: "INTERNSHIP",
  //   icon: "pi pi-graduation-cap",
  //   userType: ["super_admin", "employee"],
  //   hasSubmenu: true,
  //   submenu: [
  //     {
  //       name: "STUDENTS", // Inherits parent permission
  //       icon: "pi pi-users",
  //       routeName: "team.interns",
  //     },
  //   ],
  // },
];
