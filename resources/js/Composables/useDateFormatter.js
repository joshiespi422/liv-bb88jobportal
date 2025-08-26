import { format, parseISO, parse } from "date-fns";

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

export const formatTime = (timeString, pattern = "h:mm a") => {
  if (!timeString) return "N/A";

  try {
    const date = parse(timeString, "HH:mm:ss", new Date());
    return format(date, pattern);
  } catch (error) {
    console.error("Time formatting error:", error);
    return "Invalid Time";
  }
};

// Pre-configured formatters
export const shortDate = (dateString) => formatDate(dateString, "MMM d, yyyy");
export const longDate = (dateString) =>
  formatDate(dateString, "EEE, MMMM d, yyyy");

export const longDateTime = (dateString) =>
  formatDate(dateString, "EEE, MMMM d, yyyy - h:mm a");

export const shortDateTime = (dateString) =>
  formatDate(dateString, "MMM d, yyyy, h:mm a");

// for parsing date strings
export function parseDateString(dateString) {
  if (!dateString) return null;

  // Try ISO parse first
  let parsed = parseISO(dateString);
  if (!isNaN(parsed)) return parsed;

  // Try MySQL DATETIME (without T/Z)
  parsed = parse(dateString, "yyyy-MM-dd HH:mm:ss", new Date());
  if (!isNaN(parsed)) return parsed;

  // Try MySQL DATE
  parsed = parse(dateString, "yyyy-MM-dd", new Date());
  if (!isNaN(parsed)) return parsed;

  return null;
}
