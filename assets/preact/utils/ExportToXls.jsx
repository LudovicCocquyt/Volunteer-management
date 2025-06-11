import ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';

export const exportListXls = (data) => {
    if (!data || Object.keys(data).length === 0) {
        console.error("Aucune donnée trouvée pour l'exportation.");
        return;
    }

    const workbook  = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Feuille1');

    // Ajouter les en-têtes
    worksheet.columns = [
        { header: 'Activités', key: 'Activités' },
        { header: 'Date', key: 'Date' },
        { header: 'Heure', key: 'Heure' },
        { header: 'Besoin', key: 'Besoin' },
        { header: 'Bénévoles', key: 'Bénévoles' },
    ];

    // Centrer les en-têtes
    worksheet.getRow(1).eachCell((cell) => {
        cell.alignment = { horizontal: 'center', vertical: 'middle' }; // Centrage horizontal et vertical
        cell.font      = { bold: true }; // Mettre les en-têtes en gras (facultatif)
    });

    // Couleurs de fond alternées
    const colors   = ['FFFFCC99', 'FFCCE5FF']; // Jaune clair, Bleu clair
    let colorIndex = 0;

    // Ajouter les lignes avec alternance des couleurs
    Object.entries(data).forEach(([category, items]) => {
        items.sort((a, b) => new Date(a.startDate) - new Date(b.startDate))
             .forEach((item) => {
                 const start = new Date(item.startDate).toLocaleString();
                 const end = new Date(item.endDate).toLocaleString();

                 const row = worksheet.addRow({
                     Activités: category,
                     Date: start.substring(0, 10),
                     Heure: start.substring(11, 16) + ' - ' + end.substring(11, 16),
                     Besoin: item.title,
                     Bénévoles: item.people.trim().replace(/\n/g, ', '),
                 });

                 // Appliquer la couleur de fond à chaque cellule de la ligne
                 row.eachCell((cell) => {
                     cell.fill = {
                         type: 'pattern',
                         pattern: 'solid',
                         fgColor: { argb: colors[colorIndex] },
                     };
                     cell.border = {
                         top: { style: 'thin' },
                         left: { style: 'thin' },
                         bottom: { style: 'thin' },
                         right: { style: 'thin' },
                     };
                 });
             });

        // Alterner la couleur après chaque catégorie
        colorIndex = (colorIndex + 1) % colors.length;
    });

    // Ajuster dynamiquement la largeur de chaque colonne en fonction de son contenu
    worksheet.columns.forEach((column) => {
        let maxLength = 0;
        column.eachCell({ includeEmpty: true }, (cell) => {
            if (cell.value) {
                const value = cell.value.toString();
                maxLength = Math.max(maxLength, value.length); // Trouver la largeur maximale pour chaque cellule
            }
        });
        column.width = maxLength + 2; // Ajouter un espace supplémentaire
    });

    // Exporter et télécharger le fichier
    workbook.xlsx.writeBuffer().then((buffer) => {
        saveAs(new Blob([buffer]), 'bénévoles.xlsx');
    });
};
