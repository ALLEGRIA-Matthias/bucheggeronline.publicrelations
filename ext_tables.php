<?php
defined('TYPO3') || die();

(static function () {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['kunde'] = 'tx_publicrelations_domain_model_client';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['produkt'] = 'tx_publicrelations_domain_model_campaign';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['kampagne'] = 'tx_publicrelations_domain_model_campaign';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['news'] = 'tx_publicrelations_domain_model_news';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['mailing'] = 'tx_publicrelations_domain_model_mailing';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['mail'] = 'tx_publicrelations_domain_model_mail';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['kontakt'] = 'tt_address';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['vip'] = 'tt_address';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['presse'] = 'tt_address';
}
)();
