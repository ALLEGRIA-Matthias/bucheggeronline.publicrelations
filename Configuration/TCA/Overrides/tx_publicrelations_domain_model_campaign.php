<?php
defined('TYPO3') || die();

$GLOBALS['TCA']['tx_publicrelations_domain_model_campaign']['columns']['slug']['config']['generatorOptions']['postModifiers'][] = \BucheggerOnline\Publicrelations\Utility\SlugGenerator::class . '->generateCampaign';
