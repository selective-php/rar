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
    public int $version = 4;

    /**
     * @var string 7 bytes (0x52 0x61 0x72 0x21 0x1A 0x07 0x00)
     */
    public string $signature = '';

    /**
     * @var RarMainHeadStruct|null
     */
    public ?RarMainHeadStruct $mainHead = null;

    /**
     * @var RarFileHeadStruct[]
     */
    public array $files = [];

    /**
     * @var RarVolumeHeaderStruct[] (Not supported)
     */
    public array $volumeHeaders = [];
}
