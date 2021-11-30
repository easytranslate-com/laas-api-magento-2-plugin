<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;

abstract class AbstractGenerator
{
    public const ENTITY_CODE = '';

    public const KEY_SEPARATOR = '###';

    /**
     * @var array
     */
    protected $attributeCodes;

    /**
     * @var string
     */
    protected $idField = 'entity_id';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var VersionManagerFactory
     */
    private $versionManagerFactory;

    public function __construct(Config $config, VersionManagerFactory $versionManagerFactory)
    {
        $this->config                = $config;
        $this->versionManagerFactory = $versionManagerFactory;
    }

    abstract protected function getCollection(ProjectModel $project): AbstractDb;

    public function getContent(ProjectModel $project): array
    {
        $this->fixContentStaging();
        $content = [];
        foreach ($this->getCollection($project) as $model) {
            foreach ($this->getSingleContent($model) as $key => $value) {
                $content[$key] = $value;
            }
        }

        return $content;
    }

    private function fixContentStaging(): void
    {
        $versionManager = $this->versionManagerFactory->create();
        if (!$versionManager) {
            return;
        }
        // make sure that we retrieve the baseline version, not any scheduled content
        $versionManager->setCurrentVersionId(VersionManagerFactory::MIN_VERSION);
    }

    protected function getSingleContent($model): array
    {
        $content = [];
        foreach ($this->getAttributeCodes($model) as $attributeCode) {
            $value = $model->getData($attributeCode);
            if ($value === null || $value === '') {
                continue;
            }
            $keyParts      = [static::ENTITY_CODE, $model->getData($this->idField), $attributeCode];
            $key           = implode(self::KEY_SEPARATOR, $keyParts);
            $content[$key] = $value;
        }

        return $content;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getAttributeCodes(AbstractModel $model): array
    {
        return $this->attributeCodes;
    }
}
