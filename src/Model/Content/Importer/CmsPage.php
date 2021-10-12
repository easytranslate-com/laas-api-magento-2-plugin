<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content\Importer;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class CmsPage extends AbstractCmsImporter
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var GetPageByIdentifierInterface
     */
    private $getPageByIdentifier;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PageResource
     */
    private $pageResourceModel;

    public function __construct(
        TransactionFactory $transactionFactory,
        PageFactory $pageFactory,
        PageRepositoryInterface $pageRepository,
        GetPageByIdentifierInterface $getPageByIdentifier,
        StoreManagerInterface $storeManager,
        PageResource $pageResourceModel
    ) {
        parent::__construct($transactionFactory);
        $this->pageFactory         = $pageFactory;
        $this->pageRepository      = $pageRepository;
        $this->getPageByIdentifier = $getPageByIdentifier;
        $this->storeManager        = $storeManager;
        $this->pageResourceModel   = $pageResourceModel;
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    protected function importObject(string $id, array $attributes, int $sourceStoreId, int $targetStoreId): void
    {
        $page     = $this->loadBasePage($id, $sourceStoreId, $targetStoreId);
        $storeIds = (array)$page->getData('store_id');
        if (in_array($targetStoreId, $storeIds, false) && count($storeIds) === 1) {
            $this->handleExistingUniquePage($page, $attributes);
        } elseif (in_array(Store::DEFAULT_STORE_ID, $storeIds, false) && count($storeIds) >= 1) {
            $this->handleExistingGlobalPage($page, $attributes, $targetStoreId);
        } elseif (in_array($targetStoreId, $storeIds, false) && count($storeIds) > 1) {
            $this->handleExistingPageWithMultipleStores($page, $attributes, $targetStoreId);
        } else {
            // this should rarely happen - only if the page from the source store has been deleted in the meantime
            $page->setIdentifier($id);
            $this->handleNonExistingPage($page, $attributes, $targetStoreId);
        }
    }

    private function loadBasePage(string $id, int $sourceStoreId, int $targetStoreId): Page
    {
        $pageFromTargetStore = $this->loadExistingPage($id, $targetStoreId);
        if ($pageFromTargetStore->getId()) {
            // if there is already a page in the target store, use it as a base
            return $pageFromTargetStore;
        }

        // otherwise, use the page from the source store as a base
        return $this->loadExistingPage($id, $sourceStoreId);
    }

    /**
     */
    private function loadExistingPage(string $id, int $storeId): Page
    {
        try {
            return $this->getPageByIdentifier->execute($id, $storeId);
        } catch (NoSuchEntityException $e) {
            /** @var Page $page */
            $page = $this->pageFactory->create();
            $page->setStoreId($storeId);

            return $page;
        }
    }

    /**
     * @throws AlreadyExistsException
     */
    private function handleExistingGlobalPage(Page $page, array $newData, int $targetStoreId): void
    {
        if (!isset($newData[PageInterface::IDENTIFIER])
            || $page->getIdentifier() === $newData[PageInterface::IDENTIFIER]) {
            // make sure that the URL key is unique by moving the existing global store to the respective store views
            $allStores   = $this->storeManager->getStores();
            $allStoreIds = array_map(static function ($store) {
                return (int)$store->getId();
            }, $allStores);
            $newStoreIds = array_values(array_diff($allStoreIds, [$targetStoreId]));
            $page->setData('store_id', $newStoreIds);
            $page->setData('stores', $newStoreIds);
            $this->pageResourceModel->save($page);
        }

        $this->createNewPageForStore($page, $newData, $targetStoreId);
    }

    private function handleExistingUniquePage(Page $page, array $newData): void
    {
        $page->addData($newData);
        // workaround for a Magento bug
        // stores are not set in _afterLoad, but checked in _beforeSave / getIsUniquePageToStores
        // TODO check if this is still necessary
        $page->setData('stores', $page->getData('store_id'));
        $this->objects[] = $page;
    }

    /**
     * @throws LocalizedException
     */
    private function handleExistingPageWithMultipleStores(Page $page, array $newData, int $targetStoreId): void
    {
        // first remove the current store ID from the existing CMS page, because pages must be unique per store
        $storeIds    = (array)$page->getData('store_id');
        $newStoreIds = array_diff($storeIds, [$targetStoreId]);
        $page->setData('store_id', $newStoreIds);
        $page->setData('stores', $newStoreIds);
        // save this page directly for subsequent updates
        $this->pageRepository->save($page);
        $this->createNewPageForStore($page, $newData, $targetStoreId);
    }

    private function handleNonExistingPage(Page $page, array $newData, int $targetStoreId): void
    {
        $this->createNewPageForStore($page, $newData, $targetStoreId);
    }

    private function createNewPageForStore(Page $basePage, array $newData, int $targetStoreId): void
    {
        $page = $this->pageFactory->create();
        $page->addData($basePage->getData());
        $page->addData($newData);
        // make sure that a new page is created!
        $page->unsetData('page_id');
        $page->unsetData('creation_time');
        $page->setData('store_id', [$targetStoreId]);
        $page->setData('stores', [$targetStoreId]);
        $this->objects[] = $page;
    }
}
