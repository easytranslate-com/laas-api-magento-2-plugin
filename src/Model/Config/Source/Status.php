<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public const OPEN = 'open';
    public const SENT = 'sent';
    public const PRICE_APPROVAL_REQUEST = 'price_approval_request';
    public const PRICE_ACCEPTED = 'price_accepted';
    public const PRICE_DECLINED = 'price_declined';
    public const PARTIALLY_FINISHED = 'partially_finished';
    public const FINISHED = 'finished';

    private function getOptions(): array
    {
        return [
            self::OPEN                   => __('Open'),
            self::SENT                   => __('Sent To EasyTranslate'),
            self::PRICE_APPROVAL_REQUEST => __('Price Approval Needed'),
            self::PRICE_ACCEPTED         => __('Price Accepted'),
            self::PRICE_DECLINED         => __('Price Declined'),
            self::PARTIALLY_FINISHED     => __('Partially Finished'),
            self::FINISHED               => __('Finished'),
        ];
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
