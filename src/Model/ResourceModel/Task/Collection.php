<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\ResourceModel\Task;

use EasyTranslate\Connector\Model\ResourceModel\Task as TaskResource;
use EasyTranslate\Connector\Model\Task as TaskModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct(): void
    {
        $this->_init(TaskModel::class, TaskResource::class);
    }
}
