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
    public $packSize;

    /**
     * @var int 4 bytes
     */
    public $lowUnpackSize;

    /**
     * @var int 1 byte
     */
    public $hostOS;

    /**
     * @var string 4 bytes
     */
    public $fileCRC;

    /**
     * @var DateTimeImmutable 4 bytes
     */
    public $fileTime;

    /**
     * @var int 1 bytes
     */
    public $unpVer;

    /**
     * @var int 1 bytes
     */
    public $method;

    /**
     * @var int 2 bytes
     */
    public $nameSize;

    /**
     * @var int 4 bytes
     */
    public $fileAttr;

    /**
     * @var int 4 bytes (only present if LHD_LARGE is set)
     */
    public $highPackSize = 0;

    /**
     * @var int 4 bytes (only present if LHD_LARGE is set)
     */
    public $highUnpackSize = 0;

    /**
     * @var string (NameSize) bytes
     */
    public $fileName = '';

    /**
     * @var string 8 bytes (only present if LHD_SALT is set)
     */
    public $salt;

    /**
     * @var RarExtTimeStruct Structure, See Description (only present if LHD_EXTTIME is set)
     */
    public $extTime;

    /**
     * @var int
     */
    public $unpackSize;

    /**
     * @var bool
     */
    public $isDirectory = false;
}
