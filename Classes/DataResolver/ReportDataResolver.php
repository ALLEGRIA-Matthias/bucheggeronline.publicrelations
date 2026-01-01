<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\DataResolver;

use BucheggerOnline\Publicrelations\Domain\Repository\ClippingRouteRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ReportRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class ReportDataResolver
{
    private array $jobContext = [];
    private ?int $senderProfileUid = 1;
    private bool $isTestSend = false;
    private ?string $testRecipientEmail = null;

    // Hybrid-Konstruktor für Legacy-System
    private ClippingRouteRepository $clippingRouteRepository;
    private ReportRepository $reportRepository;
    private PersistenceManager $persistenceManager;

    public function __construct(
        ?ClippingRouteRepository $clippingRouteRepository = null,
        ?ReportRepository $reportRepository = null,
        ?PersistenceManager $persistenceManager = null
    ) {
        $this->clippingRouteRepository = $clippingRouteRepository
            ?? GeneralUtility::makeInstance(ClippingRouteRepository::class);
        $this->reportRepository = $reportRepository
            ?? GeneralUtility::makeInstance(ReportRepository::class);
        $this->persistenceManager = $persistenceManager
            ?? GeneralUtility::makeInstance(PersistenceManager::class);
    }

    public function setJobContext(array $jobContext): void
    {
        $this->jobContext = $jobContext;
        $this->senderProfileUid = (int) ($jobContext['sender_profile'] ?? 1);
        if (isset($jobContext['test_send']) && $jobContext['test_send'] === true) {
            $this->isTestSend = true;
            $this->testRecipientEmail = $jobContext['test_recipient_email'] ?? null;
        } else {
            $this->isTestSend = false;
        }
    }

    /**
     * Löst die Versanddaten für eine einzelne ClippingRoute auf.
     *
     * @param int $uid Die UID des tx_publicrelations_domain_model_clippingroute Datensatzes
     * @return array Der E-Mail-Bauplan ($resolvedData)
     */
    public function resolve(int $uid): array
    {
        // 1. Die Route laden
        $route = $this->clippingRouteRepository->findByUid($uid);
        if ($route === null) {
            throw new \RuntimeException('ClippingRoute mit UID ' . $uid . ' nicht gefunden.');
        }

        // 2. Alle "neuen" (unreported) Clippings für diese Route finden
        // (Wir brauchen die Query im ReportRepository: findUnreportedByRoute)
        $reportsToSend = $this->reportRepository->findUnreportedByRoute($uid);

        // WICHTIG: Wenn keine Reports da sind, abbrechen, sonst geht eine leere Mail raus.
        if (empty($reportsToSend)) {
            throw new \RuntimeException('Keine neuen Clippings zum Versenden für Route UID ' . $uid . ' gefunden.');
        }
        $reportCount = count($reportsToSend);

        // Für Extbase-Relationen im FE/CLI-Kontext (falls Fluid genutzt wird)
        $this->persistenceManager->persistAll();

        // 3. Empfänger-Daten parsen
        $toEmails = [];
        $ccEmails = [];
        $bccEmails = [];

        if ($this->isTestSend) {
            // --- PFAD A: TEST-VERSAND ---
            if (empty($this->testRecipientEmail)) {
                throw new \RuntimeException('Test-Versand fehlgeschlagen: "test_recipient_email" wurde nicht im Context übergeben.');
            }
            // Wir setzen NUR die Test-E-Mail als "To"
            $toEmails = [$this->testRecipientEmail];
            // CC und BCC bleiben absichtlich leer

        } else {
            // --- PFAD B: ECHTER VERSAND (wie bisher) ---
            $toEmails = $this->parseEmailList((string) $route->getToEmails());
            $ccEmails = $this->parseEmailList((string) $route->getCcEmails());
            $bccEmails = $this->parseEmailList((string) $route->getBccEmails());
        }

        $recipientAddress = $toEmails[0] ?? '';
        if (empty($recipientAddress)) {
            throw new \RuntimeException('Keine gültigen "To"-Empfänger in Route UID ' . $uid . ' definiert.');
        }

        $additionalRecipients = ['cc' => $ccEmails, 'bcc' => $bccEmails];
        if (count($toEmails) > 1) {
            $additionalTo = array_slice($toEmails, 1);
            // Wir fügen sie zu CC hinzu, da der MailBuildService dies unterstützt
            $additionalRecipients['cc'] = array_merge($additionalRecipients['cc'], $additionalTo);
        }

        // 4. Datamap für Post-Processing (der KRITISCHE Teil ⚠️)
        $datamapSuccess = [];
        $reportUidsToLog = [];

        if ($this->isTestSend === false) {
            $datamapSuccess = [
                'tx_publicrelations_domain_model_report' => []
            ];
            foreach ($reportsToSend as $report) {
                $reportUidsToLog[] = $report->getUid();
                // Wir nutzen den DataHandler, wie besprochen
                $datamapSuccess['tx_publicrelations_domain_model_report'][$report->getUid()] = [
                    'reported' => 1,
                ];
            }
        }

        // 5. Daten für Fluid und Logging (wie von dir gewünscht)
        $fluidVariables = [
            'route' => $route,
            'reports' => $reportsToSend,
            'reportCount' => $reportCount,
        ];

        // 6a. Preheader-Text generieren (wie gewünscht)
        $preheader = ($reportCount === 1)
            ? 'Ein neuer Treffer aus der Medienbeobachtung'
            : $reportCount . ' neue Treffer aus der Medienbeobachtung';

        // 6b. Datums-Platzhalter generieren (wie gewünscht)
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Vienna'));
        $currentHour = (int) $now->format('H');

        // Deine Logik für die Begrüßung
        $salutation = match (true) {
            $currentHour < 11 => 'Guten Morgen', // 00:00 - 10:59
            $currentHour < 14 => 'Mahlzeit',      // 11:00 - 13:59
            $currentHour < 17 => 'Guten Tag',       // 14:00 - 16:59
            default => 'Guten Abend',             // 17:00 - 23:59
        };

        $dateNow = $now->format('d.m.Y');
        $fmtDeLong = new \IntlDateFormatter('de_DE', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'Europe/Vienna', \IntlDateFormatter::GREGORIAN, 'd. MMMM yyyy');
        $dateNowLong = $fmtDeLong->format($now);

        // Subject Parts
        $clientName = $route?->getClient()->getShortName() ?? $route?->getClient()->getName();
        $dynamicPart = $clientName ? " [" . $clientName . "]" : "";

        // 7. Finales Daten-Array (der "Bauplan")
        return [
            // === Admin ===
            'render_type' => 'fluid', // Annahme: Clipping-Mails nutzen Fluid
            'template_name' => $this->jobContext['template_name'] ?? 'Clippingservice',
            'template_layout' => $this->jobContext['template_layout'] ?? 'AC_Clippingservice',

            // === Empfänger (aus der Route) ===
            'recipient_address' => $recipientAddress, // Der erste TO-Empfänger
            'additional_recipients' => $additionalRecipients, // Restl. TO, CC, BCC

            'override_sender_profile_uid' => $this->senderProfileUid,

            // === Inhalt ===
            'subject' => $this->jobContext['subject'] ?? 'Neue Clippings aus der Medienbeobachtung' . $dynamicPart,
            'preheader' => $preheader,

            // === Personalisierung ===
            'fluid_variables' => $fluidVariables,
            'placeholders' => [
                '###ROUTE_KEYWORD###' => $route->getKeyword(),
                '###REPORT_COUNT###' => count($reportsToSend),
                '###DATE_NOW###' => $dateNow,
                '###DATE_NOW_LONG###' => $dateNowLong,
                '###SALUTATION###' => $salutation
            ],

            // === WICHTIG: Logging & Post-Processing ===
            'post_send_success_datamap' => json_encode($datamapSuccess),
            'post_send_failure_datamap' => json_encode([]), // Kein Rollback nötig

            // === NEU: Für die Nachverfolgung (wie gewünscht) ===
            // Speichert die UIDs der Reports in resolved_data
            'sent_report_uids' => $reportUidsToLog
        ];
    }

    /**
     *
     * Nimmt einen String mit E-Mails (getrennt durch Komma, Semikolon,
     * Leerzeichen oder Zeilenumbruch) und gibt ein sauberes Array
     * mit validen E-Mail-Adressen zurück.
     *
     * @param string $rawList
     * @return string[]
     */
    private function parseEmailList(string $rawList): array
    {
        if (empty($rawList)) {
            return [];
        }

        // 1. Alle Trennzeichen (Komma, Semikolon, Zeilenumbruch) durch Leerzeichen ersetzen
        $normalizedString = str_replace([',', ';', "\n", "\r"], ' ', $rawList);

        // 2. Anhand von Leerzeichen aufteilen (auch mehrfache)
        $rawEmails = preg_split('/\s+/', $normalizedString, -1, PREG_SPLIT_NO_EMPTY);

        $cleanEmails = [];
        foreach ($rawEmails as $email) {
            // 3. Jede E-Mail trimmen
            $trimmedEmail = trim($email);

            // 4. Nur valide E-Mails hinzufügen
            if (filter_var($trimmedEmail, FILTER_VALIDATE_EMAIL)) {
                $cleanEmails[] = $trimmedEmail;
            }
        }

        return $cleanEmails;
    }
}