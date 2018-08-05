<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 8/4/18
 * Time: 8:21 AM
 */

class PHPTempPoolOptions
{
    protected $memoryLimit = 5 * 1024 * 1024; // 5MB

    /**
     * @return int
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * @param int $MemoryLimit
     * The amount of memory that might be consumed by the cache before overflow to disk is applied
     */
    public function setMemoryLimit(int $MemoryLimit): void
    {
        $this->memoryLimit = $MemoryLimit;
    }

    protected $listBlockSize = 1024;

    /**
     * The default block size for storing list (to avoid fragmentation)
     * @return int
     */
    public function getListBlockSize(): int
    {
        return $this->listBlockSize;
    }

    /**
     * @param int $listBlockSize
     */
    public function setListBlockSize(int $listBlockSize): void
    {
        $this->listBlockSize = $listBlockSize;
    }
}
