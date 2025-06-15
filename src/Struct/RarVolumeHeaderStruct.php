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
    public string $crc = '';

    /**
     * @var int 1 byte
     */
    public int $type = 0;

    /**
     * @var int 2 byte
     */
    public int $flags = 0;

    /**
     * @var int 2 byte
     */
    public int $size = 0;

    /**
     * @var int 4 byte
     */
    public int $addSize = 0;

    /**
     * @var int
     */
    public int $blockSize = 0;

    /**
     * @var bool
     */
    public bool $hasAdd = false;

    /**
     * @var int
     */
    public int $headerSize = 0;

    /**
     * @var int
     */
    public int $bodySize = 0;
}
