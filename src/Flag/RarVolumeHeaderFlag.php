<?php

namespace Selective\Rar\Flag;

/**
 * Flag.
 */
final class RarVolumeHeaderFlag
{
    public const MARK_HEAD = 0x72;
    public const MAIN_HEAD = 0x73; // 115
    public const FILE_HEAD = 0x74; // 116
    public const COMM_HEAD = 0x75;
    public const AV_HEAD = 0x76;
    public const SUB_HEAD = 0x77;
    public const PROTECT_HEAD = 0x78;
    public const SIGN_HEAD = 0x79;
    public const NEWSUB_HEAD = 0x7a;
    public const ENDARC_HEAD = 0x7b; // 123
}
