<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\ResourceModel\Project as ResourceProject;
use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @var ResourceProject
     */
    private $resource;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var ProjectFactory
     */
    private $projectFactory;

    public function __construct(
        ResourceProject $resource,
        DataObjectProcessor $dataObjectProcessor,
        ProjectFactory $projectFactory
    ) {
        $this->resource            = $resource;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->projectFactory      = $projectFactory;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function save(ProjectInterface $project): ProjectInterface
    {
        $projectData  = $this->dataObjectProcessor->buildOutputDataArray(
            $project,
            ProjectInterface::class
        );
        $projectModel = $this->projectFactory->create()->setData($projectData);
        try {
            $this->resource->save($projectModel);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the project: %1',
                $exception->getMessage()
            ));
        }

        return $projectModel;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function get(int $projectId): ProjectInterface
    {
        $project = $this->projectFactory->create();
        $this->resource->load($project, $projectId);
        if (!$project->getId()) {
            throw new NoSuchEntityException(__('Project with id "%1" does not exist.', $projectId));
        }

        return $project;
    }

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(ProjectInterface $project): bool
    {
        try {
            $projectModel = $this->projectFactory->create();
            $this->resource->load($projectModel, $project->getProjectId());
            $this->resource->delete($projectModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the Project: %1', $exception->getMessage()));
        }

        return true;
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $projectId): bool
    {
        return $this->delete($this->get($projectId));
    }
}
