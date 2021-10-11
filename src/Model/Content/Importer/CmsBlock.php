<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Importer;

use Exception;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Block as BlockModel;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

class CmsBlock extends AbstractCmsImporter
{
    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var GetBlockByIdentifierInterface
     */
    private $getBlockByIdentifier;

    public function __construct(
        TransactionFactory $transactionFactory,
        BlockFactory $blockFactory,
        BlockRepositoryInterface $blockRepository,
        GetBlockByIdentifierInterface $getBlockByIdentifier
    ) {
        parent::__construct($transactionFactory);
        $this->blockFactory         = $blockFactory;
        $this->blockRepository      = $blockRepository;
        $this->getBlockByIdentifier = $getBlockByIdentifier;
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function importObject(string $id, array $attributes, int $sourceStoreId, int $targetStoreId): void
    {
        $block    = $this->loadBaseBlock($id, $sourceStoreId, $targetStoreId);
        $storeIds = (array)$block->getData('stores');
        if (in_array(Store::DEFAULT_STORE_ID, $storeIds, false) && count($storeIds) >= 1) {
            $this->handleExistingGlobalBlock($block, $attributes, $targetStoreId);
        } elseif (in_array($targetStoreId, $storeIds, false) && count($storeIds) === 1) {
            $this->handleExistingUniqueBlock($block, $attributes);
        } elseif (in_array($targetStoreId, $storeIds, false) && count($storeIds) > 1) {
            $this->handleExistingBlockWithMultipleStores($block, $attributes, $targetStoreId);
        } else {
            // this should rarely happen - only if the block from the source store has been deleted in the meantime
            $block->setIdentifier($id);
            $this->handleNonExistingBlock($block, $attributes, $targetStoreId);
        }
    }

    private function loadBaseBlock(string $id, int $sourceStoreId, int $targetStoreId): BlockModel
    {
        $blockFromTargetStore = $this->loadExistingBlock($id, $targetStoreId);
        if ($blockFromTargetStore->getId()) {
            // if there is already a block in the target store, use it as a base
            return $blockFromTargetStore;
        }

        // otherwise, use the block from the source store as a base
        return $this->loadExistingBlock($id, $sourceStoreId);
    }

    private function loadExistingBlock(string $id, int $storeId): BlockModel
    {
        try {
            return $this->getBlockByIdentifier->execute($id, $storeId);
        } catch (NoSuchEntityException $e) {
            /** @var BlockModel $block */
            $block = $this->blockFactory->create();
            $block->setStoreId($storeId);

            return $block;
        }
    }

    private function handleExistingGlobalBlock(BlockModel $block, array $newData, int $targetStoreId): void
    {
        $this->createNewBlockForStore($block, $newData, $targetStoreId);
    }

    private function handleExistingUniqueBlock(BlockModel $block, array $newData): void
    {
        $block->addData($newData);
        $this->objects[] = $block;
    }

    /**
     * @throws Exception
     */
    private function handleExistingBlockWithMultipleStores(BlockModel $block, array $newData, int $targetStoreId): void
    {
        // first remove the current store ID from the existing CMS block, because blocks must be unique per store
        $storeIds    = (array)$block->getData('stores');
        $newStoreIds = array_diff($storeIds, [$targetStoreId]);
        $block->setData('store_id', $newStoreIds);
        $block->setData('stores', $newStoreIds);
        // save this block directly for subsequent updates
        $this->blockRepository->save($block);
        $this->createNewBlockForStore($block, $newData, $targetStoreId);
    }

    private function handleNonExistingBlock(BlockModel $block, array $newData, int $targetStoreId): void
    {
        $this->createNewBlockForStore($block, $newData, $targetStoreId);
    }

    private function createNewBlockForStore(BlockModel $baseBlock, array $newData, int $targetStoreId): void
    {
        $block = $this->blockFactory->create();
        $block->addData($baseBlock->getData());
        $block->addData($newData);
        // make sure that a new block is created!
        $block->unsetData('block_id');
        $block->unsetData('creation_time');
        $block->setData('store_id', [$targetStoreId]);
        $block->setData('stores', [$targetStoreId]);
        $this->objects[] = $block;
    }
}
