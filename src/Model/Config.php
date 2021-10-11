<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model;

use EasyTranslate\RestApiClient\Api\Configuration as ApiConfiguration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_API_ENVIRONMENT = 'easytranslate/api/environment';
    private const XML_PATH_API_CLIENT_ID = 'easytranslate/api/client_id';
    private const XML_PATH_API_CLIENT_SECRET = 'easytranslate/api/client_secret';
    private const XML_PATH_API_USERNAME = 'easytranslate/api/username';
    private const XML_PATH_API_PASSWORD = 'easytranslate/api/password';
    private const XML_PATH_PRODUCTS_ATTRIBUTES = 'easytranslate/products/attributes';
    private const XML_PATH_CATEGORIES_ATTRIBUTES = 'easytranslate/categories/attributes';
    private const XML_PATH_CMS_BLOCKS_ATTRIBUTES = 'easytranslate/cms_blocks/attributes';
    private const XML_PATH_CMS_PAGES_ATTRIBUTES = 'easytranslate/cms_pages/attributes';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getEnvironment(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_API_ENVIRONMENT, ScopeInterface::SCOPE_STORE);
    }

    public function getClientId(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_API_CLIENT_ID, ScopeInterface::SCOPE_STORE);
    }

    public function getClientSecret(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_API_CLIENT_SECRET, ScopeInterface::SCOPE_STORE);
    }

    public function getUsername(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_API_USERNAME, ScopeInterface::SCOPE_STORE);
    }

    public function getPassword(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_API_PASSWORD, ScopeInterface::SCOPE_STORE);
    }

    public function getProductsAttributes(): array
    {
        $rawAttributes = $this->scopeConfig->getValue(self::XML_PATH_PRODUCTS_ATTRIBUTES, ScopeInterface::SCOPE_STORE);

        return $this->explodeRawAttributes($rawAttributes);
    }

    public function getCategoriesAttributes(): array
    {
        $rawAttributes = $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORIES_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );

        return $this->explodeRawAttributes($rawAttributes);
    }

    public function getCmsBlocksAttributes(): array
    {
        $rawAttributes = $this->scopeConfig->getValue(
            self::XML_PATH_CMS_BLOCKS_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );

        return $this->explodeRawAttributes($rawAttributes);
    }

    public function getCmsPageAttributes(): array
    {
        $rawAttributes = $this->scopeConfig->getValue(
            self::XML_PATH_CMS_PAGES_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );

        return $this->explodeRawAttributes($rawAttributes);
    }

    public function getApiConfiguration(): ApiConfiguration
    {
        return new ApiConfiguration(
            $this->getEnvironment(),
            $this->getClientId(),
            $this->getClientSecret(),
            $this->getUsername(),
            $this->getPassword()
        );
    }

    private function explodeRawAttributes($rawAttributes): array
    {
        if ($rawAttributes === null || $rawAttributes === '') {
            return [];
        }

        return explode(',', $rawAttributes);
    }
}
