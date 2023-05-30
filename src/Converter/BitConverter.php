<?php

namespace Selective\Rar\Converter;

/**
 * Bit Converter.
 */
final class BitConverter
{
    /**
     * Convert fom 2x bytes (32bit, high and low) to 64 bit unsigned integers (uint64).
     *
     * Code from https://stackoverflow.com/a/49740818/1461181
     *
     * @param string $hi The high number
     * @param string $lo The low number
     *
     * @return int The 64 bit unsigned integer
     */
    public function toInt64(string $hi, string $lo): int
    {
        // On x64, we can just use int
        return ((int)$hi << 32) + (int)$lo;
    }

    /**
     * Is flag set.
     *
     * @param int $flags The flags
     * @param int $flag The flag
     *
     * @return int Status
     */
    public function isFlagSet($flags, $flag): int
    {
        return $flags & $flag;
    }
}
