<?php
namespace BucheggerOnline\Publicrelations\Task;

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;

class SplitContactsTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        if (!isset($taskInfo['dryRun'])) {
            $taskInfo['dryRun'] = true;
        }
        $fieldName = 'tx_scheduler[dryRun]';
        $fieldValue = (bool) ($taskInfo['dryRun'] ?? true);
        $checkedAttribute = $fieldValue ? ' checked="checked"' : '';
        $fieldCode = '<div class="form-check form-switch">';
        $fieldCode .= '<input type="hidden" name="' . $fieldName . '" value="0">';
        $fieldCode .= '<input class="form-check-input" type="checkbox" name="' . $fieldName . '" value="1" id="task_dryRun_split"' . $checkedAttribute . '>';
        $fieldCode .= '</div>';
        $additionalFields['dryRun'] = [
            'code' => $fieldCode,
            'label' => 'Nur Probelauf (Dry Run)',
        ];
        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        return true;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->dryRun = (bool) ($submittedData['dryRun'] ?? false);
    }
}