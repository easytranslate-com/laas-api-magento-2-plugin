<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\ResourceModel\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Project;
use EasyTranslate\Connector\Model\ResourceModel\Project as ProjectResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct(): void
    {
        $this->_init(Project::class, ProjectResource::class);
    }

    protected function _afterLoad(): Collection
    {
        $linkedIds = $this->getColumnValues(ProjectInterface::PROJECT_ID);
        if (!count($linkedIds)) {
            return parent::_afterLoad();
        }

        $connection = $this->getConnection();
        $select     = $connection->select()->from(['epts' => $this->getTable('easytranslate_project_target_store')])
            ->where('epts.' . ProjectInterface::PROJECT_ID . ' IN (?)', $linkedIds);
        $result     = $connection->fetchAll($select);
        if ($result) {
            $storesData = [];
            foreach ($result as $storeData) {
                $storesData[$storeData[ProjectInterface::PROJECT_ID]][] = $storeData['target_store_id'];
            }

            foreach ($this as $item) {
                $linkedId = $item->getData(ProjectInterface::PROJECT_ID);
                if (!isset($storesData[$linkedId])) {
                    continue;
                }
                $item->setData(ProjectInterface::TARGET_STORE_IDS, $storesData[$linkedId]);
            }
        }

        return parent::_afterLoad();
    }
}
