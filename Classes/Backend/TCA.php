<?php
namespace BucheggerOnline\Publicrelations\Backend;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class to register category configurations.
 */
class TCA
{

    public static function getPaletteAccess() {
        return [
           'showitem' => '
               hidden, starttime, endtime, --linebreak--,
               sys_language_uid, l10n_parent, l10n_diffsource,
               ',
           'canNotColapse' => TRUE
        ];
    }

    public static function getPaletteSEO() {
        return [
            'showitem' => '
                seo_title, seo_description',
            'canNotColapse' => TRUE
        ];
    }

    public static function getPaletteSlug() {
        return [
            'showitem' => '
                slug, slug_locked',
            'canNotColapse' => TRUE
        ];
    }

}
