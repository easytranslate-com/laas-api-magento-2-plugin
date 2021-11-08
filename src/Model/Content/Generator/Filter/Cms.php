<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator\Filter;

use Magento\Cms\Model\ResourceModel\Block\Collection as CmsResourceCollection;
use Magento\Cms\Model\ResourceModel\Page\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

class Cms
{
    /**
     * Filters CMS entities (blocks and pages), so that they are unique: If there is a store-specific entity, remove the
     * global entity
     *
     * @throws LocalizedException
     */
    public function filterEntities(AbstractDb $entities, array $identifiers): AbstractDb
    {
        if (!$entities instanceof CmsResourceCollection && !$entities instanceof Collection) {
            return $entities;
        }

        // make sure the stores are loaded
        $entities->walk('afterLoad');
        foreach ($identifiers as $identifier) {
            $entitiesWithIdentifier = $entities->getItemsByColumnValue('identifier', $identifier);
            if (count($entitiesWithIdentifier) < 2) {
                continue;
            }
            if (count($entitiesWithIdentifier) > 2) {
                throw new LocalizedException(__('The collection has more than two entities per identifier.'));
            }
            [$entityWithIdentifier1, $entityWithIdentifier2] = $entitiesWithIdentifier;
            if (in_array(0, $entityWithIdentifier1->getData('store_id'), false)) {
                $entityToRemove = $entityWithIdentifier1;
            } else {
                $entityToRemove = $entityWithIdentifier2;
            }
            $entities->removeItemByKey($entityToRemove->getId());
        }

        return $entities;
    }
}
