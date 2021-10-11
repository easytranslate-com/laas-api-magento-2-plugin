<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CmsBlockAttributes implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'title',
                'label' => __('Title'),
            ],
            [
                'value' => 'content',
                'label' => __('Content'),
            ]
        ];
    }
}
