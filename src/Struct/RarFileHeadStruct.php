<?php

namespace Selective\Rar\Struct;

use DateTimeImmutable;

/**
 * Data.
 */
final class RarFileHeadStruct
{
    /**
     * @var int 4 bytes
     */
    public int $packSize = 0;

    /**
     * @var int 4 bytes
     */
    public int $lowUnpackSize = 0;

    /**
     * @var int 1 byte
     */
    public int $hostOS = 0;

    /**
     * @var string 4 bytes
     */
    public string $fileCRC = '';

    /**
     * @var DateTimeImmutable 4 bytes
     */
    public DateTimeImmutable $fileTime;

    /**
     * @var int 1 byte
     */
    public int $unpVer = 0;

    /**
     * @var int 1 byte
     *
     * 0 = Store (no compression)
     * 1 = Fastest
     * 2 = Fast
     * 3 = Normal (corresponds to m3)
     * 4 = Good
     * 5 = Best
     */
    public int $method = 0;

    /**
     * @var int 2 bytes
     */
    public int $nameSize = 0;

    /**
     * @var int 4 bytes
     */
    public int $fileAttr = 0;

    /**
     * @var int 4 bytes (only present if LHD_LARGE is set)
     */
    public int $highPackSize = 0;

    /**
     * @var int 4 bytes (only present if LHD_LARGE is set)
     */
    public int $highUnpackSize = 0;

    /**
     * @var string Filename
     */
    public string $fileName = '';

    /**
     * @var string 8 bytes (only present if LHD_SALT is set)
     */
    public string $salt = '';

    /**
     * @var RarExtTimeStruct|null Structure, See Description (only present if LHD_EXTTIME is set)
     */
    public ?RarExtTimeStruct $extTime = null;

    /**
     * @var int
     */
    public int $unpackSize = 0;

    /**
     * @var bool
     */
    public bool $isDirectory = false;
}
