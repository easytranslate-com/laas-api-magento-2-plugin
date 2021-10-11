<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml\Source;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

abstract class EavAttributes
{
    protected const EXCLUDED_ATTRIBUTES = [];

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->attributeRepository          = $attributeRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    protected function getEntityAttributes($entityTypeCode): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria        = $searchCriteriaBuilder
            ->addFilter('frontend_input', ['text', 'textarea'], 'in')
            ->addFilter('attribute_code', static::EXCLUDED_ATTRIBUTES, 'nin')
            ->addFilter('is_global', false)
            ->create();

        return $this->attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();
    }

    public function convertEntityAttributesToOptionArray(array $attributes): array
    {
        $options = [];
        foreach ($attributes as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getData('frontend_label')
            ];
        }

        return $options;
    }
}
