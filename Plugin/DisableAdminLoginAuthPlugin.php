<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Plugin;

use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Backend\Model\Auth;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class DisableAdminLoginAuthPlugin
{
    /** @var ImsConfig */
    private ImsConfig $imsConfig;

    /** @var RedirectFactory */
    private RedirectFactory $redirectFactory;

    /** @var MessageManagerInterface */
    private MessageManagerInterface $messageManager;

    /**
     * @param ImsConfig $imsConfig
     * @param RedirectFactory $redirectFactory
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        ImsConfig $imsConfig,
        RedirectFactory $redirectFactory,
        MessageManagerInterface $messageManager
    ) {
        $this->imsConfig = $imsConfig;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * When trying to call the login but IMS is enabled redirect to the main page with error message
     *
     * @param Auth $subject
     * @param callable $proceed
     * @param string $username
     * @param string $password
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundLogin(Auth $subject, callable $proceed, string $username, string $password): void
    {
        if ($this->imsConfig->enabled() === false) {
            $proceed($username, $password);
            return;
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->redirectFactory->create();
        $this->messageManager->addErrorMessage(__('Please sign in with Adobe ID'));
        $resultRedirect->setPath('admin');
    }
}
