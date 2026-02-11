<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Event;

/**
 * Event zum Sammeln von Kontakt-Auswahl-Wizards für Events.
 */
final class GatherInvitationWizardsEvent
{
    private array $wizards = [];

    public function __construct(
        private readonly int $eventUid,
        private readonly string $clientUids = '0' // '0' = Intern, '1,2' = Client+Partner
    ) {
    }

    public function getEventUid(): int
    {
        return $this->eventUid;
    }

    public function getClientUids(): string
    {
        return $this->clientUids;
    }

    public function addWizard(
        string $identifier,
        string $title,
        string $description,
        string $routeOrAction,
        array $arguments = [],
        string $iconIdentifier = 'actions-wizard'
    ): void {
        $this->wizards[$identifier] = [
            'identifier' => $identifier,
            'title' => $title,
            'description' => $description,
            'route' => $routeOrAction,
            'arguments' => $arguments,
            'icon' => $iconIdentifier
        ];
    }

    public function getWizards(): array
    {
        return $this->wizards;
    }
}