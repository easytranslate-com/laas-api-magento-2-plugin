<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Product extends AbstractEavGenerator
{
    public const ENTITY_CODE = 'catalog_product';

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    public function __construct(Config $config, ProductCollectionFactory $productCollectionFactory)
    {
        parent::__construct($config);
        $this->attributeCodes           = $this->config->getProductsAttributes();
        $this->productCollectionFactory = $productCollectionFactory;
    }

    protected function getCollection(array $modelIds, int $storeId): ProductCollection
    {
        return $this->productCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($this->attributeCodes)
            ->addAttributeToFilter('entity_id', ['in' => $modelIds]);
    }
}
