<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Product\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class AddToProjectButton implements ButtonProviderInterface
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
            'id'         => 'easytranslate_add_product_to_project',
            'label'      => __('Add Product To Project'),
            'on_click'   => sprintf(
                "location.href = '%s';",
                $this->url->getUrl(
                    'easytranslate/product/createProjectFromProduct',
                    ['productIds' => $this->request->getParam('id')]
                )
            ),
            'class'      => 'save primary',
            'sort_order' => 100
        ];
    }
}
