<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CmsPageAttributes implements OptionSourceInterface
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
            ],
            [
                'value' => 'meta_description',
                'label' => __('Meta Description'),
            ],
            [
                'value' => 'content_heading',
                'label' => __('Content Heading'),
            ],
            [
                'value' => 'identifier',
                'label' => __('URL Key'),
            ]
        ];
    }
}
