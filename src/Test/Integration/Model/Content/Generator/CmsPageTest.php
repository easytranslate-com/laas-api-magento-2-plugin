<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Model\Content\Generator;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Content\Generator\CmsPage as CmsPageGenerator;
use EasyTranslate\Connector\Model\Project;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\Exception\NoSuchEntityException;
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
        $objectManager             = Bootstrap::getObjectManager();
        $this->cmsPageGenerator    = $objectManager->create(CmsPageGenerator::class);
        $this->getPageByIdentifier = $objectManager->create(GetPageByIdentifierInterface::class);
        $this->storeManager        = $objectManager->create(StoreManagerInterface::class);
        $this->projectRepository   = $objectManager->create(ProjectRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     * @magentoDataFixture loadProjectFixture
     * @throws NoSuchEntityException
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
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
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
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
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
     * @magentoDataFixture    loadProjectFixture
     * @throws NoSuchEntityException
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

    /**
     * @throws NoSuchEntityException
     */
    private function assertContent(
        string $identifier,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {
        /** @var Page $page */
        $page    = $this->getPageByIdentifier->execute($identifier, $storeId);
        $project = $this->projectRepository->get(self::$projectId);
        $project->setCmsPages([$page->getId()]);
        $project->setSourceStoreId($storeId);
        $generatedContents = $this->cmsPageGenerator->getContent($project);
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

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public static function loadProjectFixture(): void
    {
        include __DIR__ . '/../../../_files/project.php';
        /** @var Project $project */
        // @phpstan-ignore-next-line
        self::$projectId = (int)$project->getId();
    }
}
