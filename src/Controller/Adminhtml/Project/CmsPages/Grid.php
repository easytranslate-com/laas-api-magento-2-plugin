<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project\CmsPages;

use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\CmsPages;
use EasyTranslate\Connector\Controller\Adminhtml\Project\AbstractEntityGrid;

class Grid extends AbstractEntityGrid
{
    protected function getGridBlock(): AbstractEntity
    {
        return $this->layoutFactory->create()->createBlock(CmsPages::class, 'project.cms_pages.grid');
    }
}
