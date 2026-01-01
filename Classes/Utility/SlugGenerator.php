<?php
namespace BucheggerOnline\Publicrelations\Utility;

use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\LogManager;
use Psr\Log\LoggerInterface;

/**
 * Class to register category configurations.
 */
class SlugGenerator
{
    protected function removeSlashes(string $string) {

      $string = str_replace(' / ', ' ', $string);
      $string = str_replace('/ ', ' ', $string);
      $string = str_replace(' /', ' ', $string);

      return str_replace('/', ' ', $string);
    }

    /**
     * Removes slashes or replaces them with spaces for individual slug parts.
     */
    protected function cleanSlugPart(string $string): string
    {
        $string = str_replace(' / ', ' ', $string);
        $string = str_replace('/ ', ' ', $string);
        $string = str_replace(' /', ' ', $string);
        // Ersetzt einzelne Slashes durch Leerzeichen, die dann vom SlugHelper zu Bindestrichen gemacht werden.
        // Wenn Slashes im einzelnen Teil erhalten bleiben sollen, diese Zeile anpassen oder entfernen.
        return str_replace('/', ' ', $string);
    }

    /**
     * Fetches a related record and returns it.
     *
     * @param mixed $identifier The record identifier (UID or 'table_uid' string)
     * @param string $defaultTable The default table if identifier is numeric (e.g., 'tx_publicrelations_domain_model_client')
     * @param LoggerInterface $logger
     * @return array|null The record array or null if not found or on error.
     */
    protected function getRelatedRecord($identifier, string $defaultTable, LoggerInterface $logger): ?array
    {
        $record = null;
        if (empty($identifier)) {
            $logger->debug('Identifier is empty for ' . $defaultTable, ['identifier' => $identifier]);
            return null;
        }

        if (is_numeric($identifier)) {
            $record = BackendUtility::getRecord($defaultTable, (int)$identifier);
            if (!$record) {
                $logger->warning('Record not found using default table ' . $defaultTable . ' with UID: ' . $identifier);
            }
        } elseif (is_string($identifier)) {
            // Prüft auf das Format 'tablename_uid'
            if (preg_match('/^(.*?)_(\d+)$/', $identifier, $data)) { // (.*?) für non-greedy match des Tabellennamens
                $tableName = $data[1];
                $uid = (int)$data[2];
                if (!empty($tableName) && $uid > 0) {
                    $record = BackendUtility::getRecord($tableName, $uid);
                    if (!$record) {
                        $logger->warning('Record not found for table [' . $tableName . '] with UID [' . $uid . ']. Identifier was: ' . $identifier);
                    }
                } else {
                    $logger->warning('Failed to parse a valid table name or UID from identifier.', [
                        'identifier' => $identifier,
                        'parsed_table' => $tableName ?? 'null',
                        'parsed_uid' => $uid ?? 'null'
                    ]);
                }
            } else {
                $logger->warning('Identifier string for ' . $defaultTable . ' does not match expected pattern "tablename_uid".', ['identifier' => $identifier]);
                // Hier könnte man versuchen, den $identifier direkt als Slug-Teil zu verwenden, falls es kein Record-Identifier ist.
                // Z.B. if ($defaultTable === 'tx_publicrelations_domain_model_client' && is_string($identifier)) { /* treat $identifier as a direct name */ }
            }
        } else {
            $logger->warning('Unexpected type for ' . $defaultTable . ' identifier.', ['identifier' => $identifier, 'type' => gettype($identifier)]);
        }
        return $record;
    }

    /**
     * Generates the slug for a campaign.
     * Slug structure: kunden/produkt/presseinfo
     */
    public function generateCampaign(array &$params, SlugHelper $helper): string
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        // Loggen der Eingabeparameter kann bei der Fehlersuche helfen
        // $logger->debug('generateCampaign called with params:', $params);

        $slugParts = [];

        // 1. Kunde (Client)
        // Annahme: Das Feld für den Kunden in Ihrem TCA heißt 'client'.
        $clientIdentifier = $params['record']['client'] ?? null;
        // Die Standardtabelle für Kunden, falls 'client' nur eine UID ist.
        $clientTable = 'tx_publicrelations_domain_model_client';
        $clientRecord = $this->getRelatedRecord($clientIdentifier, $clientTable, $logger);

