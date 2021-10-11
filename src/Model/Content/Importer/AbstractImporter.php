<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Importer;

use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;

abstract class AbstractImporter
{
    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function import(array $data, int $sourceStoreId, int $targetStoreId): void
    {
        $lastId     = null;
        $attributes = [];
        foreach ($data as $key => $content) {
            $delimiter = AbstractGenerator::KEY_SEPARATOR;
            [$entityCode, $currentId, $attributeCode] = explode($delimiter, $key);
            if ($lastId !== null && $currentId !== $lastId) {
                $this->importObject($lastId, $attributes, $sourceStoreId, $targetStoreId);
                $attributes = [];
            }
            $attributes[$attributeCode] = $content;
            $lastId                     = $currentId;
        }
        // make sure to import the last object as well
        if ($lastId !== null) {
            $this->importObject($lastId, $attributes, $sourceStoreId, $targetStoreId);
        }
    }

    abstract protected function importObject(
        string $id,
        array $attributes,
        int $sourceStoreId,
        int $targetStoreId
    ): void;
}
