<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Model\Authorization;

use Magento\AdminAdobeIms\Model\Auth;
use Magento\AdobeImsApi\Api\IsTokenValidInterface;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\AuthenticationException;

/**
 * A user context determined by Adobe IMS tokens for Admin Panel.
 */
class AdobeImsAdminTokenUserContext implements UserContextInterface
{
    /**
     * @var int|null
     */
    private ?int $userId = null;

    /**
     * @var bool
     */
    private bool $isRequestProcessed = false;

    /**
     * @var ImsConfig
     */
    private ImsConfig $adminImsConfig;

    /**
     * @var Auth
     */
    protected Auth $auth;

    /**
     * @var IsTokenValidInterface
     */
    private IsTokenValidInterface $isTokenValid;

    /**
     * @var AdobeImsAdminTokenUserService
     */
    private AdobeImsAdminTokenUserService $adminTokenUserService;

    /**
     * @param ImsConfig $adminImsConfig
     * @param Auth $auth
     * @param IsTokenValidInterface $isTokenValid
     * @param AdobeImsAdminTokenUserService $adminTokenUserService
     */
    public function __construct(
        ImsConfig $adminImsConfig,
        Auth $auth,
        IsTokenValidInterface $isTokenValid,
        AdobeImsAdminTokenUserService $adminTokenUserService
    ) {
        $this->adminImsConfig = $adminImsConfig;
        $this->auth = $auth;
        $this->isTokenValid = $isTokenValid;
        $this->adminTokenUserService = $adminTokenUserService;
    }

    /**
     * @inheritdoc
     */
    public function getUserId(): ?int
    {
        if (!$this->adminImsConfig->enabled() || $this->isRequestProcessed) {
            return $this->userId;
        }

        $session = $this->auth->getAuthStorage();

        if (!empty($session->getAdobeAccessToken())) {
            $isTokenValid = $this->isTokenValid->validateToken($session->getAdobeAccessToken());
            if (!$isTokenValid) {
                throw new AuthenticationException(__('An authentication error occurred. Verify and try again.'));
            }
        } else {
            $this->adminTokenUserService->processLoginRequest();
        }

        $this->userId = (int) $session->getUser()->getUserId();
        $this->isRequestProcessed = true;

        return $this->userId;
    }

    /**
     * @inheritdoc
     */
    public function getUserType(): ?int
    {
        return UserContextInterface::USER_TYPE_ADMIN;
    }
}
