<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Setup\Patch\Data;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\ResourceModel\Project\CollectionFactory as ProjectCollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnrichWorkflowData implements DataPatchInterface
{
    /**
     * @var ProjectCollectionFactory
     */
    private $projectCollectionFactory;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(
        ProjectCollectionFactory $projectCollectionFactory,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->projectRepository        = $projectRepository;
        $this->projectCollectionFactory = $projectCollectionFactory;
    }

    public function apply(): self
    {
        $legacyWorkflows = $this->getLegacyWorkflows();
        foreach ($this->projectCollectionFactory->create()->getItems() as $project) {
            if (!isset($legacyWorkflows[$project->getWorkflow()])) {
                continue;
            }
            $project->setWorkflowName($legacyWorkflows[$project->getWorkflow()]);
            $this->projectRepository->save($project);
        }

        return $this;
    }

    private function getLegacyWorkflows(): array
    {
        return [
            'translation'              => (string)__('Translation'),
            'translation+review'       => (string)__('Translation and review'),
            'self+machine_translation' => (string)__('Translate yourself'),
        ];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
