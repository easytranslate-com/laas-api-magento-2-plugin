<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;

class Category extends AbstractEavGenerator
{
    public const ENTITY_CODE = 'catalog_category';

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    public function __construct(
        Config $config,
        VersionManagerFactory $versionManagerFactory,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($config, $versionManagerFactory);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attributeCodes            = $this->config->getCategoriesAttributes();
    }

    protected function getCollection(array $modelIds, int $storeId): AbstractDb
    {
        return $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($this->attributeCodes)
            ->addAttributeToFilter('entity_id', ['in' => $modelIds]);
    }
}
