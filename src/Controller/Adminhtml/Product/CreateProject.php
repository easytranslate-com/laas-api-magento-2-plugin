<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Product;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectDataProcessor;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Config\Source\Team;
use EasyTranslate\Connector\Model\ProjectFactory;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateProject extends Action
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
     * @var RedirectInterface
     */
    private $redirect;

    public function __construct(
        Context $context,
        Team $team,
        DataObjectHelper $dataObjectHelper,
        ProjectDataProcessor $projectDataProcessor,
        ProjectFactory $projectFactory,
        RedirectInterface $redirect
    ) {
        parent::__construct($context);
        $this->team                 = $team;
        $this->projectFactory       = $projectFactory;
        $this->dataObjectHelper     = $dataObjectHelper;
        $this->projectDataProcessor = $projectDataProcessor;
        $this->redirect             = $redirect;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $data = $this->projectData();
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $this->setRefererRedirectUrl($resultRedirect);
        }
        $project = $this->projectFactory->create();
        $this->dataObjectHelper->populateWithArray($project, $data, ProjectInterface::class);
        $project = $this->projectDataProcessor->saveProjectPostData($project, $data);
        $this->messageManager->addSuccessMessage(
        // @phpstan-ignore-next-line
            (string)__('The project has successfully been created. Please change the settings according to your needs before you send the project to EasyTranslate.') // phpcs:ignore
        );

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
            ProjectInterface::WORKFLOW         => '',
            ProjectInterface::TARGET_STORE_IDS => [],
            ProjectInterface::AUTOMATIC_IMPORT => 1,
        ];
    }

    /**
     * @throws LocalizedException
     */
    private function getProductIds(): array
    {
        $productIds = $this->getRequest()->getParam(Filter::SELECTED_PARAM);
        $productId  = $this->getRequest()->getParam('product_id');
        if (!empty($productIds)) {
            return $productIds;
        }
        if ($productId === null) {
            throw new LocalizedException(
                __('Could not find the requested product. Please check if the product exists.')
            );
        }

        return [$productId];
    }

    /**
     * @throws LocalizedException
     */
    private function getTeam()
    {
        if (empty($this->team->toOptionArray()[0])) {
            throw new LocalizedException(
                __('Could not create project. Please check your API and EasyTranslate settings.')
            );
        }

        return $this->team->toOptionArray()[0]['value'];
    }

    private function setRefererRedirectUrl(Redirect $resultRedirect): Redirect
    {
        return $resultRedirect->setPath($this->redirect->getRedirectUrl());
    }
}
