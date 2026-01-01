<?php
namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;

use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;

use Doctrine\DBAL\ParameterType;

class ContactHelper
{
    /**
     * Process a single contact and enrich it with additional data
     *
     * @param array $contact The contact data
     * @param ImageService $imageService The TYPO3 ImageService
     * @param callable $generateBackendLinkCallback Callback to generate backend link
     * @return array The processed contact
     */
    public static function processContact(array $contact, ImageService $imageService, callable $generateBackendLinkCallback): array
    {
        $contact['image'] = self::getImageForContact($contact['uid'], $imageService);
        $contact['show_link'] = $generateBackendLinkCallback($contact['uid']);
        // $contact['tags'] = self::getTagsForContact($contact['uid']);
        // $contact['groups'] = self::getGroupsForContact($contact['uid']);
        // $contact['categories'] = self::getCategoriesForContact($contact['uid']);
        $contact['contact_type'] = self::determineContactType($contact['pid']);
        return $contact;
    }

    /**
     * Retrieve the contact type based on PID
     *
     * @param int $pid The PID of the contact
     * @return string The contact type
     */
    public static function determineContactType(int $pid): string
    {
        $hierarchies = [
            'Presse' => [6],
            'Promi' => [40],
            'Verteiler' => [44],
            'Kundenkontakt' => [50],
        ];

        // Get all parent pages for the given PID
        $allPids = self::getAllParentPids($pid);

        foreach ($hierarchies as $type => $validPids) {
            if (array_intersect($allPids, $validPids)) {
                return $type;
            }
        }

        return 'Undefiniert';
    }

