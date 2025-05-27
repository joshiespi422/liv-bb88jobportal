export const menuItems = [
  {
    name: "DASHBOARD",
    icon: "pi pi-home",
    routeName: "dashboard",
  },
  {
    name: "EMPLOYEES",
    icon: "pi pi-users",
    routeName: "team.employees",
  },
  {
    name: "ATTENDANCE",
    icon: "pi pi-address-book",
    hasSubmenu: true,
    submenu: [
      {
        name: "TODAY",
        icon: "pi pi-users",
        routeName: "attendance.today",
      },
      {
        name: "TIME LOGS",
        icon: "pi pi-users",
        routeName: "attendance.timelogs",
      },
    ],
  },
  {
    name: "OTHERS",
    icon: "pi pi-cog",
    hasSubmenu: true,
    submenu: [
      {
        name: "PROFILE",
        icon: "pi pi-user",
        routeName: "profile",
      },
      {
        name: "LEAVE FORM",
        icon: "pi pi-folder-open",
        hasSubmenu: true,
        submenu: [
          {
            name: "REGULAR LEAVE",
            icon: "pi pi-folder",
            routeName: "leave.regular",
          },
          {
            name: "SPECIAL LEAVE",
            icon: "pi pi-folder",
            routeName: "leave.special",
          },
        ],
      },
    ],
  },
];
