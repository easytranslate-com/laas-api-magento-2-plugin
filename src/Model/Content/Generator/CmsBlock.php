<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Content\Generator\Filter\Cms as FilterCms;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Exception;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as CmsBlockCollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;

class CmsBlock extends AbstractGenerator
{
    public const ENTITY_CODE = 'cms_block';

    /**
     * @var string
     */
    protected $idField = 'identifier';

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
        VersionManagerFactory $versionManagerFactory,
        CmsBlockCollectionFactory $blockCollectionFactory,
        FilterCms $filterCms
    ) {
        parent::__construct($config, $versionManagerFactory);
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->filterCms              = $filterCms;
        $this->attributeCodes         = $this->config->getCmsBlocksAttributes();
    }

    /**
     * @throws Exception
     */
    protected function getCollection(ProjectModel $project, int $storeId): AbstractDb
    {
        // re-load CMS blocks based on identifiers (a language-specific one may have been added after project creation)
        $identifiers = $this->blockCollectionFactory->create()
            ->addFieldToFilter('block_id', ['in' => $project->getCmsBlocks()])
            ->getColumnValues($this->idField);
        $cmsBlocks   = $this->blockCollectionFactory->create()
            ->addFieldToSelect($this->attributeCodes)
            ->addFieldToSelect($this->idField)
            ->addStoreFilter($storeId)
            ->addFieldToFilter($this->idField, ['in' => $identifiers]);

        return $this->filterCms->filterEntities($cmsBlocks, $identifiers);
    }
}
