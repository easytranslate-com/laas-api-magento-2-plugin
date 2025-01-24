<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Locale\TargetMapper;
use EasyTranslate\Connector\Model\ResourceModel\Project\Collection;
use EasyTranslate\Connector\Model\ResourceModel\Task\Collection as TaskCollection;
use EasyTranslate\Connector\Model\TaskFactory;
use EasyTranslate\Connector\Model\ResourceModel\Task\CollectionFactory as TaskCollectionFactory;
use EasyTranslate\RestApiClient\ProjectInterface as ExternalProjectInterface;
use Exception;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Project extends AbstractModel implements ProjectInterface
{
    protected $_eventPrefix = 'easytranslate_project';

    /**
     * @var TaskCollectionFactory
     */
    private $taskCollectionFactory;

    /**
     * @var TargetMapper
     */
    private $mapper;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        TaskCollectionFactory $taskCollectionFactory,
        TargetMapper $mapper,
        ScopeConfigInterface $config,
        TaskFactory $taskFactory,
        ResourceModel\Project $resource = null,
        Collection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_init(ResourceModel\Project::class);
        $this->taskCollectionFactory = $taskCollectionFactory;
        $this->mapper                = $mapper;
        $this->config                = $config;
        $this->taskFactory           = $taskFactory;
    }

    public function getProjectId(): ?int
    {
        return (int)$this->getData(self::PROJECT_ID);
    }

    public function setProjectId(int $projectId): ProjectInterface
    {
        return $this->setData(self::PROJECT_ID, $projectId);
    }

    public function getExternalId(): ?string
    {
        return $this->getData(self::EXTERNAL_ID);
    }

    public function setExternalId(string $externalId): ProjectInterface
    {
        return $this->setData(self::EXTERNAL_ID, $externalId);
    }

    public function getSecret(): ?string
    {
        return $this->getData(self::SECRET);
    }

    public function getName(): ?string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): ProjectInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getTeam(): ?string
    {
        return $this->getData(self::TEAM);
    }

    public function setTeam(string $team): ProjectInterface
    {
        return $this->setData(self::TEAM, $team);
    }

    public function getSourceStoreId(): int
    {
        return (int)$this->getData(self::SOURCE_STORE_ID);
    }

    public function setSourceStoreId(int $sourceStoreId): ProjectInterface
    {
        return $this->setData(self::SOURCE_STORE_ID, $sourceStoreId);
    }

    public function getTargetStoreIds(): array
    {
        return (array)$this->getData(self::TARGET_STORE_IDS);
    }

    public function setTargetStoreIds(array $targetStoreIds): ProjectInterface
    {
        return $this->setData(self::TARGET_STORE_IDS, $targetStoreIds);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(string $status): ProjectInterface
    {
        if ($status) {
            return $this->setData(self::STATUS, $status);
        }

        return $this->setData(self::STATUS, 'open');
    }

    public function getPrice(): ?float
    {
        return (float)$this->getData(self::PRICE);
    }

    public function setPrice(?float $price): ProjectInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    public function getCurrency(): ?string
    {
        return $this->getData(self::CURRENCY);
    }

    public function setCurrency(string $currency): ProjectInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): ProjectInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): ProjectInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getWorkflow(): ?string
    {
        return $this->getData(self::WORKFLOW);
    }

    public function setWorkflow(string $workflow): ProjectInterface
    {
        return $this->setData(self::WORKFLOW, $workflow);
    }

    public function getWorkflowIdentifier(): ?string
    {
        return $this->getData(self::WORKFLOW_IDENTIFIER);
    }

    public function setWorkflowIdentifier(string $workflowIdentifier): ProjectInterface
    {
        return $this->setData(self::WORKFLOW_IDENTIFIER, $workflowIdentifier);
    }

    public function getWorkflowName(): ?string
    {
        return $this->getData(self::WORKFLOW_NAME);
    }

    public function setWorkflowName(string $workflowName): ProjectInterface
    {
        return $this->setData(self::WORKFLOW_NAME, $workflowName);
    }

    public function hasAutomaticImport(): bool
    {
        return (bool)$this->getData(self::AUTOMATIC_IMPORT);
    }

    public function setAutomaticImport(bool $automaticImport): ProjectInterface
    {
        return $this->setData(self::AUTOMATIC_IMPORT, $automaticImport);
    }

    public function getProducts(): array
    {
        $products = $this->getData(self::PRODUCTS);
        if ($products === null) {
            $products = $this->getResource()->getProducts($this);
            $this->setData(self::PRODUCTS, $products);
        }

        return $products;
    }

    public function setProducts($products): ProjectInterface
    {
        return $this->setData(self::PRODUCTS, $products);
    }

    public function getCategories(): array
    {
        $categories = $this->getData(self::CATEGORIES);
        if ($categories === null) {
            $categories = $this->getResource()->getCategories($this);
            $this->setData(self::CATEGORIES, $categories);
        }

        return $categories;
    }

    public function setCategories($categories): ProjectInterface
    {
        return $this->setData(self::CATEGORIES, $categories);
    }

    public function getCmsBlocks(): array
    {
        $cmsBlocks = $this->getData(self::CMS_BLOCKS);
        if ($cmsBlocks === null) {
            $cmsBlocks = $this->getResource()->getCmsBlocks($this);
            $this->setData(self::CMS_BLOCKS, $cmsBlocks);
        }

        return (array)$cmsBlocks;
    }

    public function setCmsBlocks($cmsBlocks): ProjectInterface
    {
        return $this->setData(self::CMS_BLOCKS, $cmsBlocks);
    }

    public function getCmsPages(): array
    {
        $cmsPages = $this->getData(self::CMS_PAGES);
        if ($cmsPages === null) {
            $cmsPages = $this->getResource()->getCmsPages($this);
            $this->setData(self::CMS_PAGES, $cmsPages);
        }

        return (array)$cmsPages;
    }

    public function setCmsPages($cmsPages): ProjectInterface
    {
        return $this->setData(self::CMS_PAGES, $cmsPages);
    }

    public function getTasks(): array
    {
        $tasks = $this->getData('tasks');
        if ($tasks === null) {
            $tasks = $this->getTaskCollection()->getItems();
            foreach ($tasks as $task) {
                $this->setData('tasks', $task->toArray());
            }
        }

        return $tasks;
    }

    public function getTaskCollection(): TaskCollection
    {
        /** @var TaskCollection $taskCollection */
        $taskCollection = $this->taskCollectionFactory->create();

        return $taskCollection->addFieldToFilter(ProjectInterface::PROJECT_ID, $this->getId());
    }

    public function canEditDetails(): bool
    {
        return !$this->getId() || $this->getData('status') === Status::OPEN;
    }

    public function requiresPriceApproval(): bool
    {
        return $this->getId() && $this->getData('status') === Status::PRICE_APPROVAL_REQUEST;
    }

    public function updateTasksStatus(): Project
    {
        $numberOfTasks = $this->getTaskCollection()->getSize();
        if ($numberOfTasks === 0) {
            return $this;
        }
        $numberOfCompletedTasks = $this->getTaskCollection()
            ->addFieldToFilter('processed_at', ['notnull' => true])
            ->getSize();
        if ($numberOfTasks === $numberOfCompletedTasks) {
            $this->setData('status', Status::FINISHED);
        } elseif ($numberOfCompletedTasks > 0) {
            $this->setData('status', Status::PARTIALLY_FINISHED);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function importDataFromExternalProject(ExternalProjectInterface $externalProject): void
    {
        $this->setData('external_id', $externalProject->getId());
        $this->setData('price', $externalProject->getPrice());
        $this->setData('currency', $externalProject->getCurrency());
        foreach ($externalProject->getTasks() as $externalTask) {
            $targetLanguage = $externalTask->getTargetLanguage();
            // one external task (language-specific) can result in multiple Magento tasks (store-specific)
            foreach ($this->getStoreIdsByTargetLanguage($targetLanguage) as $targetStoreId) {
                $magentoTask = $this->taskFactory->create();
                $magentoTask->setData(ProjectInterface::PROJECT_ID, $this->getId());
                $magentoTask->setData('external_id', $externalTask->getId());
                $magentoTask->setData('store_id', $targetStoreId);
                $magentoTask->setData('content_link', $externalTask->getTargetContent());
                $magentoTask->save();
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getStoreIdsByTargetLanguage(string $targetLanguage): array
    {
        $targetMagentoLocale = $this->mapper->mapExternalCodeToMagentoCode($targetLanguage);
        $storeIds            = [];
        foreach ($this->getData(ProjectInterface::TARGET_STORE_IDS) as $potentialStoreId) {
            $potentialStoreLocale = $this->config->getValue(
                Data::XML_PATH_DEFAULT_LOCALE,
                ScopeInterface::SCOPE_STORE,
                $potentialStoreId
            );
            if ($potentialStoreLocale === $targetMagentoLocale) {
                $storeIds[] = $potentialStoreId;
            }
        }

        return $storeIds;
    }
}
