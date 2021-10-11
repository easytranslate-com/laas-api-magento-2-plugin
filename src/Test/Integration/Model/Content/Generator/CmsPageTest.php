<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Model\Content\Generator;

use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Content\Generator\CmsPage as CmsPageGenerator;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Model\Page;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CmsPageTest extends TestCase
{
    /**
     * @var CmsPageGenerator
     */
    private $cmsPageGenerator;

    /**
     * @var GetPageByIdentifierInterface
     */
    private $getPageByIdentifier;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected function setUp(): void
    {
        $objectManager             = Bootstrap::getObjectManager();
        $this->cmsPageGenerator    = $objectManager->create(CmsPageGenerator::class);
        $this->getPageByIdentifier = $objectManager->create(GetPageByIdentifierInterface::class);
        $this->storeManager        = $objectManager->create(StoreManagerInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     */
    public function testGetContent(): void
    {
        $identifier         = 'page100';
        $storeId            = 1;
        $includedAttributes = ['title', 'content', 'meta_description', 'content_heading'];
        $this->assertContent($identifier, $storeId, $includedAttributes, []);
    }

    /**
     * @magentoDataFixture   Magento/Cms/_files/pages.php
     * @magentoConfigFixture current_store easytranslate/cms_pages/attributes title
     */
    public function testGetContentRespectsSettings(): void
    {
        $identifier         = 'page100';
        $storeId            = 1;
        $includedAttributes = ['title'];
        $excludedAttributes = ['content', 'meta_description', 'content_heading'];
        $this->assertContent($identifier, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture   Magento/Cms/_files/pages.php
     * @magentoConfigFixture current_store easytranslate/cms_pages/attributes
     */
    public function testGetContentRespectsSettings2(): void
    {
        $identifier         = 'page100';
        $storeId            = 1;
        $includedAttributes = [];
        $excludedAttributes = ['title', 'content', 'meta_description', 'content_heading'];
        $this->assertContent($identifier, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture    Magento/Cms/_files/two_cms_page_with_same_url_for_different_stores.php
     */
    public function testGetContentTakesCorrectBasePage(): void
    {
        $identifier         = 'page1';
        $storeId            = 1;
        $includedAttributes = ['title'];
        $this->assertContent($identifier, $storeId, $includedAttributes, []);
        $secondStoreView = $this->storeManager->getStore('fixture_second_store');
        $storeId         = (int)$secondStoreView->getId();
        $this->assertContent($identifier, $storeId, $includedAttributes, []);
    }

    private function assertContent(
        string $identifier,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {
        /** @var Page $page */
        $page              = $this->getPageByIdentifier->execute($identifier, $storeId);
        $generatedContents = $this->cmsPageGenerator->getContent([$page->getId()], $storeId);
        foreach ($includedAttributes as $attributeCode) {
            $keyParts = [CmsPageGenerator::ENTITY_CODE, $page->getData(PageInterface::IDENTIFIER), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayHasKey($key, $generatedContents);
            $expectedContent = $page->getData($attributeCode);
            $actualContent   = $generatedContents[$key];
            self::assertEquals($expectedContent, $actualContent);
        }
        foreach ($excludedAttributes as $attributeCode) {
            $keyParts = [CmsPageGenerator::ENTITY_CODE, $page->getData(PageInterface::IDENTIFIER), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayNotHasKey($key, $generatedContents);
        }
    }
}
