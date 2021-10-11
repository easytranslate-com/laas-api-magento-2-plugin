<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;

class ScheduleImport extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_import';

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        Context $context,
        ProjectRepositoryInterface $projectRepository,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->projectRepository = $projectRepository;
        $this->request           = $request;
    }

    public function execute()
    {
        $projectId = (int)$this->request->getParam(ProjectInterface::PROJECT_ID);
        $project   = $this->projectRepository->get($projectId);
        $project->setAutomaticImport(true);
        $this->projectRepository->save($project);
        $this->messageManager->addSuccessMessage(__('The import has been scheduled'));

        return $this->resultRedirectFactory->create()
            ->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $project->getProjectId()]);
    }
}