        if ($clientRecord && !empty($clientRecord['name'])) {
            $slugParts[] = $this->cleanSlugPart((string)$clientRecord['name']);
        } else {
            $logger->info('Client name could not be determined for slug.', ['client_identifier' => $clientIdentifier]);
            // Optional: Fallback, wenn kein Kunde gefunden wurde
            // $slugParts[] = 'allgemein';
        }

        // 2. Produkt
        // Annahme: Das Feld für das Produkt in Ihrem TCA heißt 'product'.
        // BITTE ANPASSEN, falls das Feld anders heißt!
        $productIdentifier = $params['record']['product'] ?? null; // z.B. $params['record']['tx_myext_product']
        if ($productIdentifier) {
            // Annahme: Die Tabelle für Produkte heißt 'tx_myext_domain_model_product'.
            // BITTE ANPASSEN an Ihre Produkt-Tabelle!
            $productTable = 'tx_yournextextension_domain_model_product'; // WICHTIG: Korrekten Tabellennamen eintragen
            $productRecord = $this->getRelatedRecord($productIdentifier, $productTable, $logger);

            if ($productRecord && !empty($productRecord['name'])) { // Annahme: Produkt-Datensätze haben ein 'name'-Feld
                $slugParts[] = $this->cleanSlugPart((string)$productRecord['name']);
            } else {
                $logger->info('Product name could not be determined for slug.', ['product_identifier' => $productIdentifier]);
            }
        }


        // 3. Presseinfo (Titel des aktuellen Datensatzes)
        // Annahme: Das Titelfeld des aktuellen Datensatzes ("Presseinfo") ist 'title'.
        $pressinfoTitle = $params['record']['title'] ?? null;
        if (!empty($pressinfoTitle)) {
            $slugParts[] = $this->cleanSlugPart((string)$pressinfoTitle);
        } else {
            $logger->info('Presseinfo title (record title) is empty for slug generation.');
        }

        // Entfernt leere Teile und fügt die Teile mit '/' zusammen
        $rawSlug = implode('/', array_filter($slugParts, function($part) {
            return !empty(trim($part));
        }));

        if (empty($rawSlug)) {
            // Fallback, falls alle Teile leer sind, um einen komplett leeren Slug zu vermeiden.
            $logger->warning('Generated slug is empty. Falling back using record UID or PID.', ['record_uid' => $params['uid'] ?? 'N/A', 'pid' => $params['pid'] ?? 'N/A']);
            if (!empty($params['uid'])) {
                $rawSlug = 'item-' . $params['uid'];
            } elseif(!empty($params['pid'])) { // pid ist die Seiten-ID, auf der der Datensatz liegt
                $rawSlug = 'record-on-page-' . $params['pid'];
            } else {
                $rawSlug = 'untitled-' . time(); // Letzter Ausweg
            }
        }

        $logger->info('Raw slug before sanitize: "' . $rawSlug . '" for record UID ' . ($params['uid'] ?? 'NEW'), ['params_uid' => $params['uid'] ?? null]);

