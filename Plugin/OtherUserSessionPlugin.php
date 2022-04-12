<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Plugin;

use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Security\Model\Config;

class OtherUserSessionPlugin
{
    /**
     * @var ImsConfig
     */
    private ImsConfig $imsConfig;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ImsConfig $imsConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ImsConfig $imsConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->imsConfig = $imsConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Allow to have multiple sessions when AdminAdobeIms Module and account sharing is enabled
     *
     * @param AdminSessionsManager $subject
     * @param callable $proceed
     * @return AdminSessionsManager
     */
    public function aroundLogoutOtherUserSessions(
        AdminSessionsManager $subject,
        callable $proceed
    ): AdminSessionsManager {
        if ($this->imsConfig->enabled() === false
            || (bool) $this->scopeConfig->getValue(Config::XML_PATH_ADMIN_ACCOUNT_SHARING) === false
        ) {
            return $proceed();
        }

        return $subject;
    }
}
