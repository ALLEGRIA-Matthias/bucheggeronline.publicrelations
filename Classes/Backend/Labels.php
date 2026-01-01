<?php
declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Backend;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class to register category configurations.
 */
class Labels
{
  /**
   * TCA label callback für tx_publicrelations_domain_model_slide
   */
  public static function generateSlide(array &$params): void
  {
    if (($params['table'] ?? '') !== 'tx_publicrelations_domain_model_slide') {
      return;
    }

    $row = $params['row'] ?? [];
    $titleOverwrite = trim((string) ($row['title_overwrite'] ?? ''));
    $title = $titleOverwrite;

    // Falls kein Override, Quelle aus Client/Campaign/News holen
    if ($title === '') {
      if (!empty($row['client'])) {
        $client = BackendUtility::getRecord(
          'tx_publicrelations_domain_model_client',
          (int) $row['client']
        );
        $title = $client['name'] ?? '';
      } elseif (!empty($row['campaign'])) {
        $campaign = BackendUtility::getRecord(
          'tx_publicrelations_domain_model_campaign',
          (int) $row['campaign']
        );
        $title = $campaign['title'] ?? '';
      } elseif (!empty($row['news'])) {
        $news = BackendUtility::getRecord(
          'tx_publicrelations_domain_model_news',
          (int) $row['news']
        );
        $title = $news['title'] ?? '';
      }
    }

    // Label je nach Typ
    if (!empty($row['client'])) {
      $label = 'Kunde: ' . $title;
    } elseif (!empty($row['campaign'])) {
      $label = 'Produkt: ' . $title;
    } elseif (!empty($row['news'])) {
      $label = 'Pressemeldung: ' . $title;
    } else {
      $label = 'Manuell: ' . $title;
    }

    $params['title'] = $label;
  }

  /**
   * TCA label callback für tx_publicrelations_domain_model_accreditation
   */
  public static function generateAccreditation(array &$params): void
  {
    if (($params['table'] ?? '') !== 'tx_publicrelations_domain_model_accreditation') {
      return;
    }

    $row = $params['row'] ?? [];
    $guestId = (int) ($row['guest'] ?? 0);
    $guest = $guestId > 0
      ? BackendUtility::getRecord('tt_address', $guestId)
      : null;

    $parts = [];
    if (is_array($guest)) {
      // tt_address-Daten
      if (!empty($guest['last_name'])) {
        $parts[] = trim($guest['last_name']);
      }
      if (!empty($guest['first_name'])) {
        $parts[] = trim($guest['first_name']);
      }
      if (!empty($guest['title'])) {
        $parts[] = trim($guest['title']);
      }
      if (!empty($guest['company'])) {
        $parts[] = '[' . trim($guest['company']) . ']';
      }
    } else {
      // Akkreditierungs-Daten
      if (!empty($row['last_name'])) {
        $parts[] = trim($row['last_name']);
      }
      if (!empty($row['first_name'])) {
        $parts[] = trim($row['first_name']);
      }
      if (!empty($row['title'])) {
        $parts[] = trim($row['title']);
      }
      if (!empty($row['medium'])) {
        $parts[] = '[' . trim($row['medium']) . ']';
      }
    }

    $params['title'] = trim(implode(' ', $parts));
  }


  public static function generateSysCategory(array &$params): void
  {
    if (($params['table'] ?? '') !== 'sys_category') {
      return;
    }

    $row = $params['row'] ?? [];
    $categoryUid = $row['uid'] ?? 'NEU';
    $categoryTitle = trim((string) ($row['title'] ?? ''));

    // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($row, 'Row für sys_category UID ' . $categoryUid);

    if (!empty($row['client'])) {
      $clientUid = (int) $row['client'];
      $label = '[Client UID ' . $clientUid . ' nicht auflösbar] | ' . $categoryTitle; // Fallback-Label

      if ($clientUid > 0) {
        $clientRecord = BackendUtility::getRecord(
          'tx_publicrelations_domain_model_client',
          $clientUid
        );
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($clientRecord, 'Client Record für UID ' . $clientUid);

        if (is_array($clientRecord)) {
          $clientShortName = trim((string) ($clientRecord['short_name'] ?? ''));
          $clientFullName = trim((string) ($clientRecord['name'] ?? ''));
          $clientName = $clientShortName ?: $clientFullName; // Bevorzuge short_name, dann name

          if ($clientName !== '') {
            $label = $clientName . ' | ' . $categoryTitle;
          } else {
            // Client gefunden, aber ohne Namen
            $label = '[Client ohne Namen] | ' . $categoryTitle;
          }
        } else {
          // Client nicht gefunden trotz UID im Category-Record
          $label = '[Client UID ' . $clientUid . ' existiert nicht] | ' . $categoryTitle;
        }
      } else {
        // $row['client'] war vorhanden, aber nicht > 0 (z.B. "0" als String oder leer)
        $label = $categoryTitle; // Nur Kategorie-Titel
      }
    } else {
      // Kein Client mit dieser Kategorie verknüpft
      $label = $categoryTitle;
    }

    $params['title'] = $label ?: ($categoryUid === 'NEU' ? '[Neue Kategorie]' : '[Unbenannte Kategorie ' . $categoryUid . ']');
  }
}
