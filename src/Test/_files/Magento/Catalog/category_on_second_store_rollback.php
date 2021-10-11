<?php

// adopted from Magento 2.4 for 2.3-compatibility - can be deleted when we drop 2.3 support

declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/category_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Store/_files/store_rollback.php');
