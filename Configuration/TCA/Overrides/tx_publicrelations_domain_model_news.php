<?php
defined('TYPO3') || die();

$GLOBALS['TCA']['tx_publicrelations_domain_model_news']['columns']['slug']['config']['generatorOptions']['postModifiers'][] = \BucheggerOnline\Publicrelations\Utility\SlugGenerator::class . '->generateNews';
