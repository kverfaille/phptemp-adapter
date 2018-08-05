<?php

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\IntegrationTests\CachePoolTest;

require_once "../PHPTempPool.php";

class PoolIntegrationTest extends CachePoolTest
{
    private $test;

    public function createCachePool()
    {
        return new PHPTempPool();
    }
}
