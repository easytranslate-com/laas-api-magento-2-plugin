<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Adminhtml;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Locale\SourceMapper;
use EasyTranslate\Connector\Model\Locale\TargetMapper;
use EasyTranslate\Connector\Model\Project;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;

class ProjectDataProcessor
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(
        SourceMapper $sourceMapper,
        TargetMapper $targetMapper,
        ScopeConfigInterface $scopeConfig,
        MessageManagerInterface $messageManager,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->sourceMapper      = $sourceMapper;
        $this->targetMapper      = $targetMapper;
        $this->scopeConfig       = $scopeConfig;
        $this->messageManager    = $messageManager;
        $this->projectRepository = $projectRepository;
    }

    public function saveProjectPostData(Project $project, array $data): ProjectInterface
    {
        $project->addData($data);
        $savedProject = $this->projectRepository->save($project);
        if (!$this->validateStoreViews($data)) {
            $this->messageManager->addWarningMessage(
                (string)__('The source store view cannot also be a target store view.')
            );
        }
        if (!$this->validateLocales($project)) {
            $this->messageManager->addWarningMessage(
                __('Some or all languages are not supported yet :-( Please contact the EasyTranslate support.')
            );
        }

        return $savedProject;
    }

    private function validateStoreViews(array $data): bool
    {
        if (!isset($data['source_store_id'], $data['target_store_ids']) || !is_array($data['target_store_ids'])) {
            return true;
        }

        return !in_array($data['source_store_id'], $data['target_store_ids'], true);
    }

    private function validateLocales($project): bool
    {
        $sourceStoreId    = $project->getSourceStoreId();
        $sourceLocaleCode = $this->scopeConfig->getValue(
            Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $sourceStoreId
        );
        if (!$this->sourceMapper->isMagentoCodeSupported($sourceLocaleCode)) {
            return false;
        }
        $targetStoreIds = $project->getTargetStoreViews();
        if ($targetStoreIds) {
            foreach ($targetStoreIds as $targetStoreId) {
                $targetLocaleCode = $this->scopeConfig->getValue(
                    Data::XML_PATH_DEFAULT_LOCALE,
                    ScopeInterface::SCOPE_STORE,
                    $targetStoreId
                );
                if (!$this->targetMapper->isMagentoCodeSupported($targetLocaleCode)) {
                    return false;
                }
            }
        }

        return true;
    }
}
