<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Service;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use BucheggerOnline\Publicrelations\Service\QrCodeService;

/**
 * Service providing helper functions and logic related to Accreditations.
 */
class AccreditationService
{

    private string $defaultTimeZone = 'Europe/Vienna';

    private QrCodeService $qrCodeService;

    /**
     * Annahme: Diese Repositories werden per DI (v13) injiziert.
     * In Legacy (v13 ohne Composer/Autowiring) müssten sie ggf.
     * im Konstruktor mit GeneralUtility::makeInstance() geholt werden.
     */
    public function __construct(
        ?QrCodeService $qrCodeService = null,
    ) {
        // 3. Der Check: Wurde was injiziert?
        if ($qrCodeService !== null) {
            // Ja: Moderner Composer-Mode -> Zuweisen
            $this->qrCodeService = $qrCodeService;
        } else {
            // Nein: Legacy-Mode -> Manuell holen
            $this->qrCodeService = GeneralUtility::makeInstance(QrCodeService::class);
        }
    }

    /**
     * Definiert alle E-Mail-Aktionscodes (inkl. Labels)
     * die in der Vorschau ausgewählt werden können.
     */
    public static function getAllVariantCodes(): array
    {
        $lllPath = 'LLL:EXT:publicrelations/Resources/Private/Language/locallang_db.xlf:tx_publicrelations_domain_model_invitationvariant.code.';

        return [
            [
                'code' => 'invite',
                'label' => 'Invite',
                'labelLLL' => $lllPath . 'invite'
            ],
            [
                'code' => 'remind',
                'label' => 'Remind',
                'labelLLL' => $lllPath . 'remind'
            ],
            [
                'code' => 'push',
                'label' => 'Push',
                'labelLLL' => $lllPath . 'push'
            ],
            [
                'code' => 'approve',
                'label' => 'Approve',
                'labelLLL' => $lllPath . 'approve'
            ],
            [
                'code' => 'reject',
                'label' => 'Reject',
                'labelLLL' => $lllPath . 'reject'
            ],
            [
                'code' => 'waiting',
                'label' => 'Waiting',
                'labelLLL' => $lllPath . 'waiting'
            ],
            [
                'code' => 'pending',
                'label' => 'Pending',
                'labelLLL' => $lllPath . 'pending'
            ],
            [
                'code' => 'approve_after_waiting',
                'label' => 'Approve after Waiting',
                'labelLLL' => $lllPath . 'approve_after_waiting'
            ],
            [
                'code' => 'reject_after_waiting',
                'label' => 'Reject after Waiting',
                'labelLLL' => $lllPath . 'reject_after_waiting'
            ],
        ];
    }

    /**
     * Checks if a requested status transition is valid for a given accreditation.
     *
     * @param Accreditation $accreditation The accreditation object.
     * @param string $requestedFunction The action identifier (e.g., 'invite', 'remind', 'approve', 'reject', 'delete', 'resend', 'confirm', 'reset').
     * @return bool True if the transition is allowed, false otherwise.
     */
    public function isValidStatusTransition(Accreditation $accreditation, string $requestedFunction): bool
    {
        $currentStatus = $accreditation->getStatus();
        $currentInvitationStatus = $accreditation->getInvitationStatus();

        // Determine validity based on the requested action
        return match (strtolower($requestedFunction)) {
            // Invitation Workflow (Applies when currentStatus is primarily 0 - pending/open)
            'invite' => ($currentStatus === 0 && $currentInvitationStatus === 0), // Nur senden, wenn vorbereitet
            'remind' => ($currentStatus === 0 && $currentInvitationStatus === 1), // Nur erinnern, wenn eingeladen
            'push' => ($currentStatus === 0 && $currentInvitationStatus === 2), // Nur pushen, wenn erinnert
            'resend' => ($currentStatus === 0 && in_array($currentInvitationStatus, [1, 2, 3], true)), // Erneut senden für alle gesendeten Einladungsstati

            // General Status Changes
            'approve', 'confirm' => true, // Bestätigen (initial oder erneut) ist meistens ok, Logik im Controller entscheidet über Mail-Typ. Confirmation kann auch bei Status 1/2 Sinn machen.
            'reject' => true, // Ablehnen ist meistens ok.
            'reset' => true, // Zurücksetzen ist meistens ok.

            // Destructive Action
            'delete' => ($currentStatus === 0 && $currentInvitationStatus === 0), // Löschen nur, wenn noch gar nichts passiert ist

            // Default: Unknown or invalid action
            default => false,
        };
    }

