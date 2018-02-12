<?php
defined('TYPO3_MODE') or die();

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

if ($_EXTCONF['enableFE']) {
    // Frontend plugin
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        $_EXTKEY,
        'Login',
        'LLL:EXT:shibboleth_auth/Resources/Private/Language/locallang_db.xlf:pluginLabel'
    );

    $pluginSignature = str_replace('_', '', $_EXTKEY) . '_login';
    $TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForm/flexform_login.xml'
    );

    // TypoScript Configuration
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Shibboleth Authentication');
}

unset($_EXTCONF);
