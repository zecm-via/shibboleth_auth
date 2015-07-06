<?php
namespace PHLU\ShibbolethAuth\Typo3\Service;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShibbolethAuthenticationService extends \TYPO3\CMS\Sv\AbstractAuthenticationService {

	public $prefixId = 'shibboleth_auth';

	/**
	 * The extension key
	 *
	 * @var string
	 */
	public $extKey = 'shibboleth_auth';

	/**
	 * @var \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
	 */
	public $pObj;

	/**
	 * @var array
	 */
	protected $extConf;

	/**
	 * @var string
	 */
	protected $remoteUser;

	/**
	 * Inits some variables
	 *
	 * @return    void
	 */
	public function init() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		if (empty($this->extConf['remoteUser'])) $this->extConf['remoteUser'] = 'REMOTE_USER';
		$this->remoteUser = $_SERVER[$this->extConf['remoteUser']];
		return parent::init();
	}

	/**
	 * Initialize authentication service
	 *
	 * @param    string $mode Subtype of the service which is used to call the service.
	 * @param    array $loginData Submitted login form data
	 * @param    array $authInfo Information array. Holds submitted form data etc.
	 * @param    object $pObj Parent object
	 * @return    mixed
	 */
	public function initAuth($mode, $loginData, $authInfo, $pObj) {

		if (defined('TYPO3_cliMode')) {
			return parent::initAuth($mode, $loginData, $authInfo, $pObj);
		}

		// bypass Shibboleth login if enableFE is 0
		if (!($this->extConf['enableFE']) && TYPO3_MODE == 'FE') {
			return parent::initAuth($mode, $loginData, $authInfo, $pObj);
		}

		$this->login = $loginData;
		if (empty($this->login['uname']) && empty($this->remoteUser)) {
			return parent::initAuth($mode, $loginData, $authInfo, $pObj);
		} else {
			$loginData['status'] = 'login';

			return parent::initAuth($mode, $loginData, $authInfo, $pObj);
		}
	}

	public function getUser() {
		$user = FALSE;
		if ($this->login['status'] == 'login' && $this->isShibbolethLogin() && empty($this->login['uname'])) {
			$storagePid = GeneralUtility::_GP('pid');
			if (empty($storagePid)) {
				GeneralUtility::_GETset($this->extConf['storagePid'], 'pid');
			}
			$user = $this->fetchUserRecord($this->remoteUser);
			if (!is_array($user) || empty($user)) {
				if ($this->authInfo['loginType'] == 'FE' && !empty($this->remoteUser) && $this->extConf['enableAutoImport']) {
					$this->importFEUser();
				} else {
					$user = FALSE;
					// Failed login attempt (no username found)
					$this->writelog(255, 3, 3, 2,
						"Login-attempt from %s (%s), username '%s' not found!!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser));
					GeneralUtility::sysLog(sprintf("Login-attempt from %s (%s), username '%s' not found!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->remoteUser), $this->extKey, 0);
				}
			} else {
				if ($this->authInfo['loginType'] == 'FE' && $this->extConf['enableAutoImport']) {
					$this->updateFEUser();
				}
				if ($this->writeDevLog) GeneralUtility::devLog('User found: ' . GeneralUtility::arrayToLogString($user, array($this->db_user['userid_column'], $this->db_user['username_column'])), $this->extKey);
			}
			if ($this->authInfo['loginType'] == 'FE') {
				// the fe_user was updated, it should be fetched again.
				$user = $this->fetchUserRecord($this->remoteUser);
			}
		}

		/* Deny Backend login for Non-Shibboleth authentication when onlyShibbolethFunc is set */
		if (!defined('TYPO3_cliMode') && $this->authInfo['loginType'] == 'BE' && $this->extConf['onlyShibbolethBE'] && empty($user)) {

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['onlyShibbolethFunc'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['onlyShibbolethFunc'] as $_classRef) {
					$_procObj =& GeneralUtility::getUserObj($_classRef);
					$_procObj->onlyShibbolethFunc($this->remoteUser);
				}
			} else {
				/** @var $messageObj \TYPO3\CMS\Core\Messaging\ErrorpageMessage */
				$messageObj = GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\ErrorpageMessage', '<p>User (' . $this->remoteUser . ') not found!</p><p><a href="' . $this->extConf['logoutHandler'] . '">Shibboleth Logout</a></p>', 'Login error');
				$messageObj->output();
			}
			foreach ($_COOKIE as $key => $val) unset($_COOKIE[$key]);
			exit;
		}

		return $user;
	}

	/**
	 * Authenticate a user (Check various conditions for the user that might invalidate its authentication, eg. password match, domain, IP, etc.)
	 *
	 * Will return one of following authentication status codes:
	 *  - 0 - authentication failure
	 *  - 100 - just go on. User is not authenticated but there is still no reason to stop
	 *  - 200 - the service was able to authenticate the user
	 *
	 * @param    array        Array containing FE user data of the logged user.
	 * @return    integer        authentication statuscode, one of 0,100 and 200
	 */
	public function authUser($user) {
		$OK = 100;

		if (defined('TYPO3_cliMode')) {
			$OK = 100;
		} else if (($this->authInfo['loginType'] == 'FE') && !empty($this->login['uname'])) {
			$OK = 100;
		} else if ($this->isShibbolethLogin() && !empty($user)
			&& ($this->remoteUser == $user[$this->authInfo['db_user']['username_column']])
		) {
			$OK = 200;
			if ($user['lockToDomain'] && $user['lockToDomain'] != $this->authInfo['HTTP_HOST']) {
				// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255, 3, 3, 1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->authInfo['db_user']['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
					GeneralUtility::sysLog(sprintf("Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						$this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->authInfo['db_user']['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']), $this->extKey, 0);
				}
				$OK = 0;
			}
		}

		return $OK;
	}

	/**
	 * Creates a new FE user from the current Shibboleth data
	 */
	protected function importFEUser() {
		$this->writelog(255, 3, 3, 2, "Importing user %s!", array($this->remoteUser));

		$user = array('crdate' => time(),
			'tstamp' => time(),
			'pid' => $this->extConf['storagePid'],
			'username' => $this->remoteUser,
			'password' => md5(GeneralUtility::shortMD5(uniqid(rand(), TRUE))),
			'email' => $this->getServerVar($this->extConf['mail']),
			'name' => $this->getServerVar($this->extConf['displayName']),
			'usergroup' => $this->getFEUserGroups(),
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->authInfo['db_user']['table'], $user);
	}

	/**
	 * Updates an existing FE user with the current data provided by Shibboleth, matched be the uniqueId
	 *
	 * @return boolean
	 */
	protected function updateFEUser() {
		$this->writelog(255, 3, 3, 2, "Updating user %s!", array($this->remoteUser));

		$where = "username = '" . $this->remoteUser . "' AND pid = " . $this->extConf['storagePid'];
		$user = array('tstamp' => time(),
			'username' => $this->remoteUser,
			'password' => GeneralUtility::shortMD5(uniqid(rand(), TRUE)),
			'email' => $this->getServerVar($this->extConf['mail']),
			'name' => $this->getServerVar($this->extConf['displayName']),
			'usergroup' => $this->getFEUserGroups(),
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->authInfo['db_user']['table'], $where, $user);
	}

	/**
	 * Fetches all affiliations from the Shibboleth user
	 * Creates a user group for each affiliation if it doesn't exist yet and returns a list of all user groups to be
	 * assigned to the user
	 *
	 * @return string
	 */
	protected function getFEUserGroups() {
		$feGroups = array();
		$eduPersonAffiliation = $this->getServerVar($this->extConf['eduPersonAffiliation']);
		if (empty($eduPersonAffiliation)) $eduPersonAffiliation = 'member';
		if (!empty($eduPersonAffiliation)) {
			$affiliation = explode(';', $eduPersonAffiliation);
			array_walk($affiliation, create_function('&$v,$k', '$v = preg_replace("/@.*/", "", $v);'));

			// insert the affiliations in fe_groups if they are not there.
			foreach ($affiliation as $title) {
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title',
					$this->authInfo['db_groups']['table'],
					"deleted = 0 AND pid = " . $this->extConf['storagePid'] . " AND title = '$title'");
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
					$feGroups[] = $row['uid'];
				} else {
					$group = array('title' => $title, 'pid' => $this->extConf['storagePid']);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->authInfo['db_groups']['table'], $group);
					$feGroups[] = $GLOBALS['TYPO3_DB']->sql_insert_id();
				}
				if ($dbres) $GLOBALS['TYPO3_DB']->sql_free_result($dbres);
			}
		}

		// Hook for any additional fe_groups
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['getFEUserGroups'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['getFEUserGroups'] as $_classRef) {
				$_procObj =& GeneralUtility::getUserObj($_classRef);
				$feGroups = $_procObj->getFEUserGroups($feGroups);
			}
		}

		return implode(',', $feGroups);
	}

	/**
	 * @return boolean
	 */
	protected function isShibbolethLogin() {
		return isset($_SERVER['AUTH_TYPE']) && (strtolower($_SERVER['AUTH_TYPE']) == 'shibboleth') && !empty($this->remoteUser);
	}

	/**
	 * Returns the requested variable from $_SERVER
	 * Falls back to the prefixed version (e.g. $_SERVER['REDIRECT_affiliation'] instead of $_SERVER['affiliation'] if needed
	 *
	 * @param $key
	 * @param string $prefix
	 * @return string|NULL
	 */
	protected function getServerVar($key, $prefix = 'REDIRECT_') {
		if (isset($_SERVER[$key])) {
			return $_SERVER[$key];
		} else if (isset($_SERVER[$prefix . $key])) {
			return $_SERVER[$prefix . $key];
		} else {
			foreach ($_SERVER as $k => $v) {
				if ($key == str_replace($prefix, '', $k)) {
					return $v;
				}
			}
		}
		return NULL;
	}
}