<?php

namespace Selective\Rar\Struct;

/**
 * Rar file.
 */
final class RarArchiveStruct
{
    /**
     * @var int The RAR version
     */
    public $version = 4;

    /**
     * @var string 7 bytes (0x52 0x61 0x72 0x21 0x1A 0x07 0x00)
     */
    public $signature;

    /**
     * @var RarMainHeadStruct
     */
    public $mainHead;

    /**
     * @var RarFileHeadStruct[]
     */
    public $files = [];

    /**
     * @var RarVolumeHeaderStruct[] (Not supported)
     */
    public $volumeHeaders = [];
}
