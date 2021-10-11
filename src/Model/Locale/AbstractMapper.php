<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Locale;

use Magento\Framework\Exception\LocalizedException;

abstract class AbstractMapper
{
    protected const INTERNAL_TO_EXTERNAL = [];

    public function isMagentoCodeSupported(string $magentoCode): bool
    {
        return isset(static::INTERNAL_TO_EXTERNAL[$magentoCode]);
    }

    public function isExternalCodeSupported(string $externalCode): bool
    {
        $mapping = array_flip(static::INTERNAL_TO_EXTERNAL);

        return isset($mapping[$externalCode]);
    }

    /**
     * @throws LocalizedException
     */
    public function mapExternalCodeToMagentoCode(string $externalCode): string
    {
        $mapping = array_flip(static::INTERNAL_TO_EXTERNAL);
        if (!$this->isExternalCodeSupported($externalCode)) {
            throw new LocalizedException(__('Unsupported locale code.'));
        }

        return $mapping[$externalCode];
    }

    /**
     * @throws LocalizedException
     */
    public function mapMagentoCodeToExternalCode(string $magentoCode): string
    {
        if (!$this->isMagentoCodeSupported($magentoCode)) {
            throw new LocalizedException(__('Unsupported locale code.'));
        }

        return static::INTERNAL_TO_EXTERNAL[$magentoCode];
    }
}
