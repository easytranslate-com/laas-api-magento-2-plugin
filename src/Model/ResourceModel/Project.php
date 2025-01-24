<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\ResourceModel;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\RestApiClient\Api\ApiException;
use EasyTranslate\RestApiClient\Api\TeamApi;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Project extends AbstractDb
{
    /**
     * @var DateTime
     */
    private $coreDate;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Context $context,
        DateTime $coreDate,
        ResourceConnection $resource,
        Config $config,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->coreDate   = $coreDate;
        $this->connection = $resource->getConnection();
        $this->config     = $config;
    }

    protected function _construct(): void
    {
        $this->_init('easytranslate_project', ProjectInterface::PROJECT_ID);
    }

    /**
     * @throws Exception
     */
    protected function _beforeSave(AbstractModel $project): Project
    {
        if (!$project->getData(ProjectInterface::PROJECT_ID)) {
            $project->setData(ProjectInterface::PROJECT_ID);
        }
        $project->setUpdatedAt($this->coreDate->gmtDate());
        $this->enrichWorkflowData($project);

        if ($project->getData('secret') === null) {
            $project->setData('secret', bin2hex(random_bytes(32)));
        }

        // make sure that we do not translate from and to the same language
        $sourceStoreId  = (array)$project->getData(ProjectInterface::SOURCE_STORE_ID);
        $targetStoreIds = (array)$project->getData(ProjectInterface::TARGET_STORE_IDS);
        $targetStoreIds = array_diff($targetStoreIds, $sourceStoreId);
        $project->setData(ProjectInterface::TARGET_STORE_IDS, $targetStoreIds);

        return parent::_beforeSave($project);
    }

    private function enrichWorkflowData(ProjectInterface $project): void
    {
        $workflowId = $project->getWorkflow();
        if (!$workflowId) {
            return;
        }
        try {
            $apiConfiguration    = $this->config->getApiConfiguration();
            $teamDetailsResponse = (new TeamApi($apiConfiguration))->getTeamDetails($project->getTeam() ?? '');
        } catch (ApiException $e) {
            return;
        }

        foreach ($teamDetailsResponse->getWorkflows() as $workflow) {
            if ($workflow->getId() !== $workflowId) {
                continue;
            }
            $project->setWorkflowIdentifier($workflow->getIdentifier());
            $project->setWorkflowName($workflow->getDisplayName());

            return;
        }
    }

    protected function _afterSave(AbstractModel $project)
    {
        $this->saveProjectStores($project);
        $this->saveProjectProducts($project);
        $this->saveProjectCategories($project);
        $this->saveProjectCmsBlocks($project);
        $this->saveProjectCmsPages($project);

        return parent::_afterSave($project);
    }

    private function saveProjectStores(ProjectModel $project): void
    {
        $projectId       = (int)$project->getId();
        $oldTargetStores = $this->lookupTargetStoreIds($projectId);
        $newTargetStores = $project->getData(ProjectInterface::TARGET_STORE_IDS);
        $table           = $this->getTable('easytranslate_project_target_store');
        $insert          = array_diff($newTargetStores, $oldTargetStores);
        $delete          = array_diff($oldTargetStores, $newTargetStores);
        if ($delete) {
            $where = [
                'project_id = ?'         => $projectId,
                'target_store_id IN (?)' => $delete
            ];

            $this->connection->delete($table, $where);
        }

        if ($insert) {
            $data = [];

            foreach ($insert as $storeId) {
                $data[] = [
                    ProjectInterface::PROJECT_ID => $projectId,
                    'target_store_id'            => (int)$storeId
                ];
            }

            $this->connection->insertMultiple($table, $data);
        }
    }

    private function saveProjectProducts(ProjectModel $project): void
    {
        $projectId   = (int)$project->getId();
        $newProducts = $project->getProducts();
        if ($newProducts === null) {
            return;
        }

        $oldProducts = $this->getProducts($project);
        if (empty($newProducts) && empty($oldProducts)) {
            return;
        }

        $table  = $this->getTable('easytranslate_project_product');
        $insert = array_diff($newProducts, $oldProducts);
        $delete = array_diff($oldProducts, $newProducts);

        if (!empty($delete)) {
            $where = [
                'product_id IN(?)' => $delete,
                'project_id=?'     => $projectId
            ];
            $this->connection->delete($table, $where);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $productId) {
                $data[] = [
                    ProjectInterface::PROJECT_ID => $projectId,
                    'product_id'                 => (int)$productId
                ];
            }
            $this->connection->insertMultiple($table, $data);
        }
    }

    private function saveProjectCategories(ProjectModel $project): void
    {
        $projectId     = (int)$project->getId();
        $newCategories = $project->getCategories();
        if ($newCategories === null) {
            return;
        }

        $oldCategories = $this->getCategories($project);
        if (empty($newCategories) && empty($oldCategories)) {
            return;
        }

        $table  = $this->getTable('easytranslate_project_category');
        $insert = array_diff($newCategories, $oldCategories);
        $delete = array_diff($oldCategories, $newCategories);

        if (!empty($delete)) {
            $where = [
                'category_id IN(?)' => $delete,
                'project_id=?'      => $projectId
            ];
            $this->connection->delete($table, $where);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $categoryId) {
                $data[] = [
                    ProjectInterface::PROJECT_ID => $projectId,
                    'category_id'                => (int)$categoryId
                ];
            }
            $this->connection->insertMultiple($table, $data);
        }
    }

    private function saveProjectCmsBlocks(ProjectModel $project): void
    {
        $projectId    = (int)$project->getId();
        $newCmsBlocks = $project->getCmsBlocks();
        if ($newCmsBlocks === null) {
            return;
        }

        $oldCmsBlocks = $this->getCmsBlocks($project);
        if (empty($oldCmsBlocks) && empty($newCmsBlocks)) {
            return;
        }

        $table  = $this->getTable('easytranslate_project_cms_block');
        $insert = array_diff($newCmsBlocks, $oldCmsBlocks);
        $delete = array_diff($oldCmsBlocks, $newCmsBlocks);

        if (!empty($delete)) {
            $where = [
                'block_id IN(?)' => $delete,
                'project_id=?'   => $projectId
            ];
            $this->connection->delete($table, $where);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $cmsBlockId) {
                $data[] = [
                    ProjectInterface::PROJECT_ID => $projectId,
                    'block_id'                   => (int)$cmsBlockId
                ];
            }
            $this->connection->insertMultiple($table, $data);
        }
    }

    private function saveProjectCmsPages(ProjectModel $project): void
    {
        $projectId   = (int)$project->getId();
        $newCmsPages = $project->getCmsPages();
        if ($newCmsPages === null) {
            return;
        }
        $oldCmsPages = $this->getCmsPages($project);
        if (empty($oldCmsPages) && empty($newCmsPages)) {
            return;
        }

        $table  = $this->getTable('easytranslate_project_cms_page');
        $insert = array_diff($newCmsPages, $oldCmsPages);
        $delete = array_diff($oldCmsPages, $newCmsPages);

        if (!empty($delete)) {
            $where = [
                'page_id IN(?)' => $delete,
                'project_id=?'  => $projectId
            ];
            $this->connection->delete($table, $where);
        }

        if (!empty($insert)) {
            $data = [];
            foreach ($insert as $cmsPageId) {
                $data[] = [
                    ProjectInterface::PROJECT_ID => $projectId,
                    'page_id'                    => (int)$cmsPageId
                ];
            }
            $this->connection->insertMultiple($table, $data);
        }
    }

    public function getProducts(ProjectModel $project): array
    {
        $select = $this->connection->select()
            ->from($this->getTable('easytranslate_project_product'), ['product_id'])
            ->where('project_id = :project_id');
        $bind   = [ProjectInterface::PROJECT_ID => (int)$project->getId()];

        return $this->connection->fetchCol($select, $bind);
    }

    public function getCategories(ProjectModel $project): array
    {
        $select = $this->connection->select()
            ->from($this->getTable('easytranslate_project_category'), ['category_id'])
            ->where('project_id = :project_id');
        $bind   = ['project_id' => (int)$project->getId()];

        return $this->connection->fetchCol($select, $bind);
    }

    public function getCmsBlocks(ProjectModel $project): array
    {
        $select = $this->connection->select()
            ->from($this->getTable('easytranslate_project_cms_block'), ['block_id'])
            ->where('project_id = :project_id');
        $bind   = ['project_id' => (int)$project->getId()];

        return $this->connection->fetchCol($select, $bind);
    }

    public function getCmsPages(ProjectModel $project): array
    {
        $select = $this->connection->select()
            ->from($this->getTable('easytranslate_project_cms_page'), ['page_id'])
            ->where('project_id = :project_id');
        $bind   = ['project_id' => (int)$project->getId()];

        return $this->connection->fetchCol($select, $bind);
    }

    protected function _afterLoad(AbstractModel $project)
    {
        if ($project->getId()) {
            $targetStoreIds = $this->lookupTargetStoreIds((int)$project->getId());
            $project->setData(ProjectInterface::TARGET_STORE_IDS, $targetStoreIds);
        }

        return parent::_afterLoad($project);
    }

    private function lookupTargetStoreIds(int $id): array
    {
        $select = $this->connection->select()
            ->from($this->getTable('easytranslate_project_target_store'), 'target_store_id')
            ->where('project_id = :project_id');
        $binds  = [':project_id' => $id];

        return $this->connection->fetchCol($select, $binds);
    }
}
