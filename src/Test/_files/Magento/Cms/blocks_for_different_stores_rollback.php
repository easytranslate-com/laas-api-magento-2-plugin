<?php

// adopted from Magento 2.4 for 2.3-compatibility - can be deleted when we drop 2.3 support

declare(strict_types=1);

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var BlockRepositoryInterface $blockRepository */
$blockRepository = $objectManager->get(BlockRepositoryInterface::class);

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter(BlockInterface::IDENTIFIER, '%test-block%', 'like')
    ->create();
$result = $blockRepository->getList($searchCriteria);

foreach ($result->getItems() as $item) {
    $blockRepository->delete($item);
}
