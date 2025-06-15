<?php

namespace Selective\Rar\Struct;

final class CompressionInfoStruct
{
    public int $version;
    public bool $solid;
    public int $method;
    public ?string $methodName;
    public int $dictionarySize;
    public int $dictionarySizeClass;
    public ?int $dictionarySizeMultiplier = null;
    public bool $forcedVersion0 = false;
}
