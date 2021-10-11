<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;

abstract class AbstractEntityGrid extends Action
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_read';

    /**
     * @var PageFactory
     */
    private $rawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    public function __construct(
        Context $context,
        RawFactory $rawFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->rawFactory    = $rawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    abstract protected function getGridBlock(): AbstractEntity;

    public function execute(): Raw
    {
        /** @var Raw $resultRaw */
        $resultRaw = $this->rawFactory->create();

        return $resultRaw->setContents($this->getGridBlock()->toHtml());
    }
}
