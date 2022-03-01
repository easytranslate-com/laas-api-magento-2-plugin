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
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as CmsBlockCollectionFactory;
use Magento\Framework\Data\Collection as CollectionData;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CmsBlocks extends AbstractEntity
{
    /**
     * @var CmsBlockCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Block
     */
    private $cmsBlock;

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
        CmsBlockCollectionFactory $collectionFactory,
        ProjectGetter $projectGetter,
        Block $cmsBlock,
        EventManager $eventManager
    ) {
        parent::__construct($context, $backendHelper);
        $this->setId('easytranslate_connector_cms_blocks');
        $this->setDefaultSort(BlockInterface::BLOCK_ID);
        $this->setUseAjax(true);
        $this->collectionFactory = $collectionFactory;
        $this->cmsBlock          = $cmsBlock;
        $this->projectGetter     = $projectGetter;
        $this->eventManager      = $eventManager;
    }

    /**
     * @param Column $column
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in cms flag
        if ($column->getId() === ProjectInterface::CMS_BLOCKS) {
            $cmsBlockIds = $this->getSelectedCmsBlockIds();
            if (empty($cmsBlockIds)) {
                $cmsBlockIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('main_table.block_id', ['in' => $cmsBlockIds]);
            } elseif (!empty($cmsBlockIds)) {
                $this->getCollection()->addFieldToFilter('main_table.block_id', ['nin' => $cmsBlockIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    protected function _prepareCollection(): Grid
    {
        $this->setDefaultFilter([ProjectInterface::CMS_BLOCKS => 1]);
        /** @var Collection $cmsBlocksCollection */
        $cmsBlocksCollection = $this->collectionFactory->create();
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            // join stores in which blocks have already been added to a project / translated
            $projectCmsBlockTable    = $cmsBlocksCollection->getTable('easytranslate_project_cms_block');
            $projectTargetStoreTable = $cmsBlocksCollection->getTable('easytranslate_project_target_store');
            $cmsBlocksCollection->getSelect()->joinLeft(
                ['etpcb' => $projectCmsBlockTable],
                'etpcb.block_id=main_table.block_id',
                ['project_ids' => 'GROUP_CONCAT(DISTINCT etpcb.project_id)']
            );
            $cmsBlocksCollection->getSelect()->joinLeft(
                ['etpts' => $projectTargetStoreTable],
                'etpts.project_id=etpcb.project_id',
                ['translated_stores' => 'GROUP_CONCAT(DISTINCT target_store_id)']
            );
            $cmsBlocksCollection->getSelect()->group('main_table.block_id');
        } else {
            $selectedCmsBlockIds = $this->getSelectedCmsBlockIds();
            $cmsBlocksCollection->addFieldToFilter('main_table.block_id', ['in' => $selectedCmsBlockIds]);
        }
        $cmsBlocksCollection->addStoreFilter($this->projectGetter->getProject()->getSourceStoreId());
        $this->eventManager->dispatch(
            'easytranslate_prepare_cms_block_collection',
            ['cms_block_collection' => $cmsBlocksCollection]
        );
        $this->setCollection($cmsBlocksCollection);

        return parent::_prepareCollection();
    }

    /**
     * @throws  Exception
     */
    protected function _prepareColumns()
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(ProjectInterface::CMS_BLOCKS, [
                'header_css_class' => 'a-center',
                'inline_css'       => 'in-project',
                'type'             => 'checkbox',
                'name'             => ProjectInterface::CMS_BLOCKS,
                'values'           => $this->getSelectedCmsBlockIds(),
                'align'            => 'center',
                'index'            => BlockInterface::BLOCK_ID
            ]);
        }
        $this->addColumn(
            BlockInterface::BLOCK_ID,
            [
                'header'           => __('ID'),
                'sortable'         => true,
                'index'            => BlockInterface::BLOCK_ID,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(BlockInterface::TITLE, ['header' => __('Title'), 'index' => BlockInterface::TITLE]);
        $this->addColumn(
            BlockInterface::IDENTIFIER,
            ['header' => __('Identifier'), 'index' => BlockInterface::IDENTIFIER]
        );
        $this->addColumn(
            BlockInterface::IS_ACTIVE,
            [
                'header'           => __('Status'),
                'sortable'         => true,
                'index'            => BlockInterface::IS_ACTIVE,
                'type'             => 'options',
                'options'          => $this->cmsBlock->getAvailableStatuses(),
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            BlockInterface::CREATION_TIME,
            [
                'header'           => __('Created'),
                'sortable'         => true,
                'index'            => BlockInterface::CREATION_TIME,
                'type'             => 'datetime',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            BlockInterface::UPDATE_TIME,
            [
                'header'           => __('Modified'),
                'index'            => BlockInterface::UPDATE_TIME,
                'type'             => 'datetime',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );

        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(
                'translated_stores',
                [
                    'header'                    => __('Already Translated In'),
                    'width'                     => '250px',
                    'index'                     => 'translated_stores',
                    'type'                      => 'store',
                    'store_view'                => true,
                    'sortable'                  => false,
                    'store_all'                 => true,
                    'filter_condition_callback' => [$this, 'filterTranslatedCondition'],
                ]
            );
        }
        $this->eventManager->dispatch('easytranslate_prepare_cms_blocks_columns', ['columns' => $this]);

        return parent::_prepareColumns();
    }

    private function getSelectedCmsBlockIds(): array
    {
        $cmsBlocks = $this->getRequest()->getPost('included_cms_blocks');
        if ($cmsBlocks === null) {
            if ($this->projectGetter->getProject()) {
                return $this->projectGetter->getProject()->getCmsBlocks();
            }

            return [];
        }

        return explode(',', $cmsBlocks);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/project_cmsBlocks/grid', ['_current' => true]);
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
