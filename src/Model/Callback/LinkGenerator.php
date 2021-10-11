<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Callback;

use EasyTranslate\Connector\Model\Project;
use Magento\Framework\UrlInterface;

class LinkGenerator
{
    public const SECRET_PARAM = 'secret';

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(UrlInterface $url)
    {
        $this->url = $url;
    }

    public function generateLink(Project $project): string
    {
        $params = [self::SECRET_PARAM => $project->getData('secret')];

        return $this->url->getUrl('easytranslate/callback/execute', $params);
    }
}
