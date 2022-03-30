<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Product;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectDataProcessor;
use EasyTranslate\Connector\Model\Config\Source\Team;
use EasyTranslate\Connector\Model\ProjectFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;

class CreateProjectFromProduct extends Action
{
    /**
     * @var Team
     */
    private $team;

    /**
     * @var ProjectFactory
     */
    private $projectFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ProjectDataProcessor
     */
    private $projectDataProcessor;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    public function __construct(
        Context $context,
        Team $team,
        DataPersistorInterface $dataPersistor,
        DataObjectHelper $dataObjectHelper,
        ProjectDataProcessor $projectDataProcessor,
        ProjectFactory $projectFactory
    ) {
        parent::__construct($context);
        $this->team                 = $team;
        $this->projectFactory       = $projectFactory;
        $this->dataObjectHelper     = $dataObjectHelper;
        $this->projectDataProcessor = $projectDataProcessor;
        $this->dataPersistor        = $dataPersistor;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->projectData();
        $project        = $this->projectFactory->create();
        $this->dataObjectHelper->populateWithArray($project, $data, ProjectInterface::class);
        $project = $this->projectDataProcessor->saveProjectPostData($project, $data);
        $this->dataPersistor->clear('easytranslate_project');
        $this->messageManager->addSuccessMessage((string)__('You have created a new project'));

        return $resultRedirect->setPath(
            'easytranslate/project/edit',
            [ProjectInterface::PROJECT_ID => $project->getProjectId()]
        );
    }

    private function projectData(): array
    {
        return [
            'products'         => [$this->getRequest()->getParam('productIds')],
            'price-visibility' => ['visible' => 'false'],
            'name'             => 'Needs to be modified',
            'team'             => $this->team->toOptionArray()[0]['value'],
            'source_store_id'  => 0,
            'status'           => 'open',
            'price'            => null,
            'workflow'         => 'translation',
            'target_store_ids' => [1],
            'automatic_import' => 1

        ];
    }
}