    /**
     * Determines the target field values for a given action.
     *
     * @param string $function The action identifier (e.g., 'invite', 'remind', 'approve').
     * @param Accreditation $accreditation The current accreditation (needed for context like tickets_wish).
     * @return array An array mapping field names to their target values (e.g., ['status' => 1, 'invitation_status' => -1]). Returns empty array for actions with no direct status change or invalid actions.
     */
    public function getDefaultChangesForAction(Accreditation $accreditation, string $function): array
    {
        $changes = [];

        switch (strtolower($function)) {
            // Invitation Workflow Status
            case 'invite':
                $changes['invitation_status'] = 1;
                break;
            case 'remind':
                $changes['invitation_status'] = 2;
                break;
            case 'push':
                $changes['invitation_status'] = 3;
                break;
            case 'reset': // Resets both statuses
                $changes['status'] = 0;
                $changes['invitation_status'] = 0;
                break;

            // Main Status Changes
            case 'approve':
            case 'confirm': // Both lead to approved state
                $changes['status'] = 1;
                $changes['invitation_status'] = -1; // Mark invitation workflow as done
                // Set approved tickets based on wish (can be overridden later)
                // $changes['tickets_approved'] = $accreditation->getTicketsWish();
                break;
            case 'reject':
                $changes['status'] = -1;
                $changes['invitation_status'] = -1; // Mark invitation workflow as done
                // Reset associated fields on rejection
                $changes['tickets_approved'] = 0;
                $changes['program'] = 0;
                $changes['pass'] = 0;
                break;

            // Actions without direct status field changes handled elsewhere
            case 'resend':
            case 'delete':
            default:
                // No status changes defined for these actions here
                break;
        }

        return $changes;
    }

    /**
     * Checks if an email can be sent for this accreditation based on guest status and email validity.
     */
    public function isValidForSending(Accreditation $accreditation): bool
    {
        $guest = $accreditation->getGuest();
        $email = $accreditation->getGuestOutput()['email'] ?? ''; // Uses fallback for manual entry

        // 1. Basic email check: Must exist and not be the noreply address
        if (empty($email) || $email === 'noreply@allegria.at') {
            return false;
        }

        // 2. Check mailing_exclude flag IF a guest record is linked
        // If $guest is null (manual entry), we assume sending is allowed if email is valid.
        if ($guest !== null && $guest->isMailingExclude()) {
            return false; // Stop if the linked guest has opted out
        }

        // If email is valid and either no guest is linked or the linked guest allows mailings
        return true;
    }

    public function getLogCodeForMailAction(string $function, int $currentInvitationStatus): ?string
    {
        return match (strtolower($function)) {
            'invite' => 'A-email-invite',
            'remind' => 'A-email-remind',
            'push' => 'A-email-push',
            'approve' => 'A-email-approve', // Assuming this is invitation approval email
            'confirm' => 'A-email-confirmation-resend', // Resend confirmation
            'reject' => 'A-email-reject', // Invitation rejection email
            'waiting' => 'A-email-waitinglist',
            'pending' => 'A-email-frontend-submit', // Map 'pending' auf "Anfrage übermittelt"
            'approve_after_waiting' => 'A-email-waitinglist-approve',
            'reject_after_waiting' => 'A-email-waitinglist-reject',
            'resend' => match ($currentInvitationStatus) {
                    1 => 'A-email-invite-resend',
                    2 => 'A-email-remind', // Resending reminder IS reminding
                    3 => 'A-email-push',   // Resending pusher IS pushing
                    default => null,
                },
            default => null,
        };
    }