        return $helper->sanitize($rawSlug);
    }


    /**
     * Generates the slug for a news item.
     * Slug structure: kunden-name/pi/news-titel
     */
    public function generateNews(array &$params, SlugHelper $helper): string
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $logger->info('Generating news slug. Record data (first level):', ['keys' => array_keys($params['record'] ?? [])]);
        $logger->debug('Full record data for news slug generation:', ['record' => $params['record'] ?? []]);

        $slugParts = [];

        // 1. Kunde (Client)
        // Annahme: Das Feld für den Kunden in Ihrem TCA heißt 'client'.
        $clientIdentifier = $params['record']['client'] ?? null;
        // Die Standardtabelle für Kunden, falls 'client' nur eine UID ist.
        $clientTable = 'tx_publicrelations_domain_model_client';
        $clientRecord = $this->getRelatedRecord($clientIdentifier, $clientTable, $logger);

        if ($clientRecord && !empty($clientRecord['name'])) {
            $slugParts[] = $this->cleanSlugPart((string)$clientRecord['name']);
        } else {
            $logger->info('Client name could not be determined for news slug.', ['client_identifier' => $clientIdentifier]);
            // Optional: Fallback, wenn kein Kunde gefunden wurde (z.B. 'allgemein')
            // $slugParts[] = 'allgemein';
        }

        // 2. Statischer Teil "pi"
        $slugParts[] = 'pi';

        // 3. News-Titel (Titel des aktuellen Datensatzes)
        // Annahme: Das Titelfeld des aktuellen Datensatzes ("News") ist 'title'.
        $newsTitle = $params['record']['title'] ?? null;
        if (!empty($newsTitle)) {
            $slugParts[] = $this->cleanSlugPart((string)$newsTitle);
        } else {
            $logger->info('News title (record title) is empty for slug generation.');
            // Optional: Fallback für leeren Titel (z.B. 'unbenannte-news')
            // $slugParts[] = 'unbenannte-news';
        }

        // Entfernt leere Teile (z.B. wenn Kundenname fehlt und kein Fallback verwendet wird)
        // und fügt die Teile mit '/' zusammen.
        // Stellt sicher, dass nicht nur "pi" übrig bleibt, wenn andere Teile fehlen.
        $filteredSlugParts = array_filter($slugParts, function($part) {
            return !empty(trim($part));
        });

        // Sicherstellen, dass "pi" nicht alleine steht, wenn es der einzige gefilterte Teil ist,
        // es sei denn, es gab tatsächlich einen Kunden und einen Titel, die aber leer waren (was array_filter entfernt hätte).
        // Wenn $clientRecord und $newsTitle beide von Anfang an null waren, wäre $filteredSlugParts = ['pi']
        // Wenn z.B. nur der Kunde da war, $filteredSlugParts = ['kunden-name', 'pi']
        if (count($filteredSlugParts) === 1 && $filteredSlugParts[0] === 'pi' && !$clientRecord && empty($newsTitle) ) {
             $rawSlug = ''; // Leeren Slug erzwingen, um Fallback auszulösen
        } else {
            $rawSlug = implode('/', $filteredSlugParts);
        }


        if (empty($rawSlug)) {
            $logger->warning('Generated news slug is empty (possibly after filtering). Falling back.', [
                'record_uid' => $params['uid'] ?? 'N/A',
                'pid' => $params['pid'] ?? 'N/A',
                'current_raw_slug' => $rawSlug,
                'original_parts_count_if_all_present' => ($clientRecord ? 1:0) + 1 + (!empty($newsTitle) ? 1:0),
                'filtered_parts_count' => count($filteredSlugParts)

            ]);
            // Fallback-Logik
            if (!empty($params['uid'])) {
                $rawSlug = 'news-item-' . $params['uid'];
            } elseif (!empty($params['pid'])) { // pid ist die Seiten-ID, auf der der Datensatz liegt
                $rawSlug = 'news-on-page-' . $params['pid'];
            } else {
                $rawSlug = 'untitled-news-' . time(); // Letzter Ausweg
            }
        }

        $logger->info('Generated raw news slug before sanitize: "' . $rawSlug . '" for record UID ' . ($params['uid'] ?? 'NEW'), ['params_uid' => $params['uid'] ?? null]);

        return $helper->sanitize($rawSlug);
    }



    // public function generateCampaign(&$params, SlugHelper $helper) {

    //   if (is_numeric($params['record']['client'])) {
    //     $client = BackendUtility::getRecord('tx_publicrelations_domain_model_client', $params['record']['client']);
    //   } else {
    //     preg_match('/(.*)[_](\d*)$/', $params['record']['client'], $clientData);
    //     $client = BackendUtility::getRecord($clientData[1], $clientData[2]);
    //   }

    //   if ($client) {
    //     $slug = $this->removeSlashes($client['name']).'/'.$this->removeSlashes($params['record']['title']);
    //   } else {
    //     $slug = '';
    //   }

    //  return $helper->sanitize($slug);
    // }

    // public function generateNews(&$params, SlugHelper $helper) {

    //   if (is_numeric($params['record']['client'])) {
    //     $client = BackendUtility::getRecord('tx_publicrelations_domain_model_client', $params['record']['client']);
    //   } else {
    //     preg_match('/(.*)[_](\d*)$/', $params['record']['client'], $clientData);
    //     $client = BackendUtility::getRecord($clientData[1], $clientData[2]);
    //   }

    //   if ($client) {
    //     $slug = $this->removeSlashes($client['name']).'/pi/'.$this->removeSlashes($params['record']['title']);
    //   } else {
    //     $slug = '';
    //   }

    //  return $helper->sanitize($slug);
    // }

}
