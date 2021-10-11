<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use Magento\Catalog\Model\Product;
use Magento\Framework\Data\OptionSourceInterface;

class ProductAttributes extends EavAttributes implements OptionSourceInterface
{
    protected const EXCLUDED_ATTRIBUTES = ['custom_layout_update', 'url_path', 'meta_keyword'];

    public function toOptionArray(): array
    {
        $productAttributes = $this->getEntityAttributes(Product::ENTITY);

        return $this->convertEntityAttributesToOptionArray($productAttributes);
    }
}
