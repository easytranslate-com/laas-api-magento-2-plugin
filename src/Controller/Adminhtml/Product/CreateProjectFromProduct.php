<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Product;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectDataProcessor;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Config\Source\Team;
use EasyTranslate\Connector\Model\ProjectFactory;
use EasyTranslate\RestApiClient\Workflow;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

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
        try {
            $data = $this->projectData();
        } catch (Exception $exception) {
            return $resultRedirect->setPath('catalog/product/edit/id/' . $this->getProductIds()[0]);
        }
        $project = $this->projectFactory->create();
        $this->dataObjectHelper->populateWithArray($project, $data, ProjectInterface::class);
        $project = $this->projectDataProcessor->saveProjectPostData($project, $data);
        $this->dataPersistor->clear('easytranslate_project');
        $this->messageManager->addSuccessMessage((string)__('You have created a new project'));

        return $resultRedirect->setPath(
            'easytranslate/project/edit',
            [ProjectInterface::PROJECT_ID => $project->getProjectId()]
        );
    }

    /**
     * @throws LocalizedException
     */
    private function projectData(): array
    {
        return [
            ProjectInterface::PRODUCTS         => $this->getProductIds(),
            ProjectInterface::NAME             => 'Easytranslate Project Name',
            ProjectInterface::TEAM             => $this->getTeam(),
            ProjectInterface::SOURCE_STORE_ID  => 0,
            ProjectInterface::STATUS           => Status::OPEN,
            ProjectInterface::PRICE            => null,
            ProjectInterface::WORKFLOW         => Workflow::TYPE_TRANSLATION,
            ProjectInterface::TARGET_STORE_IDS => [1],
            ProjectInterface::AUTOMATIC_IMPORT => 1,
            'price-visibility'                 => ['visible' => 'false']
        ];
    }

    private function getProductIds(): array
    {
        $productIds = $this->getRequest()->getParam(Filter::SELECTED_PARAM);
        if (!empty($productIds)) {
            return $productIds;
        }

        return [$this->getRequest()->getParam('product_id')];
    }

    /**
     * @throws LocalizedException
     */
    private function getTeam()
    {
        if (empty($this->team->toOptionArray()[0])) {
            $this->messageManager->addErrorMessage(__('Could not create project. Please check your credentials'));
            throw new LocalizedException(__('Could not create project. Please check your credentials'));
        }

        return $this->team->toOptionArray()[0]['value'];
    }
}
