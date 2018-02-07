<?php

namespace PHLU\ShibbolethAuth\Hook;

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

class UserAuthentication
{

    public function backendLogoutHandler()
    {
        $_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['shibboleth_auth']);
        $_GET['redirect'] = $_EXTCONF['logoutHandler'];
    }
}
