<?php
namespace BucheggerOnline\Publicrelations\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use BucheggerOnline\Publicrelations\Service\QrCodeService;

/**
 * = QR Code View Helper (gibt nur URL zurück) =
 *
 * Generiert einen QR-Code als gespeicherte Datei und gibt den
 * relativen URL-Pfad zur Datei zurück (inkl. Cache-Buster).
 *
 * Beispiele:
 * <img src="{mb:qrCode data='{myData}' label='My Label'}" alt="QR Code" />
 *
 * {imageData -> mb:qrCode(data: imageData.uid, label: 'Scan me')}
 * <img src="{imageData.qrCodeUrl}" alt="QR for {imageData.name}" />
 *
 */
class QrCodeViewHelper extends AbstractViewHelper
{

    // --- Service Property ---
    private ?QrCodeService $qrCodeService = null;

    /**
     * Inject QrCodeService
     * @param QrCodeService $qrCodeService
     */
    public function injectQrCodeService(QrCodeService $qrCodeService): void
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        // Keine Tag-Attribute mehr registrieren
        $this->registerArgument('data', 'string', 'Data für QR Code', true);
        $this->registerArgument('label', 'string', 'Label unter QR Code', false, '');
        $this->registerArgument('options', 'array', 'Zusätzliche Optionen für den QrCodeService (size, margin, color etc.)', false, []);
    }

    /**
     * Generiert den QR-Code, speichert ihn und gibt den URL-Pfad zurück.
     *
     * @return string Der relative URL-Pfad zur PNG-Datei (z.B. /fileadmin/_temp/qrcodes/qr_hash.png?v=12345)
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        // Service holen (muss statisch erfolgen)
        // @var QrCodeService $qrCodeService
        $qrCodeService = GeneralUtility::makeInstance(QrCodeService::class);

        $data = $arguments['data'];
        $label = $arguments['label'] ?? '';
        $options = $arguments['options'] ?? [];

        // Optionen aus direkten Argumenten (falls vorhanden und im Service unterstützt)
        // (Beachte: 'width' und 'alt' sind hier nicht mehr relevant, da wir keinen Tag bauen)
        $directOptions = [
            'size' => $arguments['size'] ?? null, // size statt width
            'margin' => $arguments['margin'] ?? null,
            'encoding' => $arguments['encoding'] ?? null,
            'errorCorrectionLevel' => $arguments['errorCorrectionLevel'] ?? null,
            'color' => $arguments['color'] ?? null,
            'bgColor' => $arguments['bgColor'] ?? null,
            'labelColor' => $arguments['labelColor'] ?? null,
            'labelAlign' => $arguments['labelAlign'] ?? null,
            'labelMargin' => $arguments['labelMargin'] ?? null,
            'logoPath' => $arguments['logoPath'] ?? null,
            'logoSize' => $arguments['logoSize'] ?? null,
        ];
        $directOptions = array_filter($directOptions, fn($value) => $value !== null);
        $finalOptions = array_merge($options, $directOptions);


        try {
            // --- Service aufrufen ---
            $filePath = $qrCodeService->generateAndSaveQrCode($data, $label, $finalOptions);

            // --- Cache-Buster hinzufügen ---
            $filePathWithBuster = $filePath . '?v=' . time();

            // --- NUR den Pfad zurückgeben ---
            return $filePathWithBuster;

        } catch (\Exception $e) {
            // Im Fehlerfall: Leeren String oder Fehler-URL zurückgeben
            // Loggen passiert bereits im Service
            // Alternativ: throw $e; // Um den Fehler im Fluid sichtbar zu machen
            return '#QR_CODE_ERROR';
        }
    }

}
