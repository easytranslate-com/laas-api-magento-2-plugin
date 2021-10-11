<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Ui\Component\Listing\Column;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ProjectActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[ProjectInterface::PROJECT_ID])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href'  => $this->urlBuilder->getUrl(
                                'easytranslate/project/edit',
                                [
                                    ProjectInterface::PROJECT_ID => $item[ProjectInterface::PROJECT_ID]
                                ]
                            ),
                            'label' => __('Edit')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
