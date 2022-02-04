<?php

defined('TYPO3') || defined('TYPO3_MODE') || die();

(function ($extKey = 'shibboleth_auth') {
    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get($extKey);

    if ($extensionConfiguration['enableFE']) {
        // Frontend plugin
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extKey,
            'Login',
            'LLL:EXT:shibboleth_auth/Resources/Private/Language/locallang_db.xlf:pluginLabel'
        );

        $pluginSignature = str_replace('_', '', $extKey) . '_login';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages, recursive';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            'FILE:EXT:' . $extKey . '/Configuration/FlexForm/flexform_login.xml'
        );

        // TypoScript Configuration
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript',
            'Shibboleth Authentication'
        );
    }
})();
