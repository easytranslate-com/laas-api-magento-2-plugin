<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectDataProcessor;
use EasyTranslate\Connector\Model\ProjectFactory;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Json\DecoderInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_save';

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

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        DataObjectHelper $dataObjectHelper,
        ProjectRepositoryInterface $projectRepository,
        ProjectDataProcessor $projectDataProcessor,
        ProjectFactory $projectFactory,
        DecoderInterface $decoder
    ) {
        parent::__construct($context);
        $this->dataPersistor        = $dataPersistor;
        $this->dataObjectHelper     = $dataObjectHelper;
        $this->projectRepository    = $projectRepository;
        $this->projectFactory       = $projectFactory;
        $this->projectDataProcessor = $projectDataProcessor;
        $this->decoder              = $decoder;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getParams();
        if ($data) {
            $data      = $this->decodeEntityFields($data);
            $projectId = (int)$this->getRequest()->getParam(ProjectInterface::PROJECT_ID);
            if ($projectId) {
                $project = $this->projectRepository->get($projectId);
            } else {
                $project = $this->projectFactory->create();
            }
            if (!$project->canEditDetails()) {
                return $resultRedirect->setPath('*/*/index');
            }
            if (empty($data[ProjectInterface::PRICE]) || $data[ProjectInterface::PRICE] === 'tbd') {
                $data[ProjectInterface::PRICE] = null;
            }
            $this->dataObjectHelper->populateWithArray($project, $data, ProjectInterface::class);
            try {
                $project = $this->projectDataProcessor->saveProjectPostData($project, $data);
                $this->messageManager->addSuccessMessage((string)__('You have saved the project'));
                $this->dataPersistor->clear('easytranslate_project');
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        [ProjectInterface::PROJECT_ID => $project->getProjectId()]
                    );
                }
            } catch (Exception $e) {
                $message = (string)__('Something went wrong while saving the project.');
                $this->messageManager->addExceptionMessage($e, $message);
                $this->dataPersistor->set('easytranslate_project', $data);
            }
        }

        return $resultRedirect->setPath('*/*/index');
    }

    private function decodeEntityFields(array $data): array
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

        return $data;
    }
}
