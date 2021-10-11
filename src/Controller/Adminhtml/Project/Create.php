<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Adminhtml\Project;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Create extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'EasyTranslate_Connector::Project_save';
    public const MENU_ID = 'EasyTranslate_Connector::projects';

    public function execute(): ResultInterface
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend((string)__('New Project'));

        return $resultPage;
    }
}
