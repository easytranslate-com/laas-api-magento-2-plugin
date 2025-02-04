<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Locale\SourceMapper;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\System\Store as SystemStore;

class SourceStores extends FilteredStores
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SourceMapper
     */
    private $sourceMapper;

    public function __construct(
        SystemStore $systemStore,
        Escaper $escaper,
        ProjectGetter $projectGetter,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        SourceMapper $sourceMapper
    ) {
        parent::__construct($systemStore, $escaper, $projectGetter, $config);
        $this->scopeConfig  = $scopeConfig;
        $this->sourceMapper = $sourceMapper;
    }

    protected function shouldIncludeStore(Store $store): bool
    {
        $sourceLocaleCode        = $this->scopeConfig->getValue(
            Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );
        $externalSourceLocalCode = $this->sourceMapper->mapMagentoCodeToExternalCode($sourceLocaleCode);
        $project                 = $this->projectGetter->getProject();
        if ($project === null) {
            return false;
        }
        $workflow        = $project->getWorkflowIdentifier() ?? '';
        $sourceLanguages = $this->getSourceLanguages($workflow);

        return in_array($externalSourceLocalCode, $sourceLanguages, true);
    }

    private function getSourceLanguages(string $workflow): array
    {
        if (!isset($this->getLanguagePairs()[$workflow])) {
            return [];
        }

        return array_keys($this->getLanguagePairs()[$workflow]);
    }
}
