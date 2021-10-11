<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Importer;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;

class Product extends AbstractImporter
{
    /**
     * @var Action
     */
    private $productAction;

    /**
     * @var ProductResourceModel
     */
    private $productResource;

    public function __construct(ProductResourceModel $productResource, Action $productAction)
    {
        $this->productAction   = $productAction;
        $this->productResource = $productResource;
    }

    protected function importObject(string $id, array $attributes, int $sourceStoreId, int $targetStoreId): void
    {
        // entity has been deleted in the meantime, do nothing
        if (empty($this->productResource->getProductsSku([$id]))) {
            return;
        }
        $this->productAction->updateAttributes([$id], $attributes, $targetStoreId);
    }
}
