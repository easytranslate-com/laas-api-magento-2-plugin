<?php

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;

abstract class AbstractEntity extends Extended
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMultipleRows($item): array
    {
        // we do not need this feature and it only leads to issues
        return [];
    }
}
