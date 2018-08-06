<?php

use Cache\Adapter\PHPTemp\PHPTempPool;
use Cache\IntegrationTests\CachePoolTest;

class PoolIntegrationTest extends CachePoolTest
{
    private $test;

    public function createCachePool()
    {
        return new PHPTempPool();
    }
}
