<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Block\Adminhtml\Project\Edit\GenericButton;

class BackButton extends GenericButton
{
    public function getButtonData(): array
    {
        return [
            'label'      => __('Back'),
            'on_click'   => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class'      => 'back',
            'sort_order' => 10
        ];
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/');
    }
}
