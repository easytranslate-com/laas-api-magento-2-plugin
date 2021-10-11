<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\RestApiClient\Api\ApiException;
use EasyTranslate\RestApiClient\Api\TeamApi;
use Magento\Framework\Data\OptionSourceInterface;

class Team implements OptionSourceInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    private function getOptions(): array
    {
        $apiConfiguration = $this->config->getApiConfiguration();
        try {
            $teamsApi     = new TeamApi($apiConfiguration);
            $userResponse = $teamsApi->getUser();
        } catch (ApiException $e) {
            return [];
        }

        return $userResponse->getTeams();
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
