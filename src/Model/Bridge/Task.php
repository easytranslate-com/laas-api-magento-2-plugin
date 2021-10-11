<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Bridge;

use EasyTranslate\Connector\Model\Bridge\ProjectFactory as BridgeProjectFactory;
use EasyTranslate\Connector\Model\Locale\TargetMapper;
use EasyTranslate\Connector\Model\Task as TaskModel;
use EasyTranslate\RestApiClient\ProjectInterface;
use EasyTranslate\RestApiClient\TaskInterface;
use Exception;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Task implements TaskInterface
{
    /**
     * @var TaskModel
     */
    private $task;

    /**
     * @var BridgeProjectFactory
     */
    private $bridgeProjectFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var TargetMapper
     */
    private $targetMapper;

    public function __construct(
        TaskModel $task,
        BridgeProjectFactory $bridgeProjectFactory,
        ScopeConfigInterface $config,
        TargetMapper $targetMapper
    ) {
        $this->task                 = $task;
        $this->bridgeProjectFactory = $bridgeProjectFactory;
        $this->config               = $config;
        $this->targetMapper         = $targetMapper;
    }

    public function bindMagentoTask(TaskModel $task): TaskInterface
    {
        $this->task = $task;

        return $this;
    }

    public function getId(): string
    {
        return $this->task->getData('external_id');
    }

    public function getProject(): ProjectInterface
    {
        $magentoProject = $this->task->getProject();

        return $this->bridgeProjectFactory->create()->bindMagentoProject($magentoProject);
    }

    public function getTargetContent(): ?string
    {
        return $this->task->getData('content_link');
    }

    /**
     * @throws Exception
     */
    public function getTargetLanguage(): string
    {
        $targetStore  = $this->task->getData('store_id');
        $targetLocale = $this->config->getValue(Data::XML_PATH_DEFAULT_LOCALE, $targetStore);

        return $this->targetMapper->mapMagentoCodeToExternalCode($targetLocale);
    }
}
