<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'sandbox',
                'label' => __('Sandbox'),
            ],
            [
                'value' => 'production',
                'label' => __('Production'),
            ]
        ];
    }
}
