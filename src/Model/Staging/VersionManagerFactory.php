<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Staging;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;

class VersionManagerFactory
{
    public const MIN_VERSION = 1;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    public function __construct(ObjectManagerInterface $objectManager, ModuleManager $moduleManager)
    {
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
    }

    public function create(): ?object
    {
        if (!$this->moduleManager->isEnabled('Magento_Staging')) {
            return null;
        }
        // keep it as a string to not depend on Magento_Staging!
        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        return $this->objectManager->get('Magento\Staging\Model\VersionManager');
    }
}
