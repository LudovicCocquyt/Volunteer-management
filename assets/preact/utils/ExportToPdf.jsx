
import html2canvas from 'html2canvas';
import { jsPDF } from "jspdf";
import { autoTable } from 'jspdf-autotable'

export const exportCalendar = (element) => {
  html2canvas(element).then((canvas) => {
    const imgData   = canvas.toDataURL('image/png');
    const pdf       = new jsPDF('l', 'pt', 'a4'); // 'l' pour paysage (landscape), 'pt' pour les points, 'a4' pour le format de la page
    const imgProps  = pdf.getImageProperties(imgData);
    const pdfWidth  = pdf.internal.pageSize.getWidth();
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

    let currentPageHeight = 0;
    let remainingHeight   = pdfHeight;

    while (remainingHeight > 0) {
      pdf.addImage(imgData, 'PNG', 0, currentPageHeight, pdfWidth, pdfHeight);
      currentPageHeight += pdfHeight;
      remainingHeight   -= pdfHeight;

      if (remainingHeight > 0) {
        pdf.addPage();
      }
    }

    exportToPdf(pdf, "bénévoles-calendier");
  });
};

export const exportList = (data) => {
  const doc = new jsPDF();
  Object.entries(data).forEach(([category, items]) => {
    //Add a title for each category
    doc.text(category, 10, doc.lastAutoTable ? doc.lastAutoTable.finalY + 10 : 20);

    const rows = items
      .sort((a, b) => new Date(a.startDate) - new Date(b.startDate))
      .map((item) => {
        const start = new Date(item.startDate).toLocaleString();
        return [
          start.substring(0, 10), // Date
          start.substring(11, 16) + " - " + new Date(item.endDate).toLocaleString().substring(11, 16), // Hour
          item.title, // Need
          item.people.trim().replace(/\n/g, ", "), // Volunteers names
        ];
      });

    const yPositionAfterTitle = doc.lastAutoTable ? doc.lastAutoTable.finalY + 15 : 25; // Add a space under the title

    autoTable(doc, {
      head: [["Date", "Heure", "Besoin", "Bénévoles"]],
      body: rows,
      startY: yPositionAfterTitle,
    });
  });

  exportToPdf(doc, "bénévoles-liste");
};

export const exportToPdf = (doc, name) => {
  doc.save(name + ".pdf");
};
