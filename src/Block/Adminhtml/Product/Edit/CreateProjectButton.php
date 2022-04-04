<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Product\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class CreateProjectButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        UrlInterface $url,
        RequestInterface $request
    ) {
        $this->url     = $url;
        $this->request = $request;
    }

    public function getButtonData(): array
    {
        return [
            'id'         => 'easytranslate_create_project',
            'label'      => __('Create Easytranslate Project'),
            'on_click'   => sprintf("window.open('%s','_blank')", $this->url->getUrl(
                'easytranslate/product/createProject',
                ['product_id' => $this->request->getParam('id')]
            )),
            'class'      => 'action-secondary',
            'sort_order' => 80
        ];
    }
}
