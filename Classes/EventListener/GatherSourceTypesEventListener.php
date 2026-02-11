<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\EventListener;

use Allegria\AcContacts\Event\GatherSourceTypesEvent;

/**
 * Registriert die Quellen für den UID-Import aus der PublicRelations Extension.
 */
class GatherSourceTypesEventListener
{
    public function __invoke(GatherSourceTypesEvent $event): void
    {
        // // Quelle 1: Ganze Events (Alle Gäste eines Events)
        // $event->addSource(
        //     'event',
        //     'Event UIDs',
        //     'Alle Gäste (Akkreditierungen) dieser Events laden',
        //     'bi-calendar-event'
        // );

        // Quelle 2: Einzelne Akkreditierungen (Tickets)
        $event->addSource(
            'accreditation',
            'Akkreditierung UIDs',
            'IDs von einzelnen Gästen/Tickets auflösen',
            'bi-ticket-perforated'
        );
    }
}