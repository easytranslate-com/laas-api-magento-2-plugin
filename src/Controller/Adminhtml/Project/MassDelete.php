<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Project;
use EasyTranslate\Connector\Model\ResourceModel\Project\CollectionFactory as ProjectCollectionFactory;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_delete';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var ProjectCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(
        Context $context,
        Filter $filter,
        ProjectCollectionFactory $collectionFactory,
        ProjectRepositoryInterface $projectRepository
    ) {
        parent::__construct($context);
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->projectRepository = $projectRepository;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(): Redirect
    {
        $collection              = $this->collectionFactory->create();
        $filteredCollection      = $this->filter->getCollection($collection);
        $numberOfDeletedProjects = 0;
        $addWarning              = false;
        /** @var Project $project */
        foreach ($filteredCollection->getItems() as $project) {
            try {
                if ($project->getStatus() === Status::OPEN) {
                    $this->projectRepository->delete($project);
                } else {
                    $addWarning = true;
                }

                $numberOfDeletedProjects++;
            } catch (Exception $exception) {
                $this->messageManager->addExceptionMessage(
                    $exception,
                    __('An error occurred while deleting record(s).')
                );
                $addWarning = true;
            }
        }

        if ($numberOfDeletedProjects) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $numberOfDeletedProjects)
            );
        }

        if ($addWarning) {
            $this->messageManager->addErrorMessage(
                __('One or more projects could not be deleted, because they have already been sent to EasyTranslate.')
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
