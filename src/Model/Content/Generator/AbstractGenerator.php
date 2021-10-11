<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Generator;

use EasyTranslate\Connector\Model\Config;
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

    public function __construct(Config $config)
    {
        $this->config = $config;
    }
    abstract protected function getCollection(array $modelIds, int $storeId);

    public function getContent(array $modelIds, int $storeId): array
    {
        $content = [];
        $models  = $this->getCollection($modelIds, $storeId);
        foreach ($models as $model) {
            $singleContent = $this->getSingleContent($model);
            foreach ($singleContent as $key => $value) {
                $content[$key] = $value;
            }
        }

        return $content;
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
