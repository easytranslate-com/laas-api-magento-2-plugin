<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Importer;

use Exception;
use Magento\Framework\DB\TransactionFactory;

abstract class AbstractCmsImporter extends AbstractImporter
{
    /**
     * @var array
     */
    protected $objects;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    public function __construct(TransactionFactory $transactionFactory)
    {
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @throws Exception
     */
    public function import(array $data, int $sourceStoreId, int $targetStoreId): void
    {
        parent::import($data, $sourceStoreId, $targetStoreId);
        $this->bulkSave();
    }

    abstract protected function importObject(
        string $id,
        array $attributes,
        int $sourceStoreId,
        int $targetStoreId
    ): void;

    /**
     * @throws Exception
     */
    protected function bulkSave(): void
    {
        if ($this->objects === null) {
            return;
        }
        $transaction = $this->transactionFactory->create();
        foreach ($this->objects as $object) {
            $transaction->addObject($object);
        }
        $transaction->save();
    }
}
