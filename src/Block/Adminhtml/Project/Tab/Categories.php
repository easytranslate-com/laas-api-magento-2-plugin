<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Tab;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class Categories extends AbstractEntity
{
    /**
     * @var CategoryCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    public function __construct(
        Context $context,
        Data $backendHelper,
        CategoryCollectionFactory $collectionFactory,
        ProjectGetter $projectGetter
    ) {
        parent::__construct($context, $backendHelper);
        $this->setId('easytranslate_connector_categories');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->collectionFactory = $collectionFactory;
        $this->projectGetter     = $projectGetter;
    }

    /**
     * @param Column $column
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in category flag
        if ($column->getId() === ProjectInterface::CATEGORIES) {
            $categoryIds = $this->getSelectedCategoryIds();
            if (empty($categoryIds)) {
                $categoryIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $categoryIds]);
            } elseif (!empty($categoryIds)) {
                $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $categoryIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    protected function _prepareCollection(): Grid
    {
        $this->setDefaultFilter([ProjectInterface::CATEGORIES => 1]);
        /** @var Collection $categoryCollection */
        $categoryCollection = $this->collectionFactory->create();
        $categoryCollection->addAttributeToSelect(CategoryInterface::KEY_NAME)
            ->addAttributeToFilter(CategoryInterface::KEY_LEVEL, ['gt' => 1]);
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            // join stores in which products have already been added to a project / translated
            $projectCategoryTable    = $categoryCollection->getTable('easytranslate_project_category');
            $projectTargetStoreTable = $categoryCollection->getTable('easytranslate_project_target_store');
            $categoryCollection->getSelect()->joinLeft(
                ['etpc' => $projectCategoryTable],
                'etpc.category_id=e.entity_id',
                ['project_ids' => 'GROUP_CONCAT(DISTINCT etpc.project_id)']
            );
            $categoryCollection->getSelect()->joinLeft(
                ['etpts' => $projectTargetStoreTable],
                'etpts.project_id=etpc.project_id',
                ['translated_stores' => 'GROUP_CONCAT(DISTINCT target_store_id)']
            );
            $categoryCollection->groupByAttribute('entity_id');
        } else {
            $categoryIds = $this->getSelectedCategoryIds();
            $categoryCollection->addFieldToFilter('entity_id', ['in' => $categoryIds]);
        }
        $categoryCollection->setStoreId($this->projectGetter->getProject()->getSourceStoreId());
        $this->setCollection($categoryCollection);

        return parent::_prepareCollection();
    }

    /**
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(ProjectInterface::CATEGORIES, [
                'type'             => 'checkbox',
                'name'             => ProjectInterface::CATEGORIES,
                'values'           => $this->getSelectedCategoryIds(),
                'index'            => 'entity_id',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction'
            ]);
        }
        $this->addColumn(
            'catalog_category_entity',
            [
                'header'           => __('ID'),
                'sortable'         => true,
                'index'            => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn('category_name', ['header' => __('Name'), 'index' => 'name']);
        $this->addColumn('category_url_key', ['header' => __('URL Key'), 'index' => 'url_key']);

        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(
                'translated_stores',
                [
                    'header'     => __('Already Translated In'),
                    'width'      => '250px',
                    'index'      => 'translated_stores',
                    'type'       => 'store',
                    'store_view' => true,
                    'sortable'   => false,
                ]
            );
        }

        return parent::_prepareColumns();
    }

    private function getSelectedCategoryIds(): array
    {
        $categories = $this->getRequest()->getPost('selected_categories');
        if ($categories === null) {
            if ($this->projectGetter->getProject()) {
                return $this->projectGetter->getProject()->getCategories();
            }

            return [];
        }

        return explode(',', $categories);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/project_categories/grid', ['_current' => true]);
    }
}
