<?php

namespace App\Service;

use App\Entity\Events;
use App\Entity\Plans;
use App\Entity\Subscriptions;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Seul point d'export Excel de l'application (remplace exceljs/xlsx côté front).
 */
class ExcelExporter
{
    private const ALTERNATE_COLORS = ['FFFFCC99', 'FFCCE5FF']; // Jaune clair, Bleu clair

    /**
     * Liste des inscrits d'un événement.
     *
     * @param Subscriptions[] $subscriptions
     */
    public function exportSubscriptions(Events $event, array $subscriptions): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inscriptions');

        $this->writeRow($sheet, 1, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Enfant', 'Classe', 'Commentaire']);
        $this->styleHeader($sheet, 8);

        $row = 2;
        foreach ($subscriptions as $subscription) {
            $this->writeRow($sheet, $row++, [
                $subscription->getId(),
                $subscription->getFirstname(),
                $subscription->getLastname(),
                $subscription->getEmail(),
                $subscription->getPhone(),
                $subscription->getChildName(),
                $subscription->getClass(),
                $subscription->getComment(),
            ]);
        }

        $this->autoSize($sheet, 8);

        return $this->toResponse($spreadsheet, 'inscriptions-' . $event->getName());
    }

    /**
     * Planning d'un événement : une ligne par créneau, regroupé par activité
     * (reprend la mise en forme de l'ancien export exceljs).
     *
     * @param Plans[] $plans
     */
    public function exportPlanning(Events $event, array $plans): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planning');

        $this->writeRow($sheet, 1, ['Activités', 'Date', 'Heure', 'Besoin', 'Bénévoles']);
        $this->styleHeader($sheet, 5);

        // Regroupement par activité, créneaux triés par date de début
        $byActivity = [];
        foreach ($plans as $plan) {
            $byActivity[(string) $plan->getActivityName()][] = $plan;
        }

        $row        = 2;
        $colorIndex = 0;
        foreach ($byActivity as $activityName => $activityPlans) {
            usort($activityPlans, fn (Plans $a, Plans $b) => $a->getStartDate() <=> $b->getStartDate());

            foreach ($activityPlans as $plan) {
                $people = [];
                foreach ($plan->getSubscriptions() as $subscription) {
                    $people[] = ucfirst((string) $subscription->getFirstname()) . ' '
                        . mb_strtoupper(mb_substr((string) $subscription->getLastname(), 0, 1)) . '.';
                }

                $this->writeRow($sheet, $row, [
                    $activityName,
                    $plan->getStartDate()->format('d/m/Y'),
                    $plan->getStartDate()->format('H:i') . ' - ' . $plan->getEndDate()->format('H:i'),
                    count($plan->getSubscriptions()) . '/' . $plan->getNbPers(),
                    implode(', ', $people),
                ]);

                $style = $sheet->getStyle("A{$row}:E{$row}");
                $style->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(self::ALTERNATE_COLORS[$colorIndex]);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $row++;
            }

            $colorIndex = ($colorIndex + 1) % count(self::ALTERNATE_COLORS);
        }

        $this->autoSize($sheet, 5);

        return $this->toResponse($spreadsheet, 'planning-' . $event->getName());
    }

    /**
     * Écrit toutes les valeurs en texte explicite : un nom saisi dans le formulaire
     * public qui commence par "=" ne sera jamais interprété comme une formule
     * (injection de formule / CSV injection).
     */
    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        $col = 1;
        foreach ($values as $value) {
            $coordinate = Coordinate::stringFromColumnIndex($col++) . $row;

            if (is_int($value) || is_float($value)) {
                $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            }
        }
    }

    private function styleHeader(Worksheet $sheet, int $nbColumns): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($nbColumns);
        $style      = $sheet->getStyle("A1:{$lastColumn}1");
        $style->getFont()->setBold(true);
        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A2');
    }

    private function autoSize(Worksheet $sheet, int $nbColumns): void
    {
        for ($i = 1; $i <= $nbColumns; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    private function toResponse(Spreadsheet $spreadsheet, string $baseName): StreamedResponse
    {
        $filename = (new AsciiSlugger('fr'))->slug($baseName)->lower() . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        ));
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
