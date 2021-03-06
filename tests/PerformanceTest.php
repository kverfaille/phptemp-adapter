<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 8/4/18
 * Time: 8:49 PM
 */

use Cache\Adapter\PHPTemp\PHPTempPool;
use Cache\Adapter\PHPTemp\PHPTempPoolOptions;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class PerformanceTest extends \PHPUnit\Framework\TestCase
{
    public function testWritePerformance()
    {
        ini_set('memory_limit', '1024M');
        $iterations = 100;

        // Data is about xMB
        $mb = 10;
        $data = str_repeat("a", $mb * 1024 * 1024);

        $config = new PHPTempPoolOptions();
        $config->setMemoryLimit(512 * 1024 * 1024 );
        $cache = new PHPTempPool($config);
        $this->runPerformanceOnCache($cache, $iterations, $data);

        $filesystemAdapter = new Local(sys_get_temp_dir());
        $filesystem        = new Filesystem($filesystemAdapter);
        $cache = new \Cache\Adapter\Filesystem\FilesystemCachePool($filesystem);
        $this->runPerformanceOnCache($cache, $iterations, $data);

        $cache = new \Cache\Adapter\PHPArray\ArrayCachePool();
        $this->runPerformanceOnCache($cache, $iterations, $data);
    }

    protected function runPerformanceOnCache(\Cache\Adapter\Common\AbstractCachePool $cache, $iterations, $data)
    {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cache->save(new \Cache\Adapter\Common\CacheItem($i, true, $data));
        }
        $diff = microtime(true) - $start;
        var_dump($diff);
    }
}
