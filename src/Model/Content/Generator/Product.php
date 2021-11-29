<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;

class Product extends AbstractEavGenerator
{
    public const ENTITY_CODE = 'catalog_product';

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    public function __construct(
        Config $config,
        VersionManagerFactory $versionManagerFactory,
        ProductCollectionFactory $productCollectionFactory
    ) {
        parent::__construct($config, $versionManagerFactory);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeCodes           = $this->config->getProductsAttributes();
    }

    protected function getCollection(ProjectModel $project, int $storeId): AbstractDb
    {
        return $this->productCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect($this->attributeCodes)
            ->addAttributeToFilter('entity_id', ['in' => $project->getProducts()]);
    }
}
