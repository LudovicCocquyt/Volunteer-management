import html2canvas from 'html2canvas';
import jsPDF from 'jspdf/dist/jspdf.umd';

export const exportToPdf = (element) => {
  html2canvas(element).then((canvas) => {
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF('l', 'pt', 'a4'); // 'l' pour paysage (landscape), 'pt' pour les points, 'a4' pour le format de la page
    const imgProps = pdf.getImageProperties(imgData);
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
    pdf.save('telechargement.pdf');
  });
};

