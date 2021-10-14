<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectDataProcessor;
use EasyTranslate\Connector\Model\Bridge\ProjectFactory as BridgeProjectFactory;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\ProjectFactory;
use EasyTranslate\RestApiClient\Api\ProjectApi;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Json\DecoderInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_save_send';

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ProjectFactory
     */
    private $projectFactory;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var ProjectDataProcessor
     */
    private $projectDataProcessor;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var BridgeProjectFactory
     */
    private $bridgeProjectFactory;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        DataObjectHelper $dataObjectHelper,
        ProjectRepositoryInterface $projectRepository,
        ProjectDataProcessor $projectDataProcessor,
        ProjectFactory $projectFactory,
        DecoderInterface $decoder,
        Config $config,
        BridgeProjectFactory $bridgeProjectFactory
    ) {
        parent::__construct($context);
        $this->dataPersistor        = $dataPersistor;
        $this->dataObjectHelper     = $dataObjectHelper;
        $this->projectRepository    = $projectRepository;
        $this->projectFactory       = $projectFactory;
        $this->projectDataProcessor = $projectDataProcessor;
        $this->decoder              = $decoder;
        $this->config               = $config;
        $this->bridgeProjectFactory = $bridgeProjectFactory;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getParams();
        if ($data) {
            $data              = $this->processFormData($data);
            $projectId         = (int)$this->getRequest()->getParam(ProjectInterface::PROJECT_ID);
            $shouldSendProject = (bool)$this->getRequest()->getParam('send');
            if ($projectId) {
                $project = $this->projectRepository->get($projectId);
            } else {
                $project = $this->projectFactory->create();
            }
            if (!$project->canEditDetails()) {
                return $resultRedirect->setPath('*/*/index');
            }

            $this->dataObjectHelper->populateWithArray($project, $data, ProjectInterface::class);
            try {
                $project = $this->projectDataProcessor->saveProjectPostData($project, $data);
                $this->dataPersistor->clear('easytranslate_project');
                if (!$shouldSendProject) {
                    $this->messageManager->addSuccessMessage((string)__('You have saved the project'));
                }
            } catch (Exception $e) {
                $message = (string)__('Something went wrong while saving the project.');
                $this->messageManager->addExceptionMessage($e, $message);
                $this->dataPersistor->set('easytranslate_project', $data);

                return $resultRedirect->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $project->getProjectId()]);
            }

            if ($shouldSendProject) {
                try {
                    $this->sendProject($project);
                    $this->messageManager->addSuccessMessage(
                        __('The project has successfully been sent to EasyTranslate.')
                    );
                } catch (Exception $e) {
                    $message = (string)__('The project could not be sent to EasyTranslate.');
                    $this->messageManager->addExceptionMessage($e, $message);

                    return $resultRedirect->setPath(
                        '*/*/edit',
                        [ProjectInterface::PROJECT_ID => $project->getProjectId()]
                    );
                }
            }
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $project->getProjectId()]);
            }
        }

        return $resultRedirect->setPath('*/*/index');
    }

    private function processFormData(array $data): array
    {
        $entityFields = [
            ProjectInterface::PRODUCTS,
            ProjectInterface::CATEGORIES,
            ProjectInterface::CMS_BLOCKS,
            ProjectInterface::CMS_PAGES,
        ];
        foreach ($entityFields as $entityField) {
            if (isset($data[$entityField])) {
                $data[$entityField] = $this->decoder->decode($data[$entityField]);
            }
        }

        if (empty($data[ProjectInterface::PRICE]) || $data[ProjectInterface::PRICE] === 'tbd') {
            $data[ProjectInterface::PRICE] = null;
        }

        return $data;
    }

    private function sendProject(ProjectInterface $project): void
    {
        $projectApi    = new ProjectApi($this->config->getApiConfiguration());
        $bridgeProject = $this->bridgeProjectFactory->create();
        $bridgeProject->bindMagentoProject($project);
        $projectResponse = $projectApi->sendProject($bridgeProject);
        $externalProject = $projectResponse->getProject();
        $project->setData('status', Status::SENT);
        $project->importDataFromExternalProject($externalProject);
        $this->projectRepository->save($project);
    }
}
