<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Service; // Passe Namespace ggf. an

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\PathUtility; // Für Logo-Pfad
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color; // Für Farben
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Alignment\LabelAlignmentLeft;
use Endroid\QrCode\Label\Alignment\LabelAlignmentRight;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Label\Margin\Margin; // Für Label-Margin
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Service zum Generieren und Speichern von QR-Codes.
 * Akzeptiert optionale Parameter zur Konfiguration.
 */
class QrCodeService
{
    private string $savePathRelative = 'eventmanagement/qrcodes/';
    private int $fallbackSitePid = 1; // Fallback-PID zur Ermittlung der Site/Domain (z.B. Root-Page-ID)

    private SiteFinder $siteFinder;

    /**
     * Hybrid-Konstruktor (Legacy-safe)
     */
    public function __construct(
        ?SiteFinder $siteFinder = null // <-- NEU
    ) {
        // --- NEU: SiteFinder ---
        if ($siteFinder !== null) {
            $this->siteFinder = $siteFinder;
        } else {
            $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        }
    }

    /**
     * Generiert einen QR-Code, speichert ihn als PNG-Datei
     * und gibt den relativen Pfad zurück.
     *
     * @param string $data Die zu kodierenden Daten (z.B. UID)
     * @param string $label Das Label unter dem QR-Code
     * @param array $options Optionale Konfigurationen (siehe QrCodeViewHelper Argumente)
     * @return string Der relative Pfad zur PNG-Datei (z.B. /fileadmin/_temp/qrcodes/qr_hash.png)
     * @throws \RuntimeException Wenn das Speichern fehlschlägt oder das Verzeichnis nicht erstellt werden kann.
     */
    public function generateAndSaveQrCode(string $data, string $label = '', array $options = []): string
    {
        // --- 1. Defaults definieren (basierend auf VH) ---
        $defaults = [
            'size' => 300,
            'margin' => 0,
            'encoding' => 'UTF-8',
            'errorCorrectionLevel' => 'high', // L, M, Q, H
            'color' => '#000000',
            'bgColor' => '#ffffff',
            'labelColor' => '#000000',
            // 'labelFont' => '', // Vorerst nur NotoSans unterstützt
            'labelAlign' => 'center', // center, left, right
            'labelMargin' => '0', // CSS-Style: "all", "top/bottom left/right", "top right bottom left"
            'logoPath' => null,
            'logoSize' => 50,
        ];
        $config = array_merge($defaults, $options);

        // --- 2. Dateiname (basiert NUR auf data & label für Caching) ---
        $filename = 'qr_' . md5($data) . '.png';
        $relativeFilePath = $this->savePathRelative . $filename;
        $absoluteFilePath = Environment::getPublicPath() . '/' . $relativeFilePath;

        // --- 3. Nur generieren, wenn Datei noch nicht existiert ---
        if (!file_exists($absoluteFilePath)) {
            // Verzeichnis sicherstellen
            $dir = dirname($absoluteFilePath);
            if (!is_dir($dir)) {
                try {
                    GeneralUtility::mkdir_deep($dir);
                } catch (\Exception $e) { /* ... Fehler werfen ... */
                    throw new \RuntimeException('QR Code Speicherverzeichnis konnte nicht erstellt werden: ' . $dir, 1731959330, $e);
                }
            }

            try {
                // --- 4. Builder konfigurieren ---
                $builder = Builder::create()
                    ->writer(new PngWriter())
                    ->writerOptions([])
                    ->data($data)
                    ->encoding(new Encoding($config['encoding']))
                    ->size((int) $config['size'])
                    ->margin((int) $config['margin'])
                    // ->roundBlockSizeMode(RoundBlockSizeModeMargin::create())
                    ->foregroundColor(new Color(...$this->hexToRgb($config['color'])))
                    ->backgroundColor(new Color(...$this->hexToRgb($config['bgColor'])));

                // Error Correction Level
                $ecLevel = match (strtolower($config['errorCorrectionLevel'])) {
                    'low', 'l' => new ErrorCorrectionLevelLow(),
                    'medium', 'm' => new ErrorCorrectionLevelMedium(),
                    'quartile', 'q' => new ErrorCorrectionLevelQuartile(),
                    default => new ErrorCorrectionLevelHigh(),
                };
                $builder->errorCorrectionLevel($ecLevel);

                // Label (nur wenn vorhanden)
                if (!empty($label)) {
                    $builder->labelText($label)
                        ->labelFont(new NotoSans(12)) // TODO: Font & Size konfigurierbar machen
                        ->labelTextColor(new Color(...$this->hexToRgb($config['labelColor'])));

                    // Label Alignment
                    $alignment = match (strtolower($config['labelAlign'])) {
                        'left' => new LabelAlignmentLeft(),
                        'right' => new LabelAlignmentRight(),
                        default => new LabelAlignmentCenter(), // Default 'center'
                    };
                    $builder->labelAlignment($alignment);

                    // Label Margin (simples Parsing für CSS-Style)
                    // TODO: Robustere CSS-Margin-Parsing-Logik implementieren
                    $marginParts = explode(' ', trim($config['labelMargin']));
                    $marginTop = (int) ($marginParts[0] ?? 0);
                    $marginRight = (int) ($marginParts[1] ?? $marginTop);
                    $marginBottom = (int) ($marginParts[2] ?? $marginTop);
                    $marginLeft = (int) ($marginParts[3] ?? $marginRight);
                    $builder->labelMargin(new Margin($marginTop, $marginRight, $marginBottom, $marginLeft));
                }

                // Logo (nur wenn Pfad angegeben)
                if (!empty($config['logoPath'])) {
                    // Versuche, den Pfad aufzulösen (z.B. EXT: oder fileadmin/)
                    $absoluteLogoPath = GeneralUtility::getFileAbsFileName($config['logoPath']);
                    if ($absoluteLogoPath && file_exists($absoluteLogoPath)) {
                        $builder->logoPath($absoluteLogoPath)
                            ->logoResizeToWidth((int) $config['logoSize'])
                            ->logoPunchoutBackground(true); // Wie im VH
                    } else {
                        // Logo nicht gefunden, logge Warnung
                        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                            ->getLogger(__CLASS__)
                            ->warning('QR Code Logo nicht gefunden: ' . $config['logoPath']);
                    }
                }

                // --- 5. QR Code bauen und speichern ---
                $result = $builder->validateResult(false)->build();
                $result->saveToFile($absoluteFilePath);

            } catch (\Exception $e) { /* ... Fehler werfen ... */
                throw new \RuntimeException('QR Code konnte nicht generiert/gespeichert werden: ' . $e->getMessage(), 1731959331, $e);
            }
        }

        // --- 4. Absolute URL zurückgeben ---
        try {
            $currentSite = $this->siteFinder->getSiteByPageId($this->fallbackSitePid);
            $baseUri = $currentSite->getBase(); // PSR-7 Uri Objekt

            // Korrekten absoluten Pfad zusammensetzen
            // baseUri->getPath() hat oft einen führenden Slash, relativeFilePath nicht
            $fullPath = rtrim($baseUri->getPath(), '/') . '/' . ltrim($relativeFilePath, '/');

            // Absoluten URL bauen
            $absoluteUrl = $baseUri
                ->withPath($fullPath)
                ->__toString(); // Gibt den kompletten URL-String zurück

        } catch (\Exception $e) {
            // Loggen und Fehler werfen, wenn Site/URL nicht ermittelt werden kann
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                ->getLogger(__CLASS__)
                ->error('Konnte Basis-URL für QR Code nicht ermitteln: ' . $e->getMessage(), ['fallbackPid' => $this->fallbackSitePid]);
            throw new \RuntimeException('Konnte Basis-URL für QR Code nicht ermitteln.', 1731959332, $e);
        }
        // --- ENDE NEU ---

        // --- 5. Absoluten URL mit Cache-Buster zurückgeben ---
        return $absoluteUrl;

    }

    /**
     * Helfer: Konvertiert HEX-Farbe (#RRGGBB) in ein RGB-Array [r, g, b].
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return [$r, $g, $b];
    }
}