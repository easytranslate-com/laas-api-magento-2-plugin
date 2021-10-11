<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\Products;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * @see \Magento\Catalog\Block\Adminhtml\Category\AssignProducts
 */
class AssignedProducts extends AbstractBlock
{
    private const SELECTED_PRODUCTS = 'selected_products[]';

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var Products
     */
    private $blockGrid;

    public function __construct(
        Context $context,
        ProjectGetter $projectGetter,
        EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        parent::__construct($context, $projectGetter, $data);
        $this->jsonEncoder = $jsonEncoder;
    }

    /**
     * @throws LocalizedException
     */
    public function getBlockGrid(): BlockInterface
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                Products::class,
                'project.products.grid'
            );
        }

        return $this->blockGrid;
    }

    public function getEntitiesJson(): string
    {
        $project = $this->projectGetter->getProject();
        if (!$project) {
            return $this->jsonEncoder->encode([]);
        }

        return $this->jsonEncoder->encode($project->getProducts());
    }

    public function getInputName(): string
    {
        return ProjectInterface::PRODUCTS;
    }

    public function getGridParam(): string
    {
        return self::SELECTED_PRODUCTS;
    }
}
