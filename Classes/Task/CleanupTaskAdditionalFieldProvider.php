<?php
namespace BucheggerOnline\Publicrelations\Task;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;

class CleanupTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $additionalFields = [];

        // --- Feld 1: Die "Dry Run"-Checkbox (bleibt wie gehabt) ---
        if (!isset($taskInfo['dryRun'])) {
            $taskInfo['dryRun'] = $task->dryRun ?? true;
        }
        $fieldName = 'tx_scheduler[dryRun]';
        $fieldValue = (bool) ($taskInfo['dryRun'] ?? true);
        $checkedAttribute = $fieldValue ? ' checked="checked"' : '';
        $fieldCode = '<div class="form-check form-switch">';
        $fieldCode .= '<input type="hidden" name="' . $fieldName . '" value="0">';
        $fieldCode .= '<input class="form-check-input" type="checkbox" name="' . $fieldName . '" value="1" id="task_dryRun"' . $checkedAttribute . '>';
        $fieldCode .= '</div>';
        $additionalFields['dryRun'] = [
            'code' => $fieldCode,
            'label' => 'Nur Probelauf (Dry Run)',
        ];

        // --- Feld 2 bis 8: Die konfigurierbaren UIDs ---
        $uidFields = [
            'facieTypeUid' => 'Ziel-UID für "Facie"',
            'fotografenTypeUid' => 'Ziel-UID für "Fotografen"',
            'kundenTypeUid' => 'Ziel-UID für "Kunden"',
            'mitarbeitendeTypeUid' => 'Ziel-UID für "Mitarbeitende"',
            'partnerTypeUid' => 'Ziel-UID für "Partner"',
            'redakteureTypeUid' => 'Ziel-UID für "Redakteure"',
            'contentCreatorTypeUid' => 'Ziel-UID für "Content Creators"',
        ];

        foreach ($uidFields as $propertyName => $label) {
            // Standardwert aus dem Task-Objekt holen, falls im Formular noch nichts gesetzt ist
            if (!isset($taskInfo[$propertyName]) && isset($task->$propertyName)) {
                $taskInfo[$propertyName] = $task->$propertyName;
            }
            $fieldValue = (int) ($taskInfo[$propertyName] ?? 0);
            $fieldName = 'tx_scheduler[' . $propertyName . ']';

            // HTML für das Eingabefeld bauen
            $fieldCode = '<div class="form-control-wrap">';
            $fieldCode .= '<input type="number" class="form-control" name="' . $fieldName . '" value="' . $fieldValue . '">';
            $fieldCode .= '</div>';

            $additionalFields[$propertyName] = [
                'code' => $fieldCode,
                'label' => $label,
            ];
        }

        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        // Wir validieren, ob alle UID-Felder gültige Zahlen sind.
        $uidProperties = ['facieTypeUid', 'fotografenTypeUid', 'kundenTypeUid', 'mitarbeitendeTypeUid', 'partnerTypeUid', 'redakteureTypeUid', 'contentCreatorTypeUid'];
        foreach ($uidProperties as $property) {
            if (!is_numeric($submittedData[$property]) || (int) $submittedData[$property] <= 0) {
                $schedulerModule->addMessage(sprintf('Bitte geben Sie eine gültige, positive UID für "%s" ein.', $property), \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR);
                return false;
            }
        }
        return true;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        // Speichert die Werte aller Felder in den öffentlichen Eigenschaften der Task-Klasse
        $task->dryRun = (bool) ($submittedData['dryRun'] ?? false);

        $task->facieTypeUid = (int) $submittedData['facieTypeUid'];
        $task->fotografenTypeUid = (int) $submittedData['fotografenTypeUid'];
        $task->kundenTypeUid = (int) $submittedData['kundenTypeUid'];
        $task->mitarbeitendeTypeUid = (int) $submittedData['mitarbeitendeTypeUid'];
        $task->partnerTypeUid = (int) $submittedData['partnerTypeUid'];
        $task->redakteureTypeUid = (int) $submittedData['redakteureTypeUid'];
        $task->contentCreatorTypeUid = (int) $submittedData['contentCreatorTypeUid'];
    }
}