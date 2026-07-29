// helper for compliance upload, tanstack columns definition
export function formatPeriod(returnType, period) {
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
    case "monthly":
      return months[period - 1] ?? `Month ${period}`;
    case "quarterly":
      return `Q${period}`;
    case "annual":
      return "Annual";
    default:
      return `Period ${period}`;
  }
}