    /**
     * Recursively retrieve all parent PIDs for a given page ID
     *
     * @param int $pid The starting page ID
     * @return array All parent PIDs including the given PID
     */
    protected static function getAllParentPids(int $pid): array
    {
        $pids = [$pid];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        while ($pid > 0) {
            $parentPid = $queryBuilder
                ->select('pid')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, ParameterType::INTEGER)))
                ->executeQuery()
                ->fetchOne();

            if ($parentPid && $parentPid != 0) {
                $pids[] = $parentPid;
                $pid = $parentPid;
            } else {
                break;
            }
        }

        return $pids;
    }

    /**
     * Retrieve the image path for a contact
     *
     * @param int $contactId The contact UID
     * @param ImageService $imageService The TYPO3 ImageService
     * @return string|null The image path or a placeholder
     */
    public static function getImageForContact(int $contactId, ImageService $imageService): ?string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $fileReferenceUid = $queryBuilder
            ->select('sys_file_reference.uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('sys_file_reference.uid_foreign', $queryBuilder->createNamedParameter($contactId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_file_reference.tablenames', $queryBuilder->createNamedParameter('tt_address', ParameterType::STRING)),
                $queryBuilder->expr()->eq('sys_file_reference.fieldname', $queryBuilder->createNamedParameter('image', ParameterType::STRING))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if ($fileReferenceUid) {
            return self::processImage($fileReferenceUid, $imageService);
        }

        return null; // Placeholder for contacts without image
    }

    /**
     * Verarbeitet ein Bild und gibt den Pfad zum verarbeiteten Bild zurück.
     *
     * @param int $fileReferenceUid Die UID der sys_file_reference des Bildes.
     * @param ImageService $imageService The TYPO3 ImageService
     * @return string Der öffentliche Pfad zum verarbeiteten Bild.
     */
    protected static function processImage($fileReferenceUid, ImageService $imageService): string
    {
        // Hole die FileReference-Objekt
        $fileReference = $imageService->getImage((string) $fileReferenceUid, null, true);

        $cropString = null;
        if ($fileReference->hasProperty('crop') && $fileReference->getProperty('crop')) {
            $cropString = $fileReference->getProperty('crop');
        }

        // CropVariantCollection needs a string, but this VH could also receive an array
        if (is_array($cropString)) {
            $cropString = json_encode($cropString);
        }

        $cropVariantCollection = CropVariantCollection::create((string) $cropString);
        $cropVariant = 'default';
        $cropArea = $cropVariantCollection->getCropArea($cropVariant);

        // Definiere die Verarbeitungsoptionen
        $processingInstructions = [
            'width' => '50c',
            'height' => '50c',
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($fileReference),
        ];

        // Verarbeite das Bild
        $processedImage = $imageService->applyProcessingInstructions($fileReference, $processingInstructions);

        // Hole den öffentlichen Pfad zum verarbeiteten Bild
        return $imageService->getImageUri($processedImage);
    }

    /**
     * Retrieve tags for a contact
     *
     * @param int $contactId The contact UID
     * @return array The tag titles
     */
    public static function getTagsForContact(int $contactId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_tag_record_mm');

        $tags = $queryBuilder
            ->select('sys_tag.title')
            ->from('sys_tag_record_mm')
            ->join(
                'sys_tag_record_mm',
                'sys_tag',
                'sys_tag',
                $queryBuilder->expr()->eq('sys_tag_record_mm.uid_local', 'sys_tag.uid')
            )
            ->where(
                $queryBuilder->expr()->eq('sys_tag_record_mm.uid_foreign', $queryBuilder->createNamedParameter($contactId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_tag_record_mm.tablenames', $queryBuilder->createNamedParameter('tt_address', ParameterType::STRING))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($tags, 'title');
    }

    /**
     * Retrieve groups for a contact
     *
     * @param int $contactId The contact UID
     * @return array The group titles
     */
    public static function getGroupsForContact(int $contactId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_accontacts_mm');

        $groups = $queryBuilder
            ->select('tx_accontacts_domain_model_group.name')
            ->from('tx_accontacts_mm')
            ->join(
                'tx_accontacts_mm',
                'tx_accontacts_domain_model_group',
                'tx_accontacts_domain_model_group',
                $queryBuilder->expr()->eq('tx_accontacts_mm.uid_local', 'tx_accontacts_domain_model_group.uid')
            )
            ->where(
                $queryBuilder->expr()->eq('tx_accontacts_mm.uid_foreign', $queryBuilder->createNamedParameter($contactId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('tx_accontacts_mm.tablenames', $queryBuilder->createNamedParameter('tt_address', ParameterType::STRING)),
                $queryBuilder->expr()->eq('tx_accontacts_mm.fieldname', $queryBuilder->createNamedParameter('groups', ParameterType::STRING))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($groups, 'name');
    }

    /**
     * Retrieve categories for a contact
     *
     * @param int $contactId The contact UID
     * @return array The category titles
     */
    public static function getCategoriesForContact(int $contactId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category_record_mm');

        $categories = $queryBuilder
            ->select('sys_category.title')
            ->from('sys_category_record_mm')
            ->join(
                'sys_category_record_mm',
                'sys_category',
                'sys_category',
                $queryBuilder->expr()->eq('sys_category_record_mm.uid_local', 'sys_category.uid')
            )
            ->where(
                $queryBuilder->expr()->eq('sys_category_record_mm.uid_foreign', $queryBuilder->createNamedParameter($contactId, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_category_record_mm.tablenames', $queryBuilder->createNamedParameter('tt_address', ParameterType::STRING))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($categories, 'title');
    }

    /**
     * Generate a backend link for the contact
     *
     * @param int $contactId The contact UID
     * @return string The generated backend link
     */
    public static function generateBackendLink(int $contactId): string
    {
        $uriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);

        $routeName = 'allegria_contacts';

        $parameters = [
            'contact' => $contactId,
            'action' => 'show',
            'controller' => 'Contact'
        ];

        return (string) $uriBuilder->buildUriFromRoute($routeName, $parameters);
    }

    /**
     * Generiert einen Backend-Link zum Bearbeiten eines tt_address Datensatzes.
     *
     * @param int $uid Die UID des tt_address Datensatzes
     * @return string Die generierte URL
     */
    public static function generateEditLink(int $uid): string
    {
        // 1. Den UriBuilder für das Backend holen
        $uriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);

        // 2. Die 'returnUrl' generieren, die auf die indexAction deines Moduls zeigt.
        //    Ersetze 'dein_modul_signature' mit der Signatur deines Backend-Moduls,
        //    z.B. 'web_ListExt' oder was auch immer in deiner Konfiguration steht.
        $returnUrl = (string) $uriBuilder->buildUriFromRoute('allegria_contacts');

        // 3. Den eigentlichen Edit-Link erstellen, der auf den Core-Bearbeitungs-Handler zeigt.
        //    Das ist der Standardweg in TYPO3, um einen beliebigen Datensatz zu bearbeiten.
        $editUrl = (string) $uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                'tt_address' => [
                    $uid => 'edit'
                ]
            ],
            'returnUrl' => $returnUrl
        ]);

        return $editUrl;
    }

    /**
     * Validates contact data and checks for duplicates within a given context.
     *
     * @param array $contactData Raw contact data (e.g., ['email' => 'Test@test.com', 'firstName' => 'John'])
     * @param string $contactType Context ('internal' or 'client')
     * @param int $clientId The client UID if context is 'client'
     * @return array A structured result array
     */
    public static function validateAndCheckContact(array $contactData, string $contactType, int $clientId = 0): array
    {
        $result = [
            'isValid' => true,
            'isDuplicate' => false,
            'message' => 'OK',
            'duplicates' => [],
            'sanitizedData' => $contactData,
            'diff' => [], // To hold differences for importer
        ];

        // 1. Sanitize Email
        if (empty($contactData['email']) || !filter_var($contactData['email'], FILTER_VALIDATE_EMAIL)) {
            $result['isValid'] = false;
            $result['message'] = 'Ungültige E-Mail-Adresse.';
            return $result;
        }
        $email = strtolower(trim($contactData['email']));
        $result['sanitizedData']['email'] = $email;

        // 2. Check for duplicates
        $duplicates = self::findDuplicates($email, $contactType, $clientId);

        if (!empty($duplicates)) {
            $result['isDuplicate'] = true;
            $result['message'] = 'Duplikat gefunden.';
            $result['duplicates'] = $duplicates;

            // Optional: For the importer, we can pre-calculate the diff
            // This is a simplified example. You would compare all mapped fields.
            $mainDuplicate = $duplicates[0]; // Assuming the first is the primary one
            $diff = [];
            foreach ($result['sanitizedData'] as $key => $value) {
                if (isset($mainDuplicate[$key]) && !empty($value) && $mainDuplicate[$key] != $value) {
                    $diff[$key] = [
                        'csv' => $value,
                        'db' => $mainDuplicate[$key]
                    ];
                }
            }
            $result['diff'] = $diff;
        }

        return $result;
    }

    /**
     * Finds duplicate contacts by email in a specific context.
     *
     * @param string $email The email to search for (already lowercased)
     * @param string $contactType 'internal' or 'client'
     * @param int $clientId
     * @return array Array of duplicate contacts found
     */
    private static function findDuplicates(string $email, string $contactType, int $clientId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_address');
        $expr = $queryBuilder->expr();

        $query = $queryBuilder
            ->select('uid', 'first_name', 'last_name', 'company', 'client')
            ->from('tt_address')
            ->where(
                $expr->eq('email', $queryBuilder->createNamedParameter($email, ParameterType::STRING)),
                $expr->eq('deleted', 0)
            );

        if ($contactType === 'client') {
            $query->andWhere($expr->eq('client', $queryBuilder->createNamedParameter($clientId, ParameterType::INTEGER)));
        } else {
            // For internal contacts, we might assume client is 0 or NULL
            $query->andWhere(
                $expr->or(
                    $expr->eq('client', 0),
                    $expr->isNull('client')
                )
            );
        }

        $foundContacts = $query->executeQuery()->fetchAllAssociative();

        // Enrich with edit links for the frontend
        foreach ($foundContacts as &$contact) {
            $contact['editLink'] = self::generateEditLink($contact['uid']);
            $contact['name'] = trim($contact['first_name'] . ' ' . $contact['last_name']);
        }

        return $foundContacts;
    }
}
