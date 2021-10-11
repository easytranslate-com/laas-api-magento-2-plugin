<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\BlockInterface;

abstract class AbstractBlock extends Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'EasyTranslate_Connector::assign_entities.phtml';

    /**
     * @var ProjectGetter
     */
    protected $projectGetter;

    public function __construct(
        Context $context,
        ProjectGetter $projectGetter,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->projectGetter = $projectGetter;
    }

    abstract public function getInputName(): string;

    abstract public function getBlockGrid(): BlockInterface;

    public function getGridHtml(): string
    {
        return $this->getBlockGrid()->toHtml();
    }

    abstract public function getEntitiesJson(): string;

    abstract public function getGridParam(): string;

    protected function _toHtml(): string
    {
        if (!$this->projectGetter->getProject()) {
            return '';
        }

        return parent::_toHtml();
    }
}
