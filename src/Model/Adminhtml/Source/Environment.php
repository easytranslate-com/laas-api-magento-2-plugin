<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use EasyTranslate\RestApiClient\Api\Environment as ApiEnvironment;
use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => ApiEnvironment::SANDBOX,
                'label' => __('Sandbox'),
            ],
            [
                'value' => ApiEnvironment::LIVE,
                'label' => __('Live'),
            ]
        ];
    }
}
