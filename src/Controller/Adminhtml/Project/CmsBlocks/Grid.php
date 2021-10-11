<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project\CmsBlocks;

use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\CmsBlocks;
use EasyTranslate\Connector\Controller\Adminhtml\Project\AbstractEntityGrid;

class Grid extends AbstractEntityGrid
{
    protected function getGridBlock(): AbstractEntity
    {
        return $this->layoutFactory->create()->createBlock(CmsBlocks::class, 'project.cms_blocks.grid');
    }
}
