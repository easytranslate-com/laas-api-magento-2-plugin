<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Model\Content\Generator;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Content\Generator\CmsBlock as CmsBlockGenerator;
use EasyTranslate\Connector\Model\Project;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Block;
use Magento\Framework\Exception\NoSuchEntityException;
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

    /**
     * @var int
     */
    private static $projectId;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    protected function setUp(): void
    {
        $objectManager              = Bootstrap::getObjectManager();
        $this->cmsBlockGenerator    = $objectManager->create(CmsBlockGenerator::class);
        $this->getBlockByIdentifier = $objectManager->create(GetBlockByIdentifierInterface::class);
        $this->storeManager         = $objectManager->create(StoreManagerInterface::class);
        $this->projectRepository    = $objectManager->create(ProjectRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture   Magento/Cms/_files/block.php
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
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
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
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
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
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
     * @magentoDataFixture   loadMultipleWebsitesWithStoreGroupsStoresFixture
     * @magentoDataFixture   loadBlocksForDifferentStoresFixture
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
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

    /**
     * @throws NoSuchEntityException
     */
    private function assertContent(
        string $identifier,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {
        /** @var Block $block */
        $block   = $this->getBlockByIdentifier->execute($identifier, $storeId);
        $project = $this->projectRepository->get(self::$projectId);
        $project->setCmsBlocks([$block->getId()]);
        $project->setSourceStoreId($storeId);
        $generatedContents = $this->cmsBlockGenerator->getContent($project);
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

    public static function loadProjectFixture(): void
    {
        include __DIR__ . '/../../../_files/project.php';
        /** @var Project $project */
        // @phpstan-ignore-next-line
        self::$projectId = (int)$project->getId();
    }
}
