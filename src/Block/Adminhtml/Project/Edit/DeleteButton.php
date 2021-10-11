<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;

class DeleteButton extends GenericButton
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        Escaper $escaper,
        ProjectGetter $projectGetter
    ) {
        parent::__construct($url, $request);
        $this->escaper = $escaper;

        $this->projectGetter = $projectGetter;
    }

    public function getButtonData(): array
    {
        if (!$this->shouldShowButton()) {
            return [];
        }

        $data = [];
        if ($this->getProjectId()) {
            $confirmationMessage = __('Are you sure you want to delete this project?');
            $escapedMessage      = $this->escaper->escapeJs($this->escaper->escapeHtml($confirmationMessage));
            $data                = [
                'label'      => __('Delete Project'),
                'class'      => 'delete',
                'on_click'   => 'deleteConfirm(\'' . $escapedMessage . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', [ProjectInterface::PROJECT_ID => $this->getProjectId()]);
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
