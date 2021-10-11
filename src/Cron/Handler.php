<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Cron;

use EasyTranslate\Connector\Model\Bridge\ProjectFactory as BridgeProjectFactory;
use EasyTranslate\Connector\Model\Bridge\TaskFactory as BridgeTaskFactory;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Content\Importer;
use EasyTranslate\Connector\Model\Project;
use EasyTranslate\Connector\Model\ResourceModel\Task\CollectionFactory as TaskCollectionFactory;
use EasyTranslate\Connector\Model\Task;
use EasyTranslate\RestApiClient\Api\TaskApi;
use Magento\Framework\Data\Collection;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Handler
{
    /**
     * @var TaskCollectionFactory
     */
    private $taskCollectionFactory;

    /**
     * @var Importer
     */
    private $importer;

    /**
     * @var DateTime
     */
    private $coreDate;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var BridgeProjectFactory
     */
    private $bridgeProjectFactory;

    /**
     * @var BridgeTaskFactory
     */
    private $bridgeTaskFactory;

    public function __construct(
        TaskCollectionFactory $taskCollectionFactory,
        Importer $importer,
        DateTime $coreDate,
        Config $config,
        BridgeProjectFactory $bridgeProjectFactory,
        BridgeTaskFactory $bridgeTaskFactory
    ) {
        $this->taskCollectionFactory = $taskCollectionFactory;
        $this->importer              = $importer;
        $this->coreDate              = $coreDate;
        $this->config                = $config;
        $this->bridgeProjectFactory  = $bridgeProjectFactory;
        $this->bridgeTaskFactory     = $bridgeTaskFactory;
    }

    public function execute(): void
    {
        $taskCollection = $this->taskCollectionFactory->create();
        $task           = $taskCollection
            ->addFieldToFilter('processed_at', ['null' => true])
            ->addFieldToFilter('content_link', ['notnull' => true])
            ->join(
                ['project' => 'easytranslate_project'],
                'main_table.project_id = project.project_id',
                ['automatic_import']
            )
            ->addFieldToFilter('automatic_import', 1)
            ->setOrder('created_at', Collection::SORT_ORDER_ASC)
            ->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();
        if (!$task->getId()) {
            return;
        }
        $project       = $task->getProject();
        $targetContent = $this->loadTargetContent($project, $task);
        $sourceStoreId = (int)$project->getData('source_store_id');
        $targetStoreId = (int)$task->getData('store_id');
        $this->importer->import($targetContent, $sourceStoreId, $targetStoreId);
        $task->setData('processed_at', $this->coreDate->gmtDate());
        $task->save();
    }

    private function loadTargetContent(Project $project, Task $task): array
    {
        $configuration = $this->config->getApiConfiguration();
        $taskApi       = new TaskApi($configuration);
        $bridgeProject = $this->bridgeProjectFactory->create();
        $bridgeProject->bindMagentoProject($project);
        $bridgeTask = $this->bridgeTaskFactory->create();
        $bridgeTask->bindMagentoTask($task);

        return $taskApi->downloadTaskTarget($bridgeProject, $bridgeTask)->getData();
    }
}
