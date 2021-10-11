<?php

namespace EasyTranslate\Connector\Model\Content\Generator;

use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Framework\Model\AbstractModel;

abstract class AbstractEavGenerator extends AbstractGenerator
{
    protected $sortedAttributeCodes = [];

    protected function getAttributeCodes(AbstractModel $model): array
    {
        $attributeSetId = (int)$model->getData('attribute_set_id');
        $resourceModel  = $model->getResource();
        if (!$attributeSetId || !$resourceModel instanceof AbstractEntity) {
            return parent::getAttributeCodes($model);
        }

        if (!isset($this->sortedAttributeCodes[$attributeSetId])) {
            $allSortedAttributes                         = $resourceModel->loadAllAttributes($model)
                ->getSortedAttributes($attributeSetId);
            $allSortedAttributeCodes                     = array_keys($allSortedAttributes);
            $this->sortedAttributeCodes[$attributeSetId] = array_intersect(
                $allSortedAttributeCodes,
                $this->attributeCodes
            );
        }

        return $this->sortedAttributeCodes[$attributeSetId];
    }
}
