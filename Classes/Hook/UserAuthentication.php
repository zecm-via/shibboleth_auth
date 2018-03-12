<?php

namespace Visol\ShibbolethAuth\Hook;

use TYPO3\CMS\Core\Utility\StringUtility;

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
        // Delete the Shibboleth session cookie
        foreach ($_COOKIE as $name => $value) {
            if (StringUtility::beginsWith($name, '_shibsession_')) {
                setcookie($name, null, -1, '/');
                break;
            }
        }
    }
}