    public function getLogCodeForStatusAction(string $function): ?string
    {
        return match (strtolower($function)) {
            'approve' => 'A-status-approve-manual',
            'reject' => 'A-status-reject-manual',
            'reset' => 'A-status-reset',
            // Add other non-mail status changes if needed
            default => null, // Or a generic 'A-status-manual'
        };
    }


    /**
     * 
     * LOGGING LOGICS
     *
     **/

    /**
     * Prepares the data array for creating a log entry for an accreditation action.
     *
     * @param Accreditation $accreditation The related accreditation.
     * @param ?string $logCode The action performed (e.g., 'invite', 'approve', 'job_created').
     * @param ?array $additionalData Optional data like Job UID.
     */
    public function prepareLogData(Accreditation $accreditation, ?string $logCode, ?array $additionalData = null): array
    {

        if ($logCode === null) {
            $logCode = 'A-status-unmapped-mail-action';
        }

        $subject = $this->determineLogSubject($logCode);

        $beUserId = (int) GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('backend.user', 'id', 0);

        $extConfService = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $config = $extConfService->get('ac_distribution');
        $storagePid = (int) ($config['storagePid']);

        $logData = [
            'pid' => $storagePid,
            'cruser_id' => $beUserId,
            'function' => 'Akkreditierung',
            'code' => $logCode,
            'subject' => $subject . (str_contains($logCode, 'email') ? ' [' . $accreditation->getGuestOutput()['email'] . ']' : ''),
            'accreditation' => $accreditation->getUid(),
            'tt_address' => $accreditation->getGuest() ? $accreditation->getGuest()->getUid() : 0,
            'event' => $accreditation->getEvent() ? $accreditation->getEvent()->getUid() : 0,
            'details' => $this->formatLogDetails($logCode, $accreditation, $additionalData), // Pass code and accr. too
        ];

        return $logData;
    }

    /**
     * Determines a human-readable subject based on the log code.
     */
    private function determineLogSubject(string $logCode): string
    {
        return match ($logCode) {
            'A-create-manual' => 'Erstellt (Manuell)',
            'A-create-wizzard' => 'Erstellt (Wizard)',
            'A-create-frontend' => 'Erstellt (Website-Anfrage)',
            'A-create-invitation' => 'Erstellt (Einladung)',
            'A-edit-manual' => 'Geändert (Manuell)',
            'A-edit-collection' => 'Geändert (Massenänderung)',
            'A-moved' => 'Verschoben',
            'A-delete' => 'Gelöscht',

            // Stati-Änderungen
            'A-status-invite' => 'Status: Einladung versandt',
            'A-status-remind' => 'Status: Erinnerung versandt',
            'A-status-push' => 'Status: Letzte Erinnerung versandt',
            'A-status-reject' => 'Status: Einladung abgesagt',
            'A-status-reject-manual' => 'Status: Abgelehnt (Manuell)',
            'A-status-approve' => 'Status: Einladung zugesagt',
            'A-status-approve-manual' => 'Status: Bestätigt (Manuell)',
            'A-status-approve-express' => 'Status: Bestätigt (Express)',
            'A-status-waitinglist' => 'Status: Warteliste',
            'A-status-overbooked' => 'Status: Kontingent erschöpft',
            'A-status-archive' => 'Status: Abgelegt',
            'A-status-reset' => 'Einladung zurückgesetzt',
            'A-status-checkin' => 'Eingecheckt',

            // Link-Aufrufe
            'A-link-opened' => 'Link: Geöffnet',
            'A-link-approve' => 'Link: Zugesagt',
            'A-link-reject' => 'Link: Abgesagt',
            'A-link-waitinglist' => 'Link: Warteliste',

            // Email-Versand
            'A-email-invite' => 'E-Mail: Einladung versandt',
            'A-email-remind' => 'E-Mail: Erinnerung versandt',
            'A-email-push' => 'E-Mail: Letzte Erinnerung versandt',
            'A-email-approve' => 'E-Mail: Einladung bestätigt',
            'A-email-accreditation-approve' => 'E-Mail: Akkreditierung bestätigt',
            'A-email-confirmation-resend' => 'E-Mail: Bestätigung erneut versandt',
            'A-email-invite-resend' => 'E-Mail: Einladung erneut versandt',
            'A-email-waitinglist' => 'E-Mail: Info Warteliste',
            'A-email-waitinglist-approve' => 'E-Mail: Zusage nach Warteliste',
            'A-email-waitinglist-reject' => 'E-Mail: Absage nach Warteliste',
            'A-email-reject' => 'E-Mail: Einladung abgelehnt',
            'A-email-accreditation-reject' => 'E-Mail: Akkreditierung abgelehnt',
            // 'A-email-waitinglist-info' => 'E-Mail: Info über Wartelistenplatzierung', // Duplicate? Use A-email-waitinglist?
            'A-email-overbooked-reject' => 'E-Mail: Absage (Überbucht)',
            'A-email-frontend-submit' => 'E-Mail: Anfrage übermittelt (Gast)',
            'A-email-frontend-received' => 'E-Mail: Anfrage erhalten (Intern)',
            'A-email-waitinglist-internal' => 'E-Mail: Info Warteliste (Intern)',
            'A-email-error' => 'E-Mail: Versandfehler',
            'A-job-created' => 'Versandjob erstellt',
            default => 'Unbekannte Aktion (' . $logCode . ')', // Fallback
        };
    }

