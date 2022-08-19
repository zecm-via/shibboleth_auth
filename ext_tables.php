<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die();

(function ($extKey = 'shibboleth_auth') {
    $extensionConfiguration = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    )->get($extKey);

    if ($extensionConfiguration['enableFE']) {
        // Frontend plugin
        ExtensionUtility::registerPlugin(
            $extKey,
            'Login',
            'LLL:EXT:shibboleth_auth/Resources/Private/Language/locallang_db.xlf:pluginLabel'
        );

        $pluginSignature = str_replace('_', '', $extKey) . '_login';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages, recursive';
        ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            'FILE:EXT:' . $extKey . '/Configuration/FlexForm/flexform_login.xml'
        );

        // TypoScript Configuration
        ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript',
            'Shibboleth Authentication'
        );
    }
})();
