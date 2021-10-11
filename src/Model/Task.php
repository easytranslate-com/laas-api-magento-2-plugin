<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Project as EasyTranslateProject;
use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Task extends AbstractModel
{
    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var EasyTranslateProject
     */
    protected $project;

    public function __construct(
        Context $context,
        Registry $registry,
        ProjectRepositoryInterface $projectRepository
    ) {
        parent::__construct($context, $registry);
        $this->_init(ResourceModel\Task::class);
        $this->projectRepository = $projectRepository;
    }

    public function getProject(): EasyTranslateProject
    {
        if ($this->project === null) {
            $projectId = $this->getData(ProjectInterface::PROJECT_ID);
            if ($projectId) {
                $this->project = $this->projectRepository->get((int)$projectId);
            }
        }

        return $this->project;
    }

    /**
     * @throws Exception
     */
    public function afterCommitCallback(): Task
    {
        parent::afterCommitCallback();

        $this->getProject()->updateTasksStatus()->save();

        return $this;
    }
}
