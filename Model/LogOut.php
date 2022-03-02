<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminAdobeIms\Model;

use Magento\AdminAdobeIms\Exception\AdobeImsTokenAuthorizationException;
use Magento\AdobeImsApi\Api\FlushUserTokensInterface;
use Magento\AdobeImsApi\Api\GetAccessTokenInterface;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\AdminAdobeIms\Model\ImsConnection;
use Psr\Log\LoggerInterface;

/**
 * Represent functionality for log out users from the Adobe account
 */
class LogOut
{
    /**
     * Successful result code.
     */
    private const HTTP_OK = 200;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var GetAccessTokenInterface
     */
    private $getAccessToken;

    /**
     * @var FlushUserTokensInterface
     */
    private $flushUserTokens;
    /**
     * @var ImsConfig
     */
    private ImsConfig $imsConfig;
    /**
     * @var ImsConnection
     */
    private ImsConnection $imsConnection;

    /**
     * @param LoggerInterface $logger
     * @param ImsConfig $imsConfig
     * @param CurlFactory $curlFactory
     * @param GetAccessTokenInterface $getAccessToken
     * @param FlushUserTokensInterface $flushUserTokens
     * @param ImsConnection $imsConnection
     */
    public function __construct(
        LoggerInterface $logger,
        ImsConfig $imsConfig,
        CurlFactory $curlFactory,
        GetAccessTokenInterface $getAccessToken,
        FlushUserTokensInterface $flushUserTokens,
        ImsConnection $imsConnection
    ) {
        $this->logger = $logger;
        $this->curlFactory = $curlFactory;
        $this->getAccessToken = $getAccessToken;
        $this->flushUserTokens = $flushUserTokens;
        $this->imsConfig = $imsConfig;
        $this->imsConnection = $imsConnection;
    }

    /**
     * @inheritDoc
     */
    public function execute() : bool
    {
        try {
            $accessToken = $this->getAccessToken->execute();

            if (empty($accessToken)) {
                return true;
            }

            $this->externalLogOut($accessToken);
            $this->flushUserTokens->execute();
            return true;
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            return false;
        }
    }

    /**
     * Logout user from Adobe IMS
     *
     * @param string $accessToken
     * @throws LocalizedException
     */
    private function externalLogOut(string $accessToken): void
    {
        if (!$this->checkUserProfile($accessToken)) {
            throw new LocalizedException(
                __('An error occurred during logout operation.')
            );
        }
        $curl = $this->curlFactory->create();

        $curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->addHeader('cache-control', 'no-cache');

        $curl->post(
            $this->imsConfig->getBackendLogoutUrl($accessToken),
            []
        );

        if ($curl->getStatus() !== self::HTTP_OK || ($this->checkUserProfile($accessToken))) {
            throw new LocalizedException(
                __('An error occurred during logout operation.')
            );
        }
    }

    /**
     * @param string $accessToken
     * @return bool
     * @throws AuthorizationException
     */
    private function checkUserProfile(string $accessToken): bool
    {
        $result = false;
        try {
            $profile = $this->imsConnection->getProfile($accessToken);
            if ($profile['email']) {
                $result = true;
            }
        } catch (AdobeImsTokenAuthorizationException $exception) {
            //case if profile does not exist anymore after token invalidation
        }
        return $result;
    }
}
