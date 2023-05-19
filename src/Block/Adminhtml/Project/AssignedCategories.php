<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\Categories;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\BlockInterface;

class AssignedCategories extends AbstractBlock
{
    private const SELECTED_CATEGORIES = 'selected_categories[]';

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var Categories
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

    public function getEntitiesJson(): string
    {
        $project = $this->projectGetter->getProject();
        if (!$project) {
            return $this->jsonEncoder->encode([]);
        }

        return $this->jsonEncoder->encode($project->getCategories());
    }

    /**
     * @throws LocalizedException
     */
    public function getBlockGrid(): BlockInterface
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                Categories::class,
                'project.categories.grid'
            );
        }

        return $this->blockGrid;
    }

    public function getInputName(): string
    {
        return ProjectInterface::CATEGORIES;
    }

    public function getGridParam(): string
    {
        return self::SELECTED_CATEGORIES;
    }
}