    /**
     * Formats additional data for the log details field based on code and context.
     */
    private function formatLogDetails(string $logCode, Accreditation $accreditation, ?array $additionalData): string
    {
        $details = [];

        // Add email address for relevant email logs
        if (str_starts_with($logCode, 'A-email-')) {
            $email = $accreditation->getGuestOutput()['email'] ?? null;
            if ($email && !in_array($logCode, ['A-email-frontend-received', 'A-email-waitinglist-internal'])) { // Don't add recipient for internal mails
                $details[] = 'Empfänger: ' . $email;
            }
        }

        // Add IP for link actions
        if (str_starts_with($logCode, 'A-link-')) {
            // We need a reliable way to get the IP here. Passing it via $additionalData is best.
            $ip = $additionalData['ip'] ?? GeneralUtility::getIndpEnv('REMOTE_ADDR'); // Fallback, might not be accurate
            $details[] = 'IP: ' . $ip;
        }

        // Add Job UID if provided
        if (isset($additionalData['jobUid'])) {
            $details[] = 'Job UID: ' . $additionalData['jobUid'];
        }

        // Add changed fields for edit actions
        if ($logCode === 'A-edit-collection' && isset($additionalData['changedFields'])) {
            $details[] = 'Geänderte Felder: ' . implode(', ', $additionalData['changedFields']);
        }

        // Add check-in details
        if ($logCode === 'A-checkin' && isset($additionalData['checkinDetails'])) {
            $details[] = $additionalData['checkinDetails']; // Assume pre-formatted string
        }

        // Add email error message
        if ($logCode === 'A-email-error' && isset($additionalData['errorMessage'])) {
            $details[] = 'Fehlermeldung: ' . $additionalData['errorMessage'];
        }

        return implode("\n", $details); // Use newline for potential multi-line details
    }


