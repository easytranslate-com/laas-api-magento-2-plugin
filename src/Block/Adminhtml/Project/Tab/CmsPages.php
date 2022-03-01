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
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\Collection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as CmsPageCollectionFactory;
use Magento\Framework\Data\Collection as CollectionData;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Model\PageLayout\Config\BuilderInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CmsPages extends AbstractEntity
{
    /**
     * @var CmsPageCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Page
     */
    private $cmsPage;

    /**
     * @var BuilderInterface
     */
    private $pageLayoutBuilder;

    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    private EventManager $eventManager;

    public function __construct(
        Context $context,
        Data $backendHelper,
        CmsPageCollectionFactory $collectionFactory,
        Page $cmsPage,
        BuilderInterface $pageLayoutBuilder,
        ProjectGetter $projectGetter,
        EventManager $eventManager
    ) {
        parent::__construct($context, $backendHelper);
        $this->setId('easytranslate_connector_cms_pages');
        $this->setDefaultSort(PageInterface::PAGE_ID);
        $this->setUseAjax(true);
        $this->collectionFactory = $collectionFactory;
        $this->cmsPage           = $cmsPage;
        $this->pageLayoutBuilder = $pageLayoutBuilder;
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
        if ($column->getId() === ProjectInterface::CMS_PAGES) {
            $cmsPagesIds = $this->getSelectedCmsPageIds();
            if (empty($cmsPagesIds)) {
                $cmsPagesIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter(PageInterface::PAGE_ID, ['in' => $cmsPagesIds]);
            } elseif (!empty($cmsPagesIds)) {
                $this->getCollection()->addFieldToFilter(PageInterface::PAGE_ID, ['nin' => $cmsPagesIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    protected function _prepareCollection(): Grid
    {
        $this->setDefaultFilter([ProjectInterface::CMS_PAGES => 1]);
        /** @var Collection $cmsPagesCollection */
        $cmsPagesCollection = $this->collectionFactory->create();
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $projectCmsPageTable     = $cmsPagesCollection->getTable('easytranslate_project_cms_page');
            $projectTargetStoreTable = $cmsPagesCollection->getTable('easytranslate_project_target_store');
            $cmsPagesCollection->getSelect()->joinLeft(
                ['etpcp' => $projectCmsPageTable],
                'etpcp.page_id=main_table.page_id',
                ['project_ids' => 'GROUP_CONCAT(DISTINCT etpcp.project_id)']
            );
            $cmsPagesCollection->getSelect()->joinLeft(
                ['etpts' => $projectTargetStoreTable],
                'etpts.project_id=etpcp.project_id',
                ['translated_stores' => 'GROUP_CONCAT(DISTINCT target_store_id)']
            );
            $cmsPagesCollection->getSelect()->group('main_table.page_id');
        } else {
            $selectedCmsPageIds = $this->getSelectedCmsPageIds();
            $cmsPagesCollection->addFieldToFilter('main_table.page_id', ['in' => $selectedCmsPageIds]);
        }
        $cmsPagesCollection->addStoreFilter($this->projectGetter->getProject()->getSourceStoreId());
        $this->eventManager->dispatch('easytranslate_prepare_cms_pages_collection',
            ['cmsPagesCollection' => $cmsPagesCollection]);
        $this->setCollection($cmsPagesCollection);

        return parent::_prepareCollection();
    }

    /**
     * @throws  Exception
     */
    protected function _prepareColumns()
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(ProjectInterface::CMS_PAGES, [
                'header_css_class' => 'a-center',
                'inline_css'       => 'in-project',
                'type'             => 'checkbox',
                'name'             => ProjectInterface::CMS_PAGES,
                'values'           => $this->getSelectedCmsPageIds(),
                'align'            => 'center',
                'index'            => PageInterface::PAGE_ID
            ]);
        }
        $this->addColumn(
            PageInterface::PAGE_ID,
            [
                'header'           => __('ID'),
                'sortable'         => true,
                'index'            => PageInterface::PAGE_ID,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(PageInterface::TITLE, ['header' => __('Title'), 'index' => PageInterface::TITLE]);
        $this->addColumn(PageInterface::IDENTIFIER, ['header' => __('URL Key'), 'index' => PageInterface::IDENTIFIER]);
        $this->addColumn(
            PageInterface::PAGE_LAYOUT,
            [
                'header'           => __('Layout'),
                'sortable'         => true,
                'index'            => PageInterface::PAGE_LAYOUT,
                'type'             => 'options',
                'options'          => $this->pageLayoutBuilder->getPageLayoutsConfig()->getOptions(),
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            PageInterface::IS_ACTIVE,
            [
                'header'           => __('Status'),
                'sortable'         => true,
                'index'            => PageInterface::IS_ACTIVE,
                'type'             => 'options',
                'options'          => $this->cmsPage->getAvailableStatuses(),
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            PageInterface::CREATION_TIME,
            [
                'header'           => __('Created'),
                'sortable'         => true,
                'index'            => PageInterface::CREATION_TIME,
                'type'             => 'datetime',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            PageInterface::UPDATE_TIME,
            [
                'header'           => __('Modified'),
                'index'            => PageInterface::UPDATE_TIME,
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
                    'store_all'                 => true,
                    'sortable'                  => false,
                    'filter_condition_callback' => [$this, 'filterTranslatedCondition'],
                ]
            );
        }
        $this->eventManager->dispatch('easytranslate_prepare_cms_blocks_columns');

        return parent::_prepareColumns();
    }

    private function getSelectedCmsPageIds(): array
    {
        $cmsPages = $this->getRequest()->getPost('included_cms_pages');
        if ($cmsPages === null) {
            if ($this->projectGetter->getProject()) {
                return $this->projectGetter->getProject()->getCmsPages();
            }

            return [];
        }

        return explode(',', $cmsPages);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('*/project_cmsPages/grid', ['_current' => true]);
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
