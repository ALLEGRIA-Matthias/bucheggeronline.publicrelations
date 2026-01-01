<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Task;

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Stellt die Zusatzfelder für den ImportRssTask bereit.
 */
class ImportRssTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Erstellt die HTML-Felder für das Scheduler-Formular.
     *
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        // Nur für unseren Task
        if (!$task instanceof ImportRssTask) {
            return [];
        }

        $additionalFields = [];

        // --- Feld 1: report_receiver (Textfeld) ---

        // Hole den Wert: Zuerst aus dem Formular ($taskInfo), dann aus dem Task-Objekt
        if (!isset($taskInfo['report_receiver'])) {
            $taskInfo['report_receiver'] = $task->report_receiver ?? 'office@allegria.at';
        }
        $fieldValue = htmlspecialchars((string) ($taskInfo['report_receiver'] ?? ''));
        $fieldName = 'tx_scheduler[report_receiver]';

        // HTML für das Eingabefeld bauen
        $fieldCode = '<div class="form-control-wrap">';
        $fieldCode .= '<input type="text" class="form-control" name="' . $fieldName . '" value="' . $fieldValue . '" placeholder="office@allegria.at">';
        $fieldCode .= '</div>';

        $additionalFields['report_receiver'] = [
            'code' => $fieldCode,
            'label' => 'E-Mail Empfänger für Report (falls "Sofort Senden" aus ist)',
        ];

        // --- Feld 2: send_immediately (Checkbox) ---

        // Hole den Wert
        if (!isset($taskInfo['send_immediately'])) {
            $taskInfo['send_immediately'] = $task->send_immediately ?? false;
        }
        $fieldValue = (bool) ($taskInfo['send_immediately'] ?? false);
        $fieldName = 'tx_scheduler[send_immediately]';
        $checkedAttribute = $fieldValue ? ' checked="checked"' : '';

        // HTML für die Checkbox bauen (nach "dryRun"-Muster)
        $fieldCode = '<div class="form-check form-switch">';
        $fieldCode .= '<input type="hidden" name="' . $fieldName . '" value="0">';
        $fieldCode .= '<input class="form-check-input" type="checkbox" name="' . $fieldName . '" value="1" id="task_send_immediately"' . $checkedAttribute . '>';
        $fieldCode .= '</div>';

        $additionalFields['send_immediately'] = [
            'code' => $fieldCode,
            'label' => 'Clippings sofort versenden (nur für Routen mit send_immediate=true)',
        ];

        return $additionalFields;
    }

    /**
     * Validiert die Daten (kann einfach 'true' zurückgeben)
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        return true;
    }

    /**
     * Speichert die Daten aus den Feldern in die Task-Properties
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->report_receiver = $submittedData['report_receiver'] ?? '';
        $task->send_immediately = (bool) ($submittedData['send_immediately'] ?? false);
    }
}