<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class SaveButton extends GenericButton
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
            'label'          => __('Save Project'),
            'class'          => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order'     => 40,
        ];
    }

    private function shouldShowButton(): bool
    {
        $project = $this->projectGetter->getProject();
        if (!$project) {
            return true;
        }
        if ($project->getProjectId()) {
            return $project->canEditDetails();
        }

        return false;
    }
}
