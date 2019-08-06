<?php

namespace Selective\Rar\Struct;

/**
 * Data.
 */
final class RarMainHeadStruct
{
    /**
     * @var int 2 bytes
     */
    public $highPosAv;

    /**
     * @var int 4 bytes
     */
    public $posAv;

    /**
     * @var int 1 byte Value 0x0200 (512). Indicates whether encryption is present in the archive volume.
     */
    public $encryptVer;
}
