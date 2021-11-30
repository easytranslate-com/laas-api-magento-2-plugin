<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Model\Content\Generator;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Content\Generator\Category as CategoryGenerator;
use EasyTranslate\Connector\Model\Project;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /**
     * @var CategoryGenerator
     */
    private $categoryGenerator;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

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
        $objectManager            = Bootstrap::getObjectManager();
        $this->categoryGenerator  = $objectManager->create(CategoryGenerator::class);
        $this->categoryRepository = $objectManager->create(CategoryRepositoryInterface::class);
        $this->storeManager       = $objectManager->create(StoreManagerInterface::class);
        $this->projectRepository  = $objectManager->create(ProjectRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture loadProjectFixture
     * @throws NoSuchEntityException
     */
    public function testGetContent(): void
    {
        $id                 = 333;
        $storeId            = 1;
        $includedAttributes = ['name'];
        $this->assertContent($id, $storeId, $includedAttributes, []);
    }

    /**
     * @magentoDataFixture   Magento/Catalog/_files/category.php
     * @magentoConfigFixture current_store easytranslate/categories/attributes
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
     */
    public function testGetContentRespectsSettings(): void
    {
        $id                 = 333;
        $storeId            = 1;
        $includedAttributes = [];
        $excludedAttributes = ['name'];
        $this->assertContent($id, $storeId, $includedAttributes, $excludedAttributes);
    }

    /**
     * @magentoDataFixture   Magento/Catalog/_files/category.php
     * @magentoDataFixture   Magento/Store/_files/store.php
     * @magentoDataFixture   loadCategoryOnSecondStoreFixture
     * @magentoDataFixture   loadProjectFixture
     * @throws NoSuchEntityException
     */
    public function testGetContentTakesCorrectBaseCategory(): void
    {
        $id                 = 333;
        $storeId            = 1;
        $includedAttributes = ['name'];
        $this->assertContent($id, $storeId, $includedAttributes, []);
        $secondStoreView = $this->storeManager->getStore('test');
        $storeId         = (int)$secondStoreView->getId();
        $this->assertContent($id, $storeId, $includedAttributes, []);
    }

    /**
     * @throws NoSuchEntityException
     */
    private function assertContent(
        int $id,
        int $storeId,
        array $includedAttributes,
        array $excludedAttributes
    ): void {
        /** @var Category $category */
        $category = $this->categoryRepository->get($id, $storeId);
        $project  = $this->projectRepository->get(self::$projectId);
        $project->setCategories([$category->getId()]);
        $project->setSourceStoreId($storeId);
        $generatedContents = $this->categoryGenerator->getContent($project);
        foreach ($includedAttributes as $attributeCode) {
            $keyParts = [CategoryGenerator::ENTITY_CODE, $category->getId(), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayHasKey($key, $generatedContents);
            $expectedContent = $category->getData($attributeCode);
            $actualContent   = $generatedContents[$key];
            self::assertEquals($expectedContent, $actualContent);
        }
        foreach ($excludedAttributes as $attributeCode) {
            $keyParts = [CategoryGenerator::ENTITY_CODE, $category->getId(), $attributeCode];
            $key      = implode(AbstractGenerator::KEY_SEPARATOR, $keyParts);
            self::assertArrayNotHasKey($key, $generatedContents);
        }
    }

    public static function loadCategoryOnSecondStoreFixture(): void
    {
        include __DIR__ . '/../../../../_files/Magento/Catalog/category_on_second_store.php';
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
