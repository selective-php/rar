<?php

namespace Selective\Rar\Struct;

/**
 * Data.
 */
final class RarVolumeHeaderStruct
{
    /**
     * @var string 2 bytes
     */
    public $crc = '';

    /**
     * @var int 1 bytes
     */
    public $type = 0;

    /**
     * @var int 2 byte
     */
    public $flags = 0;

    /**
     * @var int 2 byte
     */
    public $size = 0;

    /**
     * @var int 4 byte
     */
    public $addSize = 0;

    /**
     * @var int
     */
    public $blockSize = 0;

    /**
     * @var bool
     */
    public $hasAdd;

    /**
     * @var int
     */
    public $headerSize;

    /**
     * @var int
     */
    public $bodySize;
}
