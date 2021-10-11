<?php

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class ImportButton extends GenericButton
{
    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        ProjectRepositoryInterface $projectRepository
    ) {
        parent::__construct($url, $request);
        $this->projectRepository = $projectRepository;
    }

    public function getButtonData(): array
    {
        if ($this->getProjectId()) {
            $project = $this->projectRepository->get($this->getProjectId());
            if (!$project->hasAutomaticImport()
                && $project->getTaskCollection()->addFieldToFilter('processed_at', ['null' => true])->getSize()) {
                return [
                    'label'      => __('Schedule for import'),
                    'on_click'   => sprintf("location.href = '%s';", $this->getScheduleImportUrl()),
                    'class'      => 'save primary',
                    'sort_order' => 100
                ];
            }
        }

        return [];
    }

    private function getScheduleImportUrl(): string
    {
        return $this->getUrl('*/*/scheduleImport/project_id/' . $this->getProjectId());
    }
}
