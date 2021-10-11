<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Model\Content\Generator;

use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Content\Generator\Product as ProductGenerator;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var ProductGenerator
     */
    private $productGenerator;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected function setUp(): void
    {
        $objectManager           = Bootstrap::getObjectManager();
        $this->productGenerator  = $objectManager->create(ProductGenerator::class);
        $this->productRepository = $objectManager->create(ProductRepositoryInterface::class);
        $this->storeManager      = $objectManager->create(StoreManagerInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGetContent(): void
    {
        $id                 = 1;
        $storeId            = 1;
        $includedAttributes = ['name', 'short_description', 'description', 'meta_title', 'meta_description', 'url_key'];
        $this->assertContent($id, $storeId, $includedAttributes, []);
    }

    /**
     * @magentoDataFixture   Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store easytranslate/products/attributes name,description,meta_title
     */
    public function testGetContentRespectsSettings(): void
    {
        $id                 = 1;
        $storeId            = 1;
        $includedAttributes = ['name', 'description', 'meta_title'];
        $excludedAttributes = ['short_description', 'meta_description', 'url_key'];
        $this->assertContent($id, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture   Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store easytranslate/products/attributes
     */
    public function testGetContentRespectsSettings2(): void
    {
        $id                 = 1;
        $storeId            = 1;
        $includedAttributes = [];
        $excludedAttributes = ['name', 'short_description', 'description', 'meta_title', 'meta_description', 'url_key'];
        $this->assertContent($id, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixtureBeforeTransaction Magento/Catalog/_files/product_simple_multistore.php
     */
    public function testGetContentTakesCorrectBaseProduct(): void
    {
        $id                 = 1;
        $storeId            = 1;
        $includedAttributes = ['name', 'url_key'];
        $this->assertContent($id, $storeId, $includedAttributes, []);
        $secondStoreView = $this->storeManager->getStore('fixturestore');
        $storeId         = (int)$secondStoreView->getId();
        $this->assertContent($id, $storeId, $includedAttributes, []);
    }

    private function assertContent(
        int $id,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {
        /** @var Product $product */
        $product           = $this->productRepository->getById($id, false, $storeId);
        $generatedContents = $this->productGenerator->getContent([$product->getId()], $storeId);
        foreach ($includedAttributes as $attributeCode) {
            $keyParts = [ProductGenerator::ENTITY_CODE, $product->getId(), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayHasKey($key, $generatedContents);
            $expectedContent = $product->getData($attributeCode);
            $actualContent   = $generatedContents[$key];
            self::assertEquals($expectedContent, $actualContent);
        }
        foreach ($excludedAttributes as $attributeCode) {
            $keyParts = [ProductGenerator::ENTITY_CODE, $product->getId(), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayNotHasKey($key, $generatedContents);
        }
    }
}
