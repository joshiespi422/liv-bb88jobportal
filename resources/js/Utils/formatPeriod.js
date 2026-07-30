// helper for compliance upload, tanstack columns definition
export function formatPeriod(returnType, period, startDate) {
  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ];

  switch (returnType) {
    case "monthly": {
      if (startDate) {
        const startMonthIndex = new Date(startDate).getMonth();
        const monthName = months[startMonthIndex] || "";
        return `${monthName} - M${period}`;
      }
      return `Month ${period}`;
    }
    case "quarterly":
      return `Q${period}`;
    case "annual":
      return "Annual";
    default:
      return `Period ${period}`;
  }
}
