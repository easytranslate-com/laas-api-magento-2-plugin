<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Bridge;

use EasyTranslate\Connector\Model\Callback\LinkGenerator;
use EasyTranslate\Connector\Model\Content\Generator\Category as CategoryContentGenerator;
use EasyTranslate\Connector\Model\Content\Generator\CmsBlock as CmsBlocksContentGenerator;
use EasyTranslate\Connector\Model\Content\Generator\CmsPage as CmsPageContentGenerator;
use EasyTranslate\Connector\Model\Content\Generator\Product as ProductContentGenerator;
use EasyTranslate\Connector\Model\Locale\SourceMapper;
use EasyTranslate\Connector\Model\Locale\TargetMapper;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\RestApiClient\ProjectInterface;
use Exception;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Project implements ProjectInterface
{
    /**
     * @var SourceMapper
     */
    private $sourceMapper;

    /**
     * @var TargetMapper
     */
    private $targetMapper;

    /**
     * @var ProjectModel
     */
    private $magentoProject;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LinkGenerator
     */
    private $linkGenerator;

    /**
     * @var CmsBlocksContentGenerator
     */
    private $cmsBlocksContentGenerator;

    /**
     * @var CmsPageContentGenerator
     */
    private $cmsPageContentGenerator;

    /**
     * @var CategoryContentGenerator
     */
    private $categoryContentGenerator;

    /**
     * @var ProductContentGenerator
     */
    private $productContentGenerator;

    public function __construct(
        SourceMapper $sourceMapper,
        TargetMapper $targetMapper,
        ScopeConfigInterface $scopeConfig,
        LinkGenerator $linkGenerator,
        CmsBlocksContentGenerator $cmsBlocksContentGenerator,
        CmsPageContentGenerator $cmsPageContentGenerator,
        CategoryContentGenerator $categoryContentGenerator,
        ProductContentGenerator $productContentGenerator
    ) {
        $this->sourceMapper              = $sourceMapper;
        $this->targetMapper              = $targetMapper;
        $this->scopeConfig               = $scopeConfig;
        $this->linkGenerator             = $linkGenerator;
        $this->cmsBlocksContentGenerator = $cmsBlocksContentGenerator;
        $this->cmsPageContentGenerator   = $cmsPageContentGenerator;
        $this->categoryContentGenerator  = $categoryContentGenerator;
        $this->productContentGenerator   = $productContentGenerator;
    }

    public function bindMagentoProject($magentoProject): void
    {
        //TODO Find better solution to bind the magentoProject (maybe pass it in create($magentoProject))
        $this->magentoProject = $magentoProject;
    }

    public function getId(): string
    {
        return $this->magentoProject->getExternalId();
    }

    public function getTeam(): string
    {
        return $this->magentoProject->getTeam();
    }

    /**
     * @throws Exception
     */
    public function getSourceLanguage(): string
    {
        $sourceStoreId = $this->magentoProject->getData('source_store_id');
        $sourceLocale  = $this->scopeConfig->getValue(
            Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $sourceStoreId
        );

        return $this->sourceMapper->mapMagentoCodeToExternalCode($sourceLocale);
    }

    /**
     * @throws Exception
     */
    public function getTargetLanguages(): array
    {
        $targetLanguages = [];
        $targetStoreIds  = $this->magentoProject->getTargetStoreIds();
        foreach ($targetStoreIds as $targetStoreId) {
            $targetLocale      = $this->scopeConfig->getValue(
                Data::XML_PATH_DEFAULT_LOCALE,
                ScopeInterface::SCOPE_STORE,
                $targetStoreId
            );
            $targetLanguages[] = $this->targetMapper->mapMagentoCodeToExternalCode($targetLocale);
        }

        return array_values(array_unique($targetLanguages));
    }

    public function getCallbackUrl(): string
    {
        return $this->linkGenerator->generateLink($this->magentoProject);
    }

    public function getContent(): array
    {
        $storeId           = (int)$this->magentoProject->getData('source_store_id');
        $cmsBlocksContent  = $this->getCmsBlocksContent($storeId);
        $cmsPagesContent   = $this->getCmsPagesContent($storeId);
        $categoriesContent = $this->getCategoriesContent($storeId);
        $productsContent   = $this->getProductsContent($storeId);

        return array_merge($cmsBlocksContent, $cmsPagesContent, $categoriesContent, $productsContent);
    }

    private function getCmsBlocksContent(int $storeId): array
    {
        $cmsBlockIds = $this->magentoProject->getCmsBlocks();

        return $this->cmsBlocksContentGenerator->getContent($cmsBlockIds, $storeId);
    }

    private function getCmsPagesContent(int $storeId): array
    {
        $cmsPageIds = $this->magentoProject->getCmsPages();

        return $this->cmsPageContentGenerator->getContent($cmsPageIds, $storeId);
    }

    private function getCategoriesContent(int $storeId): array
    {
        $categoryIds = $this->magentoProject->getCategories();

        return $this->categoryContentGenerator->getContent($categoryIds, $storeId);
    }

    protected function getProductsContent(int $storeId): array
    {
        $productIds = $this->magentoProject->getProducts();

        return $this->productContentGenerator->getContent($productIds, $storeId);
    }

    public function getWorkflow(): string
    {
        return $this->magentoProject->getWorkflow();
    }

    public function getFolderId(): ?string
    {
        return null;
    }

    public function getFolderName(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        return $this->magentoProject->getName();
    }

    public function getTasks(): array
    {
        return $this->magentoProject->getTasks();
    }

    public function getPrice(): ?float
    {
        return (float)$this->magentoProject->getPrice();
    }

    public function getCurrency(): ?string
    {
        return $this->magentoProject->getCurrency();
    }
}
