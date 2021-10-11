<?php

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_delete';

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    public function __construct(
        Context $context,
        ProjectRepositoryInterface $projectRepository,
        ProjectGetter $projectGetter
    ) {
        parent::__construct($context);
        $this->projectRepository = $projectRepository;
        $this->projectGetter     = $projectGetter;
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $project        = $this->projectGetter->getProject();
        if (!$project) {
            $this->messageManager->addErrorMessage((string)__('We can\'t find a project to delete.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            if (!$project->canEditDetails()) {
                return $resultRedirect->setPath('*/*/index');
            }
            $this->projectRepository->deleteById($project->getProjectId());
            $this->messageManager->addSuccessMessage((string)__('You deleted the project.'));

            return $resultRedirect->setPath('*/*/');
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $project->getProjectId()]);
        }
    }
}
