<?php

declare(strict_types=1);

namespace Visol\ShibbolethAuth\EventListener;

use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Exception\Crypto\InvalidHashStringException;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final class TokenlessAuthenticationListener
{
    public function __invoke(BeforeRequestTokenProcessedEvent $event): void
    {
        $user = $event->getUser();
        $requestToken = $event->getRequestToken();
        // fine, there is a valid request token
        if ($requestToken instanceof RequestToken) {
            return;
        }

        // TODO: Consider using this only when we have a Shibboleth Login
        $requestToken = GeneralUtility::makeInstance(
            RequestToken::class,
            'core/user-auth/' . strtolower($user->loginType),
            null,
            ['pid' => $this->validateAndExtractStoragePid($event->getRequest()->getQueryParams()['pid'] ?? '')]
        );

        $event->setRequestToken($requestToken);
        if ($user->checkPid) {
            $user->checkPid_value = $requestToken->params['pid'];
        }
    }

    protected function validateAndExtractStoragePid(string $pidWithHmac): ?int
    {
        if (empty($pidWithHmac) || !str_contains($pidWithHmac, '@')) {
            // Backend login
            return null;
        }

        [$pid, $hmac] = explode('@', $pidWithHmac, 2);

        $hashService = GeneralUtility::makeInstance(HashService::class);

        if (!$hashService->validateHmac($pid, FrontendUserAuthentication::class, $hmac)) {
            throw new InvalidHashStringException('Invalid HMAC for the given PID.', 1759306233);
        }

        return (int)$pid;
    }

}
