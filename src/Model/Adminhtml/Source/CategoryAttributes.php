<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use Magento\Catalog\Model\Category;
use Magento\Framework\Data\OptionSourceInterface;

class CategoryAttributes extends EavAttributes implements OptionSourceInterface
{
    protected const EXCLUDED_ATTRIBUTES = ['custom_layout_update', 'url_path', 'meta_keywords', 'filter_price_range'];

    public function toOptionArray(): array
    {
        $categoryAttributes = $this->getEntityAttributes(Category::ENTITY);

        return $this->convertEntityAttributesToOptionArray($categoryAttributes);
    }
}
