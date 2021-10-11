<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content;

use EasyTranslate\Connector\Model\Content\Generator\Category;
use EasyTranslate\Connector\Model\Content\Generator\CmsBlock;
use EasyTranslate\Connector\Model\Content\Generator\CmsPage;
use EasyTranslate\Connector\Model\Content\Generator\Product;
use EasyTranslate\Connector\Model\Content\Importer\AbstractImporter;
use EasyTranslate\Connector\Model\Content\Importer\Category as CategoryImporter;
use EasyTranslate\Connector\Model\Content\Importer\CmsBlock as CmsBlockImporter;
use EasyTranslate\Connector\Model\Content\Importer\CmsPage as CmsPageImporter;
use EasyTranslate\Connector\Model\Content\Importer\Product as ProductImporter;

class Importer
{
    /**
     * @var ProductImporter
     */
    private $productImporter;

    /**
     * @var CategoryImporter
     */
    private $categoryImporter;

    /**
     * @var CmsBlockImporter
     */
    private $cmsBlockImporter;

    /**
     * @var CmsPageImporter
     */
    private $cmsPageImporter;

    public function __construct(
        ProductImporter $productImporter,
        CategoryImporter $categoryImporter,
        CmsBlockImporter $cmsBlockImporter,
        CmsPageImporter $cmsPageImporter
    ) {
        $this->productImporter  = $productImporter;
        $this->categoryImporter = $categoryImporter;
        $this->cmsBlockImporter = $cmsBlockImporter;
        $this->cmsPageImporter  = $cmsPageImporter;
    }

    protected const IMPORTERS
        = [
            CmsBlock::ENTITY_CODE => 'importer_cmsBlock',
            CmsPage::ENTITY_CODE  => 'importer_cmsPage',
            Product::ENTITY_CODE  => 'importer_product',
            Category::ENTITY_CODE => 'importer_category',
        ];

    public function import(array $data, int $sourceStoreId, int $targetStoreId): void
    {
        foreach (static::IMPORTERS as $code => $importer) {
            $importerData = array_filter($data, static function ($key) use ($code) {
                // if the key starts with the importer code, the importer can handle the data
                return strpos($key, $code) === 0;
            }, ARRAY_FILTER_USE_KEY);
            if ($this->getImporterModel($importer)) {
                $this->getImporterModel($importer)->import($importerData, $sourceStoreId, $targetStoreId);
            }
        }
    }

    protected function getImporterModel(string $modelClass): ?AbstractImporter
    {
        if ($modelClass === 'importer_product') {
            return $this->productImporter;
        }
        if ($modelClass === 'importer_cmsPage') {
            return $this->cmsPageImporter;
        }
        if ($modelClass === 'importer_cmsBlock') {
            return $this->cmsBlockImporter;
        }
        if ($modelClass === 'importer_category') {
            return $this->categoryImporter;
        }

        return null;
    }
}
