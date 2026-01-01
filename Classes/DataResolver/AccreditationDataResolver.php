<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\DataResolver;

use BucheggerOnline\Publicrelations\Domain\Model\Accreditation;
use BucheggerOnline\Publicrelations\Domain\Model\Invitation;
use BucheggerOnline\Publicrelations\Domain\Model\InvitationVariant;
use BucheggerOnline\Publicrelations\Domain\Repository\AccreditationRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\SenderProfileRepository; // Annahme, dass dies existiert
use BucheggerOnline\Publicrelations\Service\AccreditationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\LinkHandling\LinkService;

/**
 * Löst die Daten für den E-Mail-Versand einer einzelnen Akkreditierung auf.
 *
 * Dieser Resolver wird vom DistributionWorkerTask aufgerufen.
 * Der Worker MUSS vor dem Aufruf von resolve() die setJobContext() Methode aufrufen,
 * damit der Resolver weiß, welche Aktion (z.B. 'approve', 'remind') ausgeführt wird.
 */
class AccreditationDataResolver
{
    private array $jobContext = [];
    private ?string $function = null;
    private ?int $senderProfileUid = 1; // Default, wird aus Job überschrieben

    // --- Caching Properties (Effizienz) ---
    private ?array $globalPlaceholders = null;
    private ?Accreditation $previewAccreditation = null;
    private ?string $testRecipientEmail = null;
    private bool $isTestSend = false;

    private AccreditationRepository $accreditationRepository;
    private AccreditationService $accreditationService;
    // private SenderProfileRepository $senderProfileRepository;

    /**
     * Annahme: Diese Repositories werden per DI (v13) injiziert.
     * In Legacy (v13 ohne Composer/Autowiring) müssten sie ggf.
     * im Konstruktor mit GeneralUtility::makeInstance() geholt werden.
     */
    public function __construct(
        // 2. Mache die Argumente "nullable" und setze Default = null
        ?AccreditationRepository $accreditationRepository = null,
        ?AccreditationService $accreditationService = null
        // ?SenderProfileRepository $senderProfileRepository = null;
    ) {
        // 3. Der Check: Wurde was injiziert?
        if ($accreditationRepository !== null) {
            // Ja: Moderner Composer-Mode -> Zuweisen
            $this->accreditationRepository = $accreditationRepository;
        } else {
            // Nein: Legacy-Mode -> Manuell holen
            $this->accreditationRepository = GeneralUtility::makeInstance(AccreditationRepository::class);
        }

        if ($accreditationService !== null) {
            $this->accreditationService = $accreditationService;
        } else {
            // (Wir nehmen an, der Service nutzt auch den Hybrid-Konstruktor,
            // falls er selbst Dependencies hat)
            $this->accreditationService = GeneralUtility::makeInstance(AccreditationService::class);
        }

        // if ($senderProfileRepository !== null) {
        //     $this->senderProfileRepository = $senderProfileRepository;
        // } else {
        //     $this->senderProfileRepository = GeneralUtility::makeInstance(SenderProfileRepository::class);
        // }
    }

    /**
     * Wird vom Worker-Task injiziert, BEVOR resolve() aufgerufen wird.
     * Enthält den 'function' Code (z.B. 'approve') aus dem Job.
     */
    public function setJobContext(array $jobContext): void
    {
        $this->jobContext = $jobContext;
        $this->function = $jobContext['dataSource']['function'] ?? null;
        $this->senderProfileUid = (int) ($jobContext['sender_profile'] ?? 1);

        if (isset($jobContext['test_send']) && $jobContext['test_send'] === true) {
            $this->isTestSend = true;
            $this->testRecipientEmail = $jobContext['test_recipient_email'] ?? null;
        } else {
            $this->isTestSend = false;
        }
    }

    /**
     * Wird VOR resolve() aufgerufen, wenn im Preview-Modus
     * ein "Fake"-Objekt (z.B. UID 0) verwendet wird.
     */
    public function setPreviewAccreditation(Accreditation $accreditation): void
    {
        $this->previewAccreditation = $accreditation;
    }

