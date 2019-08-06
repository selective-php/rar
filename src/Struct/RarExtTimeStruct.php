<?php

namespace Selective\Rar\Struct;

use DateTimeImmutable;

/**
 * Data.
 */
final class RarExtTimeStruct
{
    /**
     * @var int
     */
    public $size;

    /**
     * @var int
     */
    public $type;

    /**
     * @var int
     */
    public $flags;

    /**
     * @var DateTimeImmutable|null
     */
    public $mtime;

    /**
     * @var DateTimeImmutable|null
     */
    public $ctime;

    /**
     * @var DateTimeImmutable|null
     */
    public $atime;

    /**
     * @var DateTimeImmutable|null
     */
    public $arctime;

    /**
     * @var bool
     */
    public $isUnixFormat;
}
