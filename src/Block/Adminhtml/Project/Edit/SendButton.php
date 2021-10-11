<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class SendButton extends GenericButton
{
    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    public function __construct(UrlInterface $url, RequestInterface $request, ProjectGetter $projectGetter)
    {
        parent::__construct($url, $request);
        $this->projectGetter = $projectGetter;
    }

    public function getButtonData(): array
    {
        if (!$this->shouldShowButton()) {
            return [];
        }

        return [
            'label'      => __('Send to EasyTranslate'),
            'on_click'   => sprintf("location.href = '%s';", $this->getSendUrl()),
            'class'      => 'send primary',
            'sort_order' => 50
        ];
    }

    private function getSendUrl(): string
    {
        return $this->getUrl('*/*/send/project_id/' . $this->getProjectId());
    }

    private function shouldShowButton(): bool
    {
        $project = $this->projectGetter->getProject();
        if ($project && $project->getProjectId()) {
            return $project->canEditDetails();
        }

        return false;
    }
}
