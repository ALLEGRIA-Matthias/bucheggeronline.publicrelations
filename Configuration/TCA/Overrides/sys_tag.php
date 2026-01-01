<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function () {

    $sys_tag_columns = [

        'parent' => [
            'exclude' => false,
            'label' => 'Parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'sys_tag',
                'maxitems' => 1,
                'default' => 0,
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => false,
                        'showHeader' => true,
                    ],
                ],
            ],
        ],
        'icon' => [
            'exclude' => false,
            'label' => 'Icon',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'color' => [
            'exclude' => false,
            'label' => 'Color',
            'config' => [
                'type' => 'input',
                'valuePicker' => [
                    'items' => [
                        ['Rot', '--bs-red'],
                        ['Pink', '--bs-pink'],
                        ['Lila', '--bs-purple'],
                        ['Indigo', '--bs-indigo'],
                        ['Blau', '--bs-blue'],
                        ['Cyan', '--bs-cyan'],
                        ['Türkis', '--bs-teal'],
                        ['Grün', '--bs-green'],
                        ['Gelb', '--bs-yellow'],
                        ['Orange', '--bs-orange'],
                        ['Weiß', '--bs-white'],
                        ['Schwarz', '--bs-black'],
                    ],
                ],
                'default' => '', // Kein Standardwert
            ],
        ],
    ];

    // 1. Die neuen Spalten hinzufügen (das war schon korrekt)
    ExtensionManagementUtility::addTCAcolumns('sys_tag', $sys_tag_columns);

    // 2. Eine neue Palette definieren (um Konflikte mit der Core-Palette "general" zu vermeiden)
    $GLOBALS['TCA']['sys_tag']['palettes']['tags_base'] = [
        'showitem' => 'title, description, --linebreak--, icon, color',
    ];

    // 3. Den gesamten "showitem" String für den Standard-Typ von sys_tag neu definieren
    // Wir behalten dabei den wichtigen "records" Tab aus dem Core bei.
    $GLOBALS['TCA']['sys_tag']['types']['0']['showitem'] = '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            parent,
            --palette--;;tags_base,
        --div--;LLL:EXT:tagging/Resources/Private/Language/locallang_tca.xlf:sys_tag.tabs.items,
            items,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            hidden,
            --palette--;;timeRestriction,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            description,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    ';

});

