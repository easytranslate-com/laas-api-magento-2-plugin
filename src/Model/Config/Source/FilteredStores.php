<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\RestApiClient\Api\ApiException;
use EasyTranslate\RestApiClient\Api\TeamApi;
use Magento\Framework\Escaper;
use Magento\Store\Model\Store;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Store\Ui\Component\Listing\Column\Store\Options;

abstract class FilteredStores extends Options
{
    /**
     * @var ProjectGetter
     */
    protected $projectGetter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $languagePairs;

    public function __construct(
        SystemStore $systemStore,
        Escaper $escaper,
        ProjectGetter $projectGetter,
        Config $config
    ) {
        parent::__construct($systemStore, $escaper);
        $this->projectGetter = $projectGetter;
        $this->config        = $config;
    }

    protected function generateCurrentOptions(): void
    {
        $websiteCollection = $this->systemStore->getWebsiteCollection();
        $groupCollection   = $this->systemStore->getGroupCollection();
        $storeCollection   = $this->systemStore->getStoreCollection();

        foreach ($websiteCollection as $website) {
            $groups = [];
            foreach ($groupCollection as $group) {
                if ($group->getWebsiteId() === $website->getId()) {
                    $stores = [];
                    foreach ($storeCollection as $store) {
                        if ($this->shouldIncludeStore($store) && $store->getGroupId() === $group->getId()) {
                            $stores[] = [
                                'label' => str_repeat(' ', 8) . $this->sanitizeName($store->getName()),
                                'value' => $store->getId(),
                            ];
                        }
                    }
                    if (!empty($stores)) {
                        $groups[] = [
                            'label' => str_repeat(' ', 4) . $this->sanitizeName($group->getName()),
                            'value' => array_values($stores),
                        ];
                    }
                }
            }
            if (!empty($groups)) {
                $this->currentOptions[] = [
                    'label' => $this->sanitizeName($website->getName()),
                    'value' => array_values($groups),
                ];
            }
        }
    }

    abstract protected function shouldIncludeStore(Store $store): bool;

    protected function getLanguagePairs(): array
    {
        if ($this->languagePairs === null) {
            $this->languagePairs = [];
            $project             = $this->projectGetter->getProject();
            if ($project === null) {
                return $this->languagePairs;
            }

            try {
                $apiConfiguration    = $this->config->getApiConfiguration();
                $teamDetailsResponse = (new TeamApi($apiConfiguration))->getTeamDetails($project->getTeam() ?? '');
            } catch (ApiException $e) {
                return $this->languagePairs;
            }

            $this->languagePairs = $teamDetailsResponse->getLanguagePairsByWorkflow();
        }

        return $this->languagePairs;
    }
}
