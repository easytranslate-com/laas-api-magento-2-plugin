<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Ui\Component\Listing\Column;

use Exception;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Price extends Column
{
    /**
     * @var CurrencyInterface
     */
    private $currency;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CurrencyInterface $currency,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->currency = $currency;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['currency'], $item[$fieldName]) && !empty($item[$fieldName])) {
                    $currency = $this->currency->getCurrency($item['currency']);
                    try {
                        $convertedCurrency = $currency->toCurrency($item[$fieldName]);
                        $item[$fieldName]  = $convertedCurrency;
                    } catch (Exception $e) {
                        $item[$fieldName] .= ' ' . $item['currency'];
                    }
                }
                if (!isset($item[$fieldName]) || empty($item[$fieldName])) {
                    $item[$fieldName] = __('tbd');
                }
            }
        }

        return $dataSource;
    }
}
