<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Workflow implements OptionSourceInterface
{
    private function getOptions(): array
    {
        return [
            'translation'              => __('Translation'),
            'translation+review'       => __('Translation and review'),
            'self+machine_translation' => __('Translate yourself'),
        ];
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
