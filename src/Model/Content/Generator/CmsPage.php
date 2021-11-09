<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Content\Generator\Filter\Cms as FilterCms;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Exception;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as CmsPageCollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;

class CmsPage extends AbstractGenerator
{
    public const ENTITY_CODE = 'cms_page';

    /**
     * @var string
     */
    protected $idField = 'identifier';

    /**
     * @var CmsPageCollectionFactory
     */
    private $cmsCollectionFactory;

    /**
     * @var FilterCms
     */
    private $filterCms;

    public function __construct(
        Config $config,
        VersionManagerFactory $versionManagerFactory,
        CmsPageCollectionFactory $cmsCollectionFactory,
        FilterCms $filterCms
    ) {
        parent::__construct($config, $versionManagerFactory);
        $this->cmsCollectionFactory = $cmsCollectionFactory;
        $this->filterCms            = $filterCms;
        $this->attributeCodes       = $this->config->getCmsPageAttributes();
    }

    /**
     * @throws Exception
     */
    protected function getCollection(array $modelIds, int $storeId): AbstractDb
    {
        // re-load CMS pages based on identifiers (a language-specific one may have been added after project creation)
        $identifiers = $this->cmsCollectionFactory->create()
            ->addFieldToFilter('page_id', ['in' => $modelIds])
            ->getColumnValues($this->idField);
        $cmsPages    = $this->cmsCollectionFactory->create()
            ->addFieldToSelect($this->attributeCodes)
            ->addFieldToSelect($this->idField)
            ->addStoreFilter($storeId)
            ->addFieldToFilter($this->idField, ['in' => $identifiers]);

        return $this->filterCms->filterEntities($cmsPages, $identifiers);
    }
}
