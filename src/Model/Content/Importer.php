<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content;

use EasyTranslate\Connector\Model\Content\Generator\Category;
use EasyTranslate\Connector\Model\Content\Generator\CmsBlock;
use EasyTranslate\Connector\Model\Content\Generator\CmsPage;
use EasyTranslate\Connector\Model\Content\Generator\Product;
use EasyTranslate\Connector\Model\Content\Importer\AbstractImporter;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;

class Importer
{
    /**
     * @var array
     */
    protected $importers;

    /**
     * @var VersionManagerFactory
     */
    private $versionManagerFactory;

    public function __construct(VersionManagerFactory $versionManagerFactory, array $importers)
    {
        $this->importers             = $importers;
        $this->versionManagerFactory = $versionManagerFactory;
    }

    public function import(array $data, int $sourceStoreId, int $targetStoreId): void
    {
        $this->fixContentStaging();
        foreach ($this->importers as $code => $importer) {
            $importerData = array_filter($data, static function ($key) use ($code) {
                // if the key starts with the importer code, the importer can handle the data
                return strpos($key, $code) === 0;
            }, ARRAY_FILTER_USE_KEY);

            $importer->import($importerData, $sourceStoreId, $targetStoreId);
        }
    }

    private function fixContentStaging(): void
    {
        $versionManager = $this->versionManagerFactory->create();
        if (!$versionManager) {
            return;
        }
        // make sure that we use the baseline version, not any scheduled content
        $versionManager->setCurrentVersionId(VersionManagerFactory::MIN_VERSION);
    }
}
