<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 8/4/18
 * Time: 8:35 AM
 */
namespace Cache\Adapter\PHPTemp;

class PHPTempPoolEntry
{
    protected $offset = 0;

    /**
     * The offset in the stream where
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    protected $dataLength = 0;

    /**
     * The length of the data that is stored
     * @return int
     */
    public function getDataLength()
    {
        return $this->dataLength;
    }

    /**
     * @param int $length
     */
    public function setDataLength($length)
    {
        $this->dataLength = $length;
    }

    protected $blockSize = 0;

    /**
     * The length that is used in the stream (including padding)
     * @return int
     */
    public function getBlockSize()
    {
        return $this->blockSize;
    }

    /**
     * @param int $storageLength
     */
    public function setBlockSize($storageLength)
    {
        $this->blockSize = $storageLength;
    }

    /**
     * Determines if there is place to store this item in the current block
     * @param $requiredLength
     * @return bool
     */
    public function canStore($requiredLength)
    {
        return $requiredLength <= $this->blockSize; // There is a block and its big enough
    }
}
