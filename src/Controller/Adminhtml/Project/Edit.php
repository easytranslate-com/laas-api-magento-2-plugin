<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_save_send';
    public const MENU_ID = 'EasyTranslate_Connector::projects';

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(Context $context, ProjectRepositoryInterface $projectRepository)
    {
        parent::__construct($context);
        $this->projectRepository = $projectRepository;
    }

    public function execute()
    {
        $projectId = (int)$this->getRequest()->getParam(ProjectInterface::PROJECT_ID);
        if ($projectId) {
            try {
                $project   = $this->projectRepository->get($projectId);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage((string)__('This project no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $label      = $projectId ? (string)__('Edit Project') : (string)__('New Project');
        $this->initPage($resultPage)->addBreadcrumb($label, $label);
        $resultPage->getConfig()->getTitle()->prepend((string)__('Projects'));
        $title = (string)__('New Project');
        if (isset($project) && $project->getProjectId()) {
            $title = (string)__(
                'Edit Project "%1" (#%2)',
                $project->getName(),
                $project->getProjectId()
            );
        }
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }

    private function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb((string)__('EasyTranslate'), (string)__('EasyTranslate'))
            ->addBreadcrumb((string)__('Project'), (string)__('Project'));

        return $resultPage;
    }
}
