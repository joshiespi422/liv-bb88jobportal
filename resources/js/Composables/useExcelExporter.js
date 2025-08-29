import ExcelJS from "exceljs";
import { saveAs } from "file-saver";
import { useToast } from "./useToast";

const { error } = useToast();

/**
 * A reusable composable for exporting data to a styled Excel file.
 * @returns {object} An object containing the exportToExcel function.
 */
export function useExcelExporter() {
  /**
   * Generates and downloads a styled Excel file.
   * @param {Array<object>} data - The array of data objects to export.
   * @param {Array<object>} columns - An array of column definitions. Each object should have 'header' and 'key'.
   * @param {string} fileName - The desired name for the output file (without extension).
   * @param {string} reportTitle - The main title to be displayed in the merged top row.
   */
  const exportToExcel = async (data, columns, fileName, reportTitle) => {
    if (!data || data.length === 0) {
      error("No data to export");
      return;
    }

    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet("Reports");

    // --- STYLING ---
    const titleStyle = {
      font: { bold: true, size: 16, color: { argb: "FFFFFF" } },
      fill: { type: "pattern", pattern: "solid", fgColor: { argb: "a9cf46" } },
      alignment: { horizontal: "center", vertical: "middle" },
    };
    const headerStyle = {
      font: { bold: true, color: { argb: "FFFFFF" } },
      fill: { type: "pattern", pattern: "solid", fgColor: { argb: "4f8f75" } },
      alignment: { horizontal: "center", vertical: "middle" },
    };
    const dataStyle = {
      alignment: { wrapText: true, vertical: "top" },
    };

    // --- TITLE ---
    // Dynamically determine the merge range based on the number of columns
    const lastColumn = String.fromCharCode(65 + columns.length - 1);
    worksheet.mergeCells(`A1:${lastColumn}1`);
    const titleCell = worksheet.getCell("A1");
    titleCell.value = reportTitle.toUpperCase();
    titleCell.style = titleStyle;
    worksheet.getRow(1).height = 40;

    // --- HEADERS ---
    const headerRow = worksheet.addRow(columns.map((col) => col.header));
    headerRow.eachCell((cell) => (cell.style = headerStyle));
    worksheet.getRow(2).height = 30;

    // --- FREEZE PANES ---
    worksheet.views = [{ state: "frozen", ySplit: 2 }];

    // --- DATA ROWS ---
    data.forEach((item) => {
      const rowData = columns.map((col) => item[col.key] || "N/A");
      const dataRow = worksheet.addRow(rowData);
      dataRow.eachCell((cell) => (cell.style = dataStyle));
      worksheet.getRow(dataRow.number).height = 30;
    });

    // --- COLUMN WIDTHS ---
    worksheet.columns = columns.map((col) => ({
      key: col.key,
      width: col.width || 25, // Default width if not specified
    }));

    // --- SAVE FILE ---
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
      type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    saveAs(blob, `${fileName}_${new Date().toISOString().slice(0, 10)}.xlsx`);
  };

  return { exportToExcel };
}
