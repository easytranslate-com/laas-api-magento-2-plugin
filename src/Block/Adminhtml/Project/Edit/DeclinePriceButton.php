<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Block\Adminhtml\Project\Edit;

use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;

class DeclinePriceButton extends GenericButton
{
    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    /**
     * @var Escaper
     */
    private $escaper;

    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        ProjectGetter $projectGetter,
        Escaper $escaper
    ) {
        parent::__construct($url, $request);
        $this->projectGetter = $projectGetter;
        $this->escaper       = $escaper;
    }

    public function getButtonData(): array
    {
        if (!$this->shouldShowButton()) {
            return [];
        }
        $acceptPriceUrl      = $this->getUrl('*/*/declinePrice', ['project_id' => $this->getProjectId()]);
        $confirmationMessage = __('Are you sure you want to decline the price for this project?');
        $escapedMessage      = $this->escaper->escapeJs($this->escaper->escapeHtml($confirmationMessage));

        return [
            'label'      => __('Decline Price'),
            'on_click'   => 'deleteConfirm(\'' . $escapedMessage . '\', \'' . $acceptPriceUrl . '\')',
            'class'      => 'cancel primary',
            'sort_order' => 50
        ];
    }

    private function shouldShowButton(): bool
    {
        $project = $this->projectGetter->getProject();
        if ($project) {
            return $project->requiresPriceApproval();
        }

        return false;
    }
}
