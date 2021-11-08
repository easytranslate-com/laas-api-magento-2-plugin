<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Staging;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;

class VersionManagerFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    public function __construct(ObjectManagerInterface $objectManager, ModuleManager $moduleManager)
    {
        $this->_objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
    }

    public function create(): ?object
    {
        if (!$this->moduleManager->isEnabled('Magento_Staging')) {
            return null;
        }
        // keep it as a string to not depend on Magento_Staging!
        return $this->_objectManager->get('Magento\Staging\Model\VersionManager');
    }
}
