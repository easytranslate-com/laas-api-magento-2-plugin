<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Callback;

use EasyTranslate\Connector\Model\ProjectFactory;
use EasyTranslate\Connector\Model\ResourceModel\Task\CollectionFactory as TaskCollectionFactory;
use EasyTranslate\RestApiClient\Api\Callback\DataConverter\TaskCompletedConverter;
use EasyTranslate\RestApiClient\TaskInterface;
use Magento\Framework\Exception\LocalizedException;

class TaskCompletedHandler
{
    /**
     * @var ProjectFactory
     */
    private $projectFactory;

    /**
     * @var TaskCollectionFactory
     */
    private $taskCollectionFactory;

    public function __construct(ProjectFactory $projectFactory, TaskCollectionFactory $taskCollectionFactory)
    {
        $this->projectFactory        = $projectFactory;
        $this->taskCollectionFactory = $taskCollectionFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function handle(array $data): void
    {
        $secret    = $data[LinkGenerator::SECRET_PARAM];
        $converter = new TaskCompletedConverter();
        $response  = $converter->convert($data);
        $project   = $this->projectFactory->create()->load($response->getProjectId(), 'external_id');
        if ($project->getData('secret') !== $secret) {
            throw new LocalizedException(__('Secret does not match.'));
        }
        $tasks = $this->taskCollectionFactory->create()->addFieldToFilter('external_id', $response->getTask()->getId());
        foreach ($tasks as $task) {
            $task->setData('status', $response->getTask()->getStatus());
            if ($response->getTask()->getStatus() === TaskInterface::STATUS_COMPLETED) {
                $task->setData('content_link', $response->getTask()->getTargetContent());
                // make sure the task is imported again if there is another update
                $task->setData('processed_at', null);
            }
            $task->save();
        }
    }
}
