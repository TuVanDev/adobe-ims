<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Model;

use Magento\AdminAdobeIms\Exception\AdobeImsTokenAuthorizationException;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\AdobeIms\Model\GetToken;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

class ImsConnection
{
    private const HTTP_REDIRECT_CODE = 302;

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var ImsConfig
     */
    private ImsConfig $imsConfig;
    /**
     * @var Json
     */
    private Json $json;
    /**
     * @var GetToken
     */
    private GetToken $token;

    /**
     * @param CurlFactory $curlFactory
     * @param ImsConfig $imsConfig
     * @param Json $json
     * @param GetToken $token
     */
    public function __construct(
        CurlFactory $curlFactory,
        ImsConfig $imsConfig,
        Json $json,
        GetToken $token
    ) {
        $this->curlFactory = $curlFactory;
        $this->imsConfig = $imsConfig;
        $this->json = $json;
        $this->token = $token;
    }

    /**
     * Get authorization url
     *
     * @param string|null $clientId
     * @return string
     * @throws InvalidArgumentException
     */
    public function auth(?string $clientId = null): string
    {
        $authUrl = $this->imsConfig->getAdminAdobeImsAuthUrl($clientId);
        return $this->getAuthorizationLocation($authUrl);
    }

    /**
     * Test if given ClientID is valid and is able to return an authorization URL
     *
     * @param string $clientId
     * @return bool
     * @throws InvalidArgumentException
     */
    public function testAuth(string $clientId): bool
    {
        $location = $this->auth($clientId);
        return $location !== '';
    }

    /**
     * Get authorization location from adobeIMS
     *
     * @param string $authUrl
     * @return string
     * @throws InvalidArgumentException
     */
    private function getAuthorizationLocation(string $authUrl): string
    {
        $curl = $this->curlFactory->create();

        $curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->addHeader('cache-control', 'no-cache');
        $curl->get($authUrl);

        $this->validateResponse($curl);

        return $curl->getHeaders()['location'] ?? '';
    }

    /**
     * Validate authorization call response
     *
     * @param Curl $curl
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateResponse(Curl $curl): void
    {
        if (isset($curl->getHeaders()['location'])) {
            if (
                preg_match(
                    '/error=([a-z_]+)/i',
                    $curl->getHeaders()['location'],
                    $error
                ) && isset($error[0], $error[1])
            ) {
                throw new InvalidArgumentException(
                    __('Could not connect to Adobe IMS Service: %1.', $error[1])
                );
            }
        }

        if ($curl->getStatus() !== self::HTTP_REDIRECT_CODE) {
            throw new InvalidArgumentException(__('Could not connect to Adobe IMS Service.'));
        }
    }

    /**
     * @param string $code
     * @return TokenResponseInterface
     * @throws AdobeImsTokenAuthorizationException
     */
    public function getTokenResponse(string $code): TokenResponseInterface
    {
        try {
            return $this->token->execute($code);
        } catch (AuthorizationException $exception) {
            throw new AdobeImsTokenAuthorizationException(__('The Adobe ID you\'re using does not belong to the ' .
                'organization that controlling this Commerce instance. Contact your administrator so he can add ' .
                'your Adobe ID to the organization.'));
        }
    }

    /**
     * @param string $code
     * @return array|bool|float|int|mixed|string|null
     * @throws AuthorizationException
     */
    public function getProfile(string $code)
    {
        $curl = $this->curlFactory->create();

        $curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->addHeader('cache-control', 'no-cache');
        $curl->addHeader('Authorization', 'Bearer ' . $code);

        $curl->get($this->imsConfig->getProfileUrl());

        if ($curl->getBody() === '') {
            throw new AdobeImsTokenAuthorizationException(__('The Adobe ID you\'re using is not added to this Commerce instance' .
                'Contact your organization administrator to request the access.'));
        }

        return $this->json->unserialize($curl->getBody());
    }
}