    /**
     * Löst die Versanddaten für eine einzelne Akkreditierungs-UID auf.
     *
     * @param int $uid Die UID des tx_publicrelations_domain_model_accreditation Datensatzes
     * @return array Der E-Mail-Bauplan ($resolvedData)
     */
    public function resolve(int $uid): array
    {
        // --- 1. Models laden ---
        if ($this->function === null) {
            throw new \RuntimeException('Job context (und function) wurde nicht im AccreditationDataResolver gesetzt.');
        }

        $accreditation = null;
        if ($this->previewAccreditation !== null && $uid === 0) {
            // Fall 1: Wir sind im Preview-Modus (UID 0)
            // Nutze das injizierte "Fake"-Objekt
            $accreditation = $this->previewAccreditation;
        } else {
            // Fall 2: Normaler Betrieb (Live-Laden aus DB)
            $accreditation = $this->accreditationRepository->findByUid($uid);
        }

        // (Reset für den nächsten Aufruf)
        $this->previewAccreditation = null;

        if ($accreditation === null) {
            throw new \RuntimeException('Accreditation mit UID ' . $uid . ' nicht gefunden.');
        }

        $invitation = $accreditation->getInvitationType();
        $variant = null;

        // --- 2a. Render-Typ bestimmen ---
        if ($invitation === null) {
            // --- NEUER FALLBACK (Task 2): Keine Einladung ---
            // Zwinge Fluid-Modus, wenn keine Einladung verknüpft ist
            $renderType = 'fluid';

        } else {
            // Normaler Pfad: Render-Typ aus Einladung lesen
            $renderType = $invitation->getType(); // 'html' oder 'fluid'
            if ($renderType !== 'html' && $renderType !== 'fluid') {
                throw new \RuntimeException('Accreditation UID ' . $uid . ' hat einen ungültigen "html" oder "fluid" Einladungstyp.');
            }
        }

        // --- 2b. Empfänger:in bestimmen ---
        $guestOutput = $accreditation->getGuestOutput();
        $recipientAddress = $this->testRecipientEmail ?? $guestOutput['email'] ?? null;
        if (empty($recipientAddress)) {
            throw new \RuntimeException('Keine gültige Empfänger-E-Mail für Accreditation UID ' . $uid);
        }
        $recipientName = $guestOutput['fullName'] ?? '';

        $guest = $accreditation->getGuest();

        $ccRecipients = $guest ? $guest->getEmailCcReceiver() : [];
        $bccRecipients = $guest ? $guest->getEmailBccReceiver() : [];

        // --- 3. Post-Processing Datamaps vorbereiten ---

        $datamapSuccess = [];
        $datamapFailure = [];

        if ($this->isTestSend === false) {

            // ----- DATAMAP FÜR ERFOLG (SUCCESS) -----
            // 1. Status-Änderungen holen
            $changesSuccess = $this->accreditationService->getDefaultChangesForAction($accreditation, $this->function);
            // 2. Job-Lock entfernen (wie von dir gefordert)
            $changesSuccess['distribution_job'] = 0;

            // 3. Log-Code holen
            $logCodeSuccess = $this->accreditationService->getLogCodeForMailAction($this->function, $accreditation->getInvitationStatus());
            $logDataSuccess = $this->accreditationService->prepareLogData($accreditation, $logCodeSuccess);

            // 4. Komplette Datamap für Erfolg
            $datamapSuccess = [
                'tx_publicrelations_domain_model_accreditation' => [
                    $uid => $changesSuccess
                ],
                'tx_publicrelations_domain_model_log' => [
                    'NEW_LOG_SUCCESS_' . $uid => $logDataSuccess
                ]
            ];

            // ----- DATAMAP FÜR FEHLER (FAILURE) -----
            // 1. Nur Job-Lock entfernen
            $changesFailure = ['distribution_job' => 0];

            // 2. Log-Code holen
            $logCodeFailure = 'A-email-error';
            // 3. Log-Daten vorbereiten (mit Platzhalter für die Fehlermeldung)
            $logDataFailure = $this->accreditationService->prepareLogData($accreditation, $logCodeFailure, ['errorMessage' => 'PLACEHOLDER_SEE_MESSAGE_LOG']);

            // 4. Komplette Datamap für Fehler
            $datamapFailure = [
                'tx_publicrelations_domain_model_accreditation' => [
                    $uid => $changesFailure
                ],
                'tx_publicrelations_domain_model_log' => [
                    'NEW_LOG_FAILURE_' . $uid => $logDataFailure
                ]
            ];
        }

        // Target-Function (für contentKey)
        $targetFunction = $this->function;
        if ($targetFunction === 'resend')
            $targetFunction = 'invite';
        if ($targetFunction === 'confirm')
            $targetFunction = 'approve';

        // Suche, ob eine Variante für diese Aktion existiert.
        $variant = null;
        if (isset($invitation)) {
            foreach ($invitation->getVariants() as $v) {
                if ($v->getCode() === $targetFunction) {
                    $variant = $v;
                    break;
                }
            }
        }

        // --- 4. Fallbacks (aus emConfiguration) ---
        // Lade die neuen Code-basierten Fallbacks (Stufe 3)
        $codeFallbacks = $this->getCodeBasedFallbacks();
        $codeFallbackSubject = $codeFallbacks[$targetFunction]['subject'] ?? null;
        $codeFallbackPreheader = $codeFallbacks[$targetFunction]['preheader'] ?? null;

        // Lade die "letzten" Fallbacks (Stufe 4)
        $lastResortSubject = 'Wichtige Nachricht zu ###EVENT_TITLE###';
        $lastResortPreheader = 'Details zu Ihrer Akkreditierung für ###EVENT_TITLE###';
        $defaultSenderName = 'Allegria Communications Team';
        $defaultReplyName = 'Allegria Communications Team';
        $defaultReplyEmail = 'office@allegria.at';

        // Kaskade: Variante -> Einladung -> Fallback
        $subject = $variant?->getSubject()
            ?: $invitation?->getSubject()
            ?: $codeFallbackSubject
            ?: $lastResortSubject;

        if ($this->isTestSend) {
            // $this->function enthält den $variantCode (z.B. 'invite')
            $subject = '[Test - ' . $this->function . '] ' . $subject;

            // WICHTIG: Flags für den nächsten Durchlauf zurücksetzen
            $this->isTestSend = false;
            $this->testRecipientEmail = null;
        }


        $preheader = $variant?->getPreheader()
            ?: $codeFallbackPreheader
            ?: $lastResortPreheader;

        $senderOverrides = [
            'from_name' => $variant?->getFromName()
                ?: $invitation?->getFromName()
                ?: $defaultSenderName,
            'replyto_email' => $variant?->getReplyEmail()
                ?: $invitation?->getReplyEmail()
                ?: $defaultReplyEmail,
            'replyto_name' => $variant?->getReplyName()
                ?: $invitation?->getReplyName()
                ?: $defaultReplyName,
        ];

        // --- 5. Render-Typ-spezifische Daten ---
        $fluidVariables = [];
        $placeholders = [];
        $templateFile = '';
        $templateName = '';
        $templateLayout = '';
        $attachments = [];

        // Anhänge laden
        if ($variant !== null) {
            $variantAttachments = $variant->getAttachments(); // (nutzt das neue Model-Feld)

            if ($variantAttachments && $variantAttachments->count() > 0) {
                foreach ($variantAttachments as $fileReference) {
                    if ($fileReference instanceof \TYPO3\CMS\Extbase\Domain\Model\FileReference) {
                        $attachments[] = ltrim($fileReference->getOriginalResource()->getPublicUrl(), '/');
                    }
                }
            }
        }

        // Definiere die Codes, die auf Fluid zurückfallen dürfen
        $fallbackAllowedCodes = [
            'waiting',
            'pending',
            'reject_after_waiting'
        ];

        // --- START: PFAD A (HTML) ---
        if ($renderType === 'html') {

            $variants = $invitation?->getVariants() ?? [];

            // Nur wenn 'approve_after_waiting' angefordert ist, prüfen wir dessen Existenz.
            // Wenn es fehlt, schwenken wir hart auf 'approve' um.
            if ($targetFunction === 'approve_after_waiting') {
                $variantExists = false;
                foreach ($variants as $v) {
                    if ($v->getCode() === 'approve_after_waiting') {
                        $variantExists = true;
                        break;
                    }
                }

                if (!$variantExists) {
                    $targetFunction = 'approve';
                }
            }

            // --- STANDARD SUCHE: Basierend auf (evtl. geänderter) targetFunction ---
            $variant = null;
            foreach ($variants as $v) {
                if ($v->getCode() === $targetFunction) {
                    $variant = $v;
                    break;
                }
            }

            $templateLink = $variant?->getHtml();

            // --- VALIDIERUNG & PROZESS ---
            if (empty($templateLink)) {
                // TEMPLATE FEHLT (oder Variante fehlt)

                if (in_array($targetFunction, $fallbackAllowedCodes, true)) {
                    // --- FALLBACK 1: Standard-Fluid ---
                    $renderType = 'fluid'; // Zwangsumstellung

                } else {
                    // --- HARTER FEHLER ---
                    // Kein Template gefunden UND kein Fallback erlaubt
                    // (z.B. für 'invite', 'approve', 'reject')
                    if ($variant === null) {
                        throw new \RuntimeException('Rendertyp "html" erfordert eine Variante für Code "' . $targetFunction . '".');
                    } else {
                        throw new \RuntimeException('Rendertyp "html" erfordert eine Template-Datei in Variante CODE "' . $targetFunction . '" (UID: ' . $variant->getUid() . ').');
                    }
                }

            } else {
                // --- Normaler HTML-Pfad: Template ist vorhanden ---
                $ls = GeneralUtility::makeInstance(LinkService::class);
                $info = $ls->resolve($templateLink);
                $templateFile = ltrim(trim($info['file']->getPublicUrl()), '/');
            }
        }

        // --- START: PFAD B (FLUID) ---
        if ($renderType === 'fluid') {

            // Logik 4: Template (Immer dasselbe)
            $templateName = 'AccreditationNew';
            $templateLayout = $invitation?->getAltTemplate() ?: 'Allegria-Communications';

            // Logik 6: Fluid-Variablen (wie von dir gewünscht)
            $fluidVariables = [
                'accreditation' => $accreditation,
                'guestOutput' => $guestOutput,
                'template' => $invitation?->getAltTemplate() ?: 'Allegria-Communications',
                'content' => $targetFunction
            ];

        }

        // 6. Platzhalter (ALLE)
        $globalPlaceholders = $this->accreditationService->resolveGlobalPlaceholders($accreditation);
        $uniquePlaceholders = $this->accreditationService->resolveAccreditationPlaceholders($accreditation);
        $placeholders = array_merge($globalPlaceholders, $uniquePlaceholders);


        // --- 7. Finales Daten-Array ---
        return [
            // === Admin ===
            'render_type' => $renderType, // Basiert auf $invitation->getType()

            // === Empfänger ===
            'contact' => $accreditation->getGuest()?->getUid() ?? 0,
            'recipient_address' => $recipientAddress,
            'recipient_name' => $recipientName,
            'additional_recipients' => [
                'cc' => $ccRecipients,
                'bcc' => $bccRecipients
            ],

            // === Versandweg ===
            'override_sender_profile_uid' => $this->senderProfileUid,
            'sender_overrides' => $senderOverrides, // Genutzt vom SendService

            // === Inhalt ===
            'subject' => $subject,
            'preheader' => $preheader,
            'template_file' => $templateFile, // (von HTML-Variante ODER Fluid-Fallback)
            'template_name' => $templateName, // (dasselbe)
            'template_layout' => $templateLayout,

            // === Personalisierung ===
            'fluid_variables' => $fluidVariables,
            'placeholders' => $placeholders,

            // === Sonstiges ===
            'attachments' => $attachments,

            // === NEU: Post-Processing Datamaps (als JSON) ===
            'post_send_success_datamap' => json_encode($datamapSuccess),
            'post_send_failure_datamap' => json_encode($datamapFailure),
        ];
    }

