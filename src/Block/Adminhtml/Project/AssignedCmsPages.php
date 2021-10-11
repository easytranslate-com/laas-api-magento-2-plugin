<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\CmsPages;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\BlockInterface;

class AssignedCmsPages extends AbstractBlock
{
    private const INCLUDED_CMS_PAGES = 'included_cms_pages[]';

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var CmsPages
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
                CmsPages::class,
                'project.cmspages.grid'
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

        return $this->jsonEncoder->encode($project->getCmsPages());
    }

    public function getInputName(): string
    {
        return ProjectInterface::CMS_PAGES;
    }

    public function getGridParam(): string
    {
        return self::INCLUDED_CMS_PAGES;
    }
}
