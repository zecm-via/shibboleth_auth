<?php
defined('TYPO3_MODE') or die();

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

$subTypes = array();

if ($_EXTCONF['enableBE']) {
	$subTypes[] = 'getUserBE';
	$subTypes[] = 'authUserBE';

	$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = $_EXTCONF['BE_fetchUserIfNoSession'];

	if (TYPO3_MODE == 'BE') {
		// Register backend logout handler
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = 'PHLU\\ShibbolethAuth\\Hook\\UserAuthentication->backendLogoutHandler';
	}
}

if ($_EXTCONF['enableFE']) {
	// Register FE user authentication subtypes
	$subTypes[] = 'getUserFE';
	$subTypes[] = 'authUserFE';

	// Register FE plugin
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
		'PHLU.' . $_EXTKEY,
		'Login',
		array(
			'FrontendLogin' => 'index,login,loginSuccess,logout,logoutSuccess',
		),
		// non-cacheable actions
		array(
			'FrontendLogin' => 'index,loginSuccess,logoutSuccess',
		)
	);

	// Configure if session should be fetched on each page load
	$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = $_EXTCONF['FE_fetchUserIfNoSession'];
}

// Register authentication service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'PHLU\\ShibbolethAuth\\Typo3\\Service\\ShibbolethAuthenticationService',
	array(
		'title' => 'Shibboleth-Authentication',
		'description' => 'Shibboleth Authentication service (BE & FE)',

		'subtype' => implode(',', $subTypes),

		'available' => TRUE,
		'priority' => $_EXTCONF['priority'],
		'quality' => 50,

		'os' => '',
		'exec' => '',

		'className' => 'PHLU\\ShibbolethAuth\\Typo3\\Service\\ShibbolethAuthenticationService',
	)
);

unset($_EXTCONF);