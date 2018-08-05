<?php

use Cache\Adapter\Common\AbstractCachePool;
require_once "PHPTempPoolOptions.php";
require_once "PHPTempPoolEntry.php";

/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 8/3/18
 * Time: 10:10 PM
 */

// TODO could be optimized to re-use memory parts, fragmentation etc...

class PHPTempPool extends AbstractCachePool
{
    protected $fileStream;
    protected $mapStreamEntries;

    /** @var PHPTempPoolOptions*/
    protected $options;

    public function __construct(PHPTempPoolOptions $options = null)
    {
        if (is_null($options)) {
            $options = new PHPTempPoolOptions();
        }
        $this->options = $options;
        $this->reInitCache();
    }

    public function reInitCache()
    {
        if ($this->fileStream) {
            $meta = stream_get_meta_data($this->fileStream);
            if(is_readable($meta['uri']) || is_writeable($meta['uri'])) {
                fclose($this->fileStream);
            }
        }
        // Handle stream
        $this->fileStream = fopen("php://temp/maxmemory:{$this->options->getMemoryLimit()}", 'r+');

        // Handle book keeping
        $this->mapStreamEntries = [];
    }

    private function getStreamEntry($key, $createNewIfMissing = false)
    {
        $toReturn = null;
        if (array_key_exists($key, $this->mapStreamEntries)) {
            $toReturn = $this->mapStreamEntries[$key];
        } elseif ($createNewIfMissing) {
            $toReturn = new PHPTempPoolEntry();
            $this->mapStreamEntries[$key] = $toReturn;
        }
        return $toReturn;
    }

    private function writeToStream(PHPTempPoolEntry $entry, &$serializedData, $blockSize = 0)
    {
        // Assure valid block size
        if ($blockSize < 0) {
            $blockSize = 0;
        }

        // Arrange data size
        $size = strlen($serializedData);
        $entry->setDataLength($size); // TODO take output from fputs?

        // Move pointer to storage position
        $padding = 0;
        $isNewEntry = !$entry->canStore($size);
        if ($isNewEntry) {
            // Move to EOF
            fseek($this->fileStream, 0, SEEK_END);
            $entry->setOffset(ftell($this->fileStream));

            // Assure block size

            if ($size < $blockSize) {
                $padding = $blockSize - $size;
            }
        } else {
            // Move to offset position
            fseek($this->fileStream, $entry->getOffset());
        }

        // Write on storage position
        if ($size > 0) {
            $storedSize = fputs($this->fileStream, $serializedData);
            if ($padding) {
                $storedSize += fputs($this->fileStream, str_repeat($padding, '&'));
            }
            $entry->setBlockSize($storedSize);

            if ($storedSize != ($size + $padding)) {
                throw new \Exception('Should never happen!');
            }
        }
        unset($serializedData);
        return true;
    }

    private function readFromStream(PHPTempPoolEntry $entry)
    {
        fseek($this->fileStream, $entry->getOffset());
        return @unserialize(fread($this->fileStream, $entry->getDataLength()));
    }

    /**
     * @param \Cache\Adapter\Common\PhpCacheItem $item
     * @param int|null $ttl seconds from now
     *
     * @return bool true if saved
     * @throws Exception
     */
    protected function storeItemInCache(\Cache\Adapter\Common\PhpCacheItem $item, $ttl)
    {
        $data = serialize(
            [
                $item->get(),
                $item->getTags(),
                $item->getExpirationTimestamp(),
            ]
        );

        $entry = $this->getStreamEntry($item->getKey(), true);
        return $this->writeToStream($entry, $data);
    }

    /**
     * Fetch an object from the cache implementation.
     *
     * If it is a cache miss, it MUST return [false, null, [], null]
     *
     * @param string $key
     *
     * @return array with [isHit, value, tags[], expirationTimestamp]
     */
    protected function fetchObjectFromCache($key)
    {
        $empty = [false, null, [], null];
        $entry  = $this->getStreamEntry($key);
        if (!$entry) {
            return $empty;
        }
        $data = $this->readFromStream($entry);
        if ($data === false) {
            return $empty;
        }

        // Determine expirationTimestamp from data, remove items if expired
        $expirationTimestamp = $data[2] ?: null;
        /* if ($expirationTimestamp !== null && time() > $expirationTimestamp) {
            foreach ($data[1] as $tag) {
                $this->removeListItem($this->getTagKey($tag), $key);
            }
            $this->forceClear($key);
            return $empty;
        }*/
        return [true, $data[0], $data[1], $expirationTimestamp];
    }

    /**
     * Clear all objects from cache.
     *
     * @return bool false if error
     */
    protected function clearAllObjectsFromCache()
    {
        $this->reInitCache();
        return true;
    }

    /**
     * Remove one object from cache.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function clearOneObjectFromCache($key)
    {
        $entry = $this->getStreamEntry($key);
        if (!$entry) {
            return true;
        }
        unset($this->mapStreamEntries[$key]);
        return true;
    }

    /**
     * Get an array with all the values in the list named $name.
     *
     * @param string $name
     *
     * @return array
     */
    protected function getList($name)
    {
        $entry = $this->getStreamEntry($name, true);

        // Assure entry
        if (!$entry->canStore(1)) {
            $data = serialize([]);
            $this->writeToStream($entry, $data, $this->options->getListBlockSize());
        }

        return $this->readFromStream($entry);
    }

    /**
     * Remove the list.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function removeList($name)
    {
        return $this->clearOneObjectFromCache($name);
    }

    /**
     * Add a item key on a list named $name.
     *
     * @param string $name
     * @param string $key
     */
    protected function appendListItem($name, $key)
    {
        // List will be created for sure!
        $list = $this->getList($name);
        $list[] = $key;

        // Get the corresponding entry
        $entry = $this->getStreamEntry($name);
        $data = serialize($list);
        $this->writeToStream($entry, $data, $this->options->getListBlockSize());
    }

    /**
     * Remove an item from the list.
     *
     * @param string $name
     * @param string $key
     */
    protected function removeListItem($name, $key)
    {
        $list = $this->getList($name);
        foreach ($list as $i => $item) {
            if ($item === $key) {
                unset($list[$i]);
            }
        }

        // Get the corresponding entry
        $entry = $this->getStreamEntry($name);
        $data = serialize($list);
        $this->writeToStream($entry, $data, $this->options->getListBlockSize());
    }
}