    /**
     * Löst alle "Globalen" Platzhalter auf.
     * Holt sich Event und Invitation direkt aus der Accreditation.
     *
     * @param Accreditation $accreditation (Wird 1x vom Resolver übergeben)
     * @return array
     */
    public function resolveGlobalPlaceholders(Accreditation $accreditation): array
    {
        $placeholders = [];

        // --- 1. Daten aus $accreditation holen ---
        $event = $accreditation->getEvent();
        $invitation = $accreditation->getInvitationType();

        // 2. Event-Daten
        if ($event) {
            $placeholders['###EVENT_TITLE###'] = $event->getTitle() ?? '';

            // Location-Daten
            $location = $event->getLocation();
            if ($location) {
                $placeholders['###EVENT_LOCATION_NAME###'] = $location->getName() ?? '';
                $placeholders['###EVENT_LOCATION_STREET###'] = $location->getStreet() ?? '';
                $placeholders['###EVENT_LOCATION_ZIP###'] = $location->getZip() ?? '';
                $placeholders['###EVENT_LOCATION_CITY###'] = $location->getCity() ?? '';
            }

            // 3. Event-Datum formatieren
            $placeholders = array_merge(
                $placeholders,
                $this->formatDatePlaceholders('EVENT', $event->getDate())
            );
        }

        // 4. Feedback-Datum formatieren
        if ($invitation) {
            $placeholders = array_merge(
                $placeholders,
                $this->formatDatePlaceholders('FEEDBACK_UNTIL', $invitation->getFeedbackDate())
            );
        }

        // 5. "NOW" Datum (Aktuelles Datum/Uhrzeit des Job-Laufs) ---
        $placeholders = array_merge(
            $placeholders,
            $this->formatDatePlaceholders('NOW', new \DateTimeImmutable('now'))
        );

        return $placeholders;
    }

