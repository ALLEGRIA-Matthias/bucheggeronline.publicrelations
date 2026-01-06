<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

use TYPO3\CMS\Extbase\Service\ImageService;

use BucheggerOnline\Publicrelations\Service\ImageProcessingService;
use BucheggerOnline\Publicrelations\Utility\ContactHelper;

use BucheggerOnline\Publicrelations\Domain\Repository\TtAddressRepository;
use BucheggerOnline\Publicrelations\Domain\Repository\ClientRepository;


class ContactAjaxController
{

    /**
     * @var ContactHelper
     */
    protected $contactHelper;

    /**
     * Inject the ContactHelper
     */
    public function injectContactHelper(ContactHelper $contactHelper)
    {
        $this->contactHelper = $contactHelper;
    }

    protected $pageRepository;
    protected $imageService;
    protected $ttAddressRepository;
    protected $clientRepository;

    public function __construct()
    {
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $this->ttAddressRepository = GeneralUtility::makeInstance(TtAddressRepository::class);
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
        $this->clientRepository = GeneralUtility::makeInstance(ClientRepository::class);
    }

    public function findContacts(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $searchTerm = $queryParams['q'] ?? '';
        $includeNoMailing = isset($queryParams['nomailing']) && $queryParams['nomailing'] == '1';
        $maxResults = isset($queryParams['maxResults']) && is_numeric($queryParams['maxResults']) ? (int) $queryParams['maxResults'] : 10;
        $clientUid = $queryParams['clientUid'] ?? 0;

        // Führe die Suche durch und gib die Ergebnisse zurück
        $results = $this->searchContacts($searchTerm, $includeNoMailing, $maxResults, $clientUid);

        return new JsonResponse($results);
    }

    public function validateEmailAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $params = $parsedBody['publicrelations_newcontact'] ?? [];

        $email = (string) ($params['email'] ?? '');
        $contactType = (string) ($params['contactType'] ?? '');
        $client = isset($params['client']) ? (int) $params['client'] : null;

        $result = [
            'isValidFormat' => false,
            'hasMxRecords' => false,
            'isDuplicate' => false,
            'duplicates' => [],
        ];

        // 1. Format-Validierung
        if (!GeneralUtility::validEmail($email)) {
            return new JsonResponse($result);
        }
        $result['isValidFormat'] = true;

        // 2. MX-Record-Prüfung (kann langsam sein!)
        $domain = substr($email, strpos($email, '@') + 1);
        if (checkdnsrr($domain, 'MX')) {
            $result['hasMxRecords'] = true;
        }

        // 3. Duplikat-Prüfung im richtigen Kontext
        $foundContacts = $this->ttAddressRepository->findDuplicatesByEmail(
            $email,
            $contactType,
            $client
        );

        if (count($foundContacts) > 0) {
            $result['isDuplicate'] = true;
            foreach ($foundContacts as $contact) {
                // Bereite nur die Daten vor, die du im Frontend brauchst
                $result['duplicates'][] = [
                    'uid' => $contact->getUid(),
                    'name' => $contact->getFullName(),
                    'company' => $contact->getCompany(),
                    'editLink' => ContactHelper::generateEditLink($contact->getUid())
                ];
            }
        }

