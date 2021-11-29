<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Bridge;

use EasyTranslate\Connector\Model\Callback\LinkGenerator;
use EasyTranslate\Connector\Model\Content\Generator;
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
     * @var Generator
     */
    private $contentGenerator;

    public function __construct(
        SourceMapper $sourceMapper,
        TargetMapper $targetMapper,
        ScopeConfigInterface $scopeConfig,
        LinkGenerator $linkGenerator,
        Generator $contentGenerator
    ) {
        $this->sourceMapper     = $sourceMapper;
        $this->targetMapper     = $targetMapper;
        $this->scopeConfig      = $scopeConfig;
        $this->linkGenerator    = $linkGenerator;
        $this->contentGenerator = $contentGenerator;
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
        foreach ($this->magentoProject->getTargetStoreIds() as $targetStoreId) {
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
        $storeId = (int)$this->magentoProject->getData('source_store_id');

        return $this->contentGenerator->generateContent($this->magentoProject, $storeId);
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
