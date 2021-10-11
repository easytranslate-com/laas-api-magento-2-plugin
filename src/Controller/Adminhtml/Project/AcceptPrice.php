<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Bridge\ProjectFactory as BridgeProjectFactory;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\RestApiClient\Api\ProjectApi;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class AcceptPrice extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_save';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var BridgeProjectFactory
     */
    private $bridgeProjectFactory;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(
        Context $context,
        Config $config,
        BridgeProjectFactory $bridgeProjectFactory,
        ProjectRepositoryInterface $projectRepository
    ) {
        parent::__construct($context);
        $this->config               = $config;
        $this->bridgeProjectFactory = $bridgeProjectFactory;
        $this->projectRepository    = $projectRepository;
    }

    public function execute()
    {
        $projectId     = (int)$this->getRequest()->getParam(ProjectInterface::PROJECT_ID);
        $project       = $this->projectRepository->get($projectId);
        $bridgeProject = $this->bridgeProjectFactory->create();
        $bridgeProject->bindMagentoProject($project);
        $projectApi = new ProjectApi($this->config->getApiConfiguration());
        try {
            $projectApi->acceptPrice($bridgeProject);
            $project->setData('status', Status::PRICE_ACCEPTED);
            $project->save();
            $this->messageManager->addSuccessMessage(__('The price of the project has successfully been accepted.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
        }

        return $this->resultRedirectFactory->create()
            ->setPath('*/*/edit', [ProjectInterface::PROJECT_ID => $project->getProjectId()]);
    }
}
