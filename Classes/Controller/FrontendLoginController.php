<?php

namespace Visol\ShibbolethAuth\Controller;

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
use TYPO3\CMS\Core\Utility\HttpUtility;

class FrontendLoginController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     * @var string
     */
    protected $remoteUser;

    public function initializeAction()
    {
        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['shibboleth_auth']);
        if (empty($this->extensionConfiguration['remoteUser'])) {
            $this->extensionConfiguration['remoteUser'] = 'REMOTE_USER';
        }
        if (isset($_SERVER['AUTH_TYPE']) && $_SERVER['AUTH_TYPE'] === 'shibboleth') {
            $this->remoteUser = $_SERVER[$this->extensionConfiguration['remoteUser']];
        }
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function indexAction()
    {
        // Login type
        $loginType = GeneralUtility::_GP('logintype');
        $redirectUrl = GeneralUtility::_GP('redirect_url');
        // Is user logged in?
        $userIsLoggedIn = $GLOBALS['TSFE']->loginUser;

        // What to display
        if ($userIsLoggedIn) {
            $this->remoteUser = $GLOBALS['TSFE']->fe_user->user['username'];
            if (!empty($redirectUrl)) {
                $this->forward('loginSuccess');
            } else {
                $this->forward('showLogout');
            }
        } else {
            if ($loginType === 'logout') {
                $this->forward('logoutSuccess');
            } elseif ($loginType === 'login') {
                $this->forward('showLogin');
            } else {
                $this->forward('showLogin');
            }
        }
    }

    /**
     * Display the Shibboleth login link
     */
    public function showLoginAction()
    {
        $target = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');

        if ($this->extensionConfiguration['forceSSL'] && !GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            $target = str_ireplace('http:', 'https:', $target);
            if (!preg_match('#["<>\\\]+#', $target)) {
                HttpUtility::redirect($target);
            }
        }

        // If the target page already includes an exclamation sign (e.g. non-RealURL page), we must use an ampersand here
        $target .= !strpos($target, '?') ? '?' : '&';
        $target .= 'logintype=login&pid=' . $this->extensionConfiguration['storagePid'];

        $loginHandlerUrl = $this->extensionConfiguration['loginHandler'];
        $queryStringSeparator = !strpos($loginHandlerUrl, '?') ? '?' : '&';

        $shibbolethLoginUri = $loginHandlerUrl . $queryStringSeparator . 'target=' . rawurlencode($target);
        $shibbolethLoginUri = GeneralUtility::sanitizeLocalUrl($shibbolethLoginUri);
        $this->view->assign('shibbolethLoginUri', $shibbolethLoginUri);
    }

    /**
     * Perform redirect after a successful login
     *
     * @return mixed
     */
    public function loginSuccessAction()
    {
        // TODO respect FlexForm setting
        $redirectUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . GeneralUtility::_GP('redirect_url');
        $redirectUrl = GeneralUtility::sanitizeLocalUrl($redirectUrl);
        HttpUtility::redirect($redirectUrl);
    }

    /**
     * Show logout after a successful login if no redirect URL was set
     */
    public function showLogoutAction()
    {
    }

    public function logoutSuccessAction()
    {
        $redirectUrl = $this->extensionConfiguration['logoutHandler'];
        $redirectUrl = GeneralUtility::sanitizeLocalUrl($redirectUrl);
        HttpUtility::redirect($redirectUrl);
    }
}
