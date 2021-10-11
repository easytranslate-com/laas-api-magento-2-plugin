<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class SaveAndContinueButton extends GenericButton
{
    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        ProjectGetter $projectGetter
    ) {
        parent::__construct($url, $request);
        $this->projectGetter = $projectGetter;
    }

    public function getButtonData(): array
    {
        if (!$this->shouldShowButton()) {
            return [];
        }

        return [
            'label'          => __('Save and Continue Edit'),
            'class'          => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndContinueEdit'],
                ],
            ],
            'sort_order'     => 30,
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
