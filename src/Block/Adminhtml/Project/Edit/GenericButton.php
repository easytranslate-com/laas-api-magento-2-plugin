<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

abstract class GenericButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(UrlInterface $url, RequestInterface $request)
    {
        $this->url     = $url;
        $this->request = $request;
    }

    public function getProjectId(): int
    {
        return (int)$this->request->getParam(ProjectInterface::PROJECT_ID);
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->url->getUrl($route, $params);
    }
}
