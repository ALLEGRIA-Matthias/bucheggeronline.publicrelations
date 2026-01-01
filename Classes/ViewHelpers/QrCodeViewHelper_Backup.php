<?php
namespace BucheggerOnline\Publicrelations\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Alignment\LabelAlignmentLeft;
use Endroid\QrCode\Label\Alignment\LabelAlignmentRight;
use Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;


class QrCodeViewHelperBackup extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('data', 'string', 'Die Daten für den QR-Code', false, null);
        $this->registerArgument('size', 'int', 'Die Größe des QR-Codes in Pixeln', false, 300);
        $this->registerArgument('margin', 'int', 'Der Rand des QR-Codes in Pixeln', false, 0);
        $this->registerArgument('encoding', 'string', 'Das Encoding des QR-Codes', false, 'UTF-8');
        $this->registerArgument('errorCorrectionLevel', 'string', 'Das Fehlerkorrekturlevel', false, 'high');
        $this->registerArgument('color', 'string', 'Die Vordergrundfarbe', false, '#000000');
        $this->registerArgument('bgColor', 'string', 'Die Hintergrundfarbe', false, '#ffffff');
        $this->registerArgument('label', 'string', 'Das Label', false, null);
        $this->registerArgument('labelColor', 'string', 'Die Schriftfarbe', false, '#000000');
        $this->registerArgument('labelFont', 'string', 'Die Schriftart des Labels', false, '');
        $this->registerArgument('labelAlign', 'string', 'Die Ausrichtung des Labels', false, 'center');
        $this->registerArgument('labelMargin', 'string', 'Rand des Labels, im CSS-Stil (z.B. "10" oder "10 15 10 15")', false, '0');
        $this->registerArgument('logoPath', 'string', 'Der Pfad des Logos', false, null);
        $this->registerArgument('logoSize', 'int', 'Die Größe des Logos in Pixel', false, 50);
        $this->registerArgument('forceImgTag', 'bool', 'Gibt an, ob ein img-Tag erzwungen werden soll', false, false);
        // Füge hier weitere Argumente wie für Logo-Integration etc. hinzu
    }

    public function render()
    {
        $data = $this->arguments['data'] ?? $this->renderChildren();
        $size = $this->arguments['size'];
        $margin = $this->arguments['margin'];
        $encoding = new Encoding($this->arguments['encoding']);
        $errorCorrectionLevel = $this->getErrorCorrectionLevel($this->arguments['errorCorrectionLevel']);
        $color = $this->parseColor($this->arguments['color']);
        $bgColor = $this->parseColor($this->arguments['bgColor']);
        $label = $this->arguments['label'];
        $labelColor = $this->parseColor($this->arguments['labelColor']);
        $labelMargin = $this->parseMargin($this->arguments['labelMargin']);
        $labelAlign = $this->getLabelAlignment($this->arguments['labelAlign']);
        $logoPath = $this->arguments['logoPath'];
        $logoSize = $this->arguments['logoSize'];

        $fileName = $this->sanitizeFileName($data) . '.png';

        $writer = new PngWriter();

        $qrCode = QrCode::create($data)
            ->setSize($size)
            ->setMargin($margin)
            ->setEncoding($encoding)
            ->setErrorCorrectionLevel($errorCorrectionLevel)
            // ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor($color)
            ->setBackgroundColor($bgColor);

        // Logo hinzufügen, falls Pfad angegeben
        if (!empty($logoPath)) {
            // Create generic logo
            $logo = Logo::create(__DIR__ . '/assets/symfony.png')
                ->setResizeToWidth($logoSize)
                ->setPunchoutBackground(true);
        } else {
            $logo = null;
        }

        // Label hinzufügen, falls angegeben
        if (!empty($label)) {
            // Create generic label
            $label = Label::create($label)
                ->setTextColor($labelColor)
                ->setAlignment($labelAlign)
                ->setMargin($labelMargin);
        }

        $dataUri = $writer->write($qrCode, $logo, $label)->getDataUri();

        // Extrahiere den Base64-kodierten Teil der Data-URI
        [, $base64Data] = explode(',', $dataUri);
        $imageData = base64_decode($base64Data);

        // QR-Code in temporärer Datei speichern
        $tempFilePath = GeneralUtility::tempnam('qr_code_') . '.png';
        GeneralUtility::writeFile($tempFilePath, $imageData);

        // Den Dateispeicher (File Storage) und den Zielordner in TYPO3 definieren
        $storageUid = 4; // Die UID deines Dateispeichers
        $targetFolder = 'qrcodes/'; // Der Pfad innerhalb deines Dateispeichers

        /** @var \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class);
        $storage = $storageRepository->findByUid($storageUid);
        $targetFolder = $storage->getFolder($targetFolder);

        GeneralUtility::makeInstance(ResourceFactory::class);

        // Datei dem TYPO3 Dateisystem hinzufügen
        $newFileName = $fileName; // Name der Datei im TYPO3 Dateisystem
        $fileObject = $storage->addFile(
            $tempFilePath,
            $targetFolder,
            $newFileName,
            DuplicationBehavior::REPLACE
        );

        // Aufräumen der temporären Datei, prüfe ob die Datei existiert
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }

        // URL zum QR-Code erzeugen
        $qrCodeUrl = $fileObject->getPublicUrl();

        // Manuelle Domain-Konfiguration
        $baseUrl = 'https://www.allegria.at/';

        // Vollständige URL zusammensetzen
        $fullUrl = $baseUrl . ltrim($qrCodeUrl, '/');

        return $fullUrl;
    }

    private function getErrorCorrectionLevel($level)
    {
        switch (strtolower($level)) {
            case 'low':
                return new ErrorCorrectionLevelLow();
            case 'medium':
                return new ErrorCorrectionLevelMedium();
            case 'quartile':
                return new ErrorCorrectionLevelQuartile();
            case 'high':
            default:
                return new ErrorCorrectionLevelHigh();
        }
    }

    private function getLabelAlignment(string $alignment)
    {
        switch (strtolower($alignment)) {
            case 'left':
                return new LabelAlignmentLeft();
            case 'right':
                return new LabelAlignmentRight();
            case 'interface':
                return new LabelAlignmentInterface();
            default:
                return new LabelAlignmentCenter();
        }
    }

    private function parseColor(string $colorSpec): Color
    {
        if (preg_match('/^#([a-f0-9]{6})([a-f0-9]{2})?$/i', $colorSpec, $matches)) {
            // Hex-Farbcode
            $red = hexdec(substr($matches[1], 0, 2));
            $green = hexdec(substr($matches[1], 2, 2));
            $blue = hexdec(substr($matches[1], 4, 2));
            $alpha = isset($matches[2]) ? hexdec($matches[2]) : 0;
        } elseif (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)(??,\s*(\d+(??\.\d+)?))?\)/', $colorSpec, $matches)) {
            // RGBA-Farbcode
            $red = (int) $matches[1];
            $green = (int) $matches[2];
            $blue = (int) $matches[3];
            $alpha = isset($matches[4]) ? (int) ($matches[4] * 127) : 0; // Alpha in 0-127 umrechnen
        } else {
            throw new \InvalidArgumentException("Invalid color specification: $colorSpec");
        }

        return new Color($red, $green, $blue, $alpha);
    }

    private function parseMargin(string $marginSpec): Margin
    {
        $margins = array_map('intval', explode(' ', $marginSpec));
        switch (count($margins)) {
            case 1:
                return new Margin($margins[0], $margins[0], $margins[0], $margins[0]);
            case 2:
                return new Margin($margins[0], $margins[1], $margins[0], $margins[1]);
            case 3:
                return new Margin($margins[0], $margins[1], $margins[2], $margins[1]);
            case 4:
                return new Margin($margins[0], $margins[1], $margins[2], $margins[3]);
            default:
                throw new \InvalidArgumentException("Invalid margin specification: $marginSpec");
        }
    }

    protected function sanitizeFileName($data)
    {
        // Beispiel: Ersetze ungültige Zeichen durch Unterstrich und nutze SHA-256 Hash
        $invalidChars = array_merge(
            array_map('chr', range(0, 31)),
            ["<", ">", ":", "\"", "/", "\\", "|", "?", "*"]
        );

        $cleanData = str_replace($invalidChars, '_', $data);
        $hash = hash('sha256', $cleanData);

        return 'qr_' . substr($hash, 0, 10); // Kürze den Hash für den Dateinamen
    }

}
