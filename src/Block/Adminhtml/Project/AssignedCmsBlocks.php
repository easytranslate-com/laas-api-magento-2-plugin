<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\CmsBlocks;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\BlockInterface;

class AssignedCmsBlocks extends AbstractBlock
{
    private const INCLUDED_CMS_BLOCKS = 'included_cms_blocks[]';

    /**
     * @var CmsBlocks
     */
    private $blockGrid;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

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
                CmsBlocks::class,
                'project.cmsblocks.grid'
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

        return $this->jsonEncoder->encode($project->getCmsBlocks());
    }

    public function getInputName(): string
    {
        return ProjectInterface::CMS_BLOCKS;
    }

    public function getGridParam(): string
    {
        return self::INCLUDED_CMS_BLOCKS;
    }
}
