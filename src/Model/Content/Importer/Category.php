<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Importer;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\Exception\NoSuchEntityException;

class Category extends AbstractImporter
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryResource
     */
    private $categoryResource;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryResource $categoryResource
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryResource   = $categoryResource;
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function importObject(string $id, array $attributes, int $sourceStoreId, int $targetStoreId): void
    {
        $category = $this->categoryRepository->get((int)$id);
        $category->addData($attributes);
        $attributeCodes = array_keys($attributes);
        foreach ($attributeCodes as $attributeCode) {
            $this->categoryResource->saveAttribute($category, $attributeCode);
        }
    }
}
