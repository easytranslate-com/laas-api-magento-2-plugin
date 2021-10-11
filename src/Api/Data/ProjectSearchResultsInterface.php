<?php

namespace EasyTranslate\Connector\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ProjectSearchResultsInterface extends SearchResultsInterface
{
    public function getItems(): array;

    public function setItems(array $items): ProjectSearchResultsInterface;
}
