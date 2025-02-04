<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\RestApiClient\Api\ApiException;
use EasyTranslate\RestApiClient\Api\TeamApi;
use Magento\Framework\Data\OptionSourceInterface;

class Workflow implements OptionSourceInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    public function __construct(Config $config, ProjectGetter $projectGetter)
    {
        $this->config        = $config;
        $this->projectGetter = $projectGetter;
    }

    private function getOptions(): array
    {
        $workflows = [];
        $project   = $this->projectGetter->getProject();
        if ($project === null) {
            return $workflows;
        }

        try {
            $apiConfiguration    = $this->config->getApiConfiguration();
            $teamDetailsResponse = (new TeamApi($apiConfiguration))->getTeamDetails($project->getTeam() ?? '');
        } catch (ApiException $e) {
            return $workflows;
        }

        foreach ($teamDetailsResponse->getWorkflows() as $workflow) {
            $workflows[$workflow->getId()] = $workflow->getDisplayName();
        }

        return $workflows;
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