    /**
     * Löst alle "Uniquen" Platzhalter auf (pro Akkreditierung).
     *
     * @param Accreditation $accreditation
     * @return array
     */
    public function resolveAccreditationPlaceholders(Accreditation $accreditation): array
    {
        // 1. Model-Daten holen
        $guestOutput = $accreditation->getGuestOutput();

        // 2. --- Ticket-Logik ---
        // 2a. Helper für "approve" Tickets aufrufen
        $approvedPlaceholders = $this->formatTicketPlaceholders(
            'TICKETS_APPROVED',
            (int) $accreditation->getTicketsApproved()
        );

        // 2b. Helper für "Wish" Tickets aufrufen
        $wishPlaceholders = $this->formatTicketPlaceholders(
            'TICKETS_MAX',
            (int) $accreditation->getTicketsWish()
        );

        // 3. --- Links erstellen ---
        $feedbackLink = '';
        try {
            $pageId = 38;

            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            $router = $site->getRouter();

            // Optional: choose language explicitly (else site default is used)
            $language = $site->getDefaultLanguage(); // or $site->getLanguageById(0);

            $params = [
                'tx_publicrelations_accreditationform' => [
                    'controller' => 'Accreditation',
                    'action' => 'report',
                    'accreditation' => $accreditation->getUid(),
                ],
                // Hint: PageRouter has special handling for "_language"
                // '_language' => $language,
            ];

            // Build absolute frontend URL (no PSR-7 Request needed)
            $uri = $router->generateUri(
                $pageId,
                $params,
                '',                                 // fragment
                RouterInterface::ABSOLUTE_URL       // 'url' – force absolute
            );

            $feedbackLink = (string) $uri;

        } catch (\Exception $e) {
            // Logge den Fehler, falls Link-Generierung fehlschlägt
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                ->getLogger(__CLASS__)
                ->error('Konnte Ticket-Link nicht generieren: ' . $e->getMessage(), ['accreditationUid' => $accreditation->getUid()]);
            $feedbackLink = '#error-generating-link'; // Fallback
        }

        $feedbackLinkEn = '';
        try {
            $pageId = 38;

            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            $router = $site->getRouter();

            // Optional: choose language explicitly (else site default is used)
            $language = $site->getDefaultLanguage(); // or $site->getLanguageById(0);

            $params = [
                'tx_publicrelations_accreditationform' => [
                    'controller' => 'Accreditation',
                    'action' => 'report',
                    'accreditation' => $accreditation->getUid(),
                    'lang' => 'en',
                ],
                // Hint: PageRouter has special handling for "_language"
                // '_language' => $language,
            ];

            // Build absolute frontend URL (no PSR-7 Request needed)
            $uri = $router->generateUri(
                $pageId,
                $params,
                '',                                 // fragment
                RouterInterface::ABSOLUTE_URL       // 'url' – force absolute
            );

            $feedbackLinkEn = (string) $uri;

        } catch (\Exception $e) {
            // Logge den Fehler, falls Link-Generierung fehlschlägt
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                ->getLogger(__CLASS__)
                ->error('Konnte Ticket-Link nicht generieren: ' . $e->getMessage(), ['accreditationUid' => $accreditation->getUid()]);
            $feedbackLinkEn = '#error-generating-link'; // Fallback
        }

        // 4. QR Code URL holen (über den QrCodeService) ---
        $qrCodeAbsoluteUrl = '#error-qr-url'; // Default fallback
        $qrCodeLabel = 'KEIN TICKET – Gilt am VIP-Schalter.';
        try {
            $qrData = (string) $accreditation->getUid();

            // Service aufrufen -> gibt absoluten URL zurück
            // Wir übergeben keine speziellen Optionen, nutzen die Defaults im Service
            $qrCodeAbsoluteUrl = $this->qrCodeService->generateAndSaveQrCode($qrData, $qrCodeLabel) . '?v=' . time();

            // Cache-Buster ist bereits im Service inkludiert

        } catch (\Exception $e) {
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                ->getLogger(__CLASS__)
                ->error('Konnte QR Code URL nicht abrufen: ' . $e->getMessage(), ['accreditationUid' => $accreditation->getUid()]);
            // Loggen passiert bereits im QrCodeService, hier nur für den Kontext
        }

        // 5. Statische/Guest-Daten
        $staticPlaceholders = [
            // Model-Funktionen (Effizienz)
            '###UID###' => $accreditation->getUid(),
            '###SALUTATION###' => $accreditation->getSalutation(),
            '###SALUTATION_INFORMAL###' => $accreditation->getSalutationInformal(),
            '###SEAT_INFO###' => $accreditation->getSeats() ?? '',
            '###TICKET_INFO###' => $accreditation->getTickets() ?? '',

            // Aus getGuestOutput
            '###TITLE###' => $guestOutput['title'] ?? '',
            '###TITLE_SUFFIX###' => $guestOutput['titleSuffix'] ?? '',
            '###FIRST_NAME###' => $guestOutput['firstNames'] ?? '',
            '###LAST_NAME###' => $guestOutput['lastName'] ?? '',
            '###COMPANY###' => $guestOutput['company'] ?? '',
            '###FULL_NAME###' => $guestOutput['fullName'] ?? '',

            // Links & QR
            '###FEEDBACK_LINK###' => $feedbackLink,
            '###FEEDBACK_LINK_EN###' => $feedbackLinkEn,
            '###QR###' => $qrCodeAbsoluteUrl,
        ];

        // 6. Alle Arrays zusammenführen
        return array_merge(
            $staticPlaceholders,
            $approvedPlaceholders,
            $wishPlaceholders
        );
    }

