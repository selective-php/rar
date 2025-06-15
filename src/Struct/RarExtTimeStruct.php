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
    public int $size = 0;

    /**
     * @var int
     */
    public int $type = 0;

    /**
     * @var int
     */
    public int $flags = 0;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable $mtime = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable $ctime = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable $atime = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable $arctime = null;

    /**
     * @var bool
     */
    public bool $isUnixFormat = false;
}
