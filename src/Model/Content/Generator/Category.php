<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Category extends AbstractEavGenerator
{
    public const ENTITY_CODE = 'catalog_category';

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    public function __construct(Config $config, CategoryCollectionFactory $categoryCollectionFactory)
    {
        parent::__construct($config);
        $this->attributeCodes            = $this->config->getCategoriesAttributes();
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    protected function getCollection(array $modelIds, int $storeId): CategoryCollection
    {
        return $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($this->attributeCodes)
            ->addAttributeToFilter('entity_id', ['in' => $modelIds]);
    }
}