    /**
     * Helferfunktion, die ein DateTime-Objekt in alle
     * vordefinierten Formate (DE/EN-GB) umwandelt.
     *
     * @param string $prefix (z.B. "EVENT" oder "FEEDBACK")
     * @param \DateTimeInterface|null $date
     * @return array
     */
    private function formatDatePlaceholders(string $prefix, ?\DateTimeInterface $date): array
    {
        // Wenn kein Datum gesetzt ist (z.B. Feedback-Datum), leere Strings zurückgeben
        if ($date === null || !extension_loaded('intl')) {
            // (Hier könnte der "dumme" Fallback mit date_format() hin)
            return [];
        }

        // --- DEUTSCH (de_DE) ---
        $fmtDeLong = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'EEEE, d. MMMM yyyy');
        $fmtDeNumeric = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'dd.MM.yyyy');
        $fmtDeWeekday = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'EEEE');
        $fmtDeWeekdayShort = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'EEEEEE');
        $fmtDeMonth = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'MMMM');
        $fmtDeMonthShort = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'MMM');
        $fmtDeTime = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'HH:mm');

        // --- BRITISCH (en_GB) ---
        $fmtEnLong = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'EEEE, dd MMMM yyyy');
        $fmtEnNumeric = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'dd/MM/yyyy'); // British format
        $fmtEnWeekday = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'EEEE');
        $fmtEnWeekdayShort = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'EEEEEE');
        $fmtEnMonth = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'MMMM');
        $fmtEnMonthShort = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'MMM');
        $fmtEnTime = new \IntlDateFormatter('en_GB', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $this->defaultTimeZone, \IntlDateFormatter::GREGORIAN, 'HH:mm'); // 24h clock is common

        return [
            // DE Datum
            "###{$prefix}_DATE_LONG###" => $fmtDeLong->format($date),
            "###{$prefix}_DATE###" => $fmtDeNumeric->format($date),
            "###{$prefix}_DATE_NUMERIC###" => $fmtDeNumeric->format($date),
            "###{$prefix}_WEEKDAY###" => $fmtDeWeekday->format($date),
            "###{$prefix}_WEEKDAY_SHORT###" => rtrim($fmtDeWeekdayShort->format($date), '.'),
            "###{$prefix}_DAY###" => $date->format('d'), // mit 0
            "###{$prefix}_DAY_SHORT###" => $date->format('j'), // ohne 0
            "###{$prefix}_MONTH###" => $fmtDeMonth->format($date),
            "###{$prefix}_MONTH_SHORT###" => $fmtDeMonthShort->format($date),
            "###{$prefix}_MONTH_NUM###" => $date->format('m'),
            "###{$prefix}_YEAR###" => $date->format('Y'),

            // EN Datum
            "###{$prefix}_DATE_LONG_EN###" => $fmtEnLong->format($date),
            "###{$prefix}_DATE_EN###" => $fmtEnNumeric->format($date),
            "###{$prefix}_DATE_NUMERIC_EN###" => $fmtEnNumeric->format($date),
            "###{$prefix}_WEEKDAY_EN###" => $fmtEnWeekday->format($date),
            "###{$prefix}_WEEKDAY_SHORT_EN###" => $fmtEnWeekdayShort->format($date),
            "###{$prefix}_DAY_EN###" => $date->format('d'),
            "###{$prefix}_DAY_SHORT_EN###" => $date->format('j'),
            "###{$prefix}_MONTH_EN###" => $fmtEnMonth->format($date),
            "###{$prefix}_MONTH_SHORT_EN###" => $fmtEnMonthShort->format($date),
            "###{$prefix}_MONTH_NUM_EN###" => $date->format('m'),
            "###{$prefix}_YEAR_EN###" => $date->format('Y'),

            // Zeit (Sprachunabhängig, aber Suffix für Konsistenz)
            "###{$prefix}_TIME###" => $fmtDeTime->format($date) . ' Uhr',
            "###{$prefix}_TIME_EN###" => $fmtEnTime->format($date),
            "###{$prefix}_TIME_H###" => $date->format('H'),
            "###{$prefix}_TIME_M###" => $date->format('i'),
        ];
    }

    /**
     * Private Helper: Formatiert eine Zahl in die Ticket/Personen-Platzhalter.
     *
     * @param string $prefix Der Platzhalter-Präfix (z.B. "TICKETS_APPROVED")
     * @param int $count Die Anzahl
     * @return array Das Array mit den formatierten Platzhaltern
     */
    private function formatTicketPlaceholders(string $prefix, int $count): array
    {
        $countStr = (string) $count;

        // Pluralisierung (Deutsch)
        $ticketLabel = ($count === 1) ? 'Ticket' : 'Tickets';
        $personLabel = ($count === 1) ? 'Person' : 'Personen';

        return [
            "###{$prefix}###" => $countStr,
            "###{$prefix}##TICKET###" => $countStr . ' ' . $ticketLabel,
            "###{$prefix}##PERSON###" => $countStr . ' ' . $personLabel,
        ];
    }


}