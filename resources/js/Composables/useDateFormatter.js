import { format, parseISO } from "date-fns";

export const formatDate = (dateString, pattern = "MMMM d, yyyy") => {
  if (!dateString) return "N/A";

  try {
    // Handle both ISO strings and timestamps
    const date = isNaN(Date.parse(dateString))
      ? parseISO(dateString)
      : new Date(dateString);

    return format(date, pattern);
  } catch (error) {
    console.error("Date formatting error:", error);
    return "Invalid Date";
  }
};

// Pre-configured formatters
// export const shortDate = (dateString) => formatDate(dateString, 'MM/dd/yyyy');
// export const longDate = (dateString) => formatDate(dateString, 'EEEE, MMMM d, yyyy');
