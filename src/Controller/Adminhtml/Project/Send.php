<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectDataProcessor;
use EasyTranslate\Connector\Model\Bridge\ProjectFactory as BridgeProjectFactory;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\Connector\Model\ProjectFactory;
use EasyTranslate\RestApiClient\Api\ProjectApi;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class Send extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_send';

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var ProjectFactory
     */
    private $projectFactory;

    /**
     * @var BridgeProjectFactory
     */
    private $bridgeProjectFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProjectDataProcessor
     */
    private $projectDataProcessor;

    public function __construct(
        Context $context,
        ProjectRepositoryInterface $projectRepository,
        ProjectFactory $projectFactory,
        MessageManagerInterface $messageManager,
        BridgeProjectFactory $bridgeProjectFactory,
        ProjectDataProcessor $projectDataProcessor,
        Config $config
    ) {
        parent::__construct($context);
        $this->projectRepository    = $projectRepository;
        $this->projectFactory       = $projectFactory;
        $this->messageManager       = $messageManager;
        $this->bridgeProjectFactory = $bridgeProjectFactory;
        $this->config               = $config;
        $this->projectDataProcessor = $projectDataProcessor;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getParams();
        $projectId      = (int)$this->getRequest()->getParam(ProjectInterface::PROJECT_ID);
        if ($projectId) {
            $project = $this->projectRepository->get($projectId);
        } else {
            $project = $this->projectFactory->create();
        }
        if (!$this->projectDataProcessor->validateLocales($project)) {
            $this->messageManager->addErrorMessage(__('The project could not be sent to EasyTranslate.'));

            return $resultRedirect->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $projectId]);
        }
        try {
            $this->projectDataProcessor->saveProjectPostData($project, $data);
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));

            return $resultRedirect->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $projectId]);
        }
        try {
            $this->sendSaveProject($project);
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @throws Exception
     */
    private function sendSaveProject(ProjectModel $project): void
    {
        $projectApi = new ProjectApi($this->config->getApiConfiguration());
        $bridgeProject = $this->bridgeProjectFactory->create();
        $bridgeProject->bindMagentoProject($project);
        $projectResponse = $projectApi->sendProject($bridgeProject);
        $externalProject = $projectResponse->getProject();
        $project->setData('status', Status::SENT);
        $project->importDataFromExternalProject($externalProject);
        $project->save();
        $this->messageManager->addSuccessMessage(__('The project has successfully been sent to EasyTranslate.'));
    }
}
