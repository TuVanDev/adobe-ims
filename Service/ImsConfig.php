<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminAdobeIms\Service;

use Magento\AdminAdobeIms\Controller\Adminhtml\OAuth\ImsCallback;
use Magento\AdobeIms\Model\Config;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;

class ImsConfig extends Config
{
    public const XML_PATH_ENABLED = 'adobe_ims/integration/enabled';
    public const XML_PATH_ORGANIZATION_ID = 'adobe_ims/integration/organization_id';
    public const XML_PATH_API_KEY = 'adobe_ims/integration/api_key';
    public const XML_PATH_PRIVATE_KEY = 'adobe_ims/integration/private_key';
    public const XML_PATH_AUTH_URL_PATTERN = 'adobe_ims/integration/auth_url_pattern';
    public const XML_PATH_PROFILE_URL = 'adobe_ims/integration/profile_url';
    private const OAUTH_CALLBACK_URL = 'adobe_ims_auth/oauth/';
    public const XML_PATH_LOGOUT_URL = 'adobe_ims/integration/logout_url';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @var BackendUrlInterface
     */
    private BackendUrlInterface $backendUrl;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param WriterInterface $writer
     * @param EncryptorInterface $encryptor
     * @param BackendUrlInterface $backendUrl
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $url,
        WriterInterface $writer,
        EncryptorInterface $encryptor,
        BackendUrlInterface $backendUrl
    ) {
        parent::__construct($scopeConfig, $url);
        $this->writer = $writer;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->backendUrl = $backendUrl;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED
        );
    }

    /**
     * Enable Admin Adobe IMS Module and set Client ID and Client Secret and Organization ID
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $organizationId
     * @return void
     */
    public function enableModule(
        string $clientId,
        string $clientSecret,
        string $organizationId
    ): void {
        $this->updateConfig(
            self::XML_PATH_ENABLED,
            '1'
        );

        $this->updateSecureConfig(
            self::XML_PATH_ORGANIZATION_ID,
            $organizationId
        );

        $this->updateSecureConfig(
            self::XML_PATH_API_KEY,
            $clientId
        );

        $this->updateSecureConfig(
            self::XML_PATH_PRIVATE_KEY,
            $clientSecret
        );
    }

    /**
     * Disable Admin Adobe IMS Module and unset Client ID and Client Secret from config
     *
     * @return void
     */
    public function disableModule(): void
    {
        $this->updateConfig(
            self::XML_PATH_ENABLED,
            '0'
        );

        $this->deleteConfig(self::XML_PATH_ORGANIZATION_ID);
        $this->deleteConfig(self::XML_PATH_API_KEY);
        $this->deleteConfig(self::XML_PATH_PRIVATE_KEY);
    }

    /**
     * Update config
     * Get Profile URL
     *
     * @return string
     */
    public function getProfileUrl(): string
    {
        return str_replace(
            ['#{client_id}'],
            [$this->getApiKey()],
            $this->scopeConfig->getValue(self::XML_PATH_PROFILE_URL)
        );
    }

    /**
     * @param string $path
     * @param string $value
     * @return void
     */
    public function updateConfig(string $path, string $value): void
    {
        $this->writer->save(
            $path,
            $value
        );
    }

    /**
     * Update encrypted config setting
     *
     * @param string $path
     * @param string $value
     * @return void
     */
    public function updateSecureConfig(string $path, string $value): void
    {
        $value = str_replace(['\n', '\r'], ["\n", "\r"], $value);

        if (!preg_match('/^\*+$/', $value) && !empty($value)) {
            $value = $this->encryptor->encrypt($value);

            $this->writer->save(
                $path,
                $value
            );
        }
    }

    /**
     * Delete config value
     *
     * @param string $path
     * @return void
     */
    public function deleteConfig(string $path): void
    {
        $this->writer->delete($path);
    }

    /**
     * Generate the AdminAdobeIms AuthUrl with given clientID or the ClientID stored in the config
     *
     * @param string|null $clientId
     * @return string
     */
    public function getAdminAdobeImsAuthUrl(?string $clientId): string
    {
        if ($clientId === null) {
            $clientId = $this->getApiKey();
        }

        return str_replace(
            ['#{client_id}', '#{redirect_uri}', '#{locale}'],
            [$clientId, $this->getAdminAdobeImsCallBackUrl(), $this->getLocale()],
            $this->scopeConfig->getValue(self::XML_PATH_AUTH_URL_PATTERN)
        );
    }

    /**
     * Get callback url for AdminAdobeIms Module
     *
     * @return string
     */
    private function getAdminAdobeImsCallBackUrl(): string
    {
        return $this->backendUrl->getUrl(
            self::OAUTH_CALLBACK_URL . ImsCallback::ACTION_NAME
        );
    }

    /**
     * Get locale
     *
     * @return string
     */
    private function getLocale(): string
    {
        return $this->scopeConfig->getValue(Custom::XML_PATH_GENERAL_LOCALE_CODE);
    }

    /**
     * Get BackendLogout URL
     *
     * @param string $accessToken
     * @return string
     */
    public function getBackendLogoutUrl(string $accessToken) : string
    {
        return str_replace(
            ['#{access_token}', '#{client_secret}', '#{client_id}'],
            [$accessToken, $this->getPrivateKey(), $this->getApiKey()],
            $this->scopeConfig->getValue(self::XML_PATH_LOGOUT_URL)
        );
    }

    /**
     * Retrieve Organization Id
     *
     * @return string
     */
    public function getOrganizationId(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ORGANIZATION_ID);
    }
}
