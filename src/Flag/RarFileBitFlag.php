<?php

namespace Selective\Rar\Flag;

/**
 * Flag.
 */
final class RarFileBitFlag
{
    /**
     * File continued from previous volume.
     */
    const FILE_CONTIUNED_FROM_PREV_VOLUME = 0x01;

    /**
     * File continued in next volume.
     */
    const FILE_CONTIUNED_IN_NEXT_VOLUME = 0x02;

    /**
     * File encrypted with password.
     */
    const FILE_ENCRYPED_WITH_PASSWORD = 0x04;

    /**
     * Comment block.
     */
    const COMMENT_BLOCK = 0x08;

    /**
     * HIGH_PACK_SIZE and HIGH_UNP_SIZE fields are present.
     * These fields are used to archive only very large files (larger than 2Gb),
     * for smaller files these fields are absent.
     */
    const HIGH_PACK_AND_UNP_SIZE = 0x100;

    /**
     * FILE_NAME contains both usual and encoded Unicode name separated by zero.
     * In this case NAME_SIZE field is equal to the length of usual name plus
     * encoded Unicode name plus 1. If this flag is present,
     * but FILE_NAME does not contain zero bytes, it means that file name is encoded using UTF-8.
     */
    const UNICODE_FILE_NAME = 0x200;

    /**
     * The header contains additional 8 bytes after the file name,
     * which are required to increase encryption security (so called 'salt').
     */
    const HEADER_WITH_SALT = 0x400;

    /**
     * Version flag. It is an old file version,
     * a version number is appended to file name as ';n'.
     */
    const VERSION = 0x800;

    /**
     * Extended time field present.
     */
    const EXTENDED_TIME_FIELD = 0x1000;

    /**
     * Extended time field present.
     */
    const LHD_EXTFLAGS = 0x2000;

    /**
     * This bit always is set, so the complete block size is
     * HEAD_SIZE + PACK_SIZE (and plus HIGH_PACK_SIZE, if bit 0x100 is set).
     */
    const HEAD_AND_PACK_SIZE = 0x8000;
}
