<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\EventListener;

use Allegria\AcMailer\Event\GatherReceiverWizardsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AddPRWizardsToMailing
{
    public function __invoke(GatherReceiverWizardsEvent $event): void
    {
        $mailingUid = $event->getMailingUid();

        // -------------------------------------------------------------
        // 1. Client-Scope aus dem Event holen (Entkopplung)
        // -------------------------------------------------------------
        // Wir erwarten vom Event einen String: "0" (Intern) oder "1,2,3" (Clients)
        // Fallback auf '0', falls das Event die Methode noch nicht hat.
        $clientUidsPayload = method_exists($event, 'getClientUids') ? $event->getClientUids() : '0';

        $allowedClientUids = GeneralUtility::intExplode(',', (string) $clientUidsPayload, true);

        // Configuration für den Rückweg (Transfer)
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $cancelUrl = (string) $uriBuilder->buildUriFromRoute('ac_mailer_show', [
            'action' => 'show',
            'mailing' => $mailingUid
        ]);

        $finishConfiguration = [
            'targetExtension' => 'AcMailer',
            'targetController' => 'Mailing',
            'targetAction' => 'createReceiversFromSelection',
            'redirectRoute' => 'ac_mailer_show',
            'redirectArguments' => ['mailing' => $mailingUid],
            'cancelUrl' => $cancelUrl,
            // Duplikatsprüfung
            'duplicateCheck' => [
                'table' => 'tx_acmailer_domain_model_receiver',
                'field' => 'contact',
                'scope' => ['mailing' => $mailingUid, 'deleted' => 0]
            ]
        ];

        // Wizard registrieren
        $event->addWizard(
            'ac_pr_events',
            'Akkreditierungen / Events',
            'Wähle Empfänger basierend auf Gästelisten',
            'allegria_eventcenter_selection', // Route zum neuen Controller
            [
                'finishConfiguration' => $finishConfiguration,
                'allowedClientUids' => $allowedClientUids,
            ],
            'actions-calendar' // Icon
        );
    }
}