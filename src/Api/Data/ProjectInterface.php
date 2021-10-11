<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Api\Data;

interface ProjectInterface
{
    public const PROJECT_ID = 'project_id';
    public const EXTERNAL_ID = 'external_id';
    public const SECRET = 'secret';
    public const NAME = 'name';
    public const TEAM = 'team';
    public const SOURCE_STORE_ID = 'source_store_id';
    public const TARGET_STORE_IDS = 'target_store_ids';
    public const STATUS = 'status';
    public const PRICE = 'price';
    public const CURRENCY = 'currency';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const WORKFLOW = 'workflow';
    public const AUTOMATIC_IMPORT = 'automatic_import';
    public const PRODUCTS = 'products';
    public const CATEGORIES = 'categories';
    public const CMS_BLOCKS = 'cms_blocks';
    public const CMS_PAGES = 'cms_pages';

    /**
     * @return int|null
     */
    public function getProjectId(): ?int;

    /**
     * @param int $projectId
     *
     * @return ProjectInterface
     */
    public function setProjectId(int $projectId): ProjectInterface;

    /**
     * @return string|null
     */
    public function getExternalId(): ?string;

    /**
     * @param string $externalId
     *
     * @return ProjectInterface
     */
    public function setExternalId(string $externalId): ProjectInterface;

    /**
     * @return string|null
     */
    public function getSecret(): ?string;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     *
     * @return ProjectInterface
     */
    public function setName(string $name): ProjectInterface;

    /**
     * @return string|null
     */
    public function getTeam(): ?string;

    /**
     * @param string $team
     *
     * @return ProjectInterface
     */
    public function setTeam(string $team): ProjectInterface;

    /**
     * @return int
     */
    public function getSourceStoreId(): int;

    /**
     * @param int $sourceStoreId
     *
     * @return ProjectInterface
     */
    public function setSourceStoreId(int $sourceStoreId): ProjectInterface;

    /**
     * @return int[]
     */
    public function getTargetStoreIds(): array;

    /**
     * @param int[] $targetStoreIds
     *
     * @return ProjectInterface
     */
    public function setTargetStoreIds(array $targetStoreIds): ProjectInterface;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string|null $status
     *
     * @return ProjectInterface
     */
    public function setStatus(string $status): ProjectInterface;

    /**
     * @return float|null
     */
    public function getPrice(): ?float;

    /**
     * @param float|null $price
     *
     * @return ProjectInterface
     */
    public function setPrice(?float $price): ProjectInterface;

    /**
     * @return string|null
     */
    public function getCurrency(): ?string;

    /**
     * @param string|null $currency
     *
     * @return ProjectInterface
     */
    public function setCurrency(string $currency): ProjectInterface;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $createdAt
     *
     * @return ProjectInterface
     */
    public function setCreatedAt(string $createdAt): ProjectInterface;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string $updatedAt
     *
     * @return ProjectInterface
     */
    public function setUpdatedAt(string $updatedAt): ProjectInterface;

    /**
     * @return string|null
     */
    public function getWorkflow(): ?string;

    /**
     * @param string $workflow
     *
     * @return ProjectInterface
     */
    public function setWorkflow(string $workflow): ProjectInterface;

    /**
     * @return bool
     */
    public function hasAutomaticImport(): bool;

    /**
     * @param bool $automaticImport
     *
     * @return ProjectInterface
     */
    public function setAutomaticImport(bool $automaticImport): ProjectInterface;

    /**
     * @return int[]
     */
    public function getProducts(): array;

    /**
     * @param int[]|null $products
     *
     * @return ProjectInterface
     */
    public function setProducts(?array $products): ProjectInterface;

    /**
     * @return int[]
     */
    public function getCategories(): array;

    /**
     * @param int[]|null $categories
     *
     * @return ProjectInterface
     */
    public function setCategories(?array $categories): ProjectInterface;

    /**
     * @return int[]
     */
    public function getCmsBlocks(): array;

    /**
     * @param int[]|null $cmsBlocks
     *
     * @return ProjectInterface
     */
    public function setCmsBlocks(?array $cmsBlocks): ProjectInterface;

    /**
     * @return int[]
     */
    public function getCmsPages(): array;

    /**
     * @param int[]|null $cmsPages
     *
     * @return ProjectInterface
     */
    public function setCmsPages(?array $cmsPages): ProjectInterface;
}
