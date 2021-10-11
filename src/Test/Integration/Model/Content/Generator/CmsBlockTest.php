<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Model\Content\Generator;

use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Content\Generator\CmsBlock as CmsBlockGenerator;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Block;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CmsBlockTest extends TestCase
{
    /**
     * @var CmsBlockGenerator
     */
    private $cmsBlockGenerator;

    /**
     * @var GetBlockByIdentifierInterface
     */
    private $getBlockByIdentifier;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected function setUp(): void
    {
        $objectManager              = Bootstrap::getObjectManager();
        $this->cmsBlockGenerator    = $objectManager->create(CmsBlockGenerator::class);
        $this->getBlockByIdentifier = $objectManager->create(GetBlockByIdentifierInterface::class);
        $this->storeManager         = $objectManager->create(StoreManagerInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/block.php
     */
    public function testGetContent(): void
    {
        $identifier         = 'fixture_block';
        $storeId            = 1;
        $includedAttributes = ['title', 'content'];
        $this->assertContent($identifier, $storeId, $includedAttributes, []);
    }

    /**
     * @magentoDataFixture   Magento/Cms/_files/block.php
     * @magentoConfigFixture current_store easytranslate/cms_blocks/attributes title
     */
    public function testGetContentRespectsSettings(): void
    {
        $identifier         = 'fixture_block';
        $storeId            = 1;
        $includedAttributes = ['title'];
        $excludedAttributes = ['content'];
        $this->assertContent($identifier, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture   Magento/Cms/_files/block.php
     * @magentoConfigFixture current_store easytranslate/cms_blocks/attributes
     */
    public function testGetContentRespectsSettings2(): void
    {
        $identifier         = 'fixture_block';
        $storeId            = 1;
        $includedAttributes = [];
        $excludedAttributes = ['title', 'content'];
        $this->assertContent($identifier, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture loadMultipleWebsitesWithStoreGroupsStoresFixture
     * @magentoDataFixture loadBlocksForDifferentStoresFixture
     */
    public function testGetContentTakesCorrectBaseBlock(): void
    {
        $identifier         = 'test-block';
        $secondStoreView    = $this->storeManager->getStore('second_store_view');
        $storeId            = (int)$secondStoreView->getId();
        $includedAttributes = ['title', 'content'];
        $this->assertContent($identifier, $storeId, $includedAttributes, []);
        $thirdStoreView = $this->storeManager->getStore('third_store_view');
        $storeId        = (int)$thirdStoreView->getId();
        $this->assertContent($identifier, $storeId, $includedAttributes, []);
    }

    private function assertContent(
        string $identifier,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {
        /** @var Block $block */
        $block             = $this->getBlockByIdentifier->execute($identifier, $storeId);
        $generatedContents = $this->cmsBlockGenerator->getContent([$block->getId()], $storeId);
        foreach ($includedAttributes as $attributeCode) {
            $keyParts = [CmsBlockGenerator::ENTITY_CODE, $block->getData(BlockInterface::IDENTIFIER), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayHasKey($key, $generatedContents);
            $expectedContent = $block->getData($attributeCode);
            $actualContent   = $generatedContents[$key];
            self::assertEquals($expectedContent, $actualContent);
        }
        foreach ($excludedAttributes as $attributeCode) {
            $keyParts = [CmsBlockGenerator::ENTITY_CODE, $block->getData(BlockInterface::IDENTIFIER), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayNotHasKey($key, $generatedContents);
        }
    }

    public static function loadMultipleWebsitesWithStoreGroupsStoresFixture(): void
    {
        include __DIR__ . '/../../../../_files/Magento/Store/multiple_websites_with_store_groups_stores.php';
    }

    public static function loadBlocksForDifferentStoresFixture(): void
    {
        include __DIR__ . '/../../../../_files/Magento/Cms/blocks_for_different_stores.php';
    }
}
