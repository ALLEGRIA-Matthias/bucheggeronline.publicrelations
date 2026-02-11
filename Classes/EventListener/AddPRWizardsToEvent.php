<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\EventListener;

use BucheggerOnline\Publicrelations\Event\GatherInvitationWizardsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AddPRWizardsToEvent
{
    public function __invoke(GatherInvitationWizardsEvent $event): void
    {
        $eventUid = $event->getEventUid();

        // -------------------------------------------------------------
        // 1. Client-Scope aus dem Event holen (Entkopplung)
        // -------------------------------------------------------------
        // Wir erwarten vom Event einen String: "0" (Intern) oder "1,2,3" (Clients)
        // Fallback auf '0', falls das Event die Methode noch nicht hat.
        $clientUidsPayload = method_exists($event, 'getClientUids') ? $event->getClientUids() : '0';

        $allowedClientUids = GeneralUtility::intExplode(',', (string) $clientUidsPayload, true);

        // Configuration für den Rückweg (Transfer)
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $cancelUrl = (string) $uriBuilder->buildUriFromRoute('allegria_eventcenter', [
            'action' => 'show',
            'controller' => 'Event',
            'event' => $eventUid
        ]);

        $finishConfiguration = [
            'targetExtension' => 'Publicrelations',
            'targetController' => 'Accreditation',
            'targetAction' => 'configureInvitationSelection', // Neue Action!

            // Route des Ziel-Moduls
            'redirectRoute' => 'allegria_eventcenter',
            'redirectArguments' => ['event' => $eventUid],

            'cancelUrl' => $cancelUrl,

            // Duplikatsprüfung gegen Akkreditierungen dieses Events
            'duplicateCheck' => [
                'table' => 'tx_publicrelations_domain_model_accreditation',
                'field' => 'guest', // Feldname für Kontakt-Relation in Akkreditierung
                'scope' => [
                    'event' => $eventUid,
                    'deleted' => 0
                ]
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