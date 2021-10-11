<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Content\Generator\Filter\Cms as FilterCms;
use Exception;
use Magento\Cms\Model\ResourceModel\Block\Collection as BlockCollection;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as CmsBlockCollectionFactory;

class CmsBlock extends AbstractGenerator
{
    public const ENTITY_CODE = 'cms_block';

    /**
     * @var string
     */
    protected $idField = 'identifier';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CmsBlockCollectionFactory
     */
    private $blockCollectionFactory;

    /**
     * @var FilterCms
     */
    private $filterCms;

    public function __construct(
        Config $config,
        FilterCms $filterCms,
        CmsBlockCollectionFactory $blockCollectionFactory
    ) {
        parent::__construct($config);
        $this->attributeCodes         = $this->config->getCmsBlocksAttributes();
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->filterCms              = $filterCms;
    }

    /**
     * @throws Exception
     */
    protected function getCollection(array $modelIds, int $storeId): BlockCollection
    {
        // re-load CMS blocks based on identifiers (a language-specific one may have been added after project creation)
        $identifiers = $this->blockCollectionFactory->create()
            ->addFieldToFilter('block_id', ['in' => $modelIds])
            ->getColumnValues($this->idField);
        $cmsBlocks   = $this->blockCollectionFactory->create()
            ->addFieldToSelect($this->attributeCodes)
            ->addFieldToSelect($this->idField)
            ->addStoreFilter($storeId)
            ->addFieldToFilter($this->idField, ['in' => $identifiers]);

        return $this->filterCms->filterEntities($cmsBlocks, $identifiers);
    }
}
