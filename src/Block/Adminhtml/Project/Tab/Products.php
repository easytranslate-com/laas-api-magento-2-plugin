<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Tab;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Directory\Model\Currency;
use Magento\Framework\Data\Collection as CollectionData;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Products extends AbstractEntity
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    /**
     * @var EventManager
     */
    private $eventManager;

    public function __construct(
        Context $context,
        Data $backendHelper,
        ProductCollectionFactory $productCollectionFactory,
        ProjectGetter $projectGetter,
        EventManager $eventManager
    ) {
        parent::__construct($context, $backendHelper);
        $this->setId('easytranslate_connector_products');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->projectGetter            = $projectGetter;
        $this->eventManager             = $eventManager;
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
        if ($column->getId() === ProjectInterface::PRODUCTS) {
            $productIds = $this->getSelectedProductIds();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } elseif (!empty($productIds)) {
                $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    protected function _prepareCollection(): Grid
    {
        $this->setDefaultFilter([ProjectInterface::PRODUCTS => 1]);
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(ProductInterface::NAME)
            ->addAttributeToSelect(ProductInterface::SKU)
            ->addAttributeToSelect(ProductInterface::PRICE);
        if ($this->projectGetter->getProject()) {
            $collection->addStoreFilter($this->projectGetter->getProject()->getSourceStoreId());
        }
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            // join stores in which products have already been added to a project / translated
            $projectProductTable     = $collection->getTable('easytranslate_project_product');
            $projectTargetStoreTable = $collection->getTable('easytranslate_project_target_store');
            $collection->getSelect()->joinLeft(
                ['etpp' => $projectProductTable],
                'etpp.product_id=e.entity_id',
                ['project_ids' => 'GROUP_CONCAT(DISTINCT etpp.project_id)']
            );
            $collection->getSelect()->joinLeft(
                ['etpts' => $projectTargetStoreTable],
                'etpts.project_id=etpp.project_id',
                ['translated_stores' => 'GROUP_CONCAT(DISTINCT target_store_id)']
            );
            $collection->groupByAttribute('entity_id');
        } else {
            $productIds = $this->getSelectedProductIds();
            $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        }
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($storeId > 0) {
            $collection->addStoreFilter($storeId);
        }
        $this->eventManager->dispatch('easytranslate_prepare_product_collection', ['product_collection' => $collection]);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @throws Exception
     */
    protected function _prepareColumns(): Extended
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(
                ProjectInterface::PRODUCTS,
                [
                    'type'             => 'checkbox',
                    'name'             => ProjectInterface::PRODUCTS,
                    'values'           => $this->getSelectedProductIds(),
                    'index'            => 'entity_id',
                    'header_css_class' => 'col-select col-massaction',
                    'column_css_class' => 'col-select col-massaction'
                ]
            );
        }
        $this->addColumn(
            'entity_id',
            [
                'header'           => __('ID'),
                'sortable'         => true,
                'index'            => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn('name', ['header' => __('Name'), 'index' => 'name']);
        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);
        $this->addColumn(
            'price',
            [
                'header'        => __('Price'),
                'type'          => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    Currency::XML_PATH_CURRENCY_BASE,
                    ScopeInterface::SCOPE_STORE
                ),
                'index'         => 'price'
            ]
        );
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(
                'translated_stores',
                [
                    'header'                    => __('Already Translated In'),
                    'index'                     => 'translated_stores',
                    'type'                      => 'store',
                    'store_all'                 => true,
                    'store_view'                => true,
                    'sortable'                  => false,
                    'filter_condition_callback' => [$this, 'filterTranslatedCondition'],
                ]
            );
        }
        $this->eventManager->dispatch('easytranslate_prepare_product_columns', ['columns' => $this]);

        return parent::_prepareColumns();
    }

    private function getSelectedProductIds(): array
    {
        $products = $this->getRequest()->getPost('selected_products');
        if ($products === null) {
            if ($this->projectGetter->getProject()) {
                return $this->projectGetter->getProject()->getProducts();
            }

            return [];
        }

        return explode(',', $products);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/project_products/grid', ['_current' => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    protected function filterTranslatedCondition(CollectionData $collection, Column $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value) {
            $collection->getSelect()->where('etpts.target_store_id=?', $value);
        }
    }
}
