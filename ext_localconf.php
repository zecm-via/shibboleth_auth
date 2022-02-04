<?php

defined('TYPO3') || defined('TYPO3_MODE') || die();

(function ($extKey = 'shibboleth_auth') {
    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get($extKey);

    $subTypes = [];

    if ($extensionConfiguration['enableBE']) {
        $subTypes[] = 'getUserBE';
        $subTypes[] = 'authUserBE';

        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = $extensionConfiguration['BE_fetchUserIfNoSession'];

        // Register backend logout handler
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = \Visol\ShibbolethAuth\Hook\UserAuthentication::class . '->backendLogoutHandler';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1518433441] = [
            'provider' => \Visol\ShibbolethAuth\LoginProvider\ShibbolethLoginProvider::class,
            'sorting' => 60,
            'icon-class' => 'fa-sign-in',
            'label' => 'LLL:EXT:shibboleth_auth/Resources/Private/Language/locallang.xlf:backend_login.header'
        ];
    }

    if ($extensionConfiguration['enableFE']) {
        // Register FE user authentication subtypes
        $subTypes[] = 'getUserFE';
        $subTypes[] = 'authUserFE';

        // Register FE plugin
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Visol.' . $extKey,
            'Login',
            [
                'FrontendLogin' => 'index,login,loginSuccess,logout,logoutSuccess',
            ],
            // non-cacheable actions
            [
                'FrontendLogin' => 'index,loginSuccess,logoutSuccess',
            ]
        );

        // Configure if session should be fetched on each page load
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = $extensionConfiguration['FE_fetchUserIfNoSession'];
    }

    // Register authentication service
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        $extKey,
        'auth',
        \Visol\ShibbolethAuth\Typo3\Service\ShibbolethAuthenticationService::class,
        [
            'title' => 'Shibboleth Authentication',
            'description' => 'Shibboleth Authentication service (BE & FE)',

            'subtype' => implode(',', $subTypes),

            'available' => true,
            'priority' => $extensionConfiguration['priority'],
            'quality' => 50,

            'os' => '',
            'exec' => '',

            'className' => \Visol\ShibbolethAuth\Typo3\Service\ShibbolethAuthenticationService::class,
        ]
    );

    // Use popup window to refresh login instead of the AJAX relogin
    $GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] = 1;
})();
