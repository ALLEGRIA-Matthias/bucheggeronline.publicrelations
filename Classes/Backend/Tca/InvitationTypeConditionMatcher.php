<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Tca;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

/**
 * Stellt USER-Funktionen für TCA displayCond bereit
 */
class InvitationTypeConditionMatcher
{
    /**
     * Prüft, ob das HTML-Feld in einer InvitationVariant
     * angezeigt werden soll.
     *
     * @param array $parameter Parameter-Array von EvaluateDisplayConditions
     * @return bool True, wenn das Feld angezeigt werden soll
     */
    public function showHtmlField(array $parameter): bool
    {
        // $parameter['record'] ist der *aktuelle* Datensatz
        // (also die 'tx_publicrelations_domain_model_invitationvariant')
        $currentRecord = $parameter['record'];

        // 1. Finde die UID des Elternelements (invitation)
        // Das 'invitation'-Feld ist in IRRE das Parent-Objekt
        $invitationUid = 0;

        if (isset($currentRecord['invitation']) && (int) $currentRecord['invitation'] > 0) {
            // Fall 1: Datensatz wird bearbeitet, 'invitation' ist gesetzt
            $invitationUid = (int) $currentRecord['invitation'];
        } elseif (
            isset($parameter['flexContext']['parentRecordUid']) &&
            (int) $parameter['flexContext']['parentRecordUid'] > 0
        ) {
            // Fall 2: (Fallback) Neuer Datensatz im IRRE-Kontext
            // HINWEIS: 'flexContext' ist hier irreführend benannt,
            // es enthält bei IRRE oft die Parent-Daten.
            $invitationUid = (int) $parameter['flexContext']['parentRecordUid'];
        } elseif (
            isset($currentRecord['uid']) &&
            str_starts_with((string) $currentRecord['uid'], 'NEW')
        ) {
            // Fall 3: Neuer Datensatz, wir müssen die UID aus dem
            // GET-Parameter des Ajax-Aufrufs holen (sehr wackelig)
            // Für den Moment überspringen wir diesen komplexen Fall
            // und verlassen uns auf Fall 1 & 2.
        }

        if ($invitationUid === 0) {
            // Kann Parent nicht finden, im Zweifel anzeigen
            return true;
        }

        // 2. Lade den 'type' des Elternelements
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_publicrelations_domain_model_invitation');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $parentType = $queryBuilder
            ->select('type')
            ->from('tx_publicrelations_domain_model_invitation')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($invitationUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        // 3. Evaluiere
        // Wir zeigen das Feld an, wenn der Typ 'html' ist.
        return ($parentType === 'html');
    }
}