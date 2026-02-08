<?php
declare(strict_types=1);

return [
    \BucheggerOnline\Publicrelations\Domain\Model\SysCategory::class => [
        'tableName' => 'sys_category',
        'properties' => [
            'parentcategory' => [
                'fieldName' => 'parent'
            ],
        ],
    ],
    \BucheggerOnline\Publicrelations\Domain\Model\StaticInfoCountry::class => [
        'tableName' => 'static_countries',
    ],
    \BucheggerOnline\Publicrelations\Domain\Model\Tag::class => [
        'tableName' => 'sys_tag',
    ],
    \BucheggerOnline\Publicrelations\Domain\Model\AcMailerMailing::class => [
        'tableName' => 'tx_acmailer_domain_model_mailing',
    ],
    \BucheggerOnline\Publicrelations\Domain\Model\AcMailerContent::class => [
        'tableName' => 'tx_acmailer_domain_model_content',
    ],
    \BucheggerOnline\Publicrelations\Domain\Model\TtContent::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'altText' => [
                'fieldName' => 'altText'
            ],
            'titleText' => [
                'fieldName' => 'titleText'
            ],
            'colPos' => [
                'fieldName' => 'colPos'
            ],
            'CType' => [
                'fieldName' => 'CType'
            ],
        ],
    ],
];
