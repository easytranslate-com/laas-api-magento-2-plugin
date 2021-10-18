<?php

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'EasyTranslate_Connector',
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);