    /**
     * Wird von JobsAjaxController aufgerufen.
     * Findet alle Akkreditierungen, die von diesem Job gesperrt sind,
     * und gibt die DataHandler-Map zurück, um sie zu entsperren.
     *
     * @param int $jobUid Die UID des Jobs (tx_acdistribution_domain_model_job)
     * @return array Die DataHandler-Datamap zum Entsperren.
     */
    public function cancelJob(int $jobUid): array
    {
        $uidsToUnlock = $this->accreditationRepository->findUidsByDistributionJob($jobUid);

        if (empty($uidsToUnlock)) {
            return [];
        }

        $tableName = 'tx_publicrelations_domain_model_accreditation';
        $datamap = [
            $tableName => []
        ];

        foreach ($uidsToUnlock as $uid) {
            $datamap[$tableName][(int) $uid] = [
                'distribution_job' => 0
            ];
        }

        return $datamap;
    }

    /**
     * Liefert die Code-basierten Fallbacks für Subject/Preheader (aus CSV).
     */
    private function getCodeBasedFallbacks(): array
    {
        return [
            'invite' => [
                'subject' => 'Persönliche Einladung | ###EVENT_TITLE###',
                'preheader' => '###FULL_NAME### ist herzlich eingeladen'
            ],
            'remind' => [
                'subject' => 'Freundliche Erinnerung | ###EVENT_TITLE###',
                'preheader' => 'Erinnerung: ###FULL_NAME### ist herzlich eingeladen'
            ],
            'push' => [
                'subject' => 'Letzte Erinnerung | ###EVENT_TITLE###',
                'preheader' => 'Letzte Chance: ###FULL_NAME### ist herzlich eingeladen'
            ],
            'approve' => [
                'subject' => 'Ihre Zusage | ###EVENT_TITLE###',
                'preheader' => 'Wir freuen uns auf Ihr Kommen.'
            ],
            'reject' => [
                'subject' => 'Ihre Absage | ###EVENT_TITLE###',
                'preheader' => 'Schade, dass es nicht klappt.'
            ],
            'waiting' => [
                'subject' => 'Bitte um Geduld | ###EVENT_TITLE###',
                'preheader' => 'Sie sind auf der Warteliste gelandet.'
            ],
            'approve_after_waiting' => [
                'subject' => 'Ihre Bestätigung | ###EVENT_TITLE###',
                'preheader' => 'Wir können Ihren Besuch ermöglichen.'
            ],
            'reject_after_waiting' => [
                'subject' => 'Wichtige Information | ###EVENT_TITLE###',
                'preheader' => 'Leider können wir den Besuch nicht ermöglichen.'
            ],
            'pending' => [
                'subject' => 'Neu auf der Warteliste | ###EVENT_TITLE###',
                'preheader' => '###FULL_NAME### ist auf die Warteliste gerutscht.'
            ],
        ];
    }
}