<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Locale\SourceMapper;
use EasyTranslate\Connector\Model\Locale\TargetMapper;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\System\Store as SystemStore;

class TargetStores extends FilteredStores
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SourceMapper
     */
    private $sourceMapper;

    /**
     * @var TargetMapper
     */
    private $targetMapper;

    public function __construct(
        SystemStore $systemStore,
        Escaper $escaper,
        ProjectGetter $projectGetter,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        SourceMapper $sourceMapper,
        TargetMapper $targetMapper
    ) {
        parent::__construct($systemStore, $escaper, $projectGetter, $config);
        $this->scopeConfig  = $scopeConfig;
        $this->sourceMapper = $sourceMapper;
        $this->targetMapper = $targetMapper;
    }

    protected function shouldIncludeStore(Store $store): bool
    {
        $targetLocaleCode        = $this->getDefaultLocaleCodeForStore((int)$store->getId());
        $externalTargetLocalCode = $this->targetMapper->mapMagentoCodeToExternalCode($targetLocaleCode);
        $project                 = $this->projectGetter->getProject();
        if ($project === null) {
            return false;
        }
        $workflow                = $project->getWorkflowIdentifier() ?? '';
        $sourceLocaleCode        = $this->getDefaultLocaleCodeForStore($project->getSourceStoreId());
        $externalSourceLocalCode = $this->sourceMapper->mapMagentoCodeToExternalCode($sourceLocaleCode);
        $targetLanguages         = $this->getTargetLanguages($workflow, $externalSourceLocalCode);

        return in_array($externalTargetLocalCode, $targetLanguages, true);
    }

    private function getDefaultLocaleCodeForStore(int $storeId): string
    {
        return (string)$this->scopeConfig->getValue(
            Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getTargetLanguages(string $workflow, string $externalSourceLocalCode): array
    {
        return $this->getLanguagePairs()[$workflow][$externalSourceLocalCode] ?? [];
    }
}
