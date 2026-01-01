<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

class ImageProcessingService
{
    protected ImageService $imageService;

    public function __construct()
    {
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    /**
     * Lädt und verarbeitet ein Bild aus sys_file_reference und gibt den öffentlichen URI zurück.
     *
     * @param int    $uid                     UID der foreign-Relation (z. B. tt_content.uid)
     * @param string $table                   Tablename (z. B. 'tt_content')
     * @param string $field                   Fieldname (z. B. 'image')
     * @param array  $processingInstructions  Breite, Höhe, Crop-Variant etc.
     * @return string|null                    URI des verarbeiteten Bildes oder null, wenn nicht gefunden
     */
    public function getImage(int $uid, string $table, string $field, array $processingInstructions = []): ?string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $fileReferenceUid = (int) $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($table, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter($field, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if ($fileReferenceUid > 0) {
            return $this->processImage($fileReferenceUid, $processingInstructions);
        }

        return null;
    }

    /**
     * Verarbeitet die Bild-Referenz und liefert den öffentlichen URI.
     *
     * @param int   $fileReferenceUid   UID aus sys_file_reference
     * @param array $processing         ['width'=>…, 'height'=>…, 'cropVariant'=>… usw.]
     * @return string
     */
    protected function processImage(int $fileReferenceUid, array $processing): string
    {
        /** @var FileReference $fileReference */
        $fileReference = $this->imageService->getImage((string) $fileReferenceUid, null, true);

        // Crop-Daten holen (kann String oder Array sein)
        $cropData = $fileReference->hasProperty('crop')
            ? $fileReference->getProperty('crop')
            : null;

        // Immer einen String für CropVariantCollection übergeben
        if (is_array($cropData)) {
            $cropString = json_encode($cropData);
        } elseif (is_string($cropData) && $cropData !== '') {
            $cropString = $cropData;
        } else {
            $cropString = '';
        }

        // Crop-Collection anlegen und Variante wählen
        $cropVariantCollection = CropVariantCollection::create($cropString);
        $variant = $processing['cropVariant'] ?? 'default';
        $cropArea = $cropVariantCollection->getCropArea($variant);

        // Processing-Instructions mit Fallbacks auf null
        $instructions = [
            'width' => $processing['width'] ?? null,
            'height' => $processing['height'] ?? null,
            'minWidth' => $processing['minWidth'] ?? null,
            'minHeight' => $processing['minHeight'] ?? null,
            'maxWidth' => $processing['maxWidth'] ?? null,
            'maxHeight' => $processing['maxHeight'] ?? null,
            'crop' => $cropArea->isEmpty()
                ? null
                : $cropArea->makeAbsoluteBasedOnFile($fileReference),
        ];

        // Bild verarbeiten und URI zurückgeben
        $processed = $this->imageService->applyProcessingInstructions($fileReference, $instructions);
        return $this->imageService->getImageUri($processed);
    }
}