        return new JsonResponse($result);
    }

    /**
     * Search Contacts via AJAX
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function newSearchContacts(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $searchTerm = $queryParams['query'] ?? '';

        $showClients = ($queryParams['showClients'] ?? 'true') === 'true';
        $clientContactCount = $this->ttAddressRepository->countClientContactsBySearchTerm($searchTerm);

        if (strlen($searchTerm) < 3) {
            return new JsonResponse(['error' => 'Please enter at least 3 characters'], 400);
        }

        $contacts = $this->ttAddressRepository->findBySearchTerm($searchTerm, $showClients);

        // Kontakte in ein Array konvertieren und zusätzliche Daten hinzufügen
        $processedContacts = array_map(function ($contact) {
            // Basisdaten konvertieren
            $contactData = $contact;

            // Zusätzliche Felder hinzufügen
            $contactData['image'] = ContactHelper::getImageForContact($contact['uid'], $this->imageService);
            $contactData['show_link'] = ContactHelper::generateBackendLink($contact['uid']);
            $contactData['edit_link'] = ContactHelper::generateEditLink($contact['uid']);
            $contactData['contact_type'] = ContactHelper::determineContactType($contact['pid']);
            $contactClientOutput = null;
            if ($contact['client'] > 0) {
                $contactClientData = $this->clientRepository->findByUid($contact['client']);
                $contactClientOutput = $contactClientData->getShortName() ? $contactClientData->getShortName() : $contactClientData->getName();
            }
            $contactData['client_name'] = $contactClientOutput;

            // Relationen separat laden
            $contactData['categories'] = $this->ttAddressRepository->findCategoriesOfContact($contact['uid'], true);
            $contactData['contact_types'] = $this->ttAddressRepository->findContactTypesOfContact($contact['uid'], true);
            $contactData['tags'] = $this->ttAddressRepository->findTagsOfContact($contact['uid'], true);
            // $contactData['groups'] = $this->ttAddressRepository->findGroupsOfContact($contact['uid'], true);
            $contactData['social_profiles'] = $this->ttAddressRepository->findSocialProfilesOfContact($contact['uid'], true);

            return $contactData;
        }, $contacts);

        return new JsonResponse([
            'success' => true,
            'data' => $processedContacts,
            'clientContactCount' => $clientContactCount,
            'showClients' => $showClients
        ]);
    }

    protected function searchContacts(string $searchTerm, bool $includeNoMailing = false, int $maxResults = 10, string|int $clientUid = 0): array
    {
        $searchTerms = explode(' ', $searchTerm);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_address');
        $query = $queryBuilder
            ->select('uid', 'pid', 'first_name', 'middle_name', 'last_name', 'email', 'gender', 'company', 'position', 'client')
            ->from('tt_address');

        // $query = $queryBuilder
        //     ->select('uid', 'pid', 'first_name', 'middle_name', 'last_name', 'stage_name', 'email', 'gender', 'company', 'position', 'client')
        //     ->from('tt_address');

        $conditions = [
            $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
        ];

        if (!$includeNoMailing) {
            $conditions[] = $queryBuilder->expr()->eq('mailing_exclude', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }

        if (!empty($clientUid)) {
            if (is_string($clientUid)) {
                // Wenn es ein String ist, in ein Array von Integers umwandeln
                $uidArray = GeneralUtility::intExplode(',', $clientUid, true);

                // Nur wenn UIDs gefunden wurden, die IN-Bedingung hinzufügen
                if (!empty($uidArray)) {
                    $conditions[] = $queryBuilder->expr()->in(
                        'client',
                        $queryBuilder->createNamedParameter($uidArray, Connection::PARAM_INT_ARRAY)
                    );
                }
            } else {
                // Wenn es ein einzelner Integer ist (bisherige Logik)
                $conditions[] = $queryBuilder->expr()->eq('client', $queryBuilder->createNamedParameter((int) $clientUid, Connection::PARAM_INT));
            }
        } else {
            // Fallback für interne Kontakte ($clientUid ist 0 oder leer)
            $conditions[] = $queryBuilder->expr()->eq('client', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }

        $searchConstraints = [];
        foreach ($searchTerms as $term) {
            $searchConstraints[] = $queryBuilder->expr()->or(
                $queryBuilder->expr()->like('first_name', $queryBuilder->createNamedParameter("%$term%")),
                $queryBuilder->expr()->like('middle_name', $queryBuilder->createNamedParameter("%$term%")),
                $queryBuilder->expr()->like('last_name', $queryBuilder->createNamedParameter("%$term%")),
                // $queryBuilder->expr()->like('stage_name', $queryBuilder->createNamedParameter("%$term%")),
                $queryBuilder->expr()->like('company', $queryBuilder->createNamedParameter("%$term%")),
                $queryBuilder->expr()->like('email', $queryBuilder->createNamedParameter("%$term%"))
            );
        }

        $query->where(
            $queryBuilder->expr()->and(
                ...array_merge($conditions, $searchConstraints)
            )
        );

        $results = $query->setMaxResults($maxResults)->executeQuery()->fetchAllAssociative();

        $contactCategories = [];
        foreach ($results as $contact) {
            $contactId = $contact['uid'];
            $categoryTitles = $this->getCategoriesForContact($contactId);
            $contactCategories[$contactId] = $categoryTitles;
        }

        $formattedResults = array_map(function ($item) use ($contactCategories) {
            $nameParts = array_filter([$item['first_name'], $item['middle_name'], $item['last_name']]);
            $fullName = implode(' ', $nameParts);
            // $displayName = !empty($item['stage_name']) ? $item['stage_name'] : $fullName;
            $displayName = $fullName;
            $companyDisplay = !empty($item['company']) ? ' [' . $item['company'] . ']' : '';

            if (empty($displayName) && empty($companyDisplay)) {
                $displayName = $item['email'];
            } else {
                $displayName .= $companyDisplay;
            }

            $processingInstructions = [
                'width' => '50c',
                'height' => '50c',
                'cropArea' => 'default'
            ];

            // Pass the correct parameters to the ImageProcessingService
            $imagePath = GeneralUtility::makeInstance(\BucheggerOnline\Publicrelations\Service\ImageProcessingService::class)->getImage((int) $item['uid'], 'tt_address', 'image', $processingInstructions);

            return [
                'uid' => $item['uid'],
                'firstName' => $item['first_name'],
                'lastName' => $item['last_name'],
                'middleName' => $item['middle_name'],
                'email' => $item['email'],
                'gender' => $item['gender'],
                'company' => $item['company'],
                'position' => $item['position'] ?? '',
                'categories' => $contactCategories[$item['uid']] ?? [],
                'contactType' => ((int) $item['client'] > 0) ? 'Kundenkontakt' : 'interner Kontakt',
                'image' => $imagePath ?? '',
            ];
        }, $results);

        return $formattedResults;
    }

    protected function getCategoriesForContact($contactId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category_record_mm');
        $categoryTitles = $queryBuilder
            ->select('sys_category.title')
            ->from('sys_category_record_mm')
            ->join(
                'sys_category_record_mm',
                'sys_category',
                'sys_category',
                $queryBuilder->expr()->eq('sys_category_record_mm.uid_local', $queryBuilder->quoteIdentifier('sys_category.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('sys_category_record_mm.uid_foreign', $queryBuilder->createNamedParameter($contactId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_category_record_mm.tablenames', $queryBuilder->createNamedParameter('tt_address', Connection::PARAM_STR))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($categoryTitles, 'title');
    }

    protected function getPageTitleForPid($pid): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $pageTitle = $queryBuilder
            ->select('title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchFirstColumn();

        return $pageTitle['0'] ?? '';
    }

    protected function getImageForContact($contactId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $fileReferenceUid = $queryBuilder
            ->select('sys_file_reference.uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('sys_file_reference.uid_foreign', $queryBuilder->createNamedParameter($contactId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_file_reference.tablenames', $queryBuilder->createNamedParameter('tt_address', Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('sys_file_reference.fieldname', $queryBuilder->createNamedParameter('image', Connection::PARAM_STR))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchFirstColumn();

        if ($fileReferenceUid) {
            // Verarbeite das Bild und hole den Pfad zum verarbeiteten Bild
            $imageUri = $this->processImage($fileReferenceUid);
            return $imageUri;
        }

        return null;
    }

    /**
     * Verarbeitet ein Bild und gibt den Pfad zum verarbeiteten Bild zurück.
     *
     * @param int $fileReferenceUid Die UID der sys_file_reference des Bildes.
     * @return string Der öffentliche Pfad zum verarbeiteten Bild.
     */
    protected function processImage($fileReferenceUid)
    {
        if ($this->imageService === null) {
            $this->imageService = GeneralUtility::makeInstance(ImageService::class);
        }

        // Hole die FileReference-Objekt
        $fileReference = $this->imageService->getImage((string) $fileReferenceUid, null, true);

        if ($cropString === null && $fileReference->hasProperty('crop') && $fileReference->getProperty('crop')) {
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
        $processedImage = $this->imageService->applyProcessingInstructions($fileReference, $processingInstructions);

        // Hole den öffentlichen Pfad zum verarbeiteten Bild
        $imageUri = $this->imageService->getImageUri($processedImage);

        return $imageUri;
    }

    /**
     * Empfängt die temporäre Datei und das Mapping, validiert jede Zeile
     * und gibt das Ergebnis als JSON zurück.
     */
    public function validateImportAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();
        $tempFilePath = $params['tempFilePath'] ?? '';
        $mapping = $params['mapping'] ?? [];
        $contactType = $params['contactType'] ?? 'internal';
        $client = (int) ($params['client'] ?? 0);
        $additionalCategories = $params['additionalCategories'] ?? '';

        if (!file_exists($tempFilePath) || empty($mapping)) {
            return new JsonResponse(['error' => 'Missing data'], 400);
        }

        $fileHandle = fopen($tempFilePath, 'r');
        $headers = fgetcsv($fileHandle); // Header-Zeile lesen und überspringen

        $results = [];
        while (($row = fgetcsv($fileHandle)) !== false) {
            $rowData = array_combine($headers, $row);
            $contactData = [];
            $descriptionData = [];
            $rowStatus = 'ok';
            $rowMessage = 'OK';

            // 1. Mapping anwenden
            foreach ($mapping as $fileHeader => $dbField) {
                if (empty($dbField))
                    continue; // Nicht importieren

                $value = trim($rowData[$fileHeader] ?? '');

                switch ($dbField) {
                    case 'email':
                        $value = strtolower($value);
                        break;
                    case 'gender':
                        $value = $this->mapGender($value);
                        break;
                    case 'birthday':
                        $value = $this->convertToTimestamp($value);
                        break;
                    case 'categories':
                    case 'contact_types':
                        // Extrahiere die UIDs und mache einen Komma-String daraus.
                        // Der DataHandler braucht für MM-Relationen einen String.
                        $value = implode(',', $this->parseCategoryUids($value));
                        break;
                }

                if ($dbField === 'description') {
                    $descriptionData[] = $fileHeader . ': ' . $value;
                } else {
                    $contactData[$dbField] = $value;
                }
            }
            if (!empty($descriptionData)) {
                $contactData['description'] = implode("\n", $descriptionData);
            }

            // 2. Grundlegende Validierung
            if (empty($contactData['last_name']) && empty($contactData['first_name'])) {
                $rowStatus = 'error';
                $rowMessage = 'Fehler: Name fehlt';
            } elseif (empty($contactData['email']) || !GeneralUtility::validEmail($contactData['email'])) {
                $rowStatus = 'error';
                $rowMessage = 'Fehler: E-Mail ungültig';
            } else {
                // 3. Duplikat-Prüfung
                $duplicates = $this->ttAddressRepository->findStrictDuplicates(
                    $contactData['email'],
                    $contactData['first_name'],
                    $contactData['last_name'],
                    $contactType,
                    $client
                );
                if ($duplicates->count() > 0) {
                    $rowStatus = 'duplicate';
                    $rowMessage = 'Warnung: Duplikat';

                    $existingContact = $duplicates->getFirst(); // Hole das erste gefundene Duplikat
                    $diff = [];
                    $ignoreInDiff = ['categories', 'contact_types'];
                    if ($existingContact) {
                        foreach ($contactData as $field => $csvValue) {
                            // Ignoriere die Felder aus der Liste
                            if (in_array($field, $ignoreInDiff, true)) {
                                continue;
                            }

                            $getter = 'get' . GeneralUtility::underscoredToUpperCamelCase($field);
                            if (method_exists($existingContact, $getter)) {
                                $dbValue = $existingContact->$getter();
                                // Sicherer Vergleich als String
                                if ((string) $csvValue !== (string) $dbValue) {
                                    $diff[$field] = [
                                        'csv' => htmlspecialchars($csvValue, ENT_QUOTES, 'UTF-8'),
                                        'db' => htmlspecialchars((string) ($dbValue ?: '[leer]'), ENT_QUOTES, 'UTF-8')
                                    ];
                                }
                            }
                        }
                    }
                    if (!empty($diff)) {
                        $rowMessage .= ' mit Daten-Unterschieden';
                    }
                }
            }

            $results[] = [
                'data' => $rowData,
                'status' => $rowStatus,
                'message' => $rowMessage,
                'diff' => $diff ?? []
            ];
        }
        fclose($fileHandle);

        $resultCacheId = 'import_' . uniqid();
        $resultCachePath = Environment::getVarPath() . '/transient/' . $resultCacheId . '.cache';
        $dataToCache = [
            'rows' => $results,
            'headers' => $headers,
            'mapping' => $mapping,
            'originalFilePath' => $tempFilePath,
            'context' => ['contactType' => $contactType, 'client' => $client],
            'additionalCategories' => $additionalCategories
        ];
        GeneralUtility::writeFile($resultCachePath, serialize($dataToCache));

        return new JsonResponse([
            'rows' => $results,
            'headers' => $headers,
            'resultCacheId' => $resultCacheId // Diese ID brauchen wir im nächsten Schritt
        ]);
    }

    /**
     * Führt den finalen Import basierend auf der Benutzerauswahl aus der Vorschau aus.
     */
    public function executeImportAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();
        $resultCacheId = $params['resultCacheId'] ?? '';
        $importRows = $params['importRows'] ?? []; // Die Indizes der angehakten Zeilen
        $updateFields = $params['updateFields'] ?? [];

        $resultCachePath = Environment::getVarPath() . '/transient/' . $resultCacheId . '.cache';

        if (!file_exists($resultCachePath) || empty($importRows)) {
            return new JsonResponse(['success' => false, 'message' => 'Fehlende Daten für den Import.'], 400);
        }

        $cachedData = unserialize(file_get_contents($resultCachePath));
        $rowsToProcess = $cachedData['rows'];
        $context = $cachedData['context'];
        $additionalCategories = $cachedData['additionalCategories'] ?? '';

        $dataMap = [];
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($importRows as $rowIndex) {
            $rowInfo = $rowsToProcess[$rowIndex];
            $contactData = $this->processCsvRow($rowInfo['data'], $cachedData['mapping']);

            if ($rowInfo['status'] === 'duplicate') {
                $duplicates = $this->ttAddressRepository->findStrictDuplicates(
                    $contactData['email'],
                    $contactData['first_name'],
                    $contactData['last_name'],
                    $context['contactType'],
                    $context['client']
                );
                $existingContact = $duplicates->getFirst();

                if ($existingContact) {
                    $uidToUpdate = $existingContact->getUid();
                    $updateData = [];

                    // --- SCHRITT 1: KATEGORIEN IMMER ZUSAMMENFÜHREN ---
                    $updateData['categories'] = $this->mergeCategories(
                        $existingContact->getCategories(),
                        $contactData['categories'] ?? '',
                        $additionalCategories
                    );
                    $updateData['contact_types'] = $this->mergeCategories(
                        $existingContact->getContactTypes(),
                        $contactData['contact_types'] ?? ''
                    );

                    // --- SCHRITT 2: OPTIONALE FELD-UPDATES HINZUFÜGEN ---
                    $fieldsToUpdateForThisRow = $updateFields[$rowIndex] ?? [];
                    foreach ($fieldsToUpdateForThisRow as $field => $value) {
                        if (isset($contactData[$field])) {
                            $updateData[$field] = $contactData[$field];
                        }
                    }

                    // Füge die Update-Daten zum DataMap hinzu.
                    // Dies passiert jetzt immer, mindestens mit den zusammengeführten Kategorien.
                    $dataMap['tt_address'][$uidToUpdate] = $updateData;
                    $dataMap['tt_address'][$uidToUpdate]['pid'] = $existingContact->getPid();
                    $updatedCount++;
                }
            } elseif ($rowInfo['status'] === 'ok') {
                // --- CREATE-LOGIK (unverändert) ---
                $pid = 4; // WICHTIG: Ersetze dies durch die korrekte PID!
                $tempId = 'NEW_' . $rowIndex;

                $contactData['categories'] = $this->mergeCategories(
                    $contactData['categories'] ?? '',
                    $additionalCategories
                );

                $dataMap['tt_address'][$tempId] = $contactData;
                $dataMap['tt_address'][$tempId]['pid'] = $pid;
                $dataMap['tt_address'][$tempId]['client'] = ($context['contactType'] === 'client') ? $context['client'] : 0;
                $createdCount++;
            }
        }

        // Führe den Import durch, wenn es etwas zu tun gibt
        if (!empty($dataMap)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($dataMap, []);
            $dataHandler->process_datamap();
            if (!empty($dataHandler->errorLog)) {
                return new JsonResponse(['success' => false, 'message' => 'Fehler beim Speichern.', 'errors' => $dataHandler->errorLog], 500);
            }
        }

        @unlink($cachedData['originalFilePath']);
        @unlink($resultCachePath);

        return new JsonResponse([
            'success' => true,
            'message' => sprintf('Import erfolgreich! %d Kontakte erstellt, %d aktualisiert.', $createdCount, $updatedCount)
        ]);
    }

    /**
     * Hilfsfunktion zum Zusammenführen von bestehenden und neuen Kategorie-UIDs.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage|null $existingCategories
     * @param string $csvCategoryUids Komma-getrennte UIDs aus der CSV
     * @return string Finaler, bereinigter, Komma-getrennter String von UIDs
     */
    private function mergeCategories(...$categorySources): string
    {
        $allUids = [];
        foreach ($categorySources as $source) {
            if ($source instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
                foreach ($source as $category) {
                    $allUids[] = $category->getUid();
                }
            } elseif (is_string($source) && $source !== '') {
                $allUids = array_merge($allUids, GeneralUtility::intExplode(',', $source));
            } elseif (is_array($source)) {
                $allUids = array_merge($allUids, $source);
            }
        }

        $finalUids = array_unique($allUids);
        return implode(',', $finalUids);
    }

    /**
     * Hilfsmethode: Wendet Mapping und Konvertierungen auf eine einzelne CSV-Zeile an.
     */
    private function processCsvRow(array $rowData, array $mapping): array
    {
        $contactData = [];
        $descriptionData = [];
        foreach ($mapping as $fileHeader => $dbField) {
            if (empty($dbField))
                continue;

            $value = trim($rowData[$fileHeader] ?? '');

            switch ($dbField) {
                case 'email':
                    $value = strtolower($value);
                    break;
                case 'gender':
                    $value = $this->mapGender($value);
                    break;
                case 'birthday':
                    $value = $this->convertToTimestamp($value);
                    break;
                case 'categories':
                case 'contact_types':
                    $categoryUids = $this->parseAndResolveCategories($value);
                    $value = implode(',', $categoryUids);
                    break;
            }

            if ($dbField === 'description') {
                $descriptionData[] = $fileHeader . ': ' . $value;
            } else {
                $contactData[$dbField] = $value;
            }
        }
        if (!empty($descriptionData)) {
            $contactData['description'] = implode("\n", $descriptionData);
        }
        return $contactData;
    }

    /**
     * Hilfsmethode: Parst Kategorie-Strings, findet die UIDs und erstellt Kategorien bei Bedarf.
     */
    private function parseAndResolveCategories(string $categoryInput): array
    {
        // Diese Methode implementiert die Logik, um z.B. "Presse [123]" zu parsen
        // ODER "Presse", die UID nachzuschlagen und die Kategorie ggf. anzulegen.
        // Aus Vereinfachungsgründen hier nur die UID-Extraktion, die wir schon hatten:
        preg_match_all('/\[(\d+)\]/', $categoryInput, $matches);
        return !empty($matches[1]) ? array_map('intval', $matches[1]) : [];
    }

    /**
     * Übersetzt verschiedene gängige Bezeichnungen für Geschlechter
     * in die Datenbankwerte 'm', 'f', 'v' oder '' (leer für unbekannt).
     * Die Prüfung ignoriert Groß-/Kleinschreibung und Leerzeichen.
     *
     * @param string $input Die Eingabe aus der CSV-Datei (z.B. "Herr", "weiblich", "Male")
     * @return string Gibt 'm', 'f', 'v' oder '' zurück.
     */
    private function mapGender(string $input): string
    {
        // 1. Eingabe bereinigen: Alles in Kleinbuchstaben und ohne Leerzeichen am Rand.
        $sanitizedInput = strtolower(trim($input));

        if ($sanitizedInput === '') {
            return '';
        }

        // 2. Das "Wörterbuch": Hier definieren wir, welche Eingaben was bedeuten.
        $genderMap = [
            'm' => ['herr', 'herrn', 'mann', 'männlich', 'male', 'mr', 'mr.', 'mister', 'm'],
            'f' => ['frau', 'weiblich', 'female', 'mrs', 'mrs.', 'ms', 'ms.', 'miss', 'w'],
            'v' => ['divers', 'diverse', 'd', 'v', 'x', 'non-binary', 'nonbinary', 'familie', 'frau und herrn', 'frau & herrn'],
        ];

        // 3. Das Wörterbuch durchgehen und den passenden Wert zurückgeben.
        foreach ($genderMap as $dbValue => $aliases) {
            if (in_array($sanitizedInput, $aliases, true)) {
                return $dbValue;
            }
        }

        // 4. Wenn nichts gefunden wurde, einen leeren String zurückgeben.
        return '';
    }

    /**
     * Wandelt einen Datumsstring in einen Unix-Timestamp um.
     * Gibt 0 zurück, wenn das Format ungültig ist.
     * @param string $dateString z.B. "31.12.1990" oder "1990-12-31"
     * @return int
     */
    private function convertToTimestamp(string $dateString): int
    {
        if (empty($dateString)) {
            return 0;
        }
        // strtotime ist sehr flexibel und erkennt die meisten gängigen Datumsformate.
        $timestamp = strtotime($dateString);
        return $timestamp === false ? 0 : $timestamp;
    }

    /**
     * Extrahiert alle Zahlen, die in eckigen Klammern stehen, aus einem String.
     * @param string $categoryString z.B. "Titel [123], Anderer Titel [456]"
     * @return array Ein Array von Integer-UIDs, z.B. [123, 456]
     */
    private function parseCategoryUids(string $categoryString): array
    {
        preg_match_all('/\[(\d+)\]/', $categoryString, $matches);
        if (!empty($matches[1])) {
            // Konvertiere die gefundenen String-Zahlen in echte Integer
            return array_map('intval', $matches[1]);
        }
        return [];
    }

}