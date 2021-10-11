<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Task extends AbstractDb
{
    /**
     * @var DateTime
     */
    private $coreDate;

    public function __construct(Context $context, DateTime $coreDate, $connectionName = null)
    {
        parent::__construct($context, $connectionName);
        $this->coreDate = $coreDate;
    }

    protected function _construct()
    {
        $this->_init('easytranslate_task', 'task_id');
    }

    protected function _beforeSave(AbstractModel $task): Task
    {
        if (!$task->getId()) {
            $task->setCreatedAt($this->coreDate->gmtDate());
        }

        return parent::_beforeSave($task);
    }
}
